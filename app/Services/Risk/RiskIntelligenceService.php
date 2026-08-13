<?php

declare(strict_types=1);

namespace App\Services\Risk;

use App\Enums\ApplicationStatus;
use App\Enums\CropHealthStatus;
use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Crop;
use App\Models\ExportOrder;
use App\Models\Farm;
use App\Models\InsurancePolicy;
use App\Models\LoanApplication;
use App\Models\LogisticsShipment;
use App\Models\MarketplaceCommodity;
use App\Models\RiskAlert;
use App\Models\RiskAssessment;
use App\Models\RiskMitigation;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Live AI risk intelligence: score farms, surface alerts, and track mitigations.
 */
class RiskIntelligenceService
{
    /**
     * @var list<string>
     */
    public const ACTION_TYPES = [
        'insure',
        'logistics_review',
        'market_hedge',
        'crop_scouting',
        'wallet_topup',
        'other',
    ];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getCenterData(User $user): array
    {
        $assessment = $this->latestAssessment($user);
        if (! $assessment || $assessment->calculated_at?->lt(now()->subHours(6))) {
            $assessment = $this->recalculate($user);
        }

        $alerts = RiskAlert::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'acknowledged'])
            ->latest('id')
            ->limit(20)
            ->get();

        $mitigations = RiskMitigation::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['planned', 'in_progress'])
            ->latest('id')
            ->limit(12)
            ->get();

        $categories = collect($assessment->categories ?? [])->map(function (array $row) {
            return [
                'key' => $row['key'] ?? '',
                'label' => $row['label'] ?? '',
                'level' => $row['level'] ?? 'Low',
                'tone' => $row['tone'] ?? 'low',
                'score' => (int) ($row['score'] ?? 0),
            ];
        })->all();

        $scoreValue = (int) $assessment->overall_score;
        $statusLabel = match ($assessment->status) {
            'high' => 'High Risk',
            'low' => 'Low Risk',
            default => 'Medium Risk',
        };
        $statusTone = match ($assessment->status) {
            'high' => 'text-rose-600',
            'low' => 'text-cyra-forest',
            default => 'text-amber-500',
        };

        return [
            'score' => [
                'value' => $scoreValue,
                'label' => 'Overall Risk Score',
                'status' => $statusLabel,
                'status_tone' => $statusTone,
                'calculated_at' => $assessment->calculated_at?->diffForHumans() ?? 'just now',
            ],
            'categories' => $categories,
            'alerts' => $alerts->map(fn (RiskAlert $alert) => $this->presentAlert($alert))->all(),
            'mitigations' => $mitigations->map(fn (RiskMitigation $item) => $this->presentMitigation($item))->all(),
            'report' => $this->reportSummary($assessment, $alerts, $mitigations),
            'gauge' => $this->gaugeSegments($scoreValue),
            'farms_count' => Farm::query()->where('user_id', $user->id)->count(),
            'actions' => [
                'refresh_url' => route('risk.refresh'),
                'export_url' => route('risk.export'),
                'mitigation_url' => route('risk.mitigations.store'),
                'insurance_url' => route('insurance.platform'),
                'market_url' => route('market.intelligence'),
                'wallet_url' => route('wallet.index'),
            ],
            'notifications_count' => max(2, $alerts->where('status', 'open')->count() + $mitigations->count()),
        ];
    }

    /**
     * Recalculate risk score and sync open alerts from live operational data.
     */
    public function recalculate(User $user): RiskAssessment
    {
        return DB::transaction(function () use ($user): RiskAssessment {
            $computed = $this->computeRisk($user);

            $assessment = RiskAssessment::query()->create([
                'user_id' => $user->id,
                'overall_score' => $computed['overall_score'],
                'status' => $computed['status'],
                'categories' => $computed['categories'],
                'factors' => $computed['factors'],
                'calculated_at' => now(),
                'meta' => [
                    'version' => 1,
                ],
            ]);

            $this->syncAlerts($user, $assessment, $computed['alerts']);

            return $assessment;
        });
    }

    public function acknowledgeAlert(User $user, RiskAlert $alert): RiskAlert
    {
        $this->assertOwnedAlert($user, $alert);

        if ($alert->status === 'dismissed') {
            throw new BusinessLogicException('Dismissed alerts cannot be acknowledged.');
        }

        $alert->forceFill([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ])->save();

        return $alert->refresh();
    }

    public function dismissAlert(User $user, RiskAlert $alert): RiskAlert
    {
        $this->assertOwnedAlert($user, $alert);

        $alert->forceFill([
            'status' => 'dismissed',
            'dismissed_at' => now(),
        ])->save();

        return $alert->refresh();
    }

    /**
     * @param  array{title: string, action_type: string, alert_id?: int|null}  $data
     */
    public function createMitigation(User $user, array $data): RiskMitigation
    {
        $actionType = $data['action_type'];
        if (! in_array($actionType, self::ACTION_TYPES, true)) {
            throw new BusinessLogicException('Invalid mitigation action type.');
        }

        $alertId = isset($data['alert_id']) ? (int) $data['alert_id'] : null;
        if ($alertId) {
            $alert = RiskAlert::query()->whereKey($alertId)->where('user_id', $user->id)->first();
            if (! $alert) {
                throw new BusinessLogicException('Select one of your risk alerts.');
            }
        }

        return RiskMitigation::query()->create([
            'user_id' => $user->id,
            'alert_id' => $alertId,
            'title' => trim($data['title']),
            'action_type' => $actionType,
            'status' => 'planned',
            'due_at' => now()->addDays(7),
            'meta' => [
                'link' => $this->actionLink($actionType),
            ],
        ]);
    }

    public function completeMitigation(User $user, RiskMitigation $mitigation): RiskMitigation
    {
        $this->assertOwnedMitigation($user, $mitigation);

        if ($mitigation->status === 'done') {
            throw new BusinessLogicException('This mitigation is already completed.');
        }

        $mitigation->forceFill([
            'status' => 'done',
            'completed_at' => now(),
        ])->save();

        return $mitigation->refresh();
    }

    public function exportReport(User $user): StreamedResponse
    {
        $data = $this->getCenterData($user);

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CyraAgroLink Risk Intelligence Report', now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['Overall Score', $data['score']['value'], $data['score']['status']]);
            fputcsv($out, []);
            fputcsv($out, ['Category', 'Level', 'Score']);
            foreach ($data['categories'] as $category) {
                fputcsv($out, [$category['label'], $category['level'], $category['score']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Alerts']);
            fputcsv($out, ['Title', 'Category', 'Severity', 'Status', 'Detail']);
            foreach ($data['alerts'] as $alert) {
                fputcsv($out, [
                    $alert['title'],
                    $alert['category'],
                    $alert['severity'],
                    $alert['status'],
                    $alert['detail'] ?? '',
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Mitigations']);
            fputcsv($out, ['Title', 'Action', 'Status', 'Due']);
            foreach ($data['mitigations'] as $item) {
                fputcsv($out, [
                    $item['title'],
                    $item['action_type'],
                    $item['status'],
                    $item['due'] ?? '',
                ]);
            }
            fclose($out);
        }, 'cyra-risk-intelligence-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array{
     *     overall_score: int,
     *     status: string,
     *     categories: list<array<string, mixed>>,
     *     factors: array<string, mixed>,
     *     alerts: list<array<string, mixed>>
     * }
     */
    protected function computeRisk(User $user): array
    {
        $farms = Farm::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [FarmStatus::Active, FarmStatus::PendingReview, FarmStatus::Draft])
            ->get();

        $market = $this->scoreMarket();
        $weather = $this->scoreWeather($farms);
        $disease = $this->scoreDisease($user, $farms);
        $supply = $this->scoreSupplyChain($user);
        $political = $this->scorePolitical($user, $farms);
        $credit = $this->scoreCredit($user);
        $fraud = $this->scoreFraud($user);

        $categories = [
            $this->categoryRow('market', 'Market Risk', $market['score']),
            $this->categoryRow('weather', 'Weather Risk', $weather['score']),
            $this->categoryRow('disease', 'Disease Risk', $disease['score']),
            $this->categoryRow('supply_chain', 'Supply Chain Risk', $supply['score']),
            $this->categoryRow('political', 'Political Risk', $political['score']),
            $this->categoryRow('credit', 'Credit Risk', $credit['score']),
            $this->categoryRow('fraud', 'Fraud Risk', $fraud['score']),
        ];

        $average = (int) round(collect($categories)->avg('score'));

        $activePolicies = InsurancePolicy::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();

        if ($activePolicies > 0) {
            $average = max(0, $average - min(12, $activePolicies * 4));
        }

        if ($farms->isEmpty()) {
            $average = max($average, 55);
        }

        $status = $average >= 70 ? 'high' : ($average >= 40 ? 'medium' : 'low');

        $alerts = array_values(array_filter(array_merge(
            $market['alerts'],
            $weather['alerts'],
            $disease['alerts'],
            $supply['alerts'],
            $political['alerts'],
            $credit['alerts'],
            $fraud['alerts'],
            $farms->isEmpty() ? [[
                'key' => 'no-farm',
                'category' => 'credit',
                'severity' => 'medium',
                'title' => 'No registered farm enterprise linked',
                'detail' => 'Register a crop, poultry, aquaculture, or livestock enterprise to refine risk scoring.',
            ]] : [],
            $activePolicies === 0 && $farms->isNotEmpty() ? [[
                'key' => 'no-insurance',
                'category' => 'credit',
                'severity' => 'medium',
                'title' => 'No active farm insurance cover',
                'detail' => 'Buy a policy on Farm Insurance to reduce uncovered enterprise exposure.',
            ]] : [],
        )));

        return [
            'overall_score' => $average,
            'status' => $status,
            'categories' => $categories,
            'factors' => [
                'farms' => $farms->count(),
                'active_policies' => $activePolicies,
                'wallet_balance' => $this->walletService->getBalance($user),
            ],
            'alerts' => $alerts,
        ];
    }

    /**
     * @return array{score: int, alerts: list<array<string, mixed>>}
     */
    protected function scoreMarket(): array
    {
        $commodities = MarketplaceCommodity::query()
            ->where('status', 'active')
            ->orderByDesc('is_featured')
            ->limit(12)
            ->get();

        if ($commodities->isEmpty()) {
            return ['score' => 45, 'alerts' => []];
        }

        $changes = $commodities->map(fn (MarketplaceCommodity $c) => abs($c->changePercent()));
        $avg = (float) $changes->avg();
        $max = (float) $changes->max();
        $score = (int) min(95, round(30 + ($avg * 4) + ($max * 1.5)));

        $alerts = [];
        $volatile = $commodities->first(fn (MarketplaceCommodity $c) => abs($c->changePercent()) >= 6);
        if ($volatile) {
            $change = $volatile->changePercent();
            $alerts[] = [
                'key' => 'market-volatility-'.$volatile->id,
                'category' => 'market',
                'severity' => abs($change) >= 10 ? 'high' : 'medium',
                'title' => $volatile->name.' price volatility increased',
                'detail' => sprintf('%s moved %+.1f%% — consider hedging or staged sales.', $volatile->name, $change),
            ];
        }

        return ['score' => $score, 'alerts' => $alerts];
    }

    /**
     * @param  Collection<int, Farm>  $farms
     * @return array{score: int, alerts: list<array<string, mixed>>}
     */
    protected function scoreWeather(Collection $farms): array
    {
        if ($farms->isEmpty()) {
            return ['score' => 50, 'alerts' => []];
        }

        $score = 40;
        $alerts = [];
        $wetStates = ['Ogun', 'Lagos', 'Rivers', 'Delta', 'Bayelsa'];

        $missingCoords = $farms->filter(fn (Farm $f) => $f->latitude === null || $f->longitude === null)->count();
        if ($missingCoords > 0) {
            $score += 10;
        }

        $wetFarms = $farms->filter(fn (Farm $f) => in_array((string) $f->state, $wetStates, true));
        if ($wetFarms->isNotEmpty()) {
            $score += 18;
            $state = (string) $wetFarms->first()->state;
            $alerts[] = [
                'key' => 'weather-rain-'.$state,
                'category' => 'weather',
                'severity' => 'high',
                'title' => "Heavy rainfall risk elevated in {$state} State",
                'detail' => 'Seasonal flood exposure may affect field crops and aquaculture ponds.',
            ];
        } else {
            $score += 8;
            $alerts[] = [
                'key' => 'weather-watch',
                'category' => 'weather',
                'severity' => 'medium',
                'title' => 'Seasonal weather watch active for your farms',
                'detail' => 'Monitor rainfall and heat stress over the next 7 days.',
            ];
        }

        return ['score' => min(90, $score), 'alerts' => $alerts];
    }

    /**
     * @param  Collection<int, Farm>  $farms
     * @return array{score: int, alerts: list<array<string, mixed>>}
     */
    protected function scoreDisease(User $user, Collection $farms): array
    {
        $crops = Crop::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        $unhealthy = $crops->filter(function (Crop $crop) {
            $health = $crop->health_status;

            return $health === CropHealthStatus::Poor || $health === CropHealthStatus::Critical;
        });

        $score = 25 + ($unhealthy->count() * 15);
        if ($crops->isEmpty() && $farms->isNotEmpty()) {
            $score = 45;
        }

        $alerts = [];
        if ($unhealthy->isNotEmpty()) {
            $sample = $unhealthy->first();
            $alerts[] = [
                'key' => 'disease-crop-'.$sample->id,
                'category' => 'disease',
                'severity' => $sample->health_status === CropHealthStatus::Critical ? 'high' : 'medium',
                'title' => 'Disease / health stress on '.$sample->name,
                'detail' => 'Health status is '.($sample->health_status?->label() ?? 'poor').'. Schedule scouting or veterinary support for livestock/poultry enterprises.',
            ];
        } elseif ($farms->contains(fn (Farm $f) => collect($f->crops ?? [])->contains(fn ($e) => in_array($e, ['Broilers', 'Layers', 'Catfish', 'Tilapia', 'Pig', 'Goat', 'Cattle'], true)))) {
            $alerts[] = [
                'key' => 'disease-biosecurity',
                'category' => 'disease',
                'severity' => 'medium',
                'title' => 'Biosecurity watch for livestock and aquaculture units',
                'detail' => 'Maintain vaccination, water quality, and isolation protocols for active enterprises.',
            ];
            $score = max($score, 40);
        }

        return ['score' => min(95, $score), 'alerts' => $alerts];
    }

    /**
     * @return array{score: int, alerts: list<array<string, mixed>>}
     */
    protected function scoreSupplyChain(User $user): array
    {
        $openShipments = LogisticsShipment::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();

        $openExports = ExportOrder::query()
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();

        $score = 20 + ($openShipments * 8) + ($openExports * 10);
        $alerts = [];

        if ($openShipments > 0) {
            $alerts[] = [
                'key' => 'supply-logistics',
                'category' => 'supply_chain',
                'severity' => $openShipments >= 3 ? 'high' : 'medium',
                'title' => 'Logistics delay risk detected',
                'detail' => "{$openShipments} open shipment(s) may affect delivery SLAs.",
            ];
        }

        if ($openExports > 0) {
            $alerts[] = [
                'key' => 'supply-export',
                'category' => 'supply_chain',
                'severity' => 'medium',
                'title' => 'Export orders still in progress',
                'detail' => "{$openExports} export order(s) have not reached delivery.",
            ];
        }

        return ['score' => min(90, $score), 'alerts' => $alerts];
    }

    /**
     * @param  Collection<int, Farm>  $farms
     * @return array{score: int, alerts: list<array<string, mixed>>}
     */
    protected function scorePolitical(User $user, Collection $farms): array
    {
        $score = 42;
        $alerts = [];

        $states = $farms->pluck('state')->filter()->unique()->count();
        if ($states >= 3) {
            $score += 8;
            $alerts[] = [
                'key' => 'political-multi-state',
                'category' => 'political',
                'severity' => 'medium',
                'title' => 'Multi-state operating footprint',
                'detail' => 'Policy and checkpoint differences across states can raise transit friction.',
            ];
        }

        $exportCount = ExportOrder::query()->where('user_id', $user->id)->count();
        if ($exportCount > 0) {
            $score += 6;
        }

        return ['score' => min(80, $score), 'alerts' => $alerts];
    }

    /**
     * @return array{score: int, alerts: list<array<string, mixed>>}
     */
    protected function scoreCredit(User $user): array
    {
        $balance = $this->walletService->getBalance($user);
        $openLoans = LoanApplication::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::UnderReview, ApplicationStatus::Approved])
            ->count();

        $score = 25;
        $alerts = [];

        if ($balance < 50_000) {
            $score += 30;
            $alerts[] = [
                'key' => 'credit-wallet-low',
                'category' => 'credit',
                'severity' => 'high',
                'title' => 'Low wallet liquidity for operations',
                'detail' => 'Wallet balance is ₦'.number_format($balance).'. Fund the wallet to cover premiums, logistics, and inputs.',
            ];
        } elseif ($balance < 250_000) {
            $score += 15;
            $alerts[] = [
                'key' => 'credit-wallet-watch',
                'category' => 'credit',
                'severity' => 'medium',
                'title' => 'Wallet liquidity under watch',
                'detail' => 'Keep buffer funds for seasonal shocks and claim deductibles.',
            ];
        }

        if ($openLoans > 0) {
            $score += 12 * $openLoans;
            $alerts[] = [
                'key' => 'credit-loans',
                'category' => 'credit',
                'severity' => 'medium',
                'title' => 'Open loan applications increase credit exposure',
                'detail' => "{$openLoans} financing application(s) are still open.",
            ];
        }

        return ['score' => min(95, $score), 'alerts' => $alerts];
    }

    /**
     * @return array{score: int, alerts: list<array<string, mixed>>}
     */
    protected function scoreFraud(User $user): array
    {
        // Lightweight placeholder: keep low unless unusual wallet activity volume.
        $recentTx = DB::table('wallet_transactions')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $score = 18;
        $alerts = [];
        if ($recentTx >= 20) {
            $score = 55;
            $alerts[] = [
                'key' => 'fraud-velocity',
                'category' => 'fraud',
                'severity' => 'medium',
                'title' => 'Unusual wallet activity velocity',
                'detail' => "{$recentTx} wallet movements in 24h — review for unauthorized activity.",
            ];
        }

        return ['score' => $score, 'alerts' => $alerts];
    }

    /**
     * @param  list<array<string, mixed>>  $incoming
     */
    protected function syncAlerts(User $user, RiskAssessment $assessment, array $incoming): void
    {
        $keys = [];

        foreach ($incoming as $row) {
            $key = (string) $row['key'];
            $keys[] = $key;

            $dismissed = RiskAlert::query()
                ->where('user_id', $user->id)
                ->where('alert_key', $key)
                ->where('status', 'dismissed')
                ->where('dismissed_at', '>=', now()->subDays(7))
                ->exists();

            if ($dismissed) {
                continue;
            }

            $existing = RiskAlert::query()
                ->where('user_id', $user->id)
                ->where('alert_key', $key)
                ->whereIn('status', ['open', 'acknowledged'])
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'assessment_id' => $assessment->id,
                    'category' => $row['category'],
                    'severity' => $row['severity'],
                    'title' => $row['title'],
                    'detail' => $row['detail'] ?? null,
                ])->save();

                continue;
            }

            RiskAlert::query()->create([
                'user_id' => $user->id,
                'assessment_id' => $assessment->id,
                'alert_key' => $key,
                'category' => $row['category'],
                'severity' => $row['severity'],
                'title' => $row['title'],
                'detail' => $row['detail'] ?? null,
                'status' => 'open',
            ]);
        }

        // Close open alerts that are no longer produced by the model.
        RiskAlert::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->whereNotIn('alert_key', $keys)
            ->update([
                'status' => 'dismissed',
                'dismissed_at' => now(),
            ]);
    }

    protected function latestAssessment(User $user): ?RiskAssessment
    {
        return RiskAssessment::query()
            ->where('user_id', $user->id)
            ->latest('calculated_at')
            ->latest('id')
            ->first();
    }

    /**
     * @return array{key: string, label: string, score: int, level: string, tone: string}
     */
    protected function categoryRow(string $key, string $label, int $score): array
    {
        $score = max(0, min(100, $score));
        $level = $score >= 70 ? 'High' : ($score >= 40 ? 'Medium' : 'Low');
        $tone = $score >= 70 ? 'high' : ($score >= 40 ? 'medium' : 'low');

        return [
            'key' => $key,
            'label' => $label,
            'score' => $score,
            'level' => $level,
            'tone' => $tone,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    protected function gaugeSegments(int $score): array
    {
        $low = max(0, min(40, 100 - $score));
        $mid = max(0, min(30, abs(55 - $score)));
        $high = max(10, 100 - $low - $mid);

        return [
            'labels' => ['Low', 'Moderate', 'Elevated'],
            'values' => [$low, $mid, $high],
            'colors' => ['#0A5C2E', '#10853F', '#E6A817'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentAlert(RiskAlert $alert): array
    {
        return [
            'id' => $alert->id,
            'title' => $alert->title,
            'detail' => $alert->detail,
            'category' => Str::headline($alert->category),
            'severity' => ucfirst($alert->severity),
            'severity_tone' => $alert->severity,
            'status' => ucfirst($alert->status),
            'is_open' => $alert->status === 'open',
            'can_acknowledge' => $alert->status === 'open',
            'can_dismiss' => in_array($alert->status, ['open', 'acknowledged'], true),
            'acknowledge_url' => route('risk.alerts.acknowledge', $alert),
            'dismiss_url' => route('risk.alerts.dismiss', $alert),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentMitigation(RiskMitigation $item): array
    {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'action_type' => Str::headline($item->action_type),
            'status' => Str::headline($item->status),
            'due' => $item->due_at?->format('M j, Y'),
            'link' => $item->meta['link'] ?? null,
            'complete_url' => route('risk.mitigations.complete', $item),
        ];
    }

    /**
     * @param  Collection<int, RiskAlert>  $alerts
     * @param  Collection<int, RiskMitigation>  $mitigations
     * @return array<string, mixed>
     */
    protected function reportSummary(RiskAssessment $assessment, Collection $alerts, Collection $mitigations): array
    {
        return [
            'generated_at' => now()->format('M j, Y g:i A'),
            'score' => $assessment->overall_score,
            'status' => Str::headline($assessment->status).' Risk',
            'open_alerts' => $alerts->where('status', 'open')->count(),
            'mitigations' => $mitigations->count(),
            'factors' => $assessment->factors ?? [],
        ];
    }

    protected function actionLink(string $actionType): ?string
    {
        return match ($actionType) {
            'insure' => route('insurance.platform'),
            'market_hedge' => route('market.intelligence'),
            'wallet_topup' => route('wallet.index'),
            'logistics_review' => route('logistics.index'),
            'crop_scouting' => route('crops.manage'),
            default => null,
        };
    }

    protected function assertOwnedAlert(User $user, RiskAlert $alert): void
    {
        if ((int) $alert->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own alerts.', 'ALERT_FORBIDDEN', 403);
        }
    }

    protected function assertOwnedMitigation(User $user, RiskMitigation $mitigation): void
    {
        if ((int) $mitigation->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own mitigations.', 'MITIGATION_FORBIDDEN', 403);
        }
    }
}
