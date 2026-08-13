<x-dashboard-layout
    title="Precision Agriculture"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Farm Operations'],
        ['label' => 'Precision Agriculture'],
    ]"
>
    <x-page-header
        title="Precision Agriculture"
        description="Soil nutrients, NDVI field mapping, irrigation scheduling, and fertilizer plans for {{ $farm['name'] }}."
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if (count($farms) > 1)
                    <label class="sr-only" for="precision-farm">Select farm</label>
                    <select
                        id="precision-farm"
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
                        Refresh NDVI scan
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

    <div id="overview" class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
                Precision Overview
            </h2>
            <p class="mt-1 text-sm text-cyra-muted">
                {{ $farm['name'] }} · {{ $farm['location'] }} · Soil: {{ $farm['soil_type'] }}
                @if ($lastScanAt)
                    · Last scan {{ \Illuminate\Support\Carbon::parse($lastScanAt)->diffForHumans() }}
                @endif
            </p>
        </div>

        <a
            id="fields"
            href="#ndvi"
            class="inline-flex items-center rounded-xl bg-white px-3.5 py-2 text-sm font-semibold text-cyra-ink shadow-sm ring-1 ring-cyra-line transition hover:ring-cyra-forest/30"
        >
            View All Fields
        </a>
    </div>

    <section id="soil" class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Soil metrics">
        @foreach ($soil as $metric)
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <p class="text-sm font-medium text-cyra-muted">{{ $metric['label'] }}</p>
                <p class="mt-2 text-3xl font-extrabold tabular-nums text-cyra-forest">{{ $metric['value'] }}</p>
                <p class="mt-1 text-sm font-semibold text-cyra-forest">{{ $metric['status'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-3" aria-label="Field map and agronomy status">
        <article id="ndvi" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5 xl:col-span-2">
            <h2 class="text-base font-extrabold text-cyra-ink">Field Map (NDVI)</h2>
            <div class="mt-4 overflow-hidden rounded-2xl bg-cyra-panel ring-1 ring-cyra-line/80">
                <div
                    id="precisionNdviMap"
                    class="h-72 w-full sm:h-80 lg:h-[22rem]"
                    data-center='@json($map)'
                    data-zones='@json($ndviZones)'
                    role="img"
                    aria-label="NDVI field heatmap for {{ $farm['name'] }}"
                ></div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] font-semibold text-cyra-muted">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#0A5C2E]"></span> High</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#10853F]"></span> Healthy</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#E6A817]"></span> Moderate</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-[#C2410C]"></span> Stress</span>
            </div>
        </article>

        <div class="space-y-5">
            <article id="irrigation" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink">Irrigation Status</h2>
                <p class="mt-3 text-lg font-extrabold text-cyra-forest">{{ $irrigation['status'] }}</p>
                <p class="mt-2 text-sm text-cyra-ink">
                    Next Irrigation:
                    <span class="font-bold">{{ $irrigation['next'] }}</span>
                </p>
                <form method="POST" action="{{ $actions['irrigate_url'] }}" class="mt-4">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white transition hover:bg-cyra-green">
                        Schedule irrigation
                    </button>
                </form>
            </article>

            <article id="fertilizer" class="flex flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6" x-data="{ open: {{ session('status') && str_contains(session('status'), 'Fertilizer plan applied') ? 'true' : 'false' }} }">
                <h2 class="text-base font-extrabold text-cyra-ink">Fertilizer Recommendation</h2>
                <p class="mt-3 text-lg font-extrabold text-cyra-forest">{{ $fertilizer['recommendation'] }}</p>
                <p class="mt-1 text-sm text-cyra-muted">Formula: {{ $fertilizer['formula'] }}</p>

                @if ($fertilizer['applied'])
                    <p class="mt-3 rounded-xl bg-cyra-mint/50 px-3 py-2 text-sm text-cyra-forest ring-1 ring-cyra-line">
                        Logged on
                        @if ($fertilizer['crop_url'])
                            <a href="{{ $fertilizer['crop_url'] }}" class="font-bold underline decoration-cyra-forest/40 underline-offset-2 hover:decoration-cyra-forest">
                                {{ $fertilizer['crop_name'] ?? 'crop' }}
                            </a>
                        @else
                            <span class="font-bold">{{ $fertilizer['crop_name'] ?? 'crop' }}</span>
                        @endif
                        @if ($fertilizer['applied_at'])
                            · {{ $fertilizer['applied_at'] }}
                        @endif
                    </p>
                @endif

                <div x-cloak x-show="open" x-transition class="mt-4 rounded-xl bg-cyra-surface/70 p-3 text-sm leading-relaxed text-cyra-ink ring-1 ring-cyra-line">
                    {{ $recommendationDetail }}
                </div>

                <div class="mt-4 flex flex-col gap-2">
                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex w-full items-center justify-center rounded-xl border-2 border-cyra-forest/30 bg-cyra-surface/60 px-4 py-2.5 text-sm font-bold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white"
                    >
                        <span x-text="open ? 'Hide recommendation' : 'View Full Recommendation'"></span>
                    </button>
                    <form method="POST" action="{{ $actions['fertilizer_url'] }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white transition hover:bg-cyra-green">
                            {{ $fertilizer['applied'] ? 'Re-apply fertilizer plan' : 'Apply fertilizer plan' }}
                        </button>
                    </form>
                </div>
            </article>
        </div>
    </section>
</x-dashboard-layout>
