<x-dashboard-layout
    title="Market Intelligence"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Market Overview'],
    ]"
>
    <x-page-header
        title="Market Overview"
        description="Live commodity prices, forecasts, and watchlist alerts from the marketplace."
    >
        <x-slot:actions>
            <a
                href="{{ route('market.export', array_filter(['commodity' => $selected_commodity['id'] ?? null])) }}"
                class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
            >
                View Full Report
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint px-4 py-3 text-sm font-medium text-cyra-forest ring-1 ring-cyra-soft/60" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <form method="GET" action="{{ route('market.intelligence') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="hidden" name="range" value="{{ $range }}">
        <div>
            <label for="commodity" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Focus commodity</label>
            <select
                id="commodity"
                name="commodity"
                onchange="this.form.submit()"
                class="mt-1 min-w-[12rem] rounded-xl border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20"
            >
                @foreach ($commodity_options as $option)
                    <option value="{{ $option['id'] }}" @selected((int) $option['id'] === (int) $selected_commodity['id'])>
                        {{ $option['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <p class="text-sm text-cyra-muted">
            {{ $watched_count }} on watchlist
            ·
            <a href="{{ route('exchange.show', ['commodity' => $selected_commodity['id']]) }}" class="font-bold text-cyra-forest hover:underline">
                Trade {{ $selected_commodity['name'] }}
            </a>
        </p>
    </form>

    <x-section-tabs
        :active="$tab"
        :items="[
            ['id' => 'overview', 'label' => 'Overview', 'href' => route('market.intelligence', ['tab' => 'overview', 'commodity' => $selected_commodity['id'], 'range' => $range])],
            ['id' => 'commodities', 'label' => 'Commodities', 'href' => route('market.intelligence', ['tab' => 'commodities', 'commodity' => $selected_commodity['id'], 'range' => $range])],
            ['id' => 'trends', 'label' => 'Price Trends', 'href' => route('market.intelligence', ['tab' => 'trends', 'commodity' => $selected_commodity['id'], 'range' => $range])],
            ['id' => 'demand', 'label' => 'Demand Forecast', 'href' => route('market.intelligence', ['tab' => 'demand', 'commodity' => $selected_commodity['id'], 'range' => $range])],
            ['id' => 'trade', 'label' => 'Import / Export', 'href' => route('market.intelligence', ['tab' => 'trade', 'commodity' => $selected_commodity['id'], 'range' => $range])],
            ['id' => 'alerts', 'label' => 'Alerts', 'href' => route('market.intelligence', ['tab' => 'alerts', 'commodity' => $selected_commodity['id'], 'range' => $range])],
        ]"
    />

    @if (in_array($tab, ['overview', 'commodities'], true))
        <section id="overview" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Commodity prices">
            @foreach ($prices as $price)
                <a href="{{ route('market.intelligence', ['tab' => 'trends', 'commodity' => $price['commodity_id'], 'range' => $range]) }}" class="block transition hover:-translate-y-0.5">
                    <x-market.price-card
                        :label="$price['label']"
                        :value="$price['value']"
                        :change="$price['change']"
                        :tone="$price['tone']"
                    />
                </a>
            @endforeach
        </section>
    @endif

    @if ($tab === 'commodities')
        <section id="commodities" class="mt-6" aria-label="All commodities">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-extrabold text-cyra-ink">Commodities</h2>
                    <p class="mt-1 text-sm text-cyra-muted">Watch markets and open the exchange desk.</p>
                </div>
            </div>

            <div class="mt-4 space-y-3">
                @foreach ($commodities as $row)
                    <article class="rounded-2xl bg-white p-4 ring-1 ring-cyra-line sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-extrabold text-cyra-ink">{{ $row['name'] }}</h3>
                                <p class="mt-1 text-sm text-cyra-muted">
                                    {{ $row['price'] }}
                                    ·
                                    <span @class(['font-bold', 'text-cyra-forest' => $row['tone'] === 'up', 'text-rose-600' => $row['tone'] === 'down'])>
                                        {{ $row['change'] }}
                                    </span>
                                </p>
                                <p class="mt-1 text-xs text-cyra-muted">
                                    Vol {{ $row['volume'] }} · {{ $row['location'] }}
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="{{ route('market.intelligence', ['tab' => 'trends', 'commodity' => $row['id'], 'range' => $range]) }}"
                                    class="rounded-lg border border-cyra-line px-3 py-2 text-xs font-bold text-cyra-ink hover:bg-cyra-surface"
                                >
                                    Trends
                                </a>
                                <a
                                    href="{{ $row['exchange_url'] }}"
                                    class="rounded-lg border border-cyra-forest px-3 py-2 text-xs font-bold text-cyra-forest hover:bg-cyra-mint"
                                >
                                    Trade
                                </a>
                                @if ($row['watched'])
                                    <form method="POST" action="{{ route('market.unwatch', $row['id']) }}">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="tab" value="commodities">
                                        <input type="hidden" name="commodity" value="{{ $selected_commodity['id'] }}">
                                        <button type="submit" class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">
                                            Unwatch
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('market.watch', $row['id']) }}">
                                        @csrf
                                        <input type="hidden" name="tab" value="commodities">
                                        <input type="hidden" name="commodity" value="{{ $selected_commodity['id'] }}">
                                        <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white hover:bg-cyra-green">
                                            Watch
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if (in_array($tab, ['overview', 'trends', 'demand'], true))
        <section id="price-trends" class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Trends and forecasts">
            @if (in_array($tab, ['overview', 'trends'], true))
                <article class="rounded-2xl bg-cyra-surface/50 p-4 ring-1 ring-cyra-line/80 sm:p-5"
                    x-data="{
                        range: @js($priceTrend['active_range']),
                        series: @js($priceTrend['series']),
                        commodity: @js($selected_commodity['name']),
                        init() {
                            this.$watch('range', () => window.cyraUpdateMaizePriceTrend?.(this.range, this.series, this.commodity));
                            this.$nextTick(() => window.cyraUpdateMaizePriceTrend?.(this.range, this.series, this.commodity));
                        }
                    }"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-base font-extrabold text-cyra-ink">Price Trend ({{ $selected_commodity['name'] }})</h2>
                        <div class="flex flex-wrap gap-1" role="group" aria-label="Price trend range">
                            @foreach ($priceTrend['ranges'] as $option)
                                <a
                                    href="{{ route('market.intelligence', ['tab' => $tab === 'overview' ? 'overview' : 'trends', 'commodity' => $selected_commodity['id'], 'range' => $option]) }}"
                                    @click.prevent="range = '{{ $option }}'"
                                    :class="range === '{{ $option }}'
                                        ? 'bg-cyra-forest text-white'
                                        : 'bg-white text-cyra-muted hover:text-cyra-ink'"
                                    class="rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-cyra-line transition"
                                >
                                    {{ $option }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-4 h-56 sm:h-64">
                        <canvas
                            id="maizePriceTrendChart"
                            data-commodity="{{ $selected_commodity['name'] }}"
                            aria-label="{{ $selected_commodity['name'] }} price trend chart"
                            role="img"
                        ></canvas>
                    </div>
                </article>
            @endif

            @if (in_array($tab, ['overview', 'demand'], true))
                <article id="demand" class="rounded-2xl bg-cyra-surface/50 p-4 ring-1 ring-cyra-line/80 sm:p-5">
                    <h2 class="text-base font-extrabold text-cyra-ink">Demand Forecast ({{ $selected_commodity['name'] }})</h2>
                    <div class="mt-4 h-56 sm:h-64">
                        <canvas
                            id="maizeDemandForecastChart"
                            data-labels='@json($demandForecast['labels'])'
                            data-values='@json($demandForecast['values'])'
                            data-commodity="{{ $selected_commodity['name'] }}"
                            aria-label="{{ $selected_commodity['name'] }} demand forecast chart"
                            role="img"
                        ></canvas>
                    </div>
                </article>
            @endif
        </section>
    @endif

    @if (in_array($tab, ['overview', 'trade', 'alerts'], true))
        <section id="trade" class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Exports and alerts">
            @if (in_array($tab, ['overview', 'trade'], true))
                <article class="rounded-2xl bg-cyra-surface/50 p-4 ring-1 ring-cyra-line/80 sm:p-5">
                    <h2 class="text-base font-extrabold text-cyra-ink">
                        Top Export Destinations
                        <span class="font-semibold text-cyra-muted">({{ $selected_commodity['name'] }})</span>
                    </h2>
                    <ol class="mt-4 space-y-3">
                        @foreach ($exportDestinations as $destination)
                            <li class="flex items-center gap-3 rounded-xl bg-white px-3 py-3 ring-1 ring-cyra-line/70">
                                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyra-mint text-sm font-bold text-cyra-forest">
                                    {{ $destination['rank'] }}
                                </span>
                                <span class="min-w-0 flex-1 text-sm font-semibold text-cyra-ink">
                                    {{ $destination['country'] }}
                                </span>
                                <span class="shrink-0 text-sm font-bold tabular-nums text-cyra-ink">
                                    {{ $destination['volume'] }}
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </article>
            @endif

            @if (in_array($tab, ['overview', 'alerts'], true))
                <article class="rounded-2xl bg-cyra-surface/50 p-4 ring-1 ring-cyra-line/80 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-base font-extrabold text-cyra-ink">Market Alerts</h2>
                        <a href="{{ route('market.intelligence', ['tab' => 'commodities', 'commodity' => $selected_commodity['id']]) }}" class="text-xs font-bold text-cyra-forest hover:underline">
                            Manage watchlist
                        </a>
                    </div>
                    <ul class="mt-4 space-y-3">
                        @foreach ($alerts as $alert)
                            <li
                                @class([
                                    'rounded-xl px-4 py-3 text-sm font-semibold ring-1',
                                    'bg-amber-50 text-amber-800 ring-amber-100' => ($alert['tone'] ?? 'warning') === 'warning',
                                    'bg-orange-50 text-orange-800 ring-orange-100' => ($alert['tone'] ?? '') === 'info',
                                ])
                            >
                                {{ $alert['message'] }}
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endif
        </section>
    @endif
</x-dashboard-layout>
