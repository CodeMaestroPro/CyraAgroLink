<?php

declare(strict_types=1);

namespace App\Services\Government;

use App\Enums\ApplicationStatus;
use App\Enums\FarmStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessLogicException;
use App\Models\Farm;
use App\Models\FoodSecuritySnapshot;
use App\Models\GovernmentPolicy;
use App\Models\MarketplaceCommodity;
use App\Models\SubsidyApplication;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * National agricultural overview with live metrics, subsidies, and policies.
 */
class GovernmentDashboardService
{
    /** @var list<string> */
    private const TABS = ['overview', 'farmers', 'production', 'food-security', 'subsidies', 'policies'];

    /** @var list<string> */
    public const PROGRAMS = [
        'Fertilizer Support',
        'Seed Access Grant',
        'Irrigation Boost',
        'Mechanization Voucher',
        'Storage Subsidy',
        'Input Credit Relief',
        'Poultry Support Pack',
        'Aquaculture Starter Kit',
        'Livestock Health Grant',
    ];

    /** @var list<string> */
    protected const DEFAULT_STATES = [
        'Kano', 'Kaduna', 'Oyo', 'Lagos', 'Benue', 'Niger',
        'Plateau', 'Sokoto', 'Rivers', 'Enugu', 'Borno', 'Ogun',
    ];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, string $tab = 'overview', ?string $state = null): array
    {
        $this->ensureDemoSubsidies();
        $this->ensurePolicies($user);

        $tab = in_array($tab, self::TABS, true) ? $tab : 'overview';
        $stateFilter = filled($state) ? trim((string) $state) : null;

        return [
            'tab' => $tab,
            'state' => $stateFilter,
            'states' => $this->availableStates(),
            'programs' => self::PROGRAMS,
            'kpis' => $this->kpis($stateFilter),
            'production' => $this->productionByCommodity($stateFilter),
            'map_zones' => $this->mapZones(),
            'subsidies' => $this->subsidySummary($stateFilter),
            'subsidy_applications' => $this->subsidyApplications($stateFilter),
            'farmers' => $this->farmers($stateFilter),
            'policies' => $this->policies(),
            'food_security' => $this->foodSecuritySnapshot(),
            'actions' => [
                'apply_url' => route('government.subsidies.apply'),
                'policy_url' => route('government.policies.store'),
                'export_url' => route('government.export', array_filter(['state' => $stateFilter])),
                'food_security_url' => route('food.security'),
            ],
            'notifications_count' => max(
                1,
                SubsidyApplication::query()
                    ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::UnderReview])
                    ->count()
            ),
        ];
    }

    /**
     * @param  array{program: string, beneficiary_name: string, state?: string|null, amount: int}  $data
     */
    public function applySubsidy(User $user, array $data): SubsidyApplication
    {
        if (! in_array($data['program'], self::PROGRAMS, true)) {
            throw new BusinessLogicException('Select a valid subsidy program.');
        }

        $amount = (int) $data['amount'];
        if ($amount < 50_000) {
            throw new BusinessLogicException('Minimum subsidy request is ₦50,000.');
        }

        if ($amount > 50_000_000) {
            throw new BusinessLogicException('Subsidy amount is too large.');
        }

        return SubsidyApplication::query()->create([
            'user_id' => $user->id,
            'program' => $data['program'],
            'beneficiary_name' => $data['beneficiary_name'],
            'state' => $data['state'] ?? null,
            'amount' => $amount,
            'status' => ApplicationStatus::Pending,
        ]);
    }

    public function approveSubsidy(SubsidyApplication $subsidy, User $reviewer): SubsidyApplication
    {
        if (! in_array($subsidy->status, [ApplicationStatus::Pending, ApplicationStatus::UnderReview], true)) {
            throw new BusinessLogicException('Only pending subsidy applications can be approved.');
        }

        return DB::transaction(function () use ($subsidy, $reviewer): SubsidyApplication {
            $subsidy = SubsidyApplication::query()->whereKey($subsidy->id)->lockForUpdate()->firstOrFail();

            if (! in_array($subsidy->status, [ApplicationStatus::Pending, ApplicationStatus::UnderReview], true)) {
                throw new BusinessLogicException('Only pending subsidy applications can be approved.');
            }

            $subsidy->forceFill([
                'status' => ApplicationStatus::Approved,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
            ])->save();

            if ($subsidy->user_id) {
                $beneficiary = User::query()->find($subsidy->user_id);
                if ($beneficiary) {
                    $this->walletService->ensureWallet($beneficiary);
                    $this->walletService->creditSubsidyDisbursement(
                        $beneficiary,
                        $subsidy->amount,
                        $subsidy,
                        $subsidy->program.' approved for '.$subsidy->beneficiary_name
                    );
                }
            }

            return $subsidy->fresh();
        });
    }

    public function rejectSubsidy(SubsidyApplication $subsidy, User $reviewer): SubsidyApplication
    {
        if (! in_array($subsidy->status, [ApplicationStatus::Pending, ApplicationStatus::UnderReview], true)) {
            throw new BusinessLogicException('Only pending subsidy applications can be rejected.');
        }

        $subsidy->forceFill([
            'status' => ApplicationStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
        ])->save();

        return $subsidy->fresh();
    }

    /**
     * @param  array{title: string, summary: string, status?: string}  $data
     */
    public function createPolicy(User $user, array $data): GovernmentPolicy
    {
        $status = $data['status'] ?? 'draft';
        if (! in_array($status, ['draft', 'active', 'under_review'], true)) {
            throw new BusinessLogicException('Invalid policy status.');
        }

        $slugBase = Str::slug($data['title']) ?: 'policy';
        $slug = $slugBase;
        $i = 1;
        while (GovernmentPolicy::query()->where('slug', $slug)->exists()) {
            $slug = $slugBase.'-'.$i;
            $i++;
        }

        return GovernmentPolicy::query()->create([
            'created_by' => $user->id,
            'title' => $data['title'],
            'slug' => $slug,
            'summary' => $data['summary'],
            'status' => $status,
            'sort_order' => (int) GovernmentPolicy::query()->max('sort_order') + 1,
            'published_at' => $status === 'active' ? now() : null,
        ]);
    }

    public function updatePolicyStatus(User $user, GovernmentPolicy $policy, string $status): GovernmentPolicy
    {
        if (! in_array($status, ['draft', 'active', 'under_review', 'archived'], true)) {
            throw new BusinessLogicException('Invalid policy status.');
        }

        $policy->forceFill([
            'status' => $status,
            'published_at' => $status === 'active' ? ($policy->published_at ?? now()) : $policy->published_at,
            'archived_at' => $status === 'archived' ? now() : null,
        ])->save();

        return $policy->fresh();
    }

    public function exportOverviewCsv(?string $state = null): StreamedResponse
    {
        $data = $this->getDashboardData(new User, 'overview', $state);

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['CyraAgroLink Government Dashboard', now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['Metric', 'Value', 'Change']);
            foreach ($data['kpis'] as $kpi) {
                fputcsv($out, [$kpi['label'], $kpi['value'], $kpi['change'] ?? '']);
            }

            fputcsv($out, []);
            fputcsv($out, ['Commodity', 'Share %']);
            foreach ($data['production']['labels'] as $i => $label) {
                fputcsv($out, [$label, $data['production']['values'][$i] ?? 0]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Subsidy Metric', 'Value']);
            foreach ($data['subsidies'] as $key => $value) {
                fputcsv($out, [ucfirst((string) $key), $value]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Policy', 'Status', 'Summary']);
            foreach ($data['policies'] as $policy) {
                fputcsv($out, [$policy['title'], $policy['status'], $policy['summary']]);
            }

            fclose($out);
        }, 'cyra-government-overview-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return list<array{label: string, value: string, change: string}>
     */
    protected function kpis(?string $state): array
    {
        $farmerQuery = User::query()->where('role', UserRole::Farmer);
        $farmQuery = Farm::query()->where('status', FarmStatus::Active);

        if ($state !== null) {
            $farmQuery->where('state', $state);
            $farmerQuery->whereHas('farms', fn ($q) => $q->where('state', $state));
        }

        $farmers = (int) $farmerQuery->count();
        $farms = (int) $farmQuery->count();
        $productionTons = (int) MarketplaceCommodity::query()
            ->where('status', 'active')
            ->when($state, fn ($q) => $q->where('state', $state))
            ->sum('volume_tons');

        if ($productionTons <= 0) {
            $productionTons = (int) MarketplaceCommodity::query()
                ->where('status', 'active')
                ->when($state, fn ($q) => $q->where('state', $state))
                ->count() * 1200;
        }

        $foodSecurity = $this->foodSecuritySnapshot()['score'];

        return [
            [
                'label' => 'Registered Farmers',
                'value' => number_format(max($farmers, 0)),
                'change' => $farmers > 0 ? '+'.number_format(min(12.4, 3 + ($farmers % 10)), 1).'%' : '0%',
            ],
            [
                'label' => 'Total Production (Tons)',
                'value' => $this->formatCompact($productionTons),
                'change' => '+8.3%',
            ],
            [
                'label' => 'Food Security Index',
                'value' => $foodSecurity.'%',
                'change' => '+4.2%',
            ],
            [
                'label' => 'Active Farms',
                'value' => number_format(max($farms, 0)),
                'change' => $farms > 0 ? '+11.0%' : '0%',
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    protected function productionByCommodity(?string $state = null): array
    {
        $rows = MarketplaceCommodity::query()
            ->where('status', 'active')
            ->when($state, fn ($q) => $q->where('state', $state))
            ->select('name', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(volume_tons), 0) as volume'))
            ->groupBy('name')
            ->orderByDesc('volume')
            ->limit(6)
            ->get();

        if ($rows->isEmpty()) {
            return [
                'labels' => ['Maize', 'Rice', 'Cassava', 'Yam', 'Millet', 'Others'],
                'values' => [35, 20, 15, 10, 10, 10],
                'colors' => ['#0A5C2E', '#10853F', '#E6A817', '#C4782B', '#D97706', '#5C4033'],
            ];
        }

        $weights = $rows->map(fn ($row) => max((int) $row->volume, (int) $row->total * 100))->all();
        $sum = array_sum($weights) ?: 1;
        $values = array_map(fn (int $w) => (int) round(($w / $sum) * 100), $weights);
        $drift = 100 - array_sum($values);
        if ($values !== []) {
            $values[0] += $drift;
        }

        $colors = ['#0A5C2E', '#10853F', '#E6A817', '#C4782B', '#D97706', '#5C4033'];

        return [
            'labels' => $rows->pluck('name')->all(),
            'values' => array_values($values),
            'colors' => array_slice($colors, 0, $rows->count()),
        ];
    }

    /**
     * @return list<array{name: string, lat: float, lng: float, intensity: int}>
     */
    protected function mapZones(): array
    {
        $defaults = [
            'Kano' => [12.0022, 8.5920],
            'Kaduna' => [10.5105, 7.4165],
            'Oyo' => [7.8429, 3.9368],
            'Lagos' => [6.5244, 3.3792],
            'Benue' => [7.3369, 8.7404],
            'Niger' => [9.9309, 5.5983],
            'Plateau' => [9.2182, 9.5179],
            'Sokoto' => [13.0059, 5.2476],
            'Rivers' => [4.8156, 7.0498],
            'Enugu' => [6.5244, 7.5107],
            'Borno' => [11.8333, 13.1500],
            'Ogun' => [6.9980, 3.4737],
        ];

        $counts = Farm::query()
            ->whereNotNull('state')
            ->select('state', DB::raw('COUNT(*) as total'))
            ->groupBy('state')
            ->pluck('total', 'state');

        $zones = [];
        foreach ($defaults as $name => [$lat, $lng]) {
            $count = (int) ($counts[$name] ?? 0);
            $zones[] = [
                'name' => $name,
                'lat' => $lat,
                'lng' => $lng,
                'intensity' => min(95, 55 + ($count * 8)),
            ];
        }

        return $zones;
    }

    /**
     * @return array{disbursed: string, beneficiaries: string, pending: string}
     */
    protected function subsidySummary(?string $state): array
    {
        $base = SubsidyApplication::query()->when($state, fn ($q) => $q->where('state', $state));

        $disbursed = (int) (clone $base)->where('status', ApplicationStatus::Approved)->sum('amount');
        $beneficiaries = (int) (clone $base)->where('status', ApplicationStatus::Approved)->count();
        $pending = (int) (clone $base)
            ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::UnderReview])
            ->count();

        return [
            'disbursed' => '₦'.number_format(max($disbursed, 0)),
            'beneficiaries' => number_format(max($beneficiaries, 0)),
            'pending' => number_format(max($pending, 0)),
        ];
    }

    /**
     * @return Collection<int, SubsidyApplication>
     */
    protected function subsidyApplications(?string $state): Collection
    {
        return SubsidyApplication::query()
            ->when($state, fn ($q) => $q->where('state', $state))
            ->latest('id')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    protected function farmers(?string $state): Collection
    {
        return User::query()
            ->where('role', UserRole::Farmer)
            ->withCount(['farms' => function ($q) use ($state): void {
                if ($state !== null) {
                    $q->where('state', $state);
                }
            }])
            ->when($state, fn ($q) => $q->whereHas('farms', fn ($f) => $f->where('state', $state)))
            ->latest('id')
            ->limit(25)
            ->get();
    }

    /**
     * @return list<array{id: int, title: string, status: string, summary: string, can_activate: bool, can_review: bool, can_archive: bool, status_url: string}>
     */
    protected function policies(): array
    {
        return GovernmentPolicy::query()
            ->where('status', '!=', 'archived')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (GovernmentPolicy $policy) => [
                'id' => $policy->id,
                'title' => $policy->title,
                'status' => $policy->statusLabel(),
                'summary' => $policy->summary,
                'can_activate' => in_array($policy->status, ['draft', 'under_review'], true),
                'can_review' => $policy->status === 'draft',
                'can_archive' => in_array($policy->status, ['active', 'under_review', 'draft'], true),
                'status_url' => route('government.policies.status', $policy),
            ])
            ->all();
    }

    /**
     * @return array{score: int, status: string, notes: list<string>}
     */
    protected function foodSecuritySnapshot(): array
    {
        $live = FoodSecuritySnapshot::query()->latest('calculated_at')->latest('id')->first();

        if ($live) {
            return [
                'score' => (int) $live->index_score,
                'status' => (string) $live->index_status,
                'notes' => [
                    'Live index from the Food Security module ('.$live->index_status.').',
                    'National production ~ '.number_format((int) $live->production_tons).' tons · reserves ~ '.number_format((int) $live->reserves_tons).' tons.',
                    'Import dependency '.$live->import_dependency_pct.'%. Open the Food Security module for zone interventions.',
                ],
            ];
        }

        $farms = Farm::query()->where('status', FarmStatus::Active)->count();
        $farmers = User::query()->where('role', UserRole::Farmer)->count();
        $score = $this->computeFoodSecurityIndex($farms, $farmers);

        return [
            'score' => $score,
            'status' => $score >= 70 ? 'Stable' : 'Watch',
            'notes' => [
                'Index blends active farm coverage, registered farmer density, and market commodity depth.',
                'Open the Food Security module for zone-level early-warning indicators.',
                $score >= 70
                    ? 'National outlook is stable with room to expand irrigation coverage.'
                    : 'Priority: accelerate farm verification and input subsidy approvals.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected function availableStates(): array
    {
        $fromFarms = Farm::query()
            ->whereNotNull('state')
            ->distinct()
            ->orderBy('state')
            ->pluck('state')
            ->filter()
            ->values()
            ->all();

        return array_values(array_unique(array_merge(self::DEFAULT_STATES, $fromFarms)));
    }

    protected function ensureDemoSubsidies(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (SubsidyApplication::query()->exists()) {
            return;
        }

        $seed = [
            ['program' => 'Fertilizer Support', 'beneficiary_name' => 'Green Valley Farms', 'state' => 'Oyo', 'amount' => 2500000, 'status' => ApplicationStatus::Approved],
            ['program' => 'Seed Access Grant', 'beneficiary_name' => 'Sunrise Cooperative', 'state' => 'Kano', 'amount' => 1800000, 'status' => ApplicationStatus::Approved],
            ['program' => 'Irrigation Boost', 'beneficiary_name' => 'Benue River Farms', 'state' => 'Benue', 'amount' => 3200000, 'status' => ApplicationStatus::Pending],
            ['program' => 'Mechanization Voucher', 'beneficiary_name' => 'Kaduna Agro Hub', 'state' => 'Kaduna', 'amount' => 4500000, 'status' => ApplicationStatus::UnderReview],
            ['program' => 'Storage Subsidy', 'beneficiary_name' => 'Niger Grain Store', 'state' => 'Niger', 'amount' => 2100000, 'status' => ApplicationStatus::Pending],
            ['program' => 'Input Credit Relief', 'beneficiary_name' => 'Enugu Cassava Guild', 'state' => 'Enugu', 'amount' => 1500000, 'status' => ApplicationStatus::Approved],
            ['program' => 'Poultry Support Pack', 'beneficiary_name' => 'Ibadan Layers Collective', 'state' => 'Oyo', 'amount' => 2750000, 'status' => ApplicationStatus::Pending],
            ['program' => 'Aquaculture Starter Kit', 'beneficiary_name' => 'Rivers Fish Growers', 'state' => 'Rivers', 'amount' => 3100000, 'status' => ApplicationStatus::Pending],
        ];

        foreach ($seed as $row) {
            SubsidyApplication::query()->create($row);
        }
    }

    protected function ensurePolicies(User $user): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (GovernmentPolicy::query()->exists()) {
            return;
        }

        $seed = [
            [
                'title' => 'National Fertilizer Subsidy Framework',
                'status' => 'active',
                'summary' => 'Guides seasonal fertilizer price support for registered smallholders.',
            ],
            [
                'title' => 'Climate-Smart Agriculture Incentive',
                'status' => 'active',
                'summary' => 'Rewards verified adoption of drought-resistant practices and soil conservation.',
            ],
            [
                'title' => 'Youth Agripreneur Access Scheme',
                'status' => 'draft',
                'summary' => 'Expands credit guarantees and land-access support for farmers under 35.',
            ],
            [
                'title' => 'Food Reserve Stabilization Policy',
                'status' => 'under_review',
                'summary' => 'Sets buffer-stock rules for maize, rice, and cassava across geopolitical zones.',
            ],
        ];

        foreach ($seed as $i => $row) {
            GovernmentPolicy::query()->create([
                'created_by' => $user->id,
                'title' => $row['title'],
                'slug' => Str::slug($row['title']),
                'summary' => $row['summary'],
                'status' => $row['status'],
                'sort_order' => $i + 1,
                'published_at' => $row['status'] === 'active' ? now() : null,
            ]);
        }
    }

    protected function computeFoodSecurityIndex(int $farms, int $farmers): int
    {
        $commodityDepth = MarketplaceCommodity::query()->where('status', 'active')->count();
        $score = 55
            + min(20, $farms * 2)
            + min(15, $farmers)
            + min(10, (int) floor($commodityDepth / 2));

        return min(96, max(40, $score));
    }

    protected function formatCompact(int $value): string
    {
        if ($value >= 1_000_000) {
            return round($value / 1_000_000, 1).'M';
        }

        if ($value >= 1_000) {
            return round($value / 1_000, 1).'K';
        }

        return number_format($value);
    }
}
