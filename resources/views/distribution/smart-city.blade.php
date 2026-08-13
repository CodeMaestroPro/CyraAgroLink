<x-dashboard-layout
    title="Smart City Food Distribution"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Logistics', 'href' => route('logistics.index')],
        ['label' => 'Food Distribution'],
    ]"
>
    <x-page-header
        title="Smart City Food Distribution"
        description="Monitor city-wide food deliveries, live routes, and fleet readiness in one command view."
    >
        <x-slot:actions>
            <form method="POST" action="{{ route('distribution.optimize') }}">
                @csrf
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
                >
                    Optimize Routes
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-cyra-mint px-4 py-3 text-sm font-medium text-cyra-forest ring-1 ring-cyra-soft/60" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <x-section-tabs
        :active="$tab"
        :items="[
            ['id' => 'overview', 'label' => 'Overview', 'href' => route('distribution.smart-city', ['tab' => 'overview'])],
            ['id' => 'deliveries', 'label' => 'Deliveries ('.$open_deliveries_count.')', 'href' => route('distribution.smart-city', ['tab' => 'deliveries'])],
            ['id' => 'fleet', 'label' => 'Fleet', 'href' => route('distribution.smart-city', ['tab' => 'fleet'])],
        ]"
    />

    @if ($tab === 'overview')
        <x-section-heading title="Distribution Overview" description="Today’s delivery performance across the city network." />

        <section class="grid grid-cols-2 gap-4 xl:grid-cols-4" aria-label="Distribution overview">
            @foreach ($overview as $stat)
                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                    <p class="text-sm font-medium text-cyra-muted">{{ $stat['label'] }}</p>
                    <p class="mt-2 font-display text-3xl font-bold tracking-tight tabular-nums text-cyra-ink sm:text-4xl">
                        {{ $stat['value'] }}
                    </p>
                </article>
            @endforeach
        </section>

        <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-5" aria-label="Map and fleet">
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5 xl:col-span-3">
                <x-section-heading title="Delivery Map" tone="cream" class="mb-3 !px-0 !py-0 !ring-0" />
                <div class="mt-2 overflow-hidden rounded-2xl bg-cyra-panel ring-1 ring-cyra-line/80">
                    <div
                        id="smartCityDeliveryMap"
                        class="h-72 w-full sm:h-80"
                        data-points='@json($routePoints)'
                        role="img"
                        aria-label="Smart city food delivery route map"
                    ></div>
                </div>
            </article>

            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5 xl:col-span-2">
                <x-section-heading title="Fleet Status" tone="cream" class="mb-3 !px-0 !py-0 !ring-0" />
                <ul class="mt-2 space-y-3">
                    @foreach ($fleet as $row)
                        <li class="flex items-center gap-3 rounded-xl bg-cyra-surface/70 px-3 py-3 ring-1 ring-cyra-line/70">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
                                @if ($row['icon'] === 'available')
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 16l2-8h8l2 8M7 16h10M8.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm7 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>
                                @elseif ($row['icon'] === 'transit')
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16h13V8H5.5L3 10.5V16z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11h3l2 2v3h-5v-5z"/><circle cx="7.5" cy="17.5" r="1.5" stroke-width="1.8"/><circle cx="17.5" cy="17.5" r="1.5" stroke-width="1.8"/></svg>
                                @else
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.7 6.3a4.5 4.5 0 01-6.4 6.4L4 17l3 3 4.3-4.3a4.5 4.5 0 016.4-6.4l-1.5 1.5-1.5-1.5 1.5-1.5z"/></svg>
                                @endif
                            </span>
                            <span class="min-w-0 flex-1 text-sm font-semibold text-cyra-ink">{{ $row['label'] }}</span>
                            <span class="font-display text-xl font-bold tabular-nums text-cyra-ink">{{ $row['value'] }}</span>
                        </li>
                    @endforeach
                </ul>

                <form method="POST" action="{{ route('distribution.optimize') }}" class="mt-6">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
                    >
                        Optimize Routes
                    </button>
                </form>
                <p class="mt-2 text-center text-xs text-cyra-muted">
                    {{ $open_deliveries_count }} open · {{ $available_fleet_count }} units free
                </p>
            </article>
        </section>
    @elseif ($tab === 'deliveries')
        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div>
                <x-section-heading title="Your deliveries" description="Schedule city drops, advance milestones, and cancel before dispatch." />

                <div class="mt-4 space-y-3">
                    @forelse ($deliveries as $delivery)
                        <article class="rounded-2xl bg-white p-4 ring-1 ring-cyra-line sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-extrabold text-cyra-ink">{{ $delivery['reference'] }}</p>
                                    <p class="mt-1 text-sm text-cyra-muted">{{ $delivery['cargo'] }} · {{ $delivery['route'] }}</p>
                                    <p class="mt-1 text-xs text-cyra-muted">
                                        {{ $delivery['fleet'] }}
                                        @if ($delivery['route_order'])
                                            · Route #{{ $delivery['route_order'] }}
                                        @endif
                                        · <span class="font-bold text-cyra-ink">{{ $delivery['status'] }}</span>
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($delivery['can_advance'])
                                        <form method="POST" action="{{ route('distribution.deliveries.advance', $delivery['id']) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-cyra-forest px-3 py-2 text-xs font-bold text-cyra-forest hover:bg-cyra-mint">
                                                Advance
                                            </button>
                                        </form>
                                    @endif
                                    @if ($delivery['can_cancel'])
                                        <form method="POST" action="{{ route('distribution.deliveries.cancel', $delivery['id']) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50"
                                                onclick="return confirm('Cancel this delivery?')"
                                            >
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl bg-white px-6 py-12 text-center ring-1 ring-cyra-line">
                            <p class="text-sm font-semibold text-cyra-ink">No deliveries yet.</p>
                            <p class="mt-1 text-sm text-cyra-muted">Schedule a drop using the form.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <aside class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink">Schedule delivery</h2>
                <p class="mt-1 text-sm text-cyra-muted">Create a city food drop for today.</p>

                <form method="POST" action="{{ route('distribution.deliveries.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="cargo_name" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Cargo</label>
                        <input
                            id="cargo_name"
                            type="text"
                            name="cargo_name"
                            value="{{ old('cargo_name', 'Fresh Produce') }}"
                            required
                            maxlength="120"
                            class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                        >
                        <x-input-error :messages="$errors->get('cargo_name')" class="mt-2" />
                    </div>
                    <div>
                        <label for="quantity" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Quantity (crates)</label>
                        <input
                            id="quantity"
                            type="number"
                            name="quantity"
                            min="1"
                            value="{{ old('quantity', 20) }}"
                            required
                            class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                        >
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>
                    <div>
                        <label for="origin_hub_id" class="mb-1.5 block text-sm font-semibold text-cyra-ink">From hub</label>
                        <select
                            id="origin_hub_id"
                            name="origin_hub_id"
                            required
                            class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                        >
                            @foreach ($hubs as $hub)
                                <option value="{{ $hub['id'] }}" @selected((int) old('origin_hub_id', $hubs[0]['id'] ?? 0) === (int) $hub['id'])>
                                    {{ $hub['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('origin_hub_id')" class="mt-2" />
                    </div>
                    <div>
                        <label for="destination_hub_id" class="mb-1.5 block text-sm font-semibold text-cyra-ink">To hub</label>
                        <select
                            id="destination_hub_id"
                            name="destination_hub_id"
                            required
                            class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                        >
                            @foreach ($hubs as $hub)
                                <option value="{{ $hub['id'] }}" @selected((int) old('destination_hub_id', $hubs[count($hubs) - 1]['id'] ?? 0) === (int) $hub['id'])>
                                    {{ $hub['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('destination_hub_id')" class="mt-2" />
                    </div>
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-cyra-forest px-4 py-3 text-sm font-bold text-white hover:bg-cyra-green">
                        Schedule delivery
                    </button>
                </form>
            </aside>
        </section>
    @else
        <section>
            <x-section-heading title="City fleet" description="Toggle maintenance for idle units. In-transit units stay on route." />

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($fleet_units as $unit)
                    <article class="rounded-2xl bg-white p-4 ring-1 ring-cyra-line">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-extrabold text-cyra-ink">{{ $unit['name'] }}</p>
                                <p class="mt-1 text-xs text-cyra-muted">{{ $unit['hub'] }} · {{ $unit['status'] }}</p>
                            </div>
                            @if ($unit['status_key'] !== 'in_transit')
                                <form method="POST" action="{{ route('distribution.fleet.toggle', $unit['id']) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        @class([
                                            'rounded-lg px-3 py-2 text-xs font-bold ring-1',
                                            'bg-amber-50 text-amber-800 ring-amber-200' => $unit['status_key'] === 'available',
                                            'bg-cyra-mint text-cyra-forest ring-cyra-forest/30' => $unit['status_key'] === 'maintenance',
                                        ])
                                    >
                                        {{ $unit['status_key'] === 'maintenance' ? 'Mark available' : 'Maintenance' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</x-dashboard-layout>
