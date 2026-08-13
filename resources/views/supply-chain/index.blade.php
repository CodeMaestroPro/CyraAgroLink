<x-dashboard-layout
    title="Supply Chain Tracking"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Logistics', 'href' => route('logistics.index')],
        ['label' => 'Supply Chain Tracking'],
    ]"
>
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

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
        <div>
            <x-page-header
                title="{{ $shipment['reference'] }}"
                description="{{ $shipment['cargo'] }}{{ $shipment['route_label'] !== '' ? ' · '.$shipment['route_label'] : '' }}"
            >
                <x-slot:actions>
                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold',
                        'bg-cyra-mint text-cyra-forest' => in_array($shipment['status_key'], ['delivered', 'in_warehouse', 'in_transit', 'picked_up', 'booked'], true),
                        'bg-rose-50 text-rose-700' => $shipment['status_key'] === 'cancelled',
                        'bg-cyra-surface text-cyra-muted ring-1 ring-cyra-line' => $shipment['status_key'] === 'none',
                    ])>
                        <span @class([
                            'h-1.5 w-1.5 rounded-full',
                            'bg-cyra-forest' => $shipment['status_key'] !== 'cancelled' && $shipment['status_key'] !== 'none',
                            'bg-rose-500' => $shipment['status_key'] === 'cancelled',
                            'bg-cyra-muted' => $shipment['status_key'] === 'none',
                        ]) aria-hidden="true"></span>
                        {{ $shipment['status'] }}
                    </span>
                </x-slot:actions>
            </x-page-header>

            <section class="overflow-x-auto pb-1" aria-label="Shipment milestones">
                <div class="min-w-[36rem]">
                    <x-supply-chain.progress-stepper :steps="$shipment['steps']" />
                </div>
            </section>

            <section class="mt-8" aria-label="Route map">
                <div class="overflow-hidden rounded-2xl bg-white ring-1 ring-cyra-line">
                    @if (count($shipment['route']['points']) >= 2)
                        <div
                            id="supplyChainRouteMap"
                            class="h-56 w-full sm:h-64 lg:h-72"
                            data-points='@json($shipment['route']['points'])'
                            role="img"
                            aria-label="Route from {{ $shipment['origin'] }} to {{ $shipment['destination'] }}"
                        ></div>
                    @else
                        <div class="flex h-56 items-center justify-center px-6 text-center text-sm text-cyra-muted sm:h-64">
                            Book a logistics shipment to see the live route map.
                        </div>
                    @endif
                </div>
            </section>

            <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
                @if ($shipment['can_cancel'] && $shipment['id'])
                    <form method="POST" action="{{ route('supply-chain.cancel', $shipment['id']) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200 transition hover:bg-rose-50"
                        >
                            Cancel booking
                        </button>
                    </form>
                @endif

                @if ($shipment['can_advance'] && $shipment['id'])
                    <form method="POST" action="{{ route('supply-chain.advance', $shipment['id']) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
                        >
                            Advance status
                        </button>
                    </form>
                @endif

                <a
                    href="{{ $shipment['logistics_url'] }}"
                    class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-cyra-ink ring-1 ring-cyra-line transition hover:bg-cyra-surface"
                >
                    {{ $shipment['id'] ? 'View in Logistics' : 'Book a truck' }}
                </a>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-sm font-extrabold text-cyra-ink">My shipments</h2>
                <p class="mt-1 text-xs text-cyra-muted">Select a shipment to track end-to-end.</p>

                <ul class="mt-4 space-y-2">
                    @forelse ($shipments as $item)
                        <li>
                            <a
                                href="{{ $item['url'] }}"
                                @class([
                                    'block rounded-xl px-3 py-3 ring-1 transition',
                                    'bg-cyra-mint ring-cyra-forest/30' => $item['selected'],
                                    'bg-white ring-cyra-line hover:bg-cyra-surface' => ! $item['selected'],
                                ])
                            >
                                <p class="text-sm font-bold text-cyra-ink">{{ $item['reference'] }}</p>
                                <p class="mt-0.5 text-xs text-cyra-muted">{{ $item['cargo'] }}</p>
                                <p class="mt-1 text-[11px] font-semibold text-cyra-forest">{{ $item['status'] }}</p>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-cyra-surface/70 px-3 py-4 text-sm text-cyra-muted ring-1 ring-cyra-line">
                            No shipments yet.
                        </li>
                    @endforelse
                </ul>
            </div>
        </aside>
    </div>
</x-dashboard-layout>
