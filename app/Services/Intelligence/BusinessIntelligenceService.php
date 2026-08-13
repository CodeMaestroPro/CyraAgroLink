<?php

declare(strict_types=1);

namespace App\Services\Intelligence;

use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\BiInsight;
use App\Models\BiSnapshot;
use App\Models\ConsumerOrder;
use App\Models\EquipmentOrder;
use App\Models\ExportOrder;
use App\Models\Farm;
use App\Models\InsurancePolicy;
use App\Models\LogisticsShipment;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\WalletTransaction;
use App\Models\WarehouseMovement;
use App\Models\WarehouseStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Live Business Intelligence Command Center for executive KPIs and insights.
 */
class BusinessIntelligenceService
{
    /** @var list<string> */
    public const PERIODS = ['3m', '6m', '12m', 'ytd'];

    /** @var list<string> */
    protected const COMMODITY_COLORS = ['#10853F', '#E6A817', '#2F8F4E', '#C4782B', '#6B7280'];

    /**
     * @return array<string, mixed>
     */
    public function getCommandCenterData(User $user, string $period = '6m'): array
    {
        $period = in_array($period, self::PERIODS, true) ? $period : '6m';

        $snapshot = $this->latestSnapshot($period);
        if (! $snapshot || $snapshot->calculated_at->lt(now()->subHours(6))) {
            $snapshot = $this->refresh($user, $period);
        }

        $insights = BiInsight::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'acknowledged', 'pinned'])
            ->orderByRaw("CASE status WHEN 'pinned' THEN 0 WHEN 'open' THEN 1 ELSE 2 END")
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (BiInsight $insight) => $this->presentInsight($insight))
            ->all();

        return [
            'period' => $period,
            'period_options' => [
                ['value' => '3m', 'label' => '3 months'],
                ['value' => '6m', 'label' => '6 months'],
                ['value' => '12m', 'label' => '12 months'],
                ['value' => 'ytd', 'label' => 'Year to date'],
            ],
            'kpis' => $snapshot->kpis,
            'revenue_trend' => $snapshot->revenue_trend,
            'commodities' => $snapshot->commodities,
            'insights' => $insights,
            'snapshot_at' => $snapshot->calculated_at?->format('d M Y, g:i A') ?? '',
            'actions' => [
                'refresh_url' => route('intelligence.refresh'),
                'export_url' => route('intelligence.export', ['period' => $period]),
                'filter_url' => route('intelligence.command'),
                'analytics_url' => route('reporting.analytics', ['period' => $period]),
                'insight_url' => route('intelligence.insights.store'),
            ],
            'notifications_count' => max(2, collect($insights)->where('status', 'open')->count() + 2),
        ];
    }

    /**
     * Recalculate executive KPIs, commodity mix, and sync insights.
     */
    public function refresh(User $user, string $period = '6m'): BiSnapshot
    {
        $period = in_array($period, self::PERIODS, true) ? $period : '6m';
        $computed = $this->compute($period);

        return DB::transaction(function () use ($user, $period, $computed): BiSnapshot {
            $snapshot = BiSnapshot::query()->create([
                'user_id' => $user->id,
                'period' => $period,
                'revenue_ngn' => $computed['revenue_ngn'],
                'users_count' => $computed['users_count'],
                'transactions_count' => $computed['transactions_count'],
                'farms_count' => $computed['farms_count'],
                'kpis' => $computed['kpis'],
                'revenue_trend' => $computed['revenue_trend'],
                'commodities' => $computed['commodities'],
                'meta' => $computed['meta'],
                'calculated_at' => now(),
            ]);

            $this->syncInsights($user, $snapshot, $computed['insight_rows']);

            return $snapshot;
        });
    }

    /**
     * Manually add an executive insight note.
     *
     * @param  array{title: string, detail: string, category?: string, severity?: string}  $data
     */
    public function createInsight(User $user, array $data): BiInsight
    {
        $category = $data['category'] ?? 'ops';
        if (! in_array($category, ['revenue', 'farms', 'commodities', 'risk', 'ops'], true)) {
            throw new BusinessLogicException('Invalid insight category.');
        }

        $severity = $data['severity'] ?? 'medium';
        if (! in_array($severity, ['low', 'medium', 'high'], true)) {
            $severity = 'medium';
        }

        return BiInsight::query()->create([
            'user_id' => $user->id,
            'bi_snapshot_id' => $this->latestSnapshot('6m')?->id,
            'insight_key' => 'manual-'.Str::lower(Str::random(8)),
            'category' => $category,
            'severity' => $severity,
            'title' => $data['title'],
            'detail' => $data['detail'],
            'status' => 'open',
        ]);
    }

    public function acknowledgeInsight(User $user, BiInsight $insight): BiInsight
    {
        $this->assertOwnedInsight($user, $insight);

        if ($insight->status === 'dismissed') {
            throw new BusinessLogicException('Dismissed insights cannot be acknowledged.');
        }

        $insight->forceFill([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ])->save();

        return $insight->fresh();
    }

    public function pinInsight(User $user, BiInsight $insight): BiInsight
    {
        $this->assertOwnedInsight($user, $insight);

        if ($insight->status === 'dismissed') {
            throw new BusinessLogicException('Dismissed insights cannot be pinned.');
        }

        $insight->forceFill([
            'status' => 'pinned',
            'pinned_at' => now(),
        ])->save();

        return $insight->fresh();
    }

    public function dismissInsight(User $user, BiInsight $insight): BiInsight
    {
        $this->assertOwnedInsight($user, $insight);

        $insight->forceFill([
            'status' => 'dismissed',
            'dismissed_at' => now(),
        ])->save();

        return $insight->fresh();
    }

    public function exportReport(User $user, string $period = '6m'): StreamedResponse
    {
        $data = $this->getCommandCenterData($user, $period);

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CyraAgroLink Business Intelligence Command Center', now()->toDateTimeString()]);
            fputcsv($out, ['Period', $data['period']]);
            fputcsv($out, ['Snapshot', $data['snapshot_at']]);
            fputcsv($out, []);
            fputcsv($out, ['KPI', 'Value', 'Change', 'Progress']);
            foreach ($data['kpis'] as $kpi) {
                fputcsv($out, [$kpi['label'], $kpi['value'], $kpi['change'], $kpi['progress'].'%']);
            }
            fputcsv($out, []);
            fputcsv($out, ['Month', 'Revenue Index']);
            foreach ($data['revenue_trend']['labels'] as $i => $label) {
                fputcsv($out, [$label, $data['revenue_trend']['values'][$i] ?? 0]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Commodity', 'Share %']);
            foreach ($data['commodities']['labels'] as $i => $label) {
                fputcsv($out, [$label, $data['commodities']['values'][$i] ?? 0]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Insights']);
            fputcsv($out, ['Title', 'Category', 'Severity', 'Status', 'Detail']);
            foreach ($data['insights'] as $insight) {
                fputcsv($out, [
                    $insight['title'],
                    $insight['category'],
                    $insight['severity'],
                    $insight['status'],
                    $insight['detail'],
                ]);
            }
            fclose($out);
        }, 'bi-command-center-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function compute(string $period): array
    {
        [$from, $to] = $this->periodWindow($period);
        [$prevFrom, $prevTo] = $this->previousWindow($from, $to);
        $months = $this->monthKeys($from, $to);

        $revenue = $this->totalRevenue($from, $to);
        $prevRevenue = $this->totalRevenue($prevFrom, $prevTo);
        $users = User::query()->count();
        $prevUsers = User::query()->where('created_at', '<', $from)->count();
        $farms = Farm::query()->where('status', FarmStatus::Active)->count();
        $prevFarms = Farm::query()
            ->where('status', FarmStatus::Active)
            ->where('created_at', '<', $from)
            ->count();
        $transactions = $this->transactionsCount($from, $to);
        $prevTransactions = $this->transactionsCount($prevFrom, $prevTo);

        $monthlyRevenue = [];
        foreach ($months as $month) {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            if ($end->gt($to)) {
                $end = $to->copy();
            }
            $monthlyRevenue[] = $this->totalRevenue($start, $end);
        }

        $maxMonth = max(1, ...($monthlyRevenue ?: [1]));
        $indexed = array_map(
            fn (int $value) => (int) max(0, min(100, round(($value / $maxMonth) * 98))),
            $monthlyRevenue
        );

        $revenueChange = $this->pctChange($revenue, $prevRevenue);
        $usersChange = $this->pctChange($users, max(1, $prevUsers));
        $txChange = $this->pctChange($transactions, $prevTransactions);
        $farmsChange = $this->pctChange($farms, max(1, $prevFarms));

        $commodities = $this->commodityMix();

        $kpis = [
            [
                'label' => 'Total Revenue',
                'value' => $this->compactNaira($revenue),
                'change' => $this->formatChange($revenueChange),
                'progress' => $this->progressFromChange($revenueChange, 78),
            ],
            [
                'label' => 'Total Users',
                'value' => $this->compactCount($users),
                'change' => $this->formatChange($usersChange),
                'progress' => $this->progressFromChange($usersChange, 72),
            ],
            [
                'label' => 'Transactions',
                'value' => $this->compactCount($transactions),
                'change' => $this->formatChange($txChange),
                'progress' => $this->progressFromChange($txChange, 86),
            ],
            [
                'label' => 'Active Farms',
                'value' => $this->compactCount($farms),
                'change' => $this->formatChange($farmsChange),
                'progress' => $this->progressFromChange($farmsChange, 68),
            ],
        ];

        return [
            'revenue_ngn' => $revenue,
            'users_count' => $users,
            'transactions_count' => $transactions,
            'farms_count' => $farms,
            'kpis' => $kpis,
            'revenue_trend' => [
                'labels' => array_map(
                    fn (string $m) => Carbon::createFromFormat('Y-m', $m)->format('M'),
                    $months
                ),
                'values' => $indexed,
            ],
            'commodities' => $commodities,
            'insight_rows' => $this->buildInsightRows($revenue, $revenueChange, $farms, $commodities, $txChange),
            'meta' => [
                'revenue_change' => $revenueChange,
                'users_change' => $usersChange,
                'transactions_change' => $txChange,
                'farms_change' => $farmsChange,
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString(),
            ],
        ];
    }

    protected function totalRevenue(Carbon $from, Carbon $to): int
    {
        $marketplace = (int) ConsumerOrder::query()->whereBetween('created_at', [$from, $to])->sum('total_amount')
            + (int) EquipmentOrder::query()->whereBetween('created_at', [$from, $to])->sum('amount_ngn');

        $investments = (int) UserInvestment::query()->whereBetween('created_at', [$from, $to])->sum('amount');
        $logistics = (int) LogisticsShipment::query()->whereBetween('created_at', [$from, $to])->sum('price');
        $warehouse = (int) WarehouseMovement::query()->whereBetween('created_at', [$from, $to])->sum('quantity_tons') * 2500;
        $insurance = (int) InsurancePolicy::query()->whereBetween('created_at', [$from, $to])->sum('premium_ngn');
        $exports = (int) round((float) ExportOrder::query()->whereBetween('created_at', [$from, $to])->sum('value_usd') * 1500);
        $wallet = (int) WalletTransaction::query()
            ->where('type', 'debit')
            ->whereIn('category', ['purchase', 'equipment', 'insurance', 'processing', 'coop_contribution'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return max($marketplace + $investments + $logistics + $warehouse + $insurance + $exports, $wallet);
    }

    protected function transactionsCount(Carbon $from, Carbon $to): int
    {
        return WalletTransaction::query()->whereBetween('created_at', [$from, $to])->count()
            + ConsumerOrder::query()->whereBetween('created_at', [$from, $to])->count()
            + LogisticsShipment::query()->whereBetween('created_at', [$from, $to])->count()
            + UserInvestment::query()->whereBetween('created_at', [$from, $to])->count()
            + EquipmentOrder::query()->whereBetween('created_at', [$from, $to])->count();
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    protected function commodityMix(): array
    {
        $scores = [];

        MarketplaceCommodity::query()
            ->select(['name', 'volume_tons'])
            ->orderByDesc('volume_tons')
            ->limit(40)
            ->get()
            ->each(function (MarketplaceCommodity $row) use (&$scores): void {
                $bucket = $this->bucketCommodity((string) $row->name);
                $scores[$bucket] = ($scores[$bucket] ?? 0) + max(1, (int) ($row->volume_tons ?? 1));
            });

        WarehouseStock::query()
            ->select(['commodity_name', 'quantity_tons'])
            ->orderByDesc('quantity_tons')
            ->limit(40)
            ->get()
            ->each(function (WarehouseStock $row) use (&$scores): void {
                $bucket = $this->bucketCommodity((string) $row->commodity_name);
                $scores[$bucket] = ($scores[$bucket] ?? 0) + max(1, (int) $row->quantity_tons);
            });

        Farm::query()
            ->whereNotNull('crops')
            ->latest('id')
            ->limit(50)
            ->get(['crops'])
            ->each(function (Farm $farm) use (&$scores): void {
                foreach (collect($farm->crops ?? []) as $crop) {
                    $name = is_array($crop) ? ($crop['name'] ?? null) : $crop;
                    if (! $name) {
                        continue;
                    }
                    $bucket = $this->bucketCommodity((string) $name);
                    $scores[$bucket] = ($scores[$bucket] ?? 0) + 1;
                }
            });

        if ($scores === []) {
            return [
                'labels' => ['Maize', 'Rice', 'Cassava', 'Soybean', 'Others'],
                'values' => [40, 25, 15, 10, 10],
                'colors' => self::COMMODITY_COLORS,
            ];
        }

        arsort($scores);
        $top = array_slice($scores, 0, 4, true);
        $rest = array_sum(array_slice($scores, 4, null, true));
        if ($rest > 0) {
            $top['Others'] = ($top['Others'] ?? 0) + $rest;
        }

        $labels = array_keys($top);
        $raw = array_values($top);
        $sum = max(1, array_sum($raw));
        $values = [];
        $allocated = 0;
        foreach ($raw as $i => $value) {
            if ($i === count($raw) - 1) {
                $values[] = max(0, 100 - $allocated);
                continue;
            }
            $pct = (int) round(($value / $sum) * 100);
            $values[] = $pct;
            $allocated += $pct;
        }

        $colors = [];
        foreach ($labels as $i => $label) {
            $colors[] = self::COMMODITY_COLORS[$i % count(self::COMMODITY_COLORS)];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colors,
        ];
    }

    protected function bucketCommodity(string $name): string
    {
        $name = strtolower($name);

        return match (true) {
            str_contains($name, 'maize'), str_contains($name, 'corn') => 'Maize',
            str_contains($name, 'rice') => 'Rice',
            str_contains($name, 'cassava') => 'Cassava',
            str_contains($name, 'soy') => 'Soybean',
            str_contains($name, 'poultry'), str_contains($name, 'broiler'), str_contains($name, 'egg') => 'Poultry',
            str_contains($name, 'fish'), str_contains($name, 'catfish'), str_contains($name, 'aquaculture') => 'Aquaculture',
            str_contains($name, 'goat'), str_contains($name, 'sheep'), str_contains($name, 'cattle'), str_contains($name, 'livestock') => 'Livestock',
            default => 'Others',
        };
    }

    /**
     * @param  array{labels: list<string>, values: list<int>, colors: list<string>}  $commodities
     * @return list<array{insight_key: string, category: string, severity: string, title: string, detail: string}>
     */
    protected function buildInsightRows(
        int $revenue,
        float $revenueChange,
        int $farms,
        array $commodities,
        float $txChange
    ): array {
        $rows = [];
        $topCommodity = $commodities['labels'][0] ?? 'Maize';
        $topShare = $commodities['values'][0] ?? 0;

        if ($revenueChange >= 10) {
            $rows[] = [
                'insight_key' => 'revenue-momentum-'.now()->format('Y-m'),
                'category' => 'revenue',
                'severity' => 'high',
                'title' => 'Revenue momentum is strong',
                'detail' => 'Platform revenue is up '.$this->formatChange($revenueChange).' vs the prior window ('.$this->compactNaira($revenue).').',
            ];
        } elseif ($revenueChange < 0) {
            $rows[] = [
                'insight_key' => 'revenue-soft-'.now()->format('Y-m'),
                'category' => 'revenue',
                'severity' => 'medium',
                'title' => 'Revenue soft patch detected',
                'detail' => 'Revenue changed '.$this->formatChange($revenueChange).'. Review marketplace and logistics conversion.',
            ];
        }

        $rows[] = [
            'insight_key' => 'commodity-lead-'.Str::slug($topCommodity).'-'.now()->format('Y-m'),
            'category' => 'commodities',
            'severity' => 'medium',
            'title' => $topCommodity.' leads commodity mix',
            'detail' => $topCommodity.' accounts for '.$topShare.'% of current volume across market, warehouse, and farms.',
        ];

        if ($farms < 5) {
            $rows[] = [
                'insight_key' => 'farm-growth-'.now()->format('Y-m'),
                'category' => 'farms',
                'severity' => 'medium',
                'title' => 'Active farm base is still early',
                'detail' => 'Only '.$farms.' active farms are registered. Push onboarding in high-potential states.',
            ];
        } else {
            $rows[] = [
                'insight_key' => 'farm-coverage-'.now()->format('Y-m'),
                'category' => 'farms',
                'severity' => 'low',
                'title' => 'Farm network is expanding',
                'detail' => $farms.' active farms are feeding the command-center enterprise mix.',
            ];
        }

        if ($txChange >= 15) {
            $rows[] = [
                'insight_key' => 'tx-surge-'.now()->format('Y-m'),
                'category' => 'ops',
                'severity' => 'high',
                'title' => 'Transaction surge',
                'detail' => 'Transactions rose '.$this->formatChange($txChange).'. Ensure wallet and logistics capacity keep up.',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{insight_key: string, category: string, severity: string, title: string, detail: string}>  $rows
     */
    protected function syncInsights(User $user, BiSnapshot $snapshot, array $rows): void
    {
        $activeKeys = [];

        foreach ($rows as $row) {
            $key = $row['insight_key'];
            $activeKeys[] = $key;

            $dismissed = BiInsight::query()
                ->where('user_id', $user->id)
                ->where('insight_key', $key)
                ->where('status', 'dismissed')
                ->where('dismissed_at', '>=', now()->subDays(7))
                ->exists();

            if ($dismissed) {
                continue;
            }

            $existing = BiInsight::query()
                ->where('user_id', $user->id)
                ->where('insight_key', $key)
                ->whereIn('status', ['open', 'acknowledged', 'pinned'])
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'bi_snapshot_id' => $snapshot->id,
                    'category' => $row['category'],
                    'severity' => $row['severity'],
                    'title' => $row['title'],
                    'detail' => $row['detail'],
                ])->save();

                continue;
            }

            BiInsight::query()->create([
                'user_id' => $user->id,
                'bi_snapshot_id' => $snapshot->id,
                'insight_key' => $key,
                'category' => $row['category'],
                'severity' => $row['severity'],
                'title' => $row['title'],
                'detail' => $row['detail'],
                'status' => 'open',
            ]);
        }

        // Keep pinned/manual insights; only prune stale auto keys that are open/acked.
        BiInsight::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'acknowledged'])
            ->where('insight_key', 'not like', 'manual-%')
            ->whereNotIn('insight_key', $activeKeys)
            ->update([
                'status' => 'dismissed',
                'dismissed_at' => now(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentInsight(BiInsight $insight): array
    {
        return [
            'id' => $insight->id,
            'title' => $insight->title,
            'detail' => $insight->detail,
            'category' => ucfirst($insight->category),
            'severity' => $insight->severity,
            'status' => $insight->status,
            'can_acknowledge' => $insight->status === 'open',
            'can_pin' => in_array($insight->status, ['open', 'acknowledged'], true),
            'can_dismiss' => in_array($insight->status, ['open', 'acknowledged', 'pinned'], true),
            'acknowledge_url' => route('intelligence.insights.acknowledge', $insight),
            'pin_url' => route('intelligence.insights.pin', $insight),
            'dismiss_url' => route('intelligence.insights.dismiss', $insight),
        ];
    }

    protected function assertOwnedInsight(User $user, BiInsight $insight): void
    {
        if ($insight->user_id !== $user->id) {
            throw new BusinessLogicException('This insight belongs to another account.', statusCode: 403);
        }
    }

    protected function latestSnapshot(string $period): ?BiSnapshot
    {
        return BiSnapshot::query()
            ->where('period', $period)
            ->latest('calculated_at')
            ->latest('id')
            ->first();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function periodWindow(string $period): array
    {
        $to = now();
        $from = match ($period) {
            '3m' => now()->subMonths(2)->startOfMonth(),
            '12m' => now()->subMonths(11)->startOfMonth(),
            'ytd' => now()->startOfYear(),
            default => now()->subMonths(5)->startOfMonth(),
        };

        return [$from, $to];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function previousWindow(Carbon $from, Carbon $to): array
    {
        $days = max(1, $from->diffInDays($to));

        return [$from->copy()->subDays($days + 1), $from->copy()->subSecond()];
    }

    /**
     * @return list<string>
     */
    protected function monthKeys(Carbon $from, Carbon $to): array
    {
        $cursor = $from->copy()->startOfMonth();
        $end = $to->copy()->startOfMonth();
        $keys = [];

        while ($cursor->lte($end)) {
            $keys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $keys !== [] ? $keys : [$to->format('Y-m')];
    }

    protected function pctChange(int|float $current, int|float $previous): float
    {
        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function formatChange(float $change): string
    {
        $prefix = $change >= 0 ? '+' : '';

        return $prefix.number_format($change, 1).'%';
    }

    protected function progressFromChange(float $change, int $baseline): int
    {
        return max(20, min(96, (int) round($baseline + ($change / 2))));
    }

    protected function compactNaira(int $amount): string
    {
        if ($amount >= 1_000_000_000) {
            return '₦'.$this->trimNumber($amount / 1_000_000_000).'B';
        }

        if ($amount >= 1_000_000) {
            return '₦'.$this->trimNumber($amount / 1_000_000).'M';
        }

        if ($amount >= 1_000) {
            return '₦'.$this->trimNumber($amount / 1_000).'K';
        }

        return '₦'.number_format($amount);
    }

    protected function compactCount(int $count): string
    {
        if ($count >= 1_000_000) {
            return $this->trimNumber($count / 1_000_000).'M';
        }

        if ($count >= 1_000) {
            return $this->trimNumber($count / 1_000).'K';
        }

        return number_format($count);
    }

    protected function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
