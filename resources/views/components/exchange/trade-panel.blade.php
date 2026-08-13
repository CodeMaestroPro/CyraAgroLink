@props([
    'commodity',
    'walletBalance' => 0,
])

<article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
    <h2 class="text-base font-extrabold text-cyra-ink">Trade</h2>
    <p class="mt-1 text-xs text-cyra-muted">
        Wallet balance
        <span class="font-bold text-cyra-ink">₦{{ number_format((int) $walletBalance) }}</span>
        · Buys lock funds until matched or cancelled.
    </p>

    <form method="POST" action="{{ route('exchange.order', $commodity) }}" class="mt-4 space-y-4">
        @csrf
        <input type="hidden" name="side" :value="side">

        <div class="grid grid-cols-2 overflow-hidden rounded-xl ring-1 ring-cyra-line">
            <button
                type="button"
                class="px-4 py-2.5 text-sm font-bold transition"
                :class="side === 'buy' ? 'bg-cyra-forest text-white' : 'bg-white text-cyra-ink'"
                @click="side = 'buy'"
            >
                Buy
            </button>
            <button
                type="button"
                class="px-4 py-2.5 text-sm font-bold transition"
                :class="side === 'sell' ? 'bg-rose-600 text-white' : 'bg-white text-cyra-ink'"
                @click="side = 'sell'"
            >
                Sell
            </button>
        </div>

        <div>
            <label for="quantity_tons" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Quantity (Ton)</label>
            <input
                id="quantity_tons"
                type="number"
                min="1"
                name="quantity_tons"
                x-model.number="qty"
                required
                class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >
            <x-input-error :messages="$errors->get('quantity_tons')" class="mt-2" />
        </div>

        <div>
            <label for="price_per_ton" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Price (₦/Ton)</label>
            <input
                id="price_per_ton"
                type="number"
                min="1"
                name="price_per_ton"
                x-model.number="price"
                required
                class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >
            <x-input-error :messages="$errors->get('price_per_ton')" class="mt-2" />
        </div>

        <p class="text-xs text-cyra-muted">
            Estimated total
            <span class="font-bold text-cyra-ink" x-text="'₦' + Number((qty || 0) * (price || 0)).toLocaleString()"></span>
        </p>

        <a href="{{ route('wallet.index') }}" class="block text-xs font-semibold text-cyra-forest hover:underline">
            Fund wallet
        </a>

        <button
            type="submit"
            class="inline-flex w-full items-center justify-center rounded-lg bg-cyra-forest px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
            x-text="side === 'buy' ? 'Place Buy Order' : 'Place Sell Order'"
        >
            Place Buy Order
        </button>
    </form>
</article>
