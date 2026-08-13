<?php

declare(strict_types=1);

namespace App\Services\Reporting;

use App\Exceptions\BusinessLogicException;
use App\Models\AnalyticsSnapshot;
use App\Models\ConsumerOrder;
use App\Models\CustomReportRequest;
use App\Models\EquipmentOrder;
use App\Models\ExportOrder;
use App\Models\Farm;
use App\Models\InsurancePolicy;
use App\Models\LogisticsShipment;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Live reporting and analytics from platform operational data.
 */
class ReportingAnalyticsService
{
    /** @var list<string> */
    public const PERIODS = ['3m', '6m', '12m', 'ytd'];

    /** @var list<string> */
    public const REPORT_TYPES = ['financial', 'operations', 'segment', 'regional', 'custom'];

    /** @var list<string> */
    public const SEGMENTS = ['Marketplace', 'Investments', 'Logistics', 'Warehouse', 'Others'];

    /**
     * @return array<string, mixed>
     */
    public function getOverviewData(User $user, string $period = '6m'): array
    {
        $period = in_array($period, self::PERIODS, true) ? $period : '6m';

        $snapshot = $this->latestSnapshot($period);
        if (! $snapshot || $snapshot->calculated_at->lt(now()->subHours(6))) {
            $snapshot = $this->refresh($user, $period);
        }

        $customReports = CustomReportRequest::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (CustomReportRequest $report) => $this->presentCustomReport($report))
            ->all();

        $operations = $snapshot->operations ?? [];
        $meta = $snapshot->meta ?? [];

