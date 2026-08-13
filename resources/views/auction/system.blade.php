<x-dashboard-layout
    title="Commodity Auction System"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Trading'],
        ['label' => 'Auctions'],
    ]"
>
    <x-page-header
        title="Commodity Auction System"
        description="Bid on live commodity lots with wallet escrow. Outbid funds are refunded automatically."
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

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <section id="live" class="xl:col-span-2" aria-labelledby="live-auctions-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="live-auctions-heading" class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
                    Live Auctions
                </h2>

                <div
                    class="relative"
                    x-data="{ open: false }"
                    @click.outside="open = false"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-3.5 py-2 text-sm font-semibold text-cyra-ink shadow-sm ring-1 ring-cyra-line"
                        @click="open = !open"
                    >
                        <span>{{ $activeFilter }}</span>
                        <svg class="h-4 w-4 text-cyra-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div
                        x-cloak
                        x-show="open"
                        class="absolute right-0 z-10 mt-2 w-48 overflow-hidden rounded-xl bg-white py-1 shadow-soft ring-1 ring-cyra-line"
                    >
                        @foreach ($filters as $filter)
                            <a
                                href="{{ $filter['url'] }}"
                                @class([
                                    'block w-full px-4 py-2 text-left text-sm font-medium hover:bg-cyra-mint',
                                    'bg-cyra-mint/60 text-cyra-forest' => $filter['active'],
                                    'text-cyra-ink' => ! $filter['active'],
                                ])
                            >
                                {{ $filter['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-5 space-y-4">
                @forelse ($live as $auction)
                    <x-auction.live-card
                        :auction="$auction"
                        :bid-url="$actions['bid_url']"
                        :filter="$activeFilter"
                    />
                @empty
                    <div class="rounded-2xl bg-white p-6 text-sm text-cyra-muted ring-1 ring-cyra-line">
                        No live auctions for this filter. Try another commodity or check back soon.
                    </div>
                @endforelse
            </div>
        </section>

        <div class="space-y-5">
            <section id="history" aria-labelledby="auction-history-heading">
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                    <h2 id="auction-history-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">
                        Auction History
                    </h2>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full min-w-[16rem] text-left text-sm">
                            <thead>
                                <tr class="border-b border-cyra-line text-cyra-muted">
                                    <th class="pb-2 pr-3 font-semibold">Commodity</th>
                                    <th class="pb-2 pr-3 font-semibold">Condition</th>
                                    <th class="pb-2 font-semibold">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($history as $row)
                                    <tr class="border-b border-cyra-line/70 last:border-0">
                                        <td class="py-3 pr-3 font-semibold text-cyra-ink">{{ $row['commodity'] }}</td>
                                        <td class="py-3 pr-3 font-semibold text-cyra-forest">{{ $row['status'] }}</td>
                                        <td class="py-3 font-bold tabular-nums text-cyra-ink">{{ $row['price'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-3 text-cyra-muted">No completed auctions yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <a
                        href="{{ route('auction.system') }}"
                        class="mt-5 inline-flex w-full items-center justify-center rounded-xl border-2 border-cyra-line bg-cyra-surface/60 px-4 py-2.5 text-sm font-bold text-cyra-ink transition hover:border-cyra-forest hover:text-cyra-forest"
                    >
                        View All Auctions
                    </a>
                </article>
            </section>

            <section id="bidders" aria-labelledby="my-bids-heading">
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                    <h2 id="my-bids-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">My Bids</h2>
                    <ul class="mt-4 space-y-3">
                        @forelse ($myBids as $bid)
                            <li class="border-b border-cyra-line/70 pb-3 last:border-0 last:pb-0">
                                <p class="text-sm font-bold text-cyra-ink">{{ $bid['reference'] }} · {{ $bid['auction'] }}</p>
                                <p class="text-xs text-cyra-muted">{{ $bid['amount'] }} · {{ $bid['status'] }} · {{ $bid['when'] }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-cyra-muted">No bids yet. Place a bid on a live auction.</li>
                        @endforelse
                    </ul>
                </article>
            </section>
        </div>
    </div>
</x-dashboard-layout>
