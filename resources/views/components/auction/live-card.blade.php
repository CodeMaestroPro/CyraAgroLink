@props([
    'auction',
    'bidUrl' => null,
    'filter' => null,
])

<article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#F3EDE6] text-cyra-soil">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h8l1 4H7l1-4zm-1 4h10v8a1 1 0 01-1 1H8a1 1 0 01-1-1v-8z"/></svg>
            </span>
            <div>
                <h3 class="text-base font-extrabold text-cyra-ink">{{ $auction['name'] }}</h3>
                <p class="text-xs text-cyra-muted">{{ $auction['quantity'] ?? '' }}</p>
            </div>
        </div>

        <div
            class="text-sm font-semibold text-cyra-ink"
            x-data="auctionCountdown('{{ $auction['ends_at'] }}')"
            x-init="start()"
        >
            Ends in:
            <span class="font-bold tabular-nums text-cyra-forest" x-text="display"></span>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-center">
        <div class="aspect-square overflow-hidden rounded-xl bg-cyra-panel ring-1 ring-cyra-line">
            <img
                src="{{ asset($auction['image']) }}"
                alt="{{ $auction['name'] }}"
                class="h-full w-full object-cover"
                onerror="this.src='{{ asset('images/marketplace/commodity-placeholder.svg') }}'"
            >
        </div>

        <div>
            <p class="text-sm font-medium text-cyra-muted">Highest Bid</p>
            <p class="mt-1 text-xl font-extrabold tabular-nums text-cyra-ink">{{ $auction['highest_bid'] }}</p>
            <p class="mt-1 text-sm text-cyra-muted">
                Bidder:
                <span class="font-bold text-cyra-forest">{{ $auction['bidder'] }}</span>
            </p>

            @if ($auction['is_leading'] ?? false)
                <p class="mt-4 rounded-xl bg-cyra-mint/50 px-3 py-2 text-sm font-bold text-cyra-forest ring-1 ring-cyra-line">
                    You are leading this auction
                </p>
            @elseif ($auction['can_bid'] ?? false)
                <form method="POST" action="{{ $bidUrl ?? $auction['bid_url'] }}" class="mt-4 space-y-2">
                    @csrf
                    <input type="hidden" name="auction_id" value="{{ $auction['id'] }}">
                    @if ($filter && $filter !== 'All Commodities')
                        <input type="hidden" name="commodity" value="{{ $filter }}">
                    @endif
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Your bid (min {{ $auction['min_bid_label'] }})</span>
                        <input
                            type="number"
                            name="amount_ngn"
                            value="{{ $auction['min_bid'] }}"
                            min="{{ $auction['min_bid'] }}"
                            step="1000"
                            required
                            class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                        >
                    </label>
                    <button
                        type="submit"
                        onclick="return confirm('Place this bid? The amount will be held from your wallet until you are outbid or win.');"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
                    >
                        Place Bid
                    </button>
                </form>
            @else
                <p class="mt-4 text-sm text-cyra-muted">Bidding closed for this lot.</p>
            @endif
        </div>
    </div>
</article>
