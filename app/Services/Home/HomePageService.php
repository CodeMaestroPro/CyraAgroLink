<?php

declare(strict_types=1);

namespace App\Services\Home;

use App\Enums\FarmStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AcademyCourse;
use App\Models\Farm;
use App\Models\InvestmentOpportunity;
use App\Models\LogisticsShipment;
use App\Models\LogisticsVehicle;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Services\Academy\LearningAcademyService;
use App\Services\Investment\InvestmentMarketplaceService;
use App\Services\Logistics\LogisticsNetworkService;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * Aggregate live platform data for the public marketing home page.
 */
class HomePageService
{
    public function __construct(
        protected MarketplaceService $marketplaceService,
        protected InvestmentMarketplaceService $investmentMarketplaceService,
        protected LogisticsNetworkService $logisticsNetworkService,
        protected LearningAcademyService $learningAcademyService,
    ) {
    }

    /**
     * @return array{
     *     commodities: Collection,
     *     stats: list<array{value: string, label: string, icon: string}>,
     *     invest: array<string, mixed>,
     *     logistics: array<string, mixed>,
     *     resources: array<string, mixed>,
     *     solution_stats: array<string, list<array{label: string, value: string}>>
     * }
     */
    public function getHomePageData(): array
    {
        $this->investmentMarketplaceService->ensureCatalog();
        $this->logisticsNetworkService->ensureFleetCatalog();
        $this->learningAcademyService->ensureCatalog();

        $commodities = $this->marketplaceService->getFeaturedForHome(12);
        $counts = $this->platformCounts();

        return [
            'commodities' => $commodities,
            'stats' => $this->buildStats($counts),
            'invest' => $this->buildInvestTracks(),
            'logistics' => $this->buildLogisticsModes(),
            'resources' => $this->buildResourceTracks(),
            'solution_stats' => $this->buildSolutionStats($counts),
        ];
    }