        return [
            'period' => $period,
            'period_options' => [
                ['value' => '3m', 'label' => '3 months'],
                ['value' => '6m', 'label' => '6 months'],
                ['value' => '12m', 'label' => '12 months'],
                ['value' => 'ytd', 'label' => 'Year to date'],
            ],
            'kpis' => [
                [
                    'label' => 'Total Revenue',
                    'value' => '₦'.number_format($snapshot->revenue_ngn),
                    'change' => $this->formatChange((float) ($meta['revenue_change'] ?? 0)),
                    'tone' => ((float) ($meta['revenue_change'] ?? 0)) >= 0 ? 'up' : 'down',
                ],
                [
                    'label' => 'Total Users',
                    'value' => number_format($snapshot->users_count),
                    'change' => $this->formatChange((float) ($meta['users_change'] ?? 0)),
                    'tone' => ((float) ($meta['users_change'] ?? 0)) >= 0 ? 'up' : 'down',
                ],
                [
                    'label' => 'Total Transactions',
                    'value' => number_format($snapshot->transactions_count),
                    'change' => $this->formatChange((float) ($meta['transactions_change'] ?? 0)),
                    'tone' => ((float) ($meta['transactions_change'] ?? 0)) >= 0 ? 'up' : 'down',
                ],
                [
                    'label' => 'Total Farms',
                    'value' => number_format($snapshot->farms_count),
                    'change' => $this->formatChange((float) ($meta['farms_change'] ?? 0)),
                    'tone' => ((float) ($meta['farms_change'] ?? 0)) >= 0 ? 'up' : 'down',
                ],
            ],
            'revenue_trend' => $snapshot->revenue_trend,
            'transactions' => $snapshot->transactions_trend,
            'segments' => $snapshot->segments,
            'regions' => $snapshot->regions,
            'operations' => $operations,
            'financial' => [
                'marketplace' => '₦'.number_format((int) ($meta['segment_totals']['Marketplace'] ?? 0)),
                'investments' => '₦'.number_format((int) ($meta['segment_totals']['Investments'] ?? 0)),
                'logistics' => '₦'.number_format((int) ($meta['segment_totals']['Logistics'] ?? 0)),
                'warehouse' => '₦'.number_format((int) ($meta['segment_totals']['Warehouse'] ?? 0)),
                'others' => '₦'.number_format((int) ($meta['segment_totals']['Others'] ?? 0)),
            ],
            'custom_reports' => $customReports,
            'snapshot_at' => $snapshot->calculated_at?->format('d M Y, g:i A') ?? '',
            'actions' => [
                'refresh_url' => route('reporting.refresh'),
                'export_url' => route('reporting.export', ['period' => $period]),
                'custom_url' => route('reporting.custom.store'),
                'filter_url' => route('reporting.analytics'),
            ],
            'notifications_count' => max(2, count(array_filter($customReports, fn ($r) => $r['status'] === 'queued')) + 1),
        ];
    }

    /**
     * Recalculate and persist analytics for the selected period.
     */
    public function refresh(User $user, string $period = '6m'): AnalyticsSnapshot
    {
        $period = in_array($period, self::PERIODS, true) ? $period : '6m';
        $computed = $this->compute($period);

        return AnalyticsSnapshot::query()->create([
            'user_id' => $user->id,
            'period' => $period,
            'revenue_ngn' => $computed['revenue_ngn'],
            'users_count' => $computed['users_count'],
            'transactions_count' => $computed['transactions_count'],
            'farms_count' => $computed['farms_count'],
            'revenue_trend' => $computed['revenue_trend'],
            'transactions_trend' => $computed['transactions_trend'],
            'segments' => $computed['segments'],
            'regions' => $computed['regions'],
            'operations' => $computed['operations'],
            'meta' => $computed['meta'],
            'calculated_at' => now(),
        ]);
    }

    /**
     * Queue a custom report and mark it ready immediately (local generation).
     *
     * @param  array{title: string, report_type: string, period?: string, segment?: string|null, notes?: string|null}  $data
     */
    public function createCustomReport(User $user, array $data): CustomReportRequest
    {
        $type = $data['report_type'];
        if (! in_array($type, self::REPORT_TYPES, true)) {
            throw new BusinessLogicException('Invalid report type.');
        }

        $period = $data['period'] ?? '6m';
        if (! in_array($period, self::PERIODS, true)) {
            $period = '6m';
        }

        $segment = $data['segment'] ?? null;
        if ($segment && ! in_array($segment, self::SEGMENTS, true)) {
            throw new BusinessLogicException('Invalid segment filter.');
        }

        $report = CustomReportRequest::query()->create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'report_type' => $type,
            'period' => $period,
            'segment' => $segment,
            'notes' => $data['notes'] ?? null,
            'status' => 'ready',
            'file_name' => 'custom-'.strtolower($type).'-'.now()->format('YmdHis').'.csv',
            'ready_at' => now(),
        ]);

        return $report;
    }

    public function downloadCustomReport(User $user, CustomReportRequest $report): StreamedResponse
    {
        if ($report->user_id !== $user->id) {
            throw new BusinessLogicException('This custom report belongs to another account.', statusCode: 403);
        }

        if (! $report->isReady()) {
            throw new BusinessLogicException('This report is not ready yet.');
        }

        $overview = $this->getOverviewData($user, $report->period);

        $report->forceFill([
            'status' => 'downloaded',
            'downloaded_at' => now(),
        ])->save();

        $fileName = $report->file_name ?: 'custom-report.csv';

        return response()->streamDownload(function () use ($report, $overview): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CyraAgroLink Custom Report', $report->title]);
            fputcsv($out, ['Type', $report->report_type]);
            fputcsv($out, ['Period', $report->period]);
            fputcsv($out, ['Segment', $report->segment ?? 'All']);
            fputcsv($out, ['Generated', now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['KPI', 'Value', 'Change']);
            foreach ($overview['kpis'] as $kpi) {
                fputcsv($out, [$kpi['label'], $kpi['value'], $kpi['change']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Segment', 'Share %', 'Revenue']);
            foreach ($overview['segments']['labels'] as $i => $label) {
                if ($report->segment && $report->segment !== $label) {
                    continue;
                }
                $revenueKey = match ($label) {
                    'Marketplace' => 'marketplace',
                    'Investments' => 'investments',
                    'Logistics' => 'logistics',
                    'Warehouse' => 'warehouse',
                    default => 'others',
                };
                fputcsv($out, [
                    $label,
                    $overview['segments']['values'][$i] ?? 0,
                    $overview['financial'][$revenueKey] ?? '₦0',
                ]);
            }
            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function exportReport(User $user, string $period = '6m'): StreamedResponse
    {
        $data = $this->getOverviewData($user, $period);

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CyraAgroLink Reporting & Analytics', now()->toDateTimeString()]);
            fputcsv($out, ['Period', $data['period']]);
            fputcsv($out, ['Snapshot', $data['snapshot_at']]);
            fputcsv($out, []);
            fputcsv($out, ['KPI', 'Value', 'Change']);
            foreach ($data['kpis'] as $kpi) {
                fputcsv($out, [$kpi['label'], $kpi['value'], $kpi['change']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Month', 'Revenue']);
            foreach ($data['revenue_trend']['labels'] as $i => $label) {
                fputcsv($out, [$label, $data['revenue_trend']['values'][$i] ?? 0]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Month', 'Transactions']);
            foreach ($data['transactions']['labels'] as $i => $label) {
                fputcsv($out, [$label, $data['transactions']['values'][$i] ?? 0]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Segment', 'Share %']);
            foreach ($data['segments']['labels'] as $i => $label) {
                fputcsv($out, [$label, $data['segments']['values'][$i] ?? 0]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Region', 'Score']);
            foreach ($data['regions'] as $region) {
                fputcsv($out, [$region['name'], $region['score']]);
            }
            fclose($out);
        }, 'reporting-analytics-'.now()->format('Ymd-His').'.csv', [
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

        $segmentTotals = [
            'Marketplace' => $this->marketplaceRevenue($from, $to),
            'Investments' => $this->investmentsRevenue($from, $to),
            'Logistics' => $this->logisticsRevenue($from, $to),
            'Warehouse' => $this->warehouseRevenue($from, $to),
            'Others' => $this->othersRevenue($from, $to),
        ];

        $revenue = array_sum($segmentTotals);
        $users = User::query()->count();
        $farms = Farm::query()->count();
        $transactions = $this->transactionsCount($from, $to);

        $prevRevenue = $this->marketplaceRevenue($prevFrom, $prevTo)
            + $this->investmentsRevenue($prevFrom, $prevTo)
            + $this->logisticsRevenue($prevFrom, $prevTo)
            + $this->warehouseRevenue($prevFrom, $prevTo)
            + $this->othersRevenue($prevFrom, $prevTo);
        $prevUsers = User::query()->where('created_at', '<', $from)->count();
        $prevFarms = Farm::query()->where('created_at', '<', $from)->count();
        $prevTransactions = $this->transactionsCount($prevFrom, $prevTo);

        $revenueTrendValues = [];
        $txTrendValues = [];
        foreach ($months as $month) {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            if ($end->gt($to)) {
                $end = $to->copy();
            }
            $revenueTrendValues[] = $this->marketplaceRevenue($start, $end)
                + $this->investmentsRevenue($start, $end)
                + $this->logisticsRevenue($start, $end)
                + $this->warehouseRevenue($start, $end)
                + $this->othersRevenue($start, $end);
            $txTrendValues[] = $this->transactionsCount($start, $end);
        }

        $segmentShares = $this->toPercentShares($segmentTotals);

        return [
            'revenue_ngn' => $revenue,
            'users_count' => $users,
            'transactions_count' => $transactions,
            'farms_count' => $farms,
            'revenue_trend' => [
                'labels' => array_map(
                    fn (string $m) => Carbon::createFromFormat('Y-m', $m)->format('M'),
                    $months
                ),
                'values' => $revenueTrendValues,
            ],
            'transactions_trend' => [
                'labels' => array_map(
                    fn (string $m) => Carbon::createFromFormat('Y-m', $m)->format('M'),
                    $months
                ),
                'values' => $txTrendValues,
            ],
            'segments' => [
                'labels' => self::SEGMENTS,
                'values' => array_map(
                    fn (string $label) => $segmentShares[$label] ?? 0,
                    self::SEGMENTS
                ),
                'colors' => ['#1E3A8A', '#EA580C', '#10853F', '#0EA5E9', '#E6A817'],
            ],
            'regions' => $this->regionsFromFarms(),
            'operations' => [
                ['label' => 'Active warehouses', 'value' => (string) Warehouse::query()->count()],
                ['label' => 'Warehouse movements', 'value' => (string) WarehouseMovement::query()->whereBetween('created_at', [$from, $to])->count()],
                ['label' => 'Logistics shipments', 'value' => (string) LogisticsShipment::query()->whereBetween('created_at', [$from, $to])->count()],
                ['label' => 'Equipment orders', 'value' => (string) EquipmentOrder::query()->whereBetween('created_at', [$from, $to])->count()],
                ['label' => 'Export orders', 'value' => (string) ExportOrder::query()->whereBetween('created_at', [$from, $to])->count()],
                ['label' => 'Insurance policies', 'value' => (string) InsurancePolicy::query()->whereBetween('created_at', [$from, $to])->count()],
            ],
            'meta' => [
                'revenue_change' => $this->pctChange($revenue, $prevRevenue),
                'users_change' => $this->pctChange($users, max(1, $prevUsers)),
                'transactions_change' => $this->pctChange($transactions, $prevTransactions),
                'farms_change' => $this->pctChange($farms, max(1, $prevFarms)),
                'segment_totals' => $segmentTotals,
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString(),
            ],
        ];
    }

    protected function marketplaceRevenue(Carbon $from, Carbon $to): int
    {
        $orders = (int) ConsumerOrder::query()
            ->whereBetween('created_at', [$from, $to])
            ->sum('total_amount');

        $equipment = (int) EquipmentOrder::query()
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount_ngn');

        $walletPurchases = (int) WalletTransaction::query()
            ->where('type', 'debit')
            ->whereIn('category', ['purchase', 'equipment'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return max($orders + $equipment, $walletPurchases);
    }

    protected function investmentsRevenue(Carbon $from, Carbon $to): int
    {
        return (int) UserInvestment::query()
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');
    }

    protected function logisticsRevenue(Carbon $from, Carbon $to): int
    {
        return (int) LogisticsShipment::query()
            ->whereBetween('created_at', [$from, $to])
            ->sum('price');
    }

    protected function warehouseRevenue(Carbon $from, Carbon $to): int
    {
        // Proxy: movements volume * 2500 NGN handling fee estimate.
        $movements = (int) WarehouseMovement::query()
            ->whereBetween('created_at', [$from, $to])
            ->sum('quantity_tons');

        return $movements * 2500;
    }

    protected function othersRevenue(Carbon $from, Carbon $to): int
    {
        $insurance = (int) InsurancePolicy::query()
            ->whereBetween('created_at', [$from, $to])
            ->sum('premium_ngn');

        $exportsUsd = (float) ExportOrder::query()
            ->whereBetween('created_at', [$from, $to])
            ->sum('value_usd');

        $walletOther = (int) WalletTransaction::query()
            ->where('type', 'debit')
            ->whereIn('category', ['insurance', 'processing', 'futures_margin', 'auction_hold', 'coop_contribution'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return $insurance + (int) round($exportsUsd * 1500) + $walletOther;
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
     * @param  array<string, int>  $totals
     * @return array<string, int>
     */
    protected function toPercentShares(array $totals): array
    {
        $sum = array_sum($totals);
        if ($sum <= 0) {
            return [
                'Marketplace' => 45,
                'Investments' => 25,
                'Logistics' => 15,
                'Warehouse' => 10,
                'Others' => 5,
            ];
        }

        $shares = [];
        $allocated = 0;
        $labels = array_keys($totals);
        $last = array_key_last($labels);

        foreach ($labels as $i => $label) {
            if ($i === $last) {
                $shares[$label] = max(0, 100 - $allocated);
                continue;
            }
            $pct = (int) round(($totals[$label] / $sum) * 100);
            $shares[$label] = $pct;
            $allocated += $pct;
        }

        return $shares;
    }

    /**
     * @return list<array{name: string, lat: float, lng: float, score: int}>
     */
    protected function regionsFromFarms(): array
    {
        $stateScores = Farm::query()
            ->selectRaw('state, COUNT(*) as total')
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->groupBy('state')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'state');

        if ($stateScores->isEmpty()) {
            return [
                ['name' => 'Nigeria', 'lat' => 9.0820, 'lng' => 8.6753, 'score' => 70],
                ['name' => 'Ghana', 'lat' => 7.9465, 'lng' => -1.0232, 'score' => 55],
                ['name' => 'Kenya', 'lat' => -1.2921, 'lng' => 36.8219, 'score' => 60],
                ['name' => 'Ethiopia', 'lat' => 9.1450, 'lng' => 40.4897, 'score' => 52],
                ['name' => 'South Africa', 'lat' => -30.5595, 'lng' => 22.9375, 'score' => 58],
                ['name' => 'Egypt', 'lat' => 26.8206, 'lng' => 30.8025, 'score' => 50],
                ['name' => 'Morocco', 'lat' => 31.7917, 'lng' => -7.0926, 'score' => 48],
                ['name' => 'Tanzania', 'lat' => -6.3690, 'lng' => 34.8888, 'score' => 51],
            ];
        }

        $max = max(1, (int) $stateScores->max());

        return $stateScores->map(function ($total, $state) use ($max) {
            $score = (int) max(40, min(98, round(($total / $max) * 95)));

            return [
                'name' => (string) $state,
                'lat' => $this->stateLat((string) $state),
                'lng' => $this->stateLng((string) $state),
                'score' => $score,
            ];
        })->values()->all();
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

    protected function latestSnapshot(string $period): ?AnalyticsSnapshot
    {
        return AnalyticsSnapshot::query()
            ->where('period', $period)
            ->latest('calculated_at')
            ->latest('id')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentCustomReport(CustomReportRequest $report): array
    {
        return [
            'id' => $report->id,
            'title' => $report->title,
            'type' => ucfirst($report->report_type),
            'period' => strtoupper($report->period),
            'segment' => $report->segment ?? 'All',
            'status' => $report->status,
            'ready' => $report->isReady(),
            'created' => $report->created_at?->format('d M, Y') ?? '',
            'download_url' => route('reporting.custom.download', $report),
        ];
    }

    protected function stateLat(string $state): float
    {
        $state = strtolower($state);

        return match (true) {
            str_contains($state, 'lagos') => 6.5244,
            str_contains($state, 'kano') => 12.0022,
            str_contains($state, 'rivers') => 4.8156,
            str_contains($state, 'kaduna') => 10.5105,
            str_contains($state, 'oyo'), str_contains($state, 'ibadan') => 7.3775,
            str_contains($state, 'fct'), str_contains($state, 'abuja') => 9.0765,
            str_contains($state, 'edo') => 6.3350,
            str_contains($state, 'borno') => 11.8333,
            default => 9.0820,
        };
    }

    protected function stateLng(string $state): float
    {
        $state = strtolower($state);

        return match (true) {
            str_contains($state, 'lagos') => 3.3792,
            str_contains($state, 'kano') => 8.5920,
            str_contains($state, 'rivers') => 7.0498,
            str_contains($state, 'kaduna') => 7.4165,
            str_contains($state, 'oyo'), str_contains($state, 'ibadan') => 3.9470,
            str_contains($state, 'fct'), str_contains($state, 'abuja') => 7.3986,
            str_contains($state, 'edo') => 5.6037,
            str_contains($state, 'borno') => 13.1500,
            default => 8.6753,
        };
    }
}
