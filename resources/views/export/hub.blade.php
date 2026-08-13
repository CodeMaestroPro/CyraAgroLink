<x-dashboard-layout
    title="Export & International Trade Hub"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Trade'],
        ['label' => 'Export Hub'],
    ]"
>
    <x-page-header
        title="Export & International Trade Hub"
        description="Create export orders from your farms, track trade process stages, and settle delivery proceeds to your wallet."
    >
        <x-slot:actions>
            <a
                href="{{ $actions['logistics_url'] }}"
                class="inline-flex items-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white"
            >
                Logistics
            </a>
            <a
                href="#create-order"
                class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
            >
                Create Export Order
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

    <div id="overview" class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
                Export Overview
            </h2>
            @if (count($farms))
                <p class="mt-1 text-sm text-cyra-muted">
                    Linked farms: {{ collect($farms)->pluck('name')->join(', ') }}
                </p>
            @endif
        </div>

        <a
            id="orders-jump"
            href="#orders"
            class="inline-flex items-center rounded-xl bg-white px-3.5 py-2 text-sm font-semibold text-cyra-ink shadow-sm ring-1 ring-cyra-line transition hover:ring-cyra-forest/30"
        >
            View Orders
        </a>
    </div>

    <section class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Export metrics">
        @foreach ($kpis as $kpi)
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <p class="text-sm font-medium text-cyra-muted">{{ $kpi['label'] }}</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-cyra-ink sm:text-[1.65rem]">
                    {{ $kpi['value'] }}
                </p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Destinations and export process">
        <article id="destinations" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Top Destinations</h2>
            <ul class="mt-4 divide-y divide-cyra-line/80">
                @foreach ($destinations as $destination)
                    <li class="flex items-center gap-3 py-3.5 first:pt-0 last:pb-0">
                        <span
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyra-panel text-xs font-bold text-cyra-forest ring-1 ring-cyra-line"
                            aria-hidden="true"
                            title="{{ $destination['code'] }}"
                        >{{ $destination['code'] }}</span>
                        <span class="min-w-0 flex-1 truncate font-semibold text-cyra-ink">
                            {{ $destination['country'] }}
                        </span>
                        <span class="shrink-0 font-bold tabular-nums text-cyra-ink">
                            {{ $destination['value'] }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </article>

        <article id="process" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Export Process</h2>
            @php
                $focused = collect($orders)->firstWhere('id', $focusOrderId);
            @endphp
            @if ($focused)
                <p class="mt-2 text-sm text-cyra-muted">
                    Tracking <span class="font-semibold text-cyra-ink">{{ $focused['reference'] }}</span>
                    · {{ $focused['product'] }} to {{ $focused['destination'] }}
                </p>
                <p class="mt-1 text-sm font-semibold text-cyra-forest">
                    Current stage: {{ $focused['status'] }}
                </p>
            @endif
            <ol class="relative mt-5 space-y-0">
                @foreach ($process as $index => $step)
                    <li class="relative flex gap-3 pb-6 last:pb-0">
                        @if (! $loop->last)
                            <span
                                @class([
                                    'absolute left-[0.9375rem] top-8 h-[calc(100%-1.25rem)] w-0.5',
                                    'bg-cyra-forest' => $step['done'] && ($process[$index + 1]['done'] ?? false),
                                    'bg-cyra-line' => ! ($step['done'] && ($process[$index + 1]['done'] ?? false)),
                                ])
                                aria-hidden="true"
                            ></span>
                        @endif

                        <span @class([
                            'relative z-10 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-2',
                            'bg-cyra-forest text-white ring-cyra-soft' => $step['done'] && ! ($step['current'] ?? false),
                            'bg-cyra-green text-white ring-cyra-forest ring-offset-2' => $step['current'] ?? false,
                            'bg-white text-cyra-muted ring-cyra-line' => ! $step['done'],
                        ])>
                            @if ($step['done'])
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <span class="h-2.5 w-2.5 rounded-full bg-cyra-line"></span>
                            @endif
                        </span>

                        <div class="min-w-0 pt-1">
                            <p @class([
                                'text-sm font-semibold',
                                'text-cyra-forest' => $step['current'] ?? false,
                                'text-cyra-ink' => $step['done'] && ! ($step['current'] ?? false),
                                'text-cyra-muted' => ! $step['done'],
                            ])>
                                {{ $step['label'] }}
                                @if ($step['current'] ?? false)
                                    <span class="ml-1 text-xs font-bold uppercase tracking-wide">(current)</span>
                                @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>

            @if ($focused && ($focused['can_advance'] ?? false))
                <form method="POST" action="{{ $focused['advance_url'] }}" class="mt-2">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white transition hover:bg-cyra-green">
                        {{ $focused['advance_label'] ?? 'Advance stage' }}
                    </button>
                </form>
            @elseif ($focused && ($focused['status_key'] ?? null) === 'delivered')
                <p class="mt-2 rounded-xl bg-cyra-mint/50 px-3 py-2 text-sm font-semibold text-cyra-forest ring-1 ring-cyra-line">
                    This shipment is fully delivered.
                </p>
            @endif
        </article>
    </section>

    <section id="orders" class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Export orders">
        <article id="create-order" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Create Export Order</h2>
            <p class="mt-2 text-sm text-cyra-muted">
                Start a new international shipment. Advance stages as inspection, docs, and customs complete; delivery credits your wallet.
            </p>
            <form method="POST" action="{{ $actions['store_url'] }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <label for="export-product" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Product</label>
                    <input
                        id="export-product"
                        list="export-product-options"
                        name="product"
                        value="{{ old('product', $productOptions[0] ?? 'Maize') }}"
                        required
                        maxlength="120"
                        class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                    <datalist id="export-product-options">
                        @foreach ($productOptions as $option)
                            <option value="{{ $option }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label for="export-qty" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Quantity (tons)</label>
                    <input
                        id="export-qty"
                        type="number"
                        name="quantity_tons"
                        min="0.5"
                        step="0.1"
                        value="{{ old('quantity_tons', 10) }}"
                        required
                        class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                </div>
                <div>
                    <label for="export-destination" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Destination</label>
                    <select
                        id="export-destination"
                        name="destination_code"
                        required
                        class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                        @foreach ($destinationOptions as $option)
                            <option value="{{ $option['code'] }}" @selected(old('destination_code', 'NL') === $option['code'])>
                                {{ $option['country'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if (count($farms) > 1)
                    <div class="sm:col-span-2">
                        <label for="export-farm" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Farm</label>
                        <select
                            id="export-farm"
                            name="farm_id"
                            class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                        >
                            @foreach ($farms as $farm)
                                <option value="{{ $farm['id'] }}">{{ $farm['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif (count($farms) === 1)
                    <input type="hidden" name="farm_id" value="{{ $farms[0]['id'] }}">
                @endif
                <div class="sm:col-span-2">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white transition hover:bg-cyra-green">
                        Create Export Order
                    </button>
                </div>
            </form>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Active & Recent Orders</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($orders as $order)
                    <li @class([
                        'rounded-xl p-3 ring-1',
                        'bg-cyra-mint/40 ring-cyra-forest/30' => $order['id'] === $focusOrderId,
                        'bg-cyra-surface/40 ring-cyra-line' => $order['id'] !== $focusOrderId,
                    ])>
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ $order['focus_url'] }}#process" class="text-sm font-bold text-cyra-ink hover:text-cyra-forest">
                                    {{ $order['reference'] }}
                                </a>
                                <p class="mt-0.5 text-sm text-cyra-muted">
                                    {{ $order['product'] }} · {{ $order['quantity'] }} · {{ $order['destination'] }}
                                </p>
                                <p class="mt-1 text-xs font-semibold text-cyra-forest">{{ $order['status'] }} · {{ $order['value'] }}</p>
                            </div>
                            @if ($order['can_advance'])
                                <form method="POST" action="{{ $order['advance_url'] }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl bg-cyra-forest px-3 py-2 text-xs font-bold text-white transition hover:bg-cyra-green">
                                        {{ $order['advance_label'] ?? 'Advance stage' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No export orders yet. Create one to start tracking.</li>
                @endforelse
            </ul>
        </article>
    </section>
</x-dashboard-layout>