    /**
     * @return array{
     *     farmers: int,
     *     investors: int,
     *     buyers: int,
     *     listings: int,
     *     farms: int,
     *     opportunities: int,
     *     vehicles: int,
     *     warehouses: int,
     *     in_transit: int,
     *     courses: int,
     *     transactions: int
     * }
     */
    protected function platformCounts(): array
    {
        return [
            'farmers' => User::query()
                ->where('role', UserRole::Farmer)
                ->where('status', UserStatus::Active)
                ->count(),
            'investors' => User::query()
                ->where('role', UserRole::Investor)
                ->where('status', UserStatus::Active)
                ->count(),
            'buyers' => User::query()
                ->where('role', UserRole::Buyer)
                ->where('status', UserStatus::Active)
                ->count(),
            'listings' => MarketplaceCommodity::query()
                ->where('status', 'active')
                ->count(),
            'farms' => Farm::query()
                ->where('status', FarmStatus::Active)
                ->count(),
            'opportunities' => InvestmentOpportunity::query()
                ->where('status', 'active')
                ->count(),
            'vehicles' => LogisticsVehicle::query()
                ->where('is_active', true)
                ->count(),
            'warehouses' => Warehouse::query()
                ->where('status', 'active')
                ->count(),
            'in_transit' => LogisticsShipment::query()
                ->where('status', 'in_transit')
                ->count(),
            'courses' => AcademyCourse::query()
                ->where('is_active', true)
                ->count(),
            'transactions' => WalletTransaction::query()->count(),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{value: string, label: string, icon: string}>
     */
    protected function buildStats(array $counts): array
    {
        return [
            ['value' => $this->formatCount($counts['farmers']), 'label' => __('home.stats.farmers'), 'icon' => 'farmers'],
            ['value' => $this->formatCount($counts['investors']), 'label' => __('home.stats.investors'), 'icon' => 'investors'],
            ['value' => $this->formatCount($counts['buyers']), 'label' => __('home.stats.buyers'), 'icon' => 'buyers'],
            ['value' => $this->formatCount($counts['listings']), 'label' => __('home.stats.listings'), 'icon' => 'listings'],
            ['value' => $this->formatCount($counts['farms']), 'label' => __('home.stats.farms'), 'icon' => 'farms'],
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, list<array{label: string, value: string}>>
     */
    protected function buildSolutionStats(array $counts): array
    {
        $roiRange = $this->roiRangeLabel();

        return [
            'grow' => [
                ['label' => __('home.solutions.grow.stats.farms'), 'value' => $this->formatCount($counts['farms'])],
                ['label' => __('home.solutions.grow.stats.farmers'), 'value' => $this->formatCount($counts['farmers'])],
                ['label' => __('home.solutions.grow.stats.courses'), 'value' => $this->formatCount($counts['courses'])],
            ],
            'trade' => [
                ['label' => __('home.solutions.trade.stats.buyers'), 'value' => $this->formatCount($counts['buyers'])],
                ['label' => __('home.solutions.trade.stats.listings'), 'value' => $this->formatCount($counts['listings'])],
                ['label' => __('home.solutions.trade.stats.trades'), 'value' => $this->formatCount($counts['transactions'])],
            ],
            'capital' => [
                ['label' => __('home.solutions.capital.stats.roi_range'), 'value' => $roiRange],
                ['label' => __('home.solutions.capital.stats.projects'), 'value' => $this->formatCount($counts['opportunities'])],
                ['label' => __('home.solutions.capital.stats.investors'), 'value' => $this->formatCount($counts['investors'])],
            ],
            'network' => [
                ['label' => __('home.solutions.network.stats.fleet'), 'value' => $this->formatCount($counts['vehicles'])],
                ['label' => __('home.solutions.network.stats.warehouses'), 'value' => $this->formatCount($counts['warehouses'])],
                ['label' => __('home.solutions.network.stats.in_transit'), 'value' => $this->formatCount($counts['in_transit'])],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildInvestTracks(): array
    {
        $investHref = route('investments.index');
        $dashboardHref = route('investor.dashboard');

        $active = InvestmentOpportunity::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $featured = $active->where('is_featured', true)->take(3)->values();
        if ($featured->isEmpty()) {
            $featured = $active->take(3)->values();
        }

        $highRoi = $active->sortByDesc(fn (InvestmentOpportunity $o) => (float) $o->roi_percent)
            ->take(3)
            ->values();

        $impact = $active->sortByDesc(fn (InvestmentOpportunity $o) => (int) $o->amount)
            ->take(3)
            ->values();

        $roiValues = $active->pluck('roi_percent')->map(fn ($v) => (float) $v);
        $emDash = __('home.common.em_dash');
        $topRoi = $roiValues->isNotEmpty() ? rtrim(rtrim(number_format((float) $roiValues->max(), 1), '0'), '.').'%' : $emDash;
        $avgRoi = $roiValues->isNotEmpty() ? rtrim(rtrim(number_format((float) $roiValues->avg(), 1), '0'), '.').'%' : $emDash;
        $avgCycle = $active->isNotEmpty()
            ? __('home.invest.avg_cycle_months', [
                'count' => (int) round((float) $active->avg('duration_months')),
            ])
            : $emDash;

        return [
            'featured' => [
                'label' => __('home.invest.featured.label'),
                'kicker' => __('home.invest.featured.kicker'),
                'title' => __('home.invest.featured.title'),
                'copy' => __('home.invest.featured.copy'),
                'cta' => __('home.invest.featured.cta'),
                'href' => $investHref,
                'network' => __('home.invest.featured.network'),
                'network_href' => $dashboardHref,
                'stats' => [
                    ['label' => __('home.invest.featured.stats.roi_range'), 'value' => $this->roiRangeLabel()],
                    ['label' => __('home.invest.featured.stats.projects'), 'value' => $this->formatCount($active->count())],
                    ['label' => __('home.invest.featured.stats.open_now'), 'value' => $this->formatCount($featured->count())],
                ],
                'items' => $this->mapOpportunityItems($featured),
            ],
            'roi' => [
                'label' => __('home.invest.roi.label'),
                'kicker' => __('home.invest.roi.kicker'),
                'title' => __('home.invest.roi.title'),
                'copy' => __('home.invest.roi.copy'),
                'cta' => __('home.invest.roi.cta'),
                'href' => $investHref.'?all=1',
                'network' => __('home.invest.roi.network'),
                'network_href' => $dashboardHref,
                'stats' => [
                    ['label' => __('home.invest.roi.stats.top_roi'), 'value' => $topRoi],
                    ['label' => __('home.invest.roi.stats.avg_roi'), 'value' => $avgRoi],
                    ['label' => __('home.invest.roi.stats.avg_cycle'), 'value' => $avgCycle],
                ],
                'items' => $this->mapOpportunityItems($highRoi),
            ],
            'impact' => [
                'label' => __('home.invest.impact.label'),
                'kicker' => __('home.invest.impact.kicker'),
                'title' => __('home.invest.impact.title'),
                'copy' => __('home.invest.impact.copy'),
                'cta' => __('home.invest.impact.cta'),
                'href' => $investHref.'?all=1',
                'network' => __('home.invest.impact.network'),
                'network_href' => $dashboardHref,
                'stats' => [
                    ['label' => __('home.invest.impact.stats.projects'), 'value' => $this->formatCount($active->count())],
                    ['label' => __('home.invest.impact.stats.investors'), 'value' => $this->formatCount(
                        User::query()->where('role', UserRole::Investor)->where('status', UserStatus::Active)->count()
                    )],
                    ['label' => __('home.invest.impact.stats.avg_cycle'), 'value' => $avgCycle],
                ],
                'items' => $this->mapOpportunityItems($impact),
            ],
        ];
    }

    /**
     * @param  Collection<int, InvestmentOpportunity>  $opportunities
     * @return list<array<string, mixed>>
     */
    protected function mapOpportunityItems(Collection $opportunities): array
    {
        return $opportunities->map(function (InvestmentOpportunity $opportunity) {
            $roi = rtrim(rtrim(number_format((float) $opportunity->roi_percent, 1), '0'), '.');

            return [
                'name' => $opportunity->localizedTitle(),
                'route' => $opportunity->localizedLocation(),
                'meta' => __('home.invest.item.meta', [
                    'roi' => $roi,
                    'duration' => $opportunity->durationLabel(),
                    'funded' => $opportunity->fundedLabel(),
                ]),
                'image' => $opportunity->imageUrl(),
                'eta' => $opportunity->formattedAmount(),
                'href' => route('investments.show', $opportunity),
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildLogisticsModes(): array
    {
        $logisticsHref = route('logistics.index');
        $warehouseHref = route('warehouse.index');
        $trackingHref = route('supply-chain.index');

        $fleet = LogisticsVehicle::query()
            ->where('is_active', true)
            ->where('status', 'available')
            ->orderBy('capacity_tons')
            ->limit(3)
            ->get();

        if ($fleet->isEmpty()) {
            $fleet = LogisticsVehicle::query()
                ->where('is_active', true)
                ->orderBy('capacity_tons')
                ->limit(3)
                ->get();
        }

        $warehouses = Warehouse::query()
            ->where('status', 'active')
            ->latest('id')
            ->limit(3)
            ->get();

        $shipments = LogisticsShipment::query()
            ->whereIn('status', ['booked', 'picked_up', 'in_transit', 'in_warehouse'])
            ->with('vehicle')
            ->latest('id')
            ->limit(3)
            ->get();

        $vehicleCount = LogisticsVehicle::query()->where('is_active', true)->count();
        $availableCount = LogisticsVehicle::query()->where('is_active', true)->where('status', 'available')->count();
        $warehouseCount = Warehouse::query()->where('status', 'active')->count();
        $inTransit = LogisticsShipment::query()->where('status', 'in_transit')->count();
        $openShipments = LogisticsShipment::query()
            ->whereIn('status', ['booked', 'picked_up', 'in_transit', 'in_warehouse'])
            ->count();

        return [
            'fleet' => [
                'label' => __('home.logistics.fleet.label'),
                'kicker' => __('home.logistics.fleet.kicker'),
                'title' => __('home.logistics.fleet.title'),
                'copy' => __('home.logistics.fleet.copy'),
                'cta' => __('home.logistics.fleet.cta'),
                'href' => $logisticsHref,
                'network' => __('home.logistics.fleet.network'),
                'stats' => [
                    ['label' => __('home.logistics.fleet.stats.fleet'), 'value' => $this->formatCount($vehicleCount)],
                    ['label' => __('home.logistics.fleet.stats.available'), 'value' => $this->formatCount($availableCount)],
                    ['label' => __('home.logistics.fleet.stats.routes'), 'value' => $this->formatCount($fleet->count())],
                ],
                'items' => $fleet->map(fn (LogisticsVehicle $vehicle) => [
                    'name' => $vehicle->name,
                    'route' => $vehicle->routeLabel(),
                    'meta' => __('home.logistics.fleet.item.meta', [
                        'price' => $vehicle->formattedPrice(),
                        'status' => $this->logisticsStatusLabel($vehicle->status),
                    ]),
                    'image' => $vehicle->imageUrl(),
                    'eta' => __('home.logistics.capacity_tons_short', ['count' => $vehicle->capacity_tons]),
                    'href' => $logisticsHref,
                ])->all(),
            ],
            'warehouse' => [
                'label' => __('home.logistics.warehouse.label'),
                'kicker' => __('home.logistics.warehouse.kicker'),
                'title' => __('home.logistics.warehouse.title'),
                'copy' => __('home.logistics.warehouse.copy'),
                'cta' => __('home.logistics.warehouse.cta'),
                'href' => $warehouseHref,
                'network' => __('home.logistics.warehouse.network'),
                'stats' => [
                    ['label' => __('home.logistics.warehouse.stats.sites'), 'value' => $this->formatCount($warehouseCount)],
                    ['label' => __('home.logistics.warehouse.stats.listed'), 'value' => $this->formatCount($warehouses->count())],
                    ['label' => __('home.logistics.warehouse.stats.capacity'), 'value' => __('home.logistics.capacity_tons_short', [
                        'count' => $this->formatCount((int) $warehouses->sum('capacity_tons')),
                    ])],
                ],
                'items' => $warehouses->map(function (Warehouse $warehouse) use ($warehouseHref) {
                    $free = max(0, $warehouse->capacity_tons - $warehouse->usedTons());

                    return [
                        'name' => $warehouse->name,
                        'route' => $warehouse->locationLabel(),
                        'meta' => __('home.logistics.warehouse.item.meta', [
                            'free' => number_format($free),
                            'status' => $this->logisticsStatusLabel($warehouse->status),
                        ]),
                        'image' => asset('images/logistics/truck-10t.jpg'),
                        'eta' => __('home.logistics.capacity_tons_short', ['count' => number_format($warehouse->capacity_tons)]),
                        'href' => $warehouseHref,
                    ];
                })->all(),
            ],
            'tracking' => [
                'label' => __('home.logistics.tracking.label'),
                'kicker' => __('home.logistics.tracking.kicker'),
                'title' => __('home.logistics.tracking.title'),
                'copy' => __('home.logistics.tracking.copy'),
                'cta' => __('home.logistics.tracking.cta'),
                'href' => $trackingHref,
                'network' => __('home.logistics.tracking.network'),
                'stats' => [
                    ['label' => __('home.logistics.tracking.stats.in_transit'), 'value' => $this->formatCount($inTransit)],
                    ['label' => __('home.logistics.tracking.stats.open'), 'value' => $this->formatCount($openShipments)],
                    ['label' => __('home.logistics.tracking.stats.tracked'), 'value' => $this->formatCount($shipments->count())],
                ],
                'items' => $shipments->map(function (LogisticsShipment $shipment) use ($trackingHref) {
                    $status = $this->logisticsStatusLabel($shipment->status);

                    return [
                        'name' => $shipment->referenceLabel(),
                        'route' => $shipment->routeLabel(),
                        'meta' => __('home.logistics.tracking.item.meta', [
                            'cargo' => $shipment->cargoLabel(),
                            'status' => $status,
                        ]),
                        'image' => $shipment->vehicle?->imageUrl() ?? asset('images/logistics/truck-20t.jpg'),
                        'eta' => $status,
                        'href' => $trackingHref,
                    ];
                })->all(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildResourceTracks(): array
    {
        $courses = AcademyCourse::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $beginner = $courses->where('level', 'Beginner')->values();
        $intermediate = $courses->where('level', 'Intermediate')->values();
        $featured = $courses->where('is_featured', true)->values();
        if ($featured->isEmpty()) {
            $featured = $courses->take(4)->values();
        }

        return [
            'featured' => [
                'label' => __('home.resources.featured.label'),
                'blurb' => __('home.resources.featured.blurb'),
                'courses' => $this->mapCourses($featured->take(3)),
            ],
            'beginner' => [
                'label' => __('home.resources.beginner.label'),
                'blurb' => __('home.resources.beginner.blurb'),
                'courses' => $this->mapCourses($beginner->take(3)),
            ],
            'intermediate' => [
                'label' => __('home.resources.intermediate.label'),
                'blurb' => __('home.resources.intermediate.blurb'),
                'courses' => $this->mapCourses($intermediate->take(3)),
            ],
        ];
    }

    /**
     * @param  Collection<int, AcademyCourse>  $courses
     * @return list<array<string, mixed>>
     */
    protected function mapCourses(Collection $courses): array
    {
        return $courses->map(function (AcademyCourse $course) {
            $levelKey = strtolower((string) $course->level);
            $levelPath = 'home.resources.level.'.$levelKey;
            $titlePath = 'academy.'.$course->slug.'.title';
            $summaryPath = 'academy.'.$course->slug.'.summary';

            return [
                'title' => Lang::has($titlePath) ? __($titlePath) : $course->title,
                'level' => Lang::has($levelPath) ? __($levelPath) : $course->level,
                'duration' => $course->formattedDuration(),
                'rating' => number_format((float) $course->rating, 1),
                'image' => asset($course->image_path ?: 'images/academy/maize-farming.jpg'),
                'focus' => Lang::has($summaryPath)
                    ? __($summaryPath)
                    : ($course->summary ?: __('home.resources.course_focus_fallback')),
                'href' => route('academy.learning'),
            ];
        })->all();
    }

    protected function logisticsStatusLabel(string $status): string
    {
        $key = 'home.logistics.status.'.$status;

        if (Lang::has($key)) {
            return __($key);
        }

        return ucfirst(str_replace('_', ' ', $status));
    }

    protected function roiRangeLabel(): string
    {
        $min = InvestmentOpportunity::query()->where('status', 'active')->min('roi_percent');
        $max = InvestmentOpportunity::query()->where('status', 'active')->max('roi_percent');

        if ($min === null || $max === null) {
            return __('home.common.em_dash');
        }

        $minLabel = rtrim(rtrim(number_format((float) $min, 1), '0'), '.');
        $maxLabel = rtrim(rtrim(number_format((float) $max, 1), '0'), '.');

        return $minLabel.'–'.$maxLabel.'%';
    }

    protected function formatCount(int $value): string
    {
        if ($value >= 1000000) {
            return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.').'M';
        }

        if ($value >= 10000) {
            return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.').'K';
        }

        return number_format($value);
    }
}
