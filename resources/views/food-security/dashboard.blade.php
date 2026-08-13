<x-dashboard-layout
    title="National Food Security Dashboard"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Intelligence'],
        ['label' => 'Food Security'],
    ]"
>
    <x-page-header
        title="National Food Security Dashboard"
        description="Live production, reserves, and hunger-risk signals from farms, markets, and warehouses."
    >
        <x-slot:actions>
            <form method="POST" action="{{ $actions['refresh_url'] }}">
                @csrf
                @if ($stateFilter)
                    <input type="hidden" name="state" value="{{ $stateFilter }}">
                @endif
                <button type="submit" class="inline-flex items-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-mint/40">
                    Refresh index
                </button>
            </form>
            <a
                href="{{ $actions['export_url'] }}"
                class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
            >
                Download report
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

    <div id="overview" class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
                Food Security Overview
            </h2>
            <p class="mt-1 text-sm text-cyra-muted">
                National production, reserves, and hunger risk · Updated {{ $snapshotAt }}
            </p>
        </div>
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-xl bg-white px-3.5 py-2 text-sm font-semibold text-cyra-ink shadow-sm ring-1 ring-cyra-line"
                @click="open = !open"
            >
                <span>{{ $stateFilter ?: 'All states' }}</span>
                <svg class="h-4 w-4 text-cyra-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
            </button>
            <div x-cloak x-show="open" class="absolute right-0 z-10 mt-2 max-h-64 w-48 overflow-y-auto rounded-xl bg-white py-1 shadow-soft ring-1 ring-cyra-line">
                @foreach ($stateOptions as $option)
                    <a
                        href="{{ $option['url'] }}"
                        @class([
                            'block px-4 py-2 text-sm font-medium hover:bg-cyra-mint',
                            'bg-cyra-mint/60 text-cyra-forest' => $option['active'],
                            'text-cyra-ink' => ! $option['active'],
                        ])
                    >
                        {{ $option['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <section class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Food security metrics">
        @foreach ($kpis as $kpi)
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <p class="text-sm font-medium text-cyra-muted">{{ $kpi['label'] }}</p>
                <div class="mt-2 flex flex-wrap items-baseline gap-2">
                    <p class="text-2xl font-extrabold tracking-tight tabular-nums text-cyra-ink sm:text-[1.65rem]">
                        {{ $kpi['value'] }}
                    </p>
                    @if ($kpi['status'])
                        <span class="text-sm font-bold {{ $kpi['status_tone'] ?? 'text-cyra-forest' }}">{{ $kpi['status'] }}</span>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    @if (! empty($factors))
        <p class="mt-3 text-xs text-cyra-muted">
            Signals: {{ $factors['active_farms'] ?? 0 }} farms ·
            {{ $factors['market_commodities'] ?? 0 }} market commodities ·
            {{ $factors['warehouse_stock_tons'] ?? 0 }} warehouse tons ·
            {{ $factors['insured_farms'] ?? 0 }} insured farms
        </p>
    @endif

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Commodities and hunger map">
        <article id="commodities" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Top Commodities</h2>
            <ul class="mt-5 space-y-4">
                @foreach ($commodities as $commodity)
                    <li>
                        <div class="mb-1.5 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
                                    @if ($commodity['icon'] === 'maize')
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2c2 3 3 5 3 8s-1 5-3 8c-2-3-3-5-3-8s1-5 3-8zm-4 8c0 3 1 5 2 7M16 10c0 3-1 5-2 7"/></svg>
                                    @elseif ($commodity['icon'] === 'rice')
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4c4 2 6 5 6 8 0 3-2 4-6 4s-6-1-6-4c0-3 2-6 6-8zm-6 12h12v2H6v-2z"/></svg>
                                    @elseif ($commodity['icon'] === 'cassava')
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v6m0 0c2 2 3 4 3 6a3 3 0 11-6 0c0-2 1-4 3-6zm-4 4l-2 2m10-2l2 2"/></svg>
                                    @else
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 20c2-4 3-7 3-10 0-2 1-4 2-6 1 2 2 4 2 6 0 3 1 6 3 10M9 12h6"/></svg>
                                    @endif
                                </span>
                                <span class="text-sm font-semibold text-cyra-ink">{{ $commodity['name'] }}</span>
                            </div>
                            <span class="text-sm font-bold tabular-nums text-cyra-ink">{{ $commodity['percent'] }}%</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-cyra-line/80">
                            <div
                                class="h-full rounded-full bg-cyra-forest"
                                style="width: {{ $commodity['percent'] }}%"
                            ></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </article>

        <article id="hunger" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Hunger Map (Risk Level)</h2>
            <div class="mt-4 overflow-hidden rounded-2xl bg-cyra-panel ring-1 ring-cyra-line/80">
                <div
                    id="foodSecurityHungerMap"
                    class="h-64 w-full sm:h-72"
                    data-center='@json($map)'
                    data-zones='@json($hungerZones)'
                    role="img"
                    aria-label="Nigeria hunger risk map"
                ></div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3 text-[11px] font-semibold text-cyra-muted">
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#10853F]"></span> Low</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#E6A817]"></span> Medium</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#DC2626]"></span> High</span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#7F1D1D]"></span> Severe</span>
            </div>
            <ul class="mt-4 max-h-40 space-y-2 overflow-y-auto text-sm">
                @foreach ($hungerZones as $zone)
                    <li class="flex items-start justify-between gap-2 border-b border-cyra-line/60 pb-2 last:border-0">
                        <div>
                            <p class="font-semibold text-cyra-ink">{{ $zone['name'] }}</p>
                            <p class="text-xs text-cyra-muted">{{ $zone['detail'] ?? '' }}</p>
                        </div>
                        <span @class([
                            'shrink-0 text-xs font-bold uppercase',
                            'text-cyra-forest' => ($zone['risk'] ?? '') === 'low',
                            'text-amber-500' => ($zone['risk'] ?? '') === 'medium',
                            'text-rose-600' => in_array($zone['risk'] ?? '', ['high', 'severe'], true),
                        ])>{{ $zone['risk'] }}</span>
                    </li>
                @endforeach
            </ul>
        </article>
    </section>

    <section id="interventions" class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Interventions">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Active Interventions</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($interventions as $item)
                    <li class="flex flex-wrap items-start justify-between gap-3 rounded-xl bg-cyra-surface/50 px-3 py-3 ring-1 ring-cyra-line">
                        <div>
                            <p class="text-sm font-semibold text-cyra-ink">{{ $item['title'] }}</p>
                            <p class="mt-0.5 text-xs text-cyra-muted">{{ $item['state'] }} · {{ $item['action_type'] }} · {{ $item['status'] }}@if($item['due']) · Due {{ $item['due'] }}@endif</p>
                        </div>
                        <form method="POST" action="{{ $item['complete_url'] }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">
                                Mark done
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No planned interventions yet. Create one for a high-risk state.</li>
                @endforelse
            </ul>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Plan Intervention</h2>
            <p class="mt-1 text-sm text-cyra-muted">Convert a hunger-risk signal into a concrete response action.</p>
            <form method="POST" action="{{ $actions['intervention_url'] }}" class="mt-4 space-y-3">
                @csrf
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">State</span>
                    <select name="state" required class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        @foreach ($stateOptions as $option)
                            @continue($option['label'] === 'All states')
                            <option value="{{ $option['label'] }}" @selected($stateFilter === $option['label'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Title</span>
                    <input type="text" name="title" required maxlength="160" placeholder="e.g. Release maize buffer to Yobe corridors" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Action type</span>
                    <select name="action_type" required class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        <option value="reserve_release">Reserve release</option>
                        <option value="subsidy_push">Subsidy push</option>
                        <option value="logistics_aid">Logistics aid</option>
                        <option value="market_support">Market support</option>
                        <option value="scouting">Enterprise scouting</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Notes (optional)</span>
                    <textarea name="notes" rows="2" maxlength="500" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"></textarea>
                </label>
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green">
                    Save intervention
                </button>
            </form>
        </article>
    </section>
</x-dashboard-layout>
