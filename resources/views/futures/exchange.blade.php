<x-dashboard-layout
    title="Commodity Futures Exchange"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Trading'],
        ['label' => 'Commodity Futures'],
    ]"
>
    <x-page-header
        title="Commodity Futures Exchange"
        description="Trade agricultural futures with wallet margin, live depth, and mark-to-market settlement."
    >
        <x-slot:actions>
            <a
                href="{{ $actions['wallet_url'] }}"
                class="inline-flex items-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-mint/40"
            >
                Wallet · ₦{{ number_format($walletBalance) }}
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

    <x-section-tabs
        active="board"
        :items="[
            ['id' => 'board', 'label' => 'Board', 'href' => '#board'],
            ['id' => 'chart', 'label' => 'Chart', 'href' => '#chart'],
            ['id' => 'depth', 'label' => 'Market Depth', 'href' => '#depth'],
            ['id' => 'contracts', 'label' => 'Contracts', 'href' => '#contracts'],
            ['id' => 'positions', 'label' => 'Positions', 'href' => '#positions'],
        ]"
    />

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-5">
        <section id="board" class="xl:col-span-3" aria-label="Market analysis">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-lg font-extrabold tracking-tight text-cyra-ink sm:text-xl">
                    {{ $contract['name'] }}
                </h2>
                <p class="mt-1 text-xs font-bold uppercase tracking-wide text-cyra-muted">{{ $contract['symbol'] }} · Expiry {{ $contract['expiry'] }}</p>

                <div class="mt-2 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <p class="text-2xl font-extrabold tabular-nums text-cyra-forest sm:text-3xl">
                        {{ $contract['price'] }}
                    </p>
                    <p class="text-sm font-bold sm:text-base {{ $contract['change_tone'] }}">{{ $contract['change'] }}</p>
                </div>

                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-cyra-muted">
                    @foreach ($stats as $stat)
                        <p>
                            <span class="font-medium">{{ $stat['label'] }}:</span>
                            <span class="font-bold tabular-nums text-cyra-ink">{{ $stat['value'] }}</span>
                        </p>
                    @endforeach
                </div>

                <div id="chart" class="mt-5">
                    <div class="h-64 sm:h-72">
                        <canvas
                            id="futuresCandleChart"
                            data-candles='@json($candles)'
                            data-default-range="1D"
                            aria-label="{{ $contract['name'] }} candlestick chart"
                            role="img"
                        ></canvas>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2">
                        @foreach ($ranges as $index => $range)
                            <button
                                type="button"
                                data-futures-range="{{ $range }}"
                                @class([
                                    'text-sm font-bold transition',
                                    'text-cyra-forest' => $index === 0,
                                    'text-cyra-muted hover:text-cyra-ink' => $index !== 0,
                                ])
                            >
                                {{ $range }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div id="contracts" class="mt-6">
                    <h3 class="text-sm font-extrabold text-cyra-ink">All Futures</h3>
                    <ul class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($contracts as $item)
                            <li>
                                <a
                                    href="{{ $item['url'] }}"
                                    @class([
                                        'flex items-center justify-between rounded-xl px-3 py-2.5 text-sm ring-1 transition',
                                        'bg-cyra-mint/50 ring-cyra-forest/30 font-bold text-cyra-forest' => $item['active'],
                                        'bg-cyra-surface/40 ring-cyra-line text-cyra-ink hover:ring-cyra-forest/30' => ! $item['active'],
                                    ])
                                >
                                    <span>{{ $item['name'] }}</span>
                                    <span class="tabular-nums">{{ $item['price'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </article>
        </section>

        <section id="depth" class="xl:col-span-2" aria-label="Market depth">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Market Depth</h2>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-cyra-forest">Buy Orders</p>
                        <ul class="space-y-1">
                            @foreach ($buyOrders as $order)
                                <li class="relative overflow-hidden rounded-md px-2 py-1.5 text-sm">
                                    <span
                                        class="absolute inset-y-0 left-0 bg-emerald-100/80"
                                        style="width: {{ $order['depth'] }}%"
                                        aria-hidden="true"
                                    ></span>
                                    <div class="relative flex items-center justify-between gap-2">
                                        <span class="font-semibold tabular-nums text-cyra-forest">{{ number_format($order['price']) }}</span>
                                        <span class="font-medium tabular-nums text-cyra-ink">{{ number_format($order['qty']) }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-600">Sell Orders</p>
                        <ul class="space-y-1">
                            @foreach ($sellOrders as $order)
                                <li class="relative overflow-hidden rounded-md px-2 py-1.5 text-sm">
                                    <span
                                        class="absolute inset-y-0 left-0 bg-rose-100/80"
                                        style="width: {{ $order['depth'] }}%"
                                        aria-hidden="true"
                                    ></span>
                                    <div class="relative flex items-center justify-between gap-2">
                                        <span class="font-semibold tabular-nums text-rose-600">{{ number_format($order['price']) }}</span>
                                        <span class="font-medium tabular-nums text-cyra-ink">{{ number_format($order['qty']) }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <form method="POST" action="{{ $actions['order_url'] }}" class="mt-5 space-y-3" id="trade-form">
                    @csrf
                    <input type="hidden" name="contract_id" value="{{ $contract['id'] }}">
                    <input type="hidden" name="side" id="order-side" value="buy">
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-cyra-muted">Qty (tons)</span>
                            <input
                                type="number"
                                name="quantity"
                                value="{{ $defaultQty }}"
                                min="1"
                                max="1000"
                                required
                                class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                            >
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-cyra-muted">Limit price (₦)</span>
                            <input
                                type="number"
                                name="price"
                                value="{{ $contract['price_raw'] }}"
                                min="1000"
                                required
                                class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                            >
                        </label>
                    </div>
                    <p class="text-xs text-cyra-muted">Margin locked at 10% of notional from your wallet. Crossing the market opens a position immediately.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="submit"
                            onclick="document.getElementById('order-side').value='buy'; return confirm('Place BUY order and lock 10% margin from wallet?');"
                            class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
                        >
                            Buy
                        </button>
                        <button
                            type="submit"
                            onclick="document.getElementById('order-side').value='sell'; return confirm('Place SELL order and lock 10% margin from wallet?');"
                            class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700"
                        >
                            Sell
                        </button>
                    </div>
                </form>
            </article>
        </section>
    </div>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Orders and positions">
        <article id="orders" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">My Orders</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($userOrders as $order)
                    <li class="flex flex-wrap items-center justify-between gap-2 border-b border-cyra-line/70 pb-3 last:border-0 last:pb-0">
                        <div>
                            <p class="text-sm font-bold text-cyra-ink">{{ $order['reference'] }} · {{ $order['side'] }} {{ $order['quantity'] }} @ {{ $order['price'] }}</p>
                            <p class="text-xs text-cyra-muted">Filled {{ $order['filled'] }} · Margin {{ $order['margin'] }} · {{ $order['status'] }}</p>
                        </div>
                        @if ($order['can_cancel'])
                            <form method="POST" action="{{ $order['cancel_url'] }}">
                                @csrf
                                <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50">
                                    Cancel
                                </button>
                            </form>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No orders on this contract yet. Place a Buy or Sell above.</li>
                @endforelse
            </ul>
        </article>

        <article id="positions" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Open Positions</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($positions as $position)
                    <li class="flex flex-wrap items-center justify-between gap-2 border-b border-cyra-line/70 pb-3 last:border-0 last:pb-0">
                        <div>
                            <p class="text-sm font-bold text-cyra-ink">{{ $position['reference'] }} · {{ $position['side'] }} {{ $position['quantity'] }} · {{ $position['contract'] }}</p>
                            <p class="text-xs text-cyra-muted">Entry {{ $position['entry'] }} · Mark {{ $position['mark'] }} · Margin {{ $position['margin'] }}</p>
                            <p class="mt-0.5 text-xs font-bold {{ $position['pnl_tone'] }}">Unrealized {{ $position['pnl'] }}</p>
                        </div>
                        <form method="POST" action="{{ $position['close_url'] }}">
                            @csrf
                            <button
                                type="submit"
                                onclick="return confirm('Close this position at the current mark price and settle to wallet?');"
                                class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green"
                            >
                                Close
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No open positions. Filled orders open long/short exposure here.</li>
                @endforelse
            </ul>
        </article>
    </section>
</x-dashboard-layout>
