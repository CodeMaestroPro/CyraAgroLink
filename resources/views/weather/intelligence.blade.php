<x-dashboard-layout
    title="Weather Intelligence"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Weather Intelligence'],
    ]"
>
    <x-page-header
        title="Weather Intelligence"
        description="Forecasts, alerts, and satellite insights for your farm regions."
    >
        <x-slot:actions>
            <form method="POST" action="{{ $actions['refresh_url'] }}">
                @csrf
                <input type="hidden" name="location" value="{{ $location['key'] }}">
                <button type="submit" class="inline-flex items-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-mint/40">
                    Refresh
                </button>
            </form>
            <a
                href="{{ $actions['export_url'] }}"
                class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
            >
                Export CSV
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

    <x-section-tabs
        active="overview"
        :items="[
            ['id' => 'overview', 'label' => 'Overview', 'href' => '#overview'],
            ['id' => 'forecast', 'label' => 'Forecast', 'href' => '#forecast'],
            ['id' => 'satellite', 'label' => 'Satellite', 'href' => '#satellite'],
            ['id' => 'historical', 'label' => 'Historical Data', 'href' => '#historical'],
        ]"
    />

    <div id="overview" class="flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ $actions['switch_url'] }}" class="inline-flex items-center gap-2">
            <svg class="h-4 w-4 text-cyra-forest" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11z"/>
                <circle cx="12" cy="10" r="2.5"/>
            </svg>
            <label class="sr-only" for="weather-location">Location</label>
            <select
                id="weather-location"
                name="location"
                onchange="this.form.submit()"
                class="rounded-xl border border-cyra-line bg-white px-3 py-2 text-base font-extrabold text-cyra-ink focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20 sm:text-lg"
            >
                @foreach ($locationOptions as $option)
                    <option value="{{ $option['key'] }}" @selected($location['key'] === $option['key'])>
                        {{ $option['label'] }}
                    </option>
                @endforeach
            </select>
        </form>
        <p class="text-sm text-cyra-muted">
            Update: {{ $location['updated_at'] }}
            · Source: {{ $source === 'open_meteo' ? 'Open-Meteo' : 'Climate model' }}
        </p>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-5">
        {{-- Current weather --}}
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line xl:col-span-2">
            <h2 class="text-base font-extrabold text-cyra-ink">{{ $current['date_label'] }}</h2>
            <div class="mt-5 flex items-center gap-4">
                <x-weather.condition-icon :icon="$current['icon']" size="lg" />
                <div>
                    <p class="text-4xl font-extrabold tracking-tight text-cyra-ink">{{ $current['temperature'] }}</p>
                    <p class="mt-1 text-sm font-semibold text-cyra-muted">{{ $current['condition'] }}</p>
                </div>
            </div>
            <div class="mt-6 grid grid-cols-3 gap-3 border-t border-cyra-line pt-4">
                @foreach ($current['metrics'] as $metric)
                    <div class="text-center">
                        <span class="mx-auto inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
                            @if ($metric['icon'] === 'humidity')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3s5 6 5 10a5 5 0 1 1-10 0c0-4 5-10 5-10z"/></svg>
                            @elseif ($metric['icon'] === 'rainfall')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14a4 4 0 0 1 7.7-1.5A3.2 3.2 0 0 1 17 18H8.3A3.2 3.2 0 0 1 8 14zM10 19v2M14 19v2"/></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 12h10M14 8l4 4-4 4M6 8c2-2 4-2 6 0"/></svg>
                            @endif
                        </span>
                        <p class="mt-2 text-xs font-medium text-cyra-muted">{{ $metric['label'] }}</p>
                        <p class="mt-0.5 text-sm font-bold text-cyra-ink">{{ $metric['value'] }}</p>
                    </div>
                @endforeach
            </div>
        </article>

        {{-- 5-day forecast --}}
        <article id="forecast" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line xl:col-span-3">
            <h2 class="text-base font-extrabold text-cyra-ink">5-Day Forecast</h2>
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($forecast as $day)
                    <div class="rounded-xl bg-cyra-surface/70 px-2 py-3 text-center ring-1 ring-cyra-line/70">
                        <p class="text-[11px] font-semibold text-cyra-muted sm:text-xs">{{ $day['day'] }}</p>
                        <div class="mt-2 flex justify-center">
                            <x-weather.condition-icon :icon="$day['icon']" size="sm" />
                        </div>
                        <p class="mt-2 text-sm font-extrabold text-cyra-ink">{{ $day['temp'] }}</p>
                        <p class="mt-1 text-xs font-semibold text-sky-600">{{ $day['rain'] }}</p>
                    </div>
                @endforeach
            </div>
        </article>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-5">
        {{-- Rainfall map --}}
        <article id="satellite" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line xl:col-span-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-extrabold text-cyra-ink">Rainfall Map</h2>
                <span class="text-xs font-medium text-cyra-muted">Nigeria intensity overlay</span>
            </div>
            <div class="mt-4 overflow-hidden rounded-2xl bg-cyra-surface ring-1 ring-cyra-line/80">
                <div
                    id="weatherRainfallMap"
                    class="h-64 w-full sm:h-72"
                    data-zones='@json($rainfallZones)'
                    role="img"
                    aria-label="Nigeria rainfall intensity map"
                ></div>
            </div>
            <div class="mt-4">
                <div class="h-2.5 rounded-full bg-gradient-to-r from-sky-100 via-sky-400 to-blue-700"></div>
                <div class="mt-1.5 flex items-center justify-between text-xs font-medium text-cyra-muted">
                    <span>0 mm</span>
                    <span>60 mm+</span>
                </div>
            </div>
        </article>

        {{-- Alerts + AI --}}
        <div class="flex flex-col gap-5 xl:col-span-2">
            <article id="alerts" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
                <h2 class="text-base font-extrabold text-cyra-ink">Weather Alerts</h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($alerts as $alert)
                        <li class="rounded-xl bg-cyra-surface/80 px-3 py-3 ring-1 ring-cyra-line/70">
                            <div class="flex gap-3">
                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                                    @if (($alert['icon'] ?? '') === 'heat')
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v10m0 0a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM9 6h6"/></svg>
                                    @else
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 4.3 2.8 18a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
                                    @endif
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-cyra-ink">{{ $alert['title'] }}</p>
                                    <p class="mt-0.5 text-xs text-cyra-muted">{{ $alert['detail'] }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if ($alert['can_acknowledge'])
                                            <form method="POST" action="{{ $alert['acknowledge_url'] }}">
                                                @csrf
                                                <input type="hidden" name="location" value="{{ $location['key'] }}">
                                                <button type="submit" class="rounded-lg bg-cyra-forest px-2.5 py-1 text-[11px] font-bold text-white hover:bg-cyra-green">Acknowledge</button>
                                            </form>
                                        @endif
                                        @if ($alert['can_dismiss'])
                                            <form method="POST" action="{{ $alert['dismiss_url'] }}">
                                                @csrf
                                                <input type="hidden" name="location" value="{{ $location['key'] }}">
                                                <button type="submit" class="rounded-lg border border-cyra-line bg-white px-2.5 py-1 text-[11px] font-bold text-cyra-muted hover:text-cyra-ink">Dismiss</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-cyra-muted">No active weather alerts.</li>
                    @endforelse
                </ul>
            </article>

            <article class="rounded-2xl bg-cyra-mint/70 p-5 ring-1 ring-cyra-soft/70">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyra-forest text-white">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
                        </svg>
                    </span>
                    <h2 class="text-sm font-extrabold text-cyra-forest">AI Recommendation</h2>
                </div>
                <p class="mt-3 text-sm leading-relaxed text-cyra-ink">
                    {{ $aiRecommendation['message'] }}
                </p>
                <a
                    href="#forecast"
                    class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
                >
                    {{ $aiRecommendation['action_label'] }}
                </a>
            </article>
        </div>
    </div>

    <section id="historical" class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6" aria-labelledby="historical-heading">
        <h2 id="historical-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">Historical Data</h2>
        <p class="mt-1 text-sm text-cyra-muted">Recent snapshots for {{ $location['label'] }}.</p>
        <ul class="mt-4 divide-y divide-cyra-line/80">
            @forelse ($history as $row)
                <li class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-0 last:pb-0">
                    <div>
                        <p class="font-semibold text-cyra-ink">{{ $row['when'] }}</p>
                        <p class="text-xs text-cyra-muted">{{ $row['condition'] }} · {{ $row['source'] === 'open_meteo' ? 'Open-Meteo' : 'Climate model' }}</p>
                    </div>
                    <div class="text-right text-sm">
                        <p class="font-extrabold text-cyra-ink">{{ $row['temp'] }}</p>
                        <p class="text-xs font-semibold text-sky-600">{{ $row['rain'] }}</p>
                    </div>
                </li>
            @empty
                <li class="text-sm text-cyra-muted">No historical snapshots yet. Refresh to capture the first reading.</li>
            @endforelse
        </ul>
    </section>
</x-dashboard-layout>
