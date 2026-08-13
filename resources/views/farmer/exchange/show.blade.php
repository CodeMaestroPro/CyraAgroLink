<x-dashboard-layout
    title="Commodity Exchange"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Marketplace', 'href' => route('marketplace.index')],
        ['label' => 'Commodity Exchange'],
    ]"
>
    <div
        class="space-y-6"
        x-data="{
            side: '{{ old('side', 'buy') }}',
            price: {{ (int) old('price_per_ton', $commodity->price_per_ton) }},
            qty: {{ (int) old('quantity_tons', 1) }},
            setFromDepth(price, side) {
                this.price = price;
                this.side = side === 'sell' ? 'buy' : 'sell';
            }
        }"
    >
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-page-header title="{{ $commodity->exchangeTitle() }}" />

            <form method="GET" action="{{ route('exchange.show') }}" class="flex items-end gap-2">
                <div>
                    <label for="commodity" class="mb-1 block text-xs font-bold uppercase tracking-wide text-cyra-muted">Commodity</label>
                    <select
                        id="commodity"
                        name="commodity"
                        onchange="this.form.submit()"
                        class="min-w-[12rem] rounded-lg border border-cyra-line bg-white px-3 py-2.5 text-sm font-semibold text-cyra-ink shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                        @foreach ($commodities as $option)
                            <option value="{{ $option->id }}" @selected((int) $option->id === (int) $commodity->id)>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($range !== '1D')
                    <input type="hidden" name="range" value="{{ $range }}">
                @endif
            </form>
        </div>

        @if (session('status'))
            <div class="rounded-lg bg-cyra-mint px-4 py-3 text-sm font-medium text-cyra-forest" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
            <div class="space-y-6">
                <section>
                    <p class="text-sm text-cyra-muted">Live Market Price</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <p class="text-2xl font-extrabold text-cyra-ink sm:text-3xl">
                            {{ $commodity->formattedPrice() }}
                        </p>
                        <span @class([
                            'inline-flex rounded-full px-2.5 py-1 text-xs font-bold',
                            'bg-cyra-mint text-cyra-forest' => $change_percent >= 0,
                            'bg-rose-50 text-rose-600' => $change_percent < 0,
                        ])>
                            {{ $change_percent >= 0 ? '+' : '' }}{{ number_format($change_percent, 2) }}%
                        </span>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-4 ring-1 ring-cyra-line sm:p-5">
                    <div class="h-56 sm:h-64">
                        <canvas
                            id="exchangePriceChart"
                            data-labels='@json($chart['labels'])'
                            data-values='@json($chart['values'])'
                        ></canvas>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (['1D', '7D', '1M', '3M', '1Y'] as $option)
                            <a
                                href="{{ route('exchange.show', ['commodity' => $commodity->id, 'range' => $option]) }}"
                                @class([
                                    'rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                    'bg-cyra-mint text-cyra-forest ring-1 ring-cyra-forest' => $range === $option,
                                    'bg-white text-cyra-muted ring-1 ring-cyra-line hover:text-cyra-forest' => $range !== $option,
                                ])
                            >
                                {{ $option }}
                            </a>
                        @endforeach
                    </div>
                </section>

                <section>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-extrabold text-cyra-ink">Market Depth</h2>
                        <p class="text-xs text-cyra-muted">Live open orders only · click a level to prefill</p>
                    </div>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-cyra-forest">Buy Orders</p>
                            <x-exchange.depth-table :orders="$buy_orders" side="buy" />
                        </div>
                        <div>
                            <p class="mb-2 text-xs font-bold uppercase tracking-wide text-rose-600">Sell Orders</p>
                            <x-exchange.depth-table :orders="$sell_orders" side="sell" />
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="text-sm font-extrabold text-cyra-ink">Recent trades</h2>
                    <p class="mt-1 text-xs text-cyra-muted">Matched buy/sell executions for {{ $commodity->name }}</p>

                    @if ($recent_trades->isEmpty())
                        <div class="mt-3 rounded-2xl bg-white px-5 py-8 text-center ring-1 ring-cyra-line">
                            <p class="text-sm font-semibold text-cyra-ink">No trades yet</p>
                            <p class="mt-1 text-sm text-cyra-muted">Trades appear here when buy and sell orders match.</p>
                        </div>
                    @else
                        <div class="mt-3 overflow-hidden rounded-2xl bg-white ring-1 ring-cyra-line">
                            <div class="grid grid-cols-4 gap-2 border-b border-cyra-line px-4 py-2 text-[11px] font-bold uppercase tracking-wide text-cyra-muted">
                                <span>Time</span>
                                <span>Qty</span>
                                <span>Price</span>
                                <span class="text-right">Notional</span>
                            </div>
                            <ul class="divide-y divide-cyra-line/80">
                                @foreach ($recent_trades as $trade)
                                    <li class="grid grid-cols-4 gap-2 px-4 py-2.5 text-sm">
                                        <span class="text-cyra-muted">{{ $trade->traded_at?->format('H:i') ?? '—' }}</span>
                                        <span class="font-semibold text-cyra-ink">{{ number_format($trade->quantity_tons) }} t</span>
                                        <span class="font-semibold text-cyra-forest">₦{{ number_format($trade->price_per_ton) }}</span>
                                        <span class="text-right font-medium text-cyra-ink">₦{{ number_format($trade->notional_amount) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>

                <section>
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-extrabold text-cyra-ink">Your orders</h2>
                            <p class="mt-1 text-xs text-cyra-muted">
                                {{ $open_orders_count }} open on {{ $commodity->name }}
                            </p>
                        </div>
                        <a
                            href="{{ route('marketplace.index', ['view' => 'orders', 'order_status' => 'open']) }}"
                            class="text-xs font-bold text-cyra-forest hover:underline"
                        >
                            View all marketplace orders
                        </a>
                    </div>

                    @if ($user_orders->isEmpty())
                        <div class="mt-3 rounded-2xl bg-white px-5 py-8 text-center ring-1 ring-cyra-line">
                            <p class="text-sm font-semibold text-cyra-ink">No orders for this commodity yet.</p>
                            <p class="mt-1 text-sm text-cyra-muted">Place a buy or sell order using the trade panel.</p>
                        </div>
                    @else
                        <div class="mt-3 space-y-3">
                            @foreach ($user_orders as $order)
                                @php
                                    $statusClass = match ($order->status) {
                                        'filled' => 'text-cyra-forest',
                                        'cancelled' => 'text-rose-600',
                                        default => 'text-amber-700',
                                    };
                                    $displayQty = $order->status === 'open'
                                        ? $order->quantity_tons
                                        : ($order->original_quantity_tons ?: $order->quantity_tons);
                                @endphp
                                <article class="rounded-2xl bg-white p-4 ring-1 ring-cyra-line sm:p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-extrabold text-cyra-ink">
                                                {{ strtoupper($order->side) }}
                                                · {{ number_format($displayQty) }} tons
                                                @ ₦{{ number_format($order->price_per_ton) }}
                                            </p>
                                            <p class="mt-1 text-xs text-cyra-muted">
                                                Filled {{ number_format($order->filled_quantity_tons) }}
                                                · Remaining {{ number_format($order->quantity_tons) }}
                                                ·
                                                <span class="font-bold {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                                            </p>
                                        </div>

                                        @if ($order->status === 'open')
                                            <form method="POST" action="{{ route('exchange.orders.cancel', $order) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50"
                                                    onclick="return confirm('Cancel this order? Unused buy holds return to your wallet.')"
                                                >
                                                    Cancel
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <aside class="space-y-5">
                <x-exchange.market-summary :commodity="$commodity" :summary="$summary" />
                <x-exchange.trade-panel :commodity="$commodity" :wallet-balance="$wallet_balance" />
            </aside>
        </div>
    </div>
</x-dashboard-layout>
