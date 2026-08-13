<x-dashboard-layout
    title="AI Digital Twin Farm"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Farm Operations'],
        ['label' => 'Digital Twin'],
    ]"
>
    <x-page-header
        title="AI Digital Twin Farm"
        description="Live twin of your registered farm — plots, crop health, moisture, and actionable alerts."
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if (count($farms) > 1)
                    <label class="sr-only" for="twin-farm">Select farm</label>
                    <select
                        id="twin-farm"
                        class="rounded-xl border-cyra-line bg-white text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                        onchange="window.location.href=this.value"
                    >
                        @foreach ($farms as $option)
                            <option value="{{ $option['url'] }}" @selected($option['active'])>
                                {{ $option['name'] }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <form method="POST" action="{{ $actions['scan_url'] }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green">
                        Run twin scan
                    </button>
                </form>
                <form method="POST" action="{{ $actions['irrigate_url'] }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-xl border border-cyra-line bg-white px-4 py-2 text-sm font-semibold text-cyra-ink transition hover:bg-cyra-surface">
                        Simulate irrigation
                    </button>
                </form>
            </div>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/50 px-4 py-3 text-sm text-cyra-forest ring-1 ring-cyra-line" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (! empty($alerts))
        <div class="mb-4 space-y-2" aria-label="Twin alerts">
            @foreach ($alerts as $alert)
                <div class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-950 ring-1 ring-amber-200/80">
                    {{ $alert }}
                </div>
            @endforeach
        </div>
    @endif

    <div class="relative overflow-hidden rounded-2xl bg-[#0B1A12] shadow-soft ring-1 ring-white/10">
        <div
            id="digitalTwinFarmMap"
            class="absolute inset-0 z-0 min-h-[36rem] w-full lg:min-h-full"
            data-center='@json($map)'
            data-plots='@json($plots)'
            role="img"
            aria-label="{{ $farm['name'] }} digital twin map"
        ></div>

        <div class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-b from-black/45 via-transparent to-black/70"></div>

        <div class="relative z-20 flex min-h-[36rem] flex-col p-4 sm:p-6 lg:min-h-[44rem] lg:p-8">
            <div id="overview" class="pointer-events-none">
                <p class="text-sm font-medium text-white/80">{{ $farm['overview_label'] }}</p>
                <h2 class="mt-1 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                    {{ $farm['name'] }}
                </h2>
                @if (! empty($farm['location']))
                    <p class="mt-1 text-sm text-white/70">{{ $farm['location'] }}</p>
                @endif
                <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-cyra-forest/90 px-3 py-1 text-xs font-bold text-white shadow-sm ring-1 ring-white/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-cyra-soft" aria-hidden="true"></span>
                    {{ $farm['status'] }}
                </span>
                @if ($lastScanAt)
                    <p class="mt-2 text-xs text-white/60">Last scan: {{ \Illuminate\Support\Carbon::parse($lastScanAt)->diffForHumans() }}</p>
                @endif
            </div>

            <div class="mt-auto space-y-4 pt-48 sm:pt-56">
                <section
                    id="layers"
                    class="pointer-events-auto grid grid-cols-2 gap-px overflow-hidden rounded-2xl bg-white/10 shadow-soft ring-1 ring-white/15 backdrop-blur-md sm:grid-cols-4"
                    aria-label="Primary farm metrics"
                >
                    @foreach ($kpis as $kpi)
                        <div class="bg-black/45 px-4 py-4 text-center backdrop-blur-md sm:px-5 sm:py-5">
                            <p class="text-xs font-medium text-white/70 sm:text-sm">{{ $kpi['label'] }}</p>
                            <p class="mt-1.5 text-xl font-extrabold tabular-nums text-white sm:text-2xl">
                                {{ $kpi['value'] }}
                            </p>
                        </div>
                    @endforeach
                </section>

                <section
                    id="crops"
                    class="pointer-events-auto grid grid-cols-2 gap-3 xl:grid-cols-4"
                    aria-label="Farm status widgets"
                >
                    @foreach ($widgets as $widget)
                        @php
                            $iconTone = match ($widget['tone']) {
                                'blue' => 'text-sky-400',
                                'lime' => 'text-lime-300',
                                'amber' => 'text-amber-300',
                                default => 'text-cyra-soft',
                            };
                            $widgetId = match ($widget['icon']) {
                                'soil', 'water', 'pest' => $widget['icon'],
                                default => null,
                            };
                        @endphp
                        <article
                            @if ($widgetId) id="{{ $widgetId }}" @endif
                            class="rounded-2xl bg-black/50 px-4 py-4 shadow-soft ring-1 ring-white/12 backdrop-blur-md sm:px-5"
                        >
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 {{ $iconTone }}">
                                    @if ($widget['icon'] === 'soil')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c2 3 3 5 3 7a3 3 0 11-6 0c0-2 1-4 3-7zM5 20h14M7 20c1-3 3-5 5-5s4 2 5 5"/></svg>
                                    @elseif ($widget['icon'] === 'water')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c3 4 6 7.2 6 10.5A6 6 0 116 13.5C6 10.2 9 7 12 3z"/></svg>
                                    @elseif ($widget['icon'] === 'pest')
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9a4 4 0 014 4v2H8v-2a4 4 0 014-4zm0 0V5m-4 12v2m8-2v2M5 13h3m8 0h3"/></svg>
                                    @else
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4V2m0 20v-2m8-8h2M2 12h2m13.657-5.657l1.414-1.414M4.929 19.071l1.414-1.414m0-11.314L4.929 4.929m14.142 14.142l-1.414-1.414M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-medium text-white/65">{{ $widget['label'] }}</p>
                                    <p class="truncate text-sm font-extrabold text-white sm:text-base">{{ $widget['value'] }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>
            </div>
        </div>
    </div>
</x-dashboard-layout>
