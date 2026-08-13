<x-dashboard-layout
    title="Logistics Network"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Logistics Network'],
    ]"
>
    <x-page-header
        title="Find & Book Transport"
        description="Reliable transport for your goods — book with wallet and track shipments."
    >
        <x-slot:actions>
            <a
                href="{{ route('wallet.index') }}"
                class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-cyra-ink ring-1 ring-cyra-line transition hover:text-cyra-forest"
            >
                Wallet ₦{{ number_format($wallet_balance) }}
            </a>
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
            <a href="{{ route('wallet.index') }}" class="ml-2 font-bold underline">Fund wallet</a>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-5 lg:gap-8">
        <section class="lg:col-span-3" aria-label="Transport booking" id="vehicles">
            <div class="flex gap-6 border-b border-cyra-line" role="tablist" aria-label="Logistics views">
                <a
                    href="{{ route('logistics.index', ['tab' => 'trucks']) }}"
                    role="tab"
                    aria-selected="{{ $tab === 'trucks' ? 'true' : 'false' }}"
                    @class([
                        'relative pb-3 text-sm font-semibold transition',
                        'text-cyra-forest' => $tab === 'trucks',
                        'text-cyra-muted hover:text-cyra-ink' => $tab !== 'trucks',
                    ])
                >
                    Available Trucks
                    @if ($tab === 'trucks')
                        <span class="absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-cyra-forest"></span>
                    @endif
                </a>
                <a
                    href="{{ route('logistics.index', ['tab' => 'shipments']) }}"
                    role="tab"
                    aria-selected="{{ $tab === 'shipments' ? 'true' : 'false' }}"
                    @class([
                        'relative pb-3 text-sm font-semibold transition',
                        'text-cyra-forest' => $tab === 'shipments',
                        'text-cyra-muted hover:text-cyra-ink' => $tab !== 'shipments',
                    ])
                >
                    My Shipments ({{ $shipments_count }})
                    @if ($tab === 'shipments')
                        <span class="absolute inset-x-0 -bottom-px h-0.5 rounded-full bg-cyra-forest"></span>
                    @endif
                </a>
            </div>

            @if ($tab === 'trucks')
                <div class="mt-4 space-y-3">
                    @foreach ($vehicles as $vehicle)
                        <x-logistics.vehicle-row
                            :name="$vehicle['name']"
                            :route="$vehicle['route']"
                            :price="$vehicle['price']"
                            :status="$vehicle['status']"
                            :image="$vehicle['image']"
                        >
                            <x-slot:actions>
                                @if ($vehicle['available'])
                                    <form
                                        method="POST"
                                        action="{{ route('logistics.book', $vehicle['id']) }}"
                                        class="flex w-full flex-wrap items-end gap-2"
                                    >
                                        @csrf
                                        <div class="min-w-[8rem] flex-1">
                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-cyra-muted">Cargo</label>
                                            <input
                                                type="text"
                                                name="cargo_name"
                                                value="{{ old('cargo_name', 'Maize') }}"
                                                required
                                                maxlength="120"
                                                class="w-full rounded-lg border border-cyra-line px-2.5 py-2 text-sm"
                                            >
                                        </div>
                                        <div class="w-24">
                                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-cyra-muted">Tons</label>
                                            <input
                                                type="number"
                                                name="cargo_tons"
                                                min="1"
                                                max="{{ $vehicle['capacity_tons'] }}"
                                                value="{{ old('cargo_tons', min(10, $vehicle['capacity_tons'])) }}"
                                                required
                                                class="w-full rounded-lg border border-cyra-line px-2.5 py-2 text-sm"
                                            >
                                        </div>
                                        @if ($wallet_balance >= $vehicle['price_raw'])
                                            <button
                                                type="submit"
                                                class="rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white hover:bg-cyra-green"
                                            >
                                                Book & pay
                                            </button>
                                        @else
                                            <a
                                                href="{{ route('wallet.index') }}"
                                                class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100"
                                            >
                                                Fund wallet
                                            </a>
                                        @endif
                                    </form>
                                @endif
                            </x-slot:actions>
                        </x-logistics.vehicle-row>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-center">
                    <a
                        href="#vehicles"
                        class="inline-flex min-w-[12rem] items-center justify-center rounded-xl bg-cyra-forest px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green focus:outline-none focus-visible:ring-2 focus-visible:ring-cyra-forest focus-visible:ring-offset-2"
                    >
                        View All Vehicles
                    </a>
                </div>
            @else
                <div class="mt-4 space-y-3">
                    @forelse ($shipments as $shipment)
                        <x-logistics.vehicle-row
                            :name="$shipment['name']"
                            :route="$shipment['route']"
                            :price="$shipment['price']"
                            :status="$shipment['status']"
                            :image="$shipment['image']"
                            :href="route('logistics.index', ['tab' => 'shipments', 'shipment' => $shipment['id']])"
                        >
                            <x-slot:actions>
                                <a
                                    href="{{ route('logistics.index', ['tab' => 'shipments', 'shipment' => $shipment['id']]) }}"
                                    class="rounded-lg border border-cyra-line px-3 py-2 text-xs font-bold text-cyra-ink hover:bg-cyra-surface"
                                >
                                    Track
                                </a>
                                @if ($shipment['can_advance'])
                                    <form method="POST" action="{{ route('logistics.advance', $shipment['id']) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg border border-cyra-forest px-3 py-2 text-xs font-bold text-cyra-forest hover:bg-cyra-mint">
                                            Advance status
                                        </button>
                                    </form>
                                @endif
                                @if ($shipment['status_key'] === 'booked')
                                    <form method="POST" action="{{ route('logistics.cancel', $shipment['id']) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50"
                                            onclick="return confirm('Cancel this shipment and refund your wallet?')"
                                        >
                                            Cancel & refund
                                        </button>
                                    </form>
                                @endif
                            </x-slot:actions>
                        </x-logistics.vehicle-row>
                    @empty
                        <p class="rounded-xl bg-white px-4 py-6 text-sm text-cyra-muted ring-1 ring-cyra-line">
                            No shipments yet. Book a truck to get started.
                        </p>
                    @endforelse
                </div>
            @endif
        </section>

        <aside class="lg:col-span-2" aria-labelledby="shipment-tracking-heading">
            <div class="h-full rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                <h2 id="shipment-tracking-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">
                    Shipment Tracking
                </h2>
                <p class="mt-2 text-sm font-semibold text-cyra-ink">
                    {{ $tracking['reference'] }}
                </p>
                <p class="mt-0.5 text-sm text-cyra-muted">
                    {{ $tracking['cargo'] }}
                </p>

                <div class="mt-6">
                    <x-logistics.tracking-timeline :steps="$tracking['steps']" />
                </div>

                @if (($tracking['shipment_id'] ?? null) && (($tracking['can_advance'] ?? false) || ($tracking['can_cancel'] ?? false)))
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-cyra-line pt-4">
                        @if ($tracking['can_advance'] ?? false)
                            <form method="POST" action="{{ route('logistics.advance', $tracking['shipment_id']) }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white hover:bg-cyra-green">
                                    Advance status
                                </button>
                            </form>
                        @endif
                        @if ($tracking['can_cancel'] ?? false)
                            <form method="POST" action="{{ route('logistics.cancel', $tracking['shipment_id']) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50"
                                    onclick="return confirm('Cancel this shipment and refund your wallet?')"
                                >
                                    Cancel & refund
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </aside>
    </div>
</x-dashboard-layout>
