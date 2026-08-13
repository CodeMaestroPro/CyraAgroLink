<?php

declare(strict_types=1);

namespace App\Services\FoodSecurity;

use App\Enums\CropHealthStatus;
use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Crop;
use App\Models\Farm;
use App\Models\FoodSecurityIntervention;
use App\Models\FoodSecuritySnapshot;
use App\Models\InsurancePolicy;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Models\WarehouseStock;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Live national food security dashboard from farms, markets, and reserves.
 */
class FoodSecurityDashboardService
{
    /**
     * @var list<string>
     */
    public const ACTION_TYPES = [
        'reserve_release',
        'subsidy_push',
        'logistics_aid',
        'market_support',
        'scouting',
        'other',
    ];

    /**
     * @var array<string, array{lat: float, lng: float, baseline: string}>
     */
    protected const ZONE_CATALOG = [
        'Lagos' => ['lat' => 6.5244, 'lng' => 3.3792, 'baseline' => 'low'],
        'Ogun' => ['lat' => 6.9980, 'lng' => 3.4737, 'baseline' => 'low'],
        'Oyo' => ['lat' => 7.8429, 'lng' => 3.9368, 'baseline' => 'low'],
        'Kano' => ['lat' => 12.0022, 'lng' => 8.5920, 'baseline' => 'medium'],
        'Kaduna' => ['lat' => 10.5105, 'lng' => 7.4165, 'baseline' => 'medium'],
        'Benue' => ['lat' => 7.3369, 'lng' => 8.7404, 'baseline' => 'low'],
        'Borno' => ['lat' => 11.8333, 'lng' => 13.1500, 'baseline' => 'severe'],
        'Yobe' => ['lat' => 12.0000, 'lng' => 11.5000, 'baseline' => 'high'],
        'Sokoto' => ['lat' => 13.0059, 'lng' => 5.2476, 'baseline' => 'high'],
        'Rivers' => ['lat' => 4.8156, 'lng' => 7.0498, 'baseline' => 'medium'],
        'Enugu' => ['lat' => 6.5244, 'lng' => 7.5107, 'baseline' => 'low'],
        'Niger' => ['lat' => 9.9309, 'lng' => 5.5983, 'baseline' => 'medium'],
    ];

    public function __construct(
        protected MarketplaceService $marketplaceService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, ?string $state = null): array
    {
        $this->marketplaceService->getCatalog();

        $snapshot = $this->latestSnapshot();
        if (! $snapshot || $snapshot->calculated_at?->lt(now()->subHours(6))) {
            $snapshot = $this->recalculate($user);
        }

        $stateFilter = $state && isset(self::ZONE_CATALOG[$state]) ? $state : null;
        $zones = collect($snapshot->hunger_zones ?? [])
            ->when($stateFilter, fn (Collection $c) => $c->where('name', $stateFilter)->values())
            ->values()
            ->all();

        $interventions = FoodSecurityIntervention::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['planned', 'in_progress'])
            ->latest('id')
            ->limit(12)
            ->get();

        $indexStatus = $snapshot->index_status;
        $statusTone = match ($indexStatus) {
            'Critical', 'Poor' => 'text-rose-600',
            'Fair' => 'text-amber-500',
            default => 'text-cyra-forest',
        };

