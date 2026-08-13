<x-dashboard-layout
    title="Reporting & Analytics"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Business Overview'],
    ]"
>
    <x-page-header
        title="Business Overview"
        description="Key performance indicators · Updated {{ $snapshotAt }}"
    >
        <x-slot:actions>
            <form method="GET" action="{{ $actions['filter_url'] }}" class="inline-flex">
                <label class="sr-only" for="report-period">Period</label>
                <select
                    id="report-period"
                    name="period"
                    onchange="this.form.submit()"
                    class="rounded-xl border border-cyra-line bg-white px-3 py-2 text-sm font-semibold text-cyra-ink focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
                    @foreach ($periodOptions as $option)
                        <option value="{{ $option['value'] }}" @selected($period === $option['value'])>{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </form>
            <form method="POST" action="{{ $actions['refresh_url'] }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button type="submit" class="inline-flex items-center justify-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2.5 text-sm font-bold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-mint/40">
                    Refresh
                </button>
            </form>
            <a
                href="{{ $actions['export_url'] }}"
                class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
            >
                Export Report
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/50 px-4 py-3 text-sm text-cyra-forest ring-1 ring-cyra-line" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <x-section-tabs
        active="overview"
        :items="[
            ['id' => 'overview', 'label' => 'Overview', 'href' => '#overview'],
            ['id' => 'financial', 'label' => 'Financial Reports', 'href' => '#financial'],
            ['id' => 'operations', 'label' => 'Operations', 'href' => '#operations'],
            ['id' => 'custom', 'label' => 'Custom Reports', 'href' => '#custom'],
            ['id' => 'export', 'label' => 'Data Export', 'href' => '#export'],
        ]"
    />

    <section id="overview" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Key metrics">
        @foreach ($kpis as $kpi)
            <x-reporting.kpi-card
                :label="$kpi['label']"
                :value="$kpi['value']"
                :change="$kpi['change']"
                :tone="$kpi['tone']"
            />
        @endforeach
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Growth charts">
        <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
            <h2 class="text-base font-extrabold text-cyra-ink">Revenue Trend</h2>
            <div class="mt-4 h-56 sm:h-64">
                <canvas
                    id="reportingRevenueTrendChart"
                    data-labels='@json($revenueTrend['labels'])'
                    data-values='@json($revenueTrend['values'])'
                    aria-label="Revenue trend chart"
                    role="img"
                ></canvas>
            </div>
        </article>

        <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
            <h2 class="text-base font-extrabold text-cyra-ink">Transactions</h2>
            <div class="mt-4 h-56 sm:h-64">
                <canvas
                    id="reportingTransactionsChart"
                    data-labels='@json($transactions['labels'])'
                    data-values='@json($transactions['values'])'
                    aria-label="Monthly transactions chart"
                    role="img"
                ></canvas>
            </div>
        </article>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Segments and regions">
        <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
            <h2 class="text-base font-extrabold text-cyra-ink">Revenue by Segment</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                <div class="mx-auto h-56 w-full max-w-[16rem] sm:mx-0 sm:h-64">
                    <canvas
                        id="reportingSegmentsChart"
                        data-labels='@json($segments['labels'])'
                        data-values='@json($segments['values'])'
                        data-colors='@json($segments['colors'])'
                        aria-label="Revenue by segment doughnut chart"
                        role="img"
                    ></canvas>
                </div>
                <ul class="space-y-2.5">
                    @foreach ($segments['labels'] as $index => $label)
                        <li class="flex items-center gap-2.5 text-sm">
                            <span
                                class="h-2.5 w-2.5 shrink-0 rounded-full"
                                style="background-color: {{ $segments['colors'][$index] }}"
                                aria-hidden="true"
                            ></span>
                            <span class="min-w-0 flex-1 font-medium text-cyra-ink">{{ $label }}</span>
                            <span class="font-bold tabular-nums text-cyra-ink">{{ $segments['values'][$index] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </article>

        <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
            <h2 class="text-base font-extrabold text-cyra-ink">Top Performing Regions</h2>
            <div class="mt-4 overflow-hidden rounded-2xl bg-cyra-surface ring-1 ring-cyra-line/80">
                <div
                    id="reportingRegionsMap"
                    class="h-64 w-full sm:h-72"
                    data-regions='@json($regions)'
                    role="img"
                    aria-label="Top performing regions map"
                ></div>
            </div>
            <p class="mt-3 text-xs text-cyra-muted">
                Darker markers indicate stronger regional performance.
            </p>
        </article>
    </section>

    <section id="financial" class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6" aria-labelledby="financial-heading">
        <h2 id="financial-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">Financial Reports</h2>
        <p class="mt-1 text-sm text-cyra-muted">Segment revenue for the selected period.</p>
        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                'Marketplace' => $financial['marketplace'],
                'Investments' => $financial['investments'],
                'Logistics' => $financial['logistics'],
                'Warehouse' => $financial['warehouse'],
                'Others' => $financial['others'],
            ] as $label => $value)
                <article class="rounded-xl bg-cyra-surface/70 px-3 py-3 ring-1 ring-cyra-line/70">
                    <p class="text-xs font-medium text-cyra-muted">{{ $label }}</p>
                    <p class="mt-1 text-lg font-extrabold tabular-nums text-cyra-forest">{{ $value }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section id="operations" class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6" aria-labelledby="operations-heading">
        <h2 id="operations-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">Operations</h2>
        <p class="mt-1 text-sm text-cyra-muted">Live operational throughput across the platform.</p>
        <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($operations as $item)
                <li class="flex items-center justify-between rounded-xl bg-cyra-surface/70 px-3 py-3 ring-1 ring-cyra-line/70">
                    <span class="text-sm font-medium text-cyra-ink">{{ $item['label'] }}</span>
                    <span class="text-sm font-extrabold tabular-nums text-cyra-forest">{{ $item['value'] }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section id="custom" class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-labelledby="custom-heading">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 id="custom-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">Custom Reports</h2>
            <p class="mt-1 text-sm text-cyra-muted">Build a tailored CSV from live analytics.</p>
            <form method="POST" action="{{ $actions['custom_url'] }}" class="mt-4 space-y-3">
                @csrf
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Title</span>
                    <input type="text" name="title" required maxlength="160" value="Monthly executive pack" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                </label>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Type</span>
                        <select name="report_type" required class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            <option value="financial">Financial</option>
                            <option value="operations">Operations</option>
                            <option value="segment">Segment</option>
                            <option value="regional">Regional</option>
                            <option value="custom">Custom</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Period</span>
                        <select name="period" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            @foreach ($periodOptions as $option)
                                <option value="{{ $option['value'] }}" @selected($period === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Segment (optional)</span>
                    <select name="segment" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        <option value="">All segments</option>
                        @foreach ($segments['labels'] as $label)
                            <option value="{{ $label }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Notes</span>
                    <textarea name="notes" maxlength="500" rows="2" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20" placeholder="Optional brief for stakeholders"></textarea>
                </label>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green">
                    Generate report
                </button>
            </form>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Your custom reports</h2>
            <ul class="mt-4 divide-y divide-cyra-line/80">
                @forelse ($customReports as $report)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-cyra-ink">{{ $report['title'] }}</p>
                            <p class="text-xs text-cyra-muted">{{ $report['type'] }} · {{ $report['period'] }} · {{ $report['segment'] }} · {{ $report['created'] }}</p>
                        </div>
                        @if ($report['ready'])
                            <a href="{{ $report['download_url'] }}" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">Download</a>
                        @else
                            <span class="text-xs font-bold text-cyra-muted">{{ ucfirst($report['status']) }}</span>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No custom reports yet. Generate one to get started.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section id="export" class="mt-6 rounded-2xl bg-cyra-mint/50 p-5 ring-1 ring-cyra-line sm:p-6" aria-labelledby="export-heading">
        <h2 id="export-heading" class="text-base font-extrabold text-cyra-forest sm:text-lg">Data Export</h2>
        <p class="mt-1 text-sm text-cyra-ink/80">Download the full KPI, trend, segment, and regional pack for the current period.</p>
        <a
            href="{{ $actions['export_url'] }}"
            class="mt-4 inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
        >
            Export Report
        </a>
    </section>
</x-dashboard-layout>
