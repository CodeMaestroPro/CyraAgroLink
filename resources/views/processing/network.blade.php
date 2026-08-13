<x-dashboard-layout
    title="Food Processing Network"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Supply Chain'],
        ['label' => 'Processing Network'],
    ]"
>
    <x-page-header
        title="Food Processing Network"
        description="Book factory services, deliver farm produce via logistics, then start processing once cargo arrives."
    >
        <x-slot:actions>
            <a
                href="{{ $actions['logistics_url'] }}"
                class="inline-flex items-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white"
            >
                Logistics
            </a>
            <a
                href="{{ $actions['warehouse_url'] }}"
                class="inline-flex items-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white"
            >
                Warehouse
            </a>
            <a
                href="#create-request"
                class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
            >
                New request
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

    <div id="overview">
        <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
            Processing Overview
        </h2>
        <p class="mt-1 text-sm text-cyra-muted">
            Factories, capacity, and service requests
            @if (count($farms))
                · Linked farms: {{ collect($farms)->pluck('name')->join(', ') }}
            @endif
            · Wallet: ₦{{ number_format($walletBalance) }}
            · Produce is hauled to factories through logistics before processing starts
        </p>
    </div>

    <section id="factories" class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Processing metrics">
        @foreach ($kpis as $kpi)
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <p class="text-sm font-medium text-cyra-muted">{{ $kpi['label'] }}</p>
                <p @class([
                    'mt-2 text-2xl font-extrabold tracking-tight tabular-nums sm:text-[1.65rem]',
                    'text-cyra-forest' => $kpi['tone'] === 'green',
                    'text-cyra-ink' => $kpi['tone'] === 'ink',
                ])>
                    {{ $kpi['value'] }}
                </p>
            </article>
        @endforeach
    </section>

    <section id="services" class="mt-6" aria-labelledby="popular-services-heading">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 id="popular-services-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">
                Popular Services
            </h2>
            <div class="mt-5 grid grid-cols-3 gap-4 sm:grid-cols-6">
                @foreach ($services as $service)
                    @php
                        $toneClass = match ($service['tone']) {
                            'soil' => 'bg-[#F3EDE6] text-cyra-soil',
                            'blue' => 'bg-sky-50 text-sky-600',
                            'amber' => 'bg-amber-50 text-amber-600',
                            default => 'bg-cyra-mint text-cyra-forest',
                        };
                    @endphp
                    <a href="#create-request" class="text-center transition hover:opacity-90" title="Request {{ $service['label'] }}">
                        <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full {{ $toneClass }} ring-1 ring-black/5">
                            @if ($service['icon'] === 'milling')
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v3m0 12v3M3 12h3m12 0h3M6.3 6.3l2.1 2.1m7.2 7.2l2.1 2.1M17.7 6.3l-2.1 2.1M6.3 17.7l2.1-2.1M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                            @elseif ($service['icon'] === 'packaging')
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 8l-9-5-9 5v8l9 5 9-5V8zm-9 5l9-5m-9 5L3 8m9 5v8"/></svg>
                            @elseif ($service['icon'] === 'drying')
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/></svg>
                            @elseif ($service['icon'] === 'cold')
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v18M5 7l14 10M19 7L5 17M8 4.5l4 2 4-2M8 19.5l4-2 4 2"/></svg>
                            @elseif ($service['icon'] === 'juicing')
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 3h6l1 4H8l1-4zm-1 4h8v12a2 2 0 01-2 2h-4a2 2 0 01-2-2V7z"/></svg>
                            @else
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 15a4 4 0 004 4h10a4 4 0 100-8 5.5 5.5 0 00-10.7 1.5A4 4 0 003 15z"/></svg>
                            @endif
                        </span>
                        <p class="mt-2 text-xs font-bold text-cyra-ink">{{ $service['label'] }}</p>
                    </a>
                @endforeach
            </div>
        </article>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Requests and factories">
        <article id="requests" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 id="recent-requests-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">
                Recent Requests
            </h2>
            <ul class="mt-4 divide-y divide-cyra-line/80">
                @forelse ($requests as $request)
                    <li class="space-y-2 py-3.5 first:pt-0 last:pb-0">
                        <div class="flex items-start gap-3">
                            <span @class([
                                'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full',
                                'bg-cyra-panel text-cyra-muted' => $request['status_tone'] === 'progress',
                                'bg-cyra-mint text-cyra-forest' => $request['status_tone'] === 'done',
                                'bg-cyra-soft/40 text-cyra-forest' => $request['status_tone'] === 'queued',
                            ])>
                                @if ($request['icon'] === 'check')
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                                @elseif ($request['icon'] === 'box')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.37 2.37 1.724 1.724 0 001.065 2.572 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.37 2.37 1.724 1.724 0 00-2.572 1.065 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.37-2.37 1.724 1.724 0 00-1.065-2.572 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.37-2.37c.83.5 1.89.24 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-cyra-ink">{{ $request['title'] }}</p>
                                <p class="truncate text-sm text-cyra-muted">
                                    {{ $request['detail'] }}
                                    @if ($request['factory'])
                                        · {{ $request['factory'] }}
                                    @endif
                                </p>
                                @if ($request['logistics'])
                                    <p class="mt-1 text-xs text-cyra-forest">
                                        {{ $request['logistics']['reference'] }}
                                        · {{ $request['logistics']['route'] }}
                                        · {{ $request['logistics']['status'] }}
                                        ·
                                        <a href="{{ $request['logistics']['track_url'] }}" class="font-bold underline decoration-cyra-forest/40 underline-offset-2 hover:decoration-cyra-forest">
                                            Track in logistics
                                        </a>
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-wrap justify-end gap-2 pl-12">
                            @if (($request['logistics']['can_advance'] ?? false))
                                <form method="POST" action="{{ $request['logistics']['advance_url'] }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl border-2 border-cyra-forest/30 px-3 py-2 text-xs font-bold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white">
                                        {{ $request['logistics']['advance_label'] }}
                                    </button>
                                </form>
                            @endif
                            @if ($request['can_advance'])
                                <form method="POST" action="{{ $request['advance_url'] }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl bg-cyra-forest px-3 py-2 text-xs font-bold text-white transition hover:bg-cyra-green">
                                        {{ $request['advance_label'] }}
                                    </button>
                                </form>
                            @elseif ($request['awaiting_delivery'] ?? false)
                                <span class="rounded-xl bg-amber-50 px-3 py-2 text-xs font-bold text-amber-700 ring-1 ring-amber-200">
                                    Waiting for factory delivery
                                </span>
                            @elseif ($request['status'] === 'Completed')
                                <span class="text-xs font-bold text-cyra-forest">Completed</span>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="py-2 text-sm text-cyra-muted">No processing requests yet. Submit one below.</li>
                @endforelse
            </ul>
        </article>

        <article id="create-request" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Submit Processing Request</h2>
            <p class="mt-2 text-sm text-cyra-muted">
                Submitting books a logistics truck from your farm to the factory, then charges the processing fee from your wallet. Start processing only after delivery.
            </p>
            <form method="POST" action="{{ $actions['store_url'] }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="processing-service" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Service</label>
                    <select
                        id="processing-service"
                        name="service"
                        required
                        class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                        @foreach ($serviceOptions as $option)
                            <option value="{{ $option['value'] }}" @selected(old('service', 'milling') === $option['value'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="processing-qty" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Quantity (tons)</label>
                    <input
                        id="processing-qty"
                        type="number"
                        name="quantity_tons"
                        min="0.5"
                        step="0.1"
                        value="{{ old('quantity_tons', 5) }}"
                        required
                        class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                </div>
                <div class="sm:col-span-2">
                    <label for="processing-product" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Product</label>
                    <input
                        id="processing-product"
                        list="processing-product-options"
                        name="product"
                        value="{{ old('product', $productOptions[0] ?? 'Maize') }}"
                        required
                        maxlength="120"
                        class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                    <datalist id="processing-product-options">
                        @foreach ($productOptions as $option)
                            <option value="{{ $option }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div class="sm:col-span-2">
                    <label for="processing-factory" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Factory</label>
                    <select
                        id="processing-factory"
                        name="factory_id"
                        class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                        @foreach ($factoryOptions as $option)
                            <option value="{{ $option['id'] }}">{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                @if (count($farms) > 1)
                    <div class="sm:col-span-2">
                        <label for="processing-farm" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Farm</label>
                        <select
                            id="processing-farm"
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
                        Submit processing request
                    </button>
                </div>
            </form>
        </article>
    </section>

    @if (count($factories))
        <section class="mt-6" aria-label="Factory network sample">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Network Factories</h2>
                <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($factories as $factory)
                        <li class="rounded-xl bg-cyra-surface/50 px-3 py-3 ring-1 ring-cyra-line">
                            <p class="text-sm font-bold text-cyra-ink">{{ $factory['name'] }}</p>
                            <p class="mt-0.5 text-xs text-cyra-muted">{{ $factory['state'] }} · {{ $factory['utilization'] }} utilized</p>
                            <p class="mt-1 text-xs text-cyra-forest">{{ $factory['services'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </article>
        </section>
    @endif

    <div id="equipment" class="mt-6 flex flex-wrap justify-end gap-3">
        <a
            href="{{ $actions['equipment_url'] }}"
            class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
        >
            View All Equipments
        </a>
    </div>
</x-dashboard-layout>