        return [
            'kpis' => [
                [
                    'label' => 'Food Security Index',
                    'value' => (string) $snapshot->index_score,
                    'status' => $indexStatus,
                    'status_tone' => $statusTone,
                ],
                [
                    'label' => 'National Production',
                    'value' => $this->formatTons((int) $snapshot->production_tons),
                    'status' => null,
                    'status_tone' => null,
                ],
                [
                    'label' => 'Import Dependency',
                    'value' => $snapshot->import_dependency_pct.'%',
                    'status' => null,
                    'status_tone' => null,
                ],
                [
                    'label' => 'Food Reserves',
                    'value' => $this->formatTons((int) $snapshot->reserves_tons),
                    'status' => null,
                    'status_tone' => null,
                ],
            ],
            'commodities' => $snapshot->commodities ?? [],
            'hunger_zones' => $zones,
            'all_zones' => $snapshot->hunger_zones ?? [],
            'map' => [
                'lat' => 9.2,
                'lng' => 8.1,
                'zoom' => $stateFilter ? 6.5 : 5.5,
            ],
            'state_filter' => $stateFilter,
            'state_options' => collect(array_keys(self::ZONE_CATALOG))
                ->map(fn (string $name) => [
                    'label' => $name,
                    'active' => $stateFilter === $name,
                    'url' => route('food.security', ['state' => $name]),
                ])
                ->prepend([
                    'label' => 'All states',
                    'active' => $stateFilter === null,
                    'url' => route('food.security'),
                ])
                ->values()
                ->all(),
            'interventions' => $interventions->map(fn (FoodSecurityIntervention $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'state' => $item->state,
                'action_type' => Str::headline($item->action_type),
                'status' => Str::headline($item->status),
                'due' => $item->due_at?->format('M j, Y'),
                'complete_url' => route('food.interventions.complete', $item),
            ])->all(),
            'snapshot_at' => $snapshot->calculated_at?->diffForHumans() ?? 'just now',
            'factors' => $snapshot->factors ?? [],
            'actions' => [
                'refresh_url' => route('food.refresh'),
                'export_url' => route('food.export'),
                'intervention_url' => route('food.interventions.store'),
                'insurance_url' => route('insurance.platform'),
                'market_url' => route('market.intelligence'),
            ],
            'notifications_count' => max(
                2,
                collect($snapshot->hunger_zones ?? [])->whereIn('risk', ['high', 'severe'])->count()
                + $interventions->count()
            ),
        ];
    }

    public function recalculate(User $user): FoodSecuritySnapshot
    {
        $this->marketplaceService->getCatalog();

        $computed = $this->computeSnapshot();

        return FoodSecuritySnapshot::query()->create([
            'user_id' => $user->id,
            'index_score' => $computed['index_score'],
            'index_status' => $computed['index_status'],
            'production_tons' => $computed['production_tons'],
            'import_dependency_pct' => $computed['import_dependency_pct'],
            'reserves_tons' => $computed['reserves_tons'],
            'commodities' => $computed['commodities'],
            'hunger_zones' => $computed['hunger_zones'],
            'factors' => $computed['factors'],
            'calculated_at' => now(),
            'meta' => ['version' => 1],
        ]);
    }

    /**
     * @param  array{state: string, title: string, action_type: string, notes?: string|null}  $data
     */
    public function createIntervention(User $user, array $data): FoodSecurityIntervention
    {
        if (! isset(self::ZONE_CATALOG[$data['state']])) {
            throw new BusinessLogicException('Select a valid Nigerian state zone.');
        }

        if (! in_array($data['action_type'], self::ACTION_TYPES, true)) {
            throw new BusinessLogicException('Invalid intervention action type.');
        }

        return FoodSecurityIntervention::query()->create([
            'user_id' => $user->id,
            'state' => $data['state'],
            'title' => trim($data['title']),
            'action_type' => $data['action_type'],
            'status' => 'planned',
            'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
            'due_at' => now()->addDays(14),
            'meta' => [
                'risk' => self::ZONE_CATALOG[$data['state']]['baseline'],
            ],
        ]);
    }

    public function completeIntervention(User $user, FoodSecurityIntervention $intervention): FoodSecurityIntervention
    {
        if ((int) $intervention->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own interventions.', 'INTERVENTION_FORBIDDEN', 403);
        }

        if ($intervention->status === 'done') {
            throw new BusinessLogicException('This intervention is already completed.');
        }

        $intervention->forceFill([
            'status' => 'done',
            'completed_at' => now(),
        ])->save();

        return $intervention->refresh();
    }

    public function exportReport(User $user): StreamedResponse
    {
        $data = $this->getDashboardData($user);

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CyraAgroLink Food Security Report', now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['KPI', 'Value', 'Status']);
            foreach ($data['kpis'] as $kpi) {
                fputcsv($out, [$kpi['label'], $kpi['value'], $kpi['status'] ?? '']);
            }
            fputcsv($out, []);
            fputcsv($out, ['Commodity', 'Share %']);
            foreach ($data['commodities'] as $row) {
                fputcsv($out, [$row['name'], $row['percent']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['State', 'Risk', 'Farms', 'Notes']);
            foreach ($data['all_zones'] as $zone) {
                fputcsv($out, [
                    $zone['name'],
                    $zone['risk'],
                    $zone['farms'] ?? 0,
                    $zone['detail'] ?? '',
                ]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Interventions']);
            fputcsv($out, ['Title', 'State', 'Action', 'Status', 'Due']);
            foreach ($data['interventions'] as $item) {
                fputcsv($out, [
                    $item['title'],
                    $item['state'],
                    $item['action_type'],
                    $item['status'],
                    $item['due'] ?? '',
                ]);
            }
            fclose($out);
        }, 'cyra-food-security-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function computeSnapshot(): array
    {
        $farms = Farm::query()
            ->whereIn('status', [FarmStatus::Active, FarmStatus::PendingReview])
            ->get(['id', 'state', 'size_hectares', 'crops', 'status']);

        $hectares = (float) $farms->sum(fn (Farm $f) => (float) ($f->size_hectares ?? 0));
        $farmProduction = (int) max(0, round($hectares * 2.4)); // tons estimate

        $marketVolume = (int) MarketplaceCommodity::query()
            ->where('status', 'active')
            ->sum('volume_tons');

        $reserves = (int) WarehouseStock::query()->sum('quantity_tons');
        $productionTons = max($farmProduction + (int) round($marketVolume * 0.35), $marketVolume > 0 ? $marketVolume : 500);

        $commodityCount = MarketplaceCommodity::query()->where('status', 'active')->count();
        $insuredFarms = InsurancePolicy::query()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->pluck('farm_id')
            ->unique()
            ->count();

        $unhealthyCrops = Crop::query()
            ->where('status', 'active')
            ->whereIn('health_status', [CropHealthStatus::Poor->value, CropHealthStatus::Critical->value])
            ->count();

        $importDependency = (int) max(5, min(45, 32 - min(20, (int) floor($commodityCount / 2)) + min(12, $unhealthyCrops * 2)));

        $index = 50
            + min(20, (int) floor($farms->count() * 1.5))
            + min(15, (int) floor($commodityCount))
            + min(10, (int) floor($reserves / 500))
            + min(8, $insuredFarms)
            - min(15, $unhealthyCrops * 3);
        $index = (int) max(35, min(96, $index));

        $indexStatus = match (true) {
            $index >= 75 => 'Good',
            $index >= 60 => 'Fair',
            $index >= 45 => 'Poor',
            default => 'Critical',
        };

        return [
            'index_score' => $index,
            'index_status' => $indexStatus,
            'production_tons' => $productionTons,
            'import_dependency_pct' => $importDependency,
            'reserves_tons' => max($reserves, (int) round($productionTons * 0.18)),
            'commodities' => $this->computeCommodities(),
            'hunger_zones' => $this->computeHungerZones($farms),
            'factors' => [
                'active_farms' => $farms->count(),
                'hectares' => round($hectares, 1),
                'market_commodities' => $commodityCount,
                'insured_farms' => $insuredFarms,
                'unhealthy_crops' => $unhealthyCrops,
                'warehouse_stock_tons' => $reserves,
            ],
        ];
    }

    /**
     * @return list<array{name: string, percent: int, icon: string, tons: int}>
     */
    protected function computeCommodities(): array
    {
        $preferred = ['Maize', 'Rice', 'Cassava', 'Yam', 'Cocoa', 'Soybean'];
        $commodities = MarketplaceCommodity::query()
            ->where('status', 'active')
            ->whereIn('name', $preferred)
            ->get();

        if ($commodities->isEmpty()) {
            $commodities = MarketplaceCommodity::query()
                ->where('status', 'active')
                ->orderByDesc('volume_tons')
                ->limit(4)
                ->get();
        }

        $total = max(1, (int) $commodities->sum(fn (MarketplaceCommodity $c) => max(1, (int) ($c->volume_tons ?? 1))));

        $rows = $commodities
            ->take(4)
            ->map(function (MarketplaceCommodity $c) use ($total) {
                $tons = max(1, (int) ($c->volume_tons ?? 1));
                $percent = (int) max(5, min(95, round(($tons / $total) * 100)));
                $icon = match (strtolower($c->name)) {
                    'maize' => 'maize',
                    'rice' => 'rice',
                    'cassava' => 'cassava',
                    default => 'wheat',
                };

                return [
                    'name' => $c->name,
                    'percent' => $percent,
                    'icon' => $icon,
                    'tons' => $tons,
                ];
            })
            ->values()
            ->all();

        if ($rows === []) {
            return [
                ['name' => 'Maize', 'percent' => 40, 'icon' => 'maize', 'tons' => 0],
                ['name' => 'Rice', 'percent' => 30, 'icon' => 'rice', 'tons' => 0],
                ['name' => 'Cassava', 'percent' => 20, 'icon' => 'cassava', 'tons' => 0],
                ['name' => 'Wheat', 'percent' => 10, 'icon' => 'wheat', 'tons' => 0],
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Farm>  $farms
     * @return list<array<string, mixed>>
     */
    protected function computeHungerZones(Collection $farms): array
    {
        $farmsByState = $farms->groupBy(fn (Farm $f) => (string) ($f->state ?: 'Unknown'));

        $zones = [];
        foreach (self::ZONE_CATALOG as $name => $meta) {
            $stateFarms = $farmsByState->get($name, collect());
            $count = $stateFarms->count();
            $risk = $meta['baseline'];

            if ($count >= 5) {
                $risk = $this->easeRisk($risk);
            } elseif ($count === 0 && in_array($risk, ['low', 'medium'], true)) {
                // Keep baseline for sparse coverage states.
            }

            $detail = match ($risk) {
                'severe' => 'Acute access stress — prioritize logistics and reserve release.',
                'high' => 'Elevated hunger risk — monitor prices and stock corridors.',
                'medium' => 'Watchlist zone — maintain buffer stocks and market watch.',
                default => 'Stable supply signals from current platform data.',
            };

            if ($count > 0) {
                $detail .= " {$count} linked farm".($count === 1 ? '' : 's').' on CyraAgroLink.';
            }

            $zones[] = [
                'name' => $name,
                'lat' => $meta['lat'],
                'lng' => $meta['lng'],
                'risk' => $risk,
                'farms' => $count,
                'detail' => $detail,
            ];
        }

        return $zones;
    }

    protected function easeRisk(string $risk): string
    {
        return match ($risk) {
            'severe' => 'high',
            'high' => 'medium',
            'medium' => 'low',
            default => 'low',
        };
    }

    protected function latestSnapshot(): ?FoodSecuritySnapshot
    {
        return FoodSecuritySnapshot::query()
            ->latest('calculated_at')
            ->latest('id')
            ->first();
    }

    protected function formatTons(int $tons): string
    {
        if ($tons >= 1_000_000) {
            return rtrim(rtrim(number_format($tons / 1_000_000, 1), '0'), '.').'M Tons';
        }

        if ($tons >= 1_000) {
            return rtrim(rtrim(number_format($tons / 1_000, 1), '0'), '.').'K Tons';
        }

        return number_format($tons).' Tons';
    }
}
