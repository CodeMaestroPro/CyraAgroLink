<?php

declare(strict_types=1);

namespace App\Services\Market;

use App\Exceptions\BusinessLogicException;
use App\Models\MarketWatchlist;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Live market intelligence: prices, trends, watchlists, and reports.
 */
class MarketIntelligenceService
{
    private const TABS = ['overview', 'commodities', 'trends', 'demand', 'trade', 'alerts'];

    private const RANGES = ['1W', '1M', '3M', '6M', '1Y'];

    public function __construct(
        protected MarketplaceService $marketplaceService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getOverviewData(
        User $user,
        string $tab = 'overview',
        ?int $commodityId = null,
        string $range = '6M'
    ): array {
        $this->marketplaceService->getCatalog();

        $tab = in_array($tab, self::TABS, true) ? $tab : 'overview';
        $range = strtoupper($range);
        if (! in_array($range, self::RANGES, true)) {
            $range = '6M';
        }

        $commodities = MarketplaceCommodity::query()
            ->where('status', 'active')
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();

        $selected = $this->resolveCommodity($commodities, $commodityId);
        $watchedIds = MarketWatchlist::query()
            ->where('user_id', $user->id)
            ->pluck('commodity_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $preferred = ['Maize', 'Rice', 'Cocoa', 'Yam'];
        $priceSource = collect($preferred)
            ->map(fn (string $name) => $commodities->firstWhere('name', $name))
            ->filter()
            ->values();

        if ($priceSource->count() < 4) {
            $priceSource = $priceSource
                ->concat($commodities->reject(fn (MarketplaceCommodity $c) => $priceSource->contains('id', $c->id)))
                ->take(4)
                ->values();
        }

        $prices = $priceSource
            ->take(4)
            ->map(fn (MarketplaceCommodity $c) => $this->priceCard($c))
            ->values()
            ->all();

        $commodityRows = $commodities->map(function (MarketplaceCommodity $c) use ($watchedIds) {
            $change = $c->changePercent();

            return [
                'id' => $c->id,
                'name' => $c->name,
                'price' => $c->formattedPrice(),
                'price_raw' => $c->price_per_ton,
                'change' => ($change >= 0 ? '+' : '').number_format($change, 1).'%',
                'tone' => $change < 0 ? 'down' : 'up',
                'volume' => number_format((int) ($c->volume_tons ?? 0)).' Tons',
                'location' => $c->locationLabel() ?: '—',
                'watched' => in_array((int) $c->id, $watchedIds, true),
                'exchange_url' => route('exchange.show', ['commodity' => $c->id]),
            ];
        })->values();

        return [
            'tab' => $tab,
            'range' => $range,
            'prices' => $prices,
            'commodities' => $commodityRows,
            'selected_commodity' => [
                'id' => $selected->id,
                'name' => $selected->name,
            ],
            'commodity_options' => $commodities->map(fn (MarketplaceCommodity $c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values()->all(),
            'price_trend' => [
                'ranges' => self::RANGES,
                'active_range' => $range,
                'series' => $this->buildPriceTrendSeries($selected),
            ],
            'demand_forecast' => $this->buildDemandForecast($selected),
            'export_destinations' => $this->exportDestinations($selected),
            'alerts' => $this->buildAlerts($commodities, $watchedIds),
            'watched_count' => count($watchedIds),
            'notifications_count' => max(2, count($watchedIds) + 1),
        ];
    }

    /**
     * Add a commodity to the user's watchlist.
     */
    public function watch(User $user, MarketplaceCommodity $commodity): MarketWatchlist
    {
        if ($commodity->status !== 'active') {
            throw new BusinessLogicException('This commodity is not available to watch.');
        }

        return MarketWatchlist::query()->firstOrCreate([
            'user_id' => $user->id,
            'commodity_id' => $commodity->id,
        ]);
    }

    /**
     * Remove a commodity from the user's watchlist.
     */
    public function unwatch(User $user, MarketplaceCommodity $commodity): void
    {
        MarketWatchlist::query()
            ->where('user_id', $user->id)
            ->where('commodity_id', $commodity->id)
            ->delete();
    }

    /**
     * Export a market intelligence CSV snapshot.
     */
    public function exportReport(User $user, ?int $commodityId = null): StreamedResponse
    {
        $data = $this->getOverviewData($user, 'overview', $commodityId);

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CyraAgroLink Market Intelligence Report', now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['Commodity', 'Price / Ton', 'Change', 'Volume', 'Location', 'Watched']);

            foreach ($data['commodities'] as $row) {
                fputcsv($out, [
                    $row['name'],
                    $row['price'],
                    $row['change'],
                    $row['volume'],
                    $row['location'],
                    $row['watched'] ? 'Yes' : 'No',
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Market Alerts']);
            foreach ($data['alerts'] as $alert) {
                fputcsv($out, [$alert['message']]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Top Export Destinations ('.$data['selected_commodity']['name'].')']);
            foreach ($data['export_destinations'] as $destination) {
                fputcsv($out, [
                    $destination['rank'],
                    $destination['country'],
                    $destination['volume'],
                ]);
            }

            fclose($out);
        }, 'cyra-market-intelligence-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  Collection<int, MarketplaceCommodity>  $commodities
     */
    protected function resolveCommodity(Collection $commodities, ?int $commodityId): MarketplaceCommodity
    {
        if ($commodityId !== null) {
            $match = $commodities->firstWhere('id', $commodityId);
            if ($match) {
                return $match;
            }
        }

        return $commodities->firstWhere('name', 'Maize')
            ?? $commodities->first()
            ?? MarketplaceCommodity::query()->where('status', 'active')->firstOrFail();
    }

    /**
     * @return array{label: string, value: string, change: string, tone: string, commodity_id: int}
     */
    protected function priceCard(MarketplaceCommodity $commodity): array
    {
        $change = $commodity->changePercent();

        return [
            'label' => $commodity->name.' Price / Ton',
            'value' => '₦'.number_format($commodity->price_per_ton),
            'change' => ($change >= 0 ? '+' : '').number_format($change, 1).'%',
            'tone' => $change < 0 ? 'down' : 'up',
            'commodity_id' => $commodity->id,
        ];
    }

    /**
     * @return array<string, array{labels: list<string>, values: list<int>}>
     */
    protected function buildPriceTrendSeries(MarketplaceCommodity $commodity): array
    {
        $base = (int) $commodity->price_per_ton;
        $previous = (int) ($commodity->previous_price_per_ton ?: (int) round($base * 0.96));

        return [
            '1W' => $this->seriesFromBase($base, $previous, 7, 'day'),
            '1M' => $this->seriesFromBase($base, $previous, 4, 'week'),
            '3M' => $this->seriesFromBase($base, $previous, 3, 'month'),
            '6M' => $this->seriesFromBase($base, $previous, 6, 'month'),
            '1Y' => $this->seriesFromBase($base, $previous, 7, 'bi_month'),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    protected function seriesFromBase(int $base, int $previous, int $points, string $mode): array
    {
        $labels = [];
        $values = [];
        $start = (int) max(1000, round($previous * 0.92));

        for ($i = 0; $i < $points; $i++) {
            $progress = $points === 1 ? 1 : $i / ($points - 1);
            $wave = sin($i / 1.7) * ($base * 0.02);
            $price = (int) max(1000, round($start + (($base - $start) * $progress) + $wave));
            $values[] = $price;
            $labels[] = match ($mode) {
                'day' => now()->subDays($points - $i - 1)->format('D'),
                'week' => 'W'.($i + 1),
                'bi_month' => now()->subMonths(($points - $i - 1) * 2)->format('M'),
                default => now()->subMonths($points - $i - 1)->format('M'),
            };
        }

        $values[$points - 1] = $base;

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    protected function buildDemandForecast(MarketplaceCommodity $commodity): array
    {
        $volume = max(1000, (int) ($commodity->volume_tons ?? 5000));
        $year = (int) now()->format('Y');
        $labels = [];
        $values = [];

        for ($i = 0; $i < 5; $i++) {
            $labels[] = (string) ($year - 1 + $i);
            $values[] = (int) round($volume * (3.5 + ($i * 0.45)));
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return list<array{rank: int, country: string, volume: string}>
     */
    protected function exportDestinations(MarketplaceCommodity $commodity): array
    {
        $volume = max(500, (int) ($commodity->volume_tons ?? 2000));

        $destinations = [
            ['country' => 'China', 'share' => 0.38],
            ['country' => 'Netherlands', 'share' => 0.28],
            ['country' => 'Spain', 'share' => 0.18],
            ['country' => 'India', 'share' => 0.10],
            ['country' => 'USA', 'share' => 0.06],
        ];

        return collect($destinations)
            ->take(3)
            ->values()
            ->map(fn (array $row, int $index) => [
                'rank' => $index + 1,
                'country' => $row['country'],
                'volume' => number_format((int) round($volume * 22 * $row['share'])).' Tons',
            ])
            ->all();
    }

    /**
     * @param  Collection<int, MarketplaceCommodity>  $commodities
     * @param  list<int>  $watchedIds
     * @return list<array{message: string, tone: string, commodity_id: int|null}>
     */
    protected function buildAlerts(Collection $commodities, array $watchedIds): array
    {
        $alerts = [];
        $month = now()->addMonth()->format('M Y');
        $quarter = 'Q'.(int) ceil(now()->month / 3).' '.now()->format('Y');

        foreach ($commodities->sortByDesc(fn (MarketplaceCommodity $c) => abs($c->changePercent()))->take(4) as $commodity) {
            $change = $commodity->changePercent();

            if (abs($change) < 1) {
                continue;
            }

            if ($change >= 3) {
                $alerts[] = [
                    'message' => "High demand expected for {$commodity->name} in {$month}",
                    'tone' => 'warning',
                    'commodity_id' => $commodity->id,
                ];
            } elseif ($change > 0) {
                $alerts[] = [
                    'message' => "{$commodity->name} prices likely to rise in {$quarter}",
                    'tone' => 'info',
                    'commodity_id' => $commodity->id,
                ];
            } else {
                $alerts[] = [
                    'message' => "{$commodity->name} softening ({$change}%) — review sell timing",
                    'tone' => 'warning',
                    'commodity_id' => $commodity->id,
                ];
            }
        }

        foreach ($commodities->whereIn('id', $watchedIds) as $watched) {
            $alerts[] = [
                'message' => "Watchlist: {$watched->name} trading at ₦".number_format($watched->price_per_ton).'/Ton',
                'tone' => 'info',
                'commodity_id' => $watched->id,
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'message' => 'Markets are stable. Add commodities to your watchlist for tailored alerts.',
                'tone' => 'info',
                'commodity_id' => null,
            ];
        }

        return array_slice($alerts, 0, 6);
    }
}
