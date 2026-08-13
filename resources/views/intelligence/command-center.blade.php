<x-dashboard-layout
    title="Business Intelligence Command Center"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Intelligence'],
        ['label' => 'Command Center'],
    ]"
>
    <x-page-header
        title="Business Intelligence Command Center"
        description="Executive KPIs, revenue momentum, and commodity performance · Updated {{ $snapshotAt }}"
    >
        <x-slot:actions>
            <form method="GET" action="{{ $actions['filter_url'] }}" class="inline-flex">
                <label class="sr-only" for="bi-period">Period</label>
                <select
                    id="bi-period"
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
                Export
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

    <div id="summary">
        <x-section-heading title="Executive Summary" description="Platform-wide growth signals at a glance." />
    </div>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Executive summary">
        @foreach ($kpis as $kpi)
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <p class="text-sm font-medium text-cyra-muted">{{ $kpi['label'] }}</p>
                <p class="mt-2 font-display text-2xl font-bold tracking-tight tabular-nums text-cyra-ink sm:text-[1.7rem]">
                    {{ $kpi['value'] }}
                </p>
                <p class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-cyra-forest">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 3a.75.75 0 01.53.22l5 5a.75.75 0 11-1.06 1.06l-3.72-3.72v10.69a.75.75 0 01-1.5 0V5.56L5.53 9.28A.75.75 0 014.47 8.22l5-5A.75.75 0 0110 3z" clip-rule="evenodd"/>
                    </svg>
                    {{ $kpi['change'] }}
                </p>
                <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-cyra-line/80">
                    <div class="h-full rounded-full bg-cyra-forest" style="width: {{ $kpi['progress'] }}%"></div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-5" aria-label="Revenue and commodities">
        <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5 xl:col-span-3">
            <x-section-heading title="Revenue Trend" tone="cream" class="mb-3 !px-0 !py-0 !ring-0" />
            <div class="mt-2 h-64 sm:h-72">
                <canvas
                    id="biRevenueTrendChart"
                    data-labels='@json($revenueTrend['labels'])'
                    data-values='@json($revenueTrend['values'])'
                    aria-label="Revenue trend index chart"
                    role="img"
                ></canvas>
            </div>
        </article>

        <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5 xl:col-span-2">
            <x-section-heading title="Top Performing Commodities" tone="cream" class="mb-3 !px-0 !py-0 !ring-0" />
            <div class="mt-2 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                <div class="mx-auto h-52 w-full max-w-[14rem] sm:mx-0 sm:h-56">
                    <canvas
                        id="biCommoditiesChart"
                        data-labels='@json($commodities['labels'])'
                        data-values='@json($commodities['values'])'
                        data-colors='@json($commodities['colors'])'
                        aria-label="Top performing commodities doughnut chart"
                        role="img"
                    ></canvas>
                </div>
                <ul class="space-y-2.5">
                    @foreach ($commodities['labels'] as $index => $label)
                        <li class="flex items-center gap-2.5 text-sm">
                            <span
                                class="h-2.5 w-2.5 shrink-0 rounded-full"
                                style="background-color: {{ $commodities['colors'][$index] }}"
                                aria-hidden="true"
                            ></span>
                            <span class="min-w-0 flex-1 font-medium text-cyra-ink">{{ $label }}</span>
                            <span class="font-bold tabular-nums text-cyra-ink">{{ $commodities['values'][$index] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </article>
    </section>

    <section id="insights" class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Executive insights">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Executive Insights</h2>
            <p class="mt-1 text-sm text-cyra-muted">Auto signals plus notes you pin for the leadership pack.</p>
            <ul class="mt-4 space-y-3">
                @forelse ($insights as $insight)
                    <li class="rounded-xl bg-cyra-surface/70 px-3 py-3 ring-1 ring-cyra-line/70">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-cyra-ink">{{ $insight['title'] }}</p>
                                <p class="mt-0.5 text-xs text-cyra-muted">{{ $insight['category'] }} · {{ ucfirst($insight['severity']) }} · {{ ucfirst($insight['status']) }}</p>
                                <p class="mt-2 text-sm text-cyra-ink/80">{{ $insight['detail'] }}</p>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @if ($insight['can_acknowledge'])
                                    <form method="POST" action="{{ $insight['acknowledge_url'] }}">
                                        @csrf
                                        <input type="hidden" name="period" value="{{ $period }}">
                                        <button type="submit" class="rounded-lg bg-cyra-forest px-2.5 py-1 text-[11px] font-bold text-white hover:bg-cyra-green">Ack</button>
                                    </form>
                                @endif
                                @if ($insight['can_pin'])
                                    <form method="POST" action="{{ $insight['pin_url'] }}">
                                        @csrf
                                        <input type="hidden" name="period" value="{{ $period }}">
                                        <button type="submit" class="rounded-lg border border-cyra-forest/30 bg-white px-2.5 py-1 text-[11px] font-bold text-cyra-forest hover:bg-cyra-mint/40">Pin</button>
                                    </form>
                                @endif
                                @if ($insight['can_dismiss'])
                                    <form method="POST" action="{{ $insight['dismiss_url'] }}">
                                        @csrf
                                        <input type="hidden" name="period" value="{{ $period }}">
                                        <button type="submit" class="rounded-lg border border-cyra-line bg-white px-2.5 py-1 text-[11px] font-bold text-cyra-muted hover:text-cyra-ink">Dismiss</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No open insights. Refresh to generate signals.</li>
                @endforelse
            </ul>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Add insight</h2>
            <p class="mt-1 text-sm text-cyra-muted">Capture a board note tied to this command center.</p>
            <form method="POST" action="{{ $actions['insight_url'] }}" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Title</span>
                    <input type="text" name="title" required maxlength="160" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20" placeholder="Expand maize corridor capacity">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Detail</span>
                    <textarea name="detail" required maxlength="500" rows="3" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20" placeholder="What should leadership act on?"></textarea>
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Category</span>
                        <select name="category" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            <option value="ops">Ops</option>
                            <option value="revenue">Revenue</option>
                            <option value="farms">Farms</option>
                            <option value="commodities">Commodities</option>
                            <option value="risk">Risk</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Severity</span>
                        <select name="severity" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="low">Low</option>
                        </select>
                    </label>
                </div>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green">
                    Save insight
                </button>
            </form>
        </article>
    </section>

    <div class="mt-8 flex justify-center">
        <a
            href="{{ $actions['analytics_url'] }}"
            class="inline-flex items-center justify-center rounded-xl border-2 border-cyra-forest bg-white px-6 py-2.5 text-sm font-bold text-cyra-forest shadow-sm transition hover:bg-cyra-mint"
        >
            View Full Analytics
        </a>
    </div>
</x-dashboard-layout>
