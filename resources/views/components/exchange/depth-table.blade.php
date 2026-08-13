@props([
    'orders' => [],
    'side' => 'buy',
])

@php
    $isBuy = $side === 'buy';
@endphp

<div class="overflow-hidden rounded-xl ring-1 {{ $isBuy ? 'ring-emerald-100' : 'ring-rose-100' }}">
    <div class="grid grid-cols-2 px-3 py-2 text-xs font-bold uppercase tracking-wide {{ $isBuy ? 'bg-emerald-50 text-cyra-forest' : 'bg-rose-50 text-rose-700' }}">
        <span>Price</span>
        <span class="text-right">Qty</span>
    </div>
    @if (count($orders) === 0)
        <p class="px-3 py-6 text-center text-sm text-cyra-muted">
            No live {{ $isBuy ? 'buy' : 'sell' }} orders
        </p>
    @else
        <ul class="divide-y {{ $isBuy ? 'divide-emerald-50 bg-emerald-50/40' : 'divide-rose-50 bg-rose-50/40' }}">
            @foreach ($orders as $order)
                <li>
                    <button
                        type="button"
                        class="grid w-full grid-cols-2 px-3 py-2 text-left text-sm transition hover:bg-white/70"
                        @click="setFromDepth({{ (int) $order['price'] }}, '{{ $side }}')"
                        title="Use this price in the trade ticket"
                    >
                        <span @class(['font-semibold', 'text-cyra-forest' => $isBuy, 'text-rose-600' => ! $isBuy])>
                            ₦{{ number_format($order['price']) }}
                        </span>
                        <span class="text-right font-medium text-cyra-ink">{{ number_format($order['qty']) }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
