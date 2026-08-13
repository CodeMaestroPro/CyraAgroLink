<x-dashboard-layout
    title="Agricultural Equipment Marketplace"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Marketplace'],
        ['label' => 'Equipment'],
    ]"
>
    <x-page-header
        title="Agricultural Equipment Marketplace"
        description="Browse equipment, add Buy or Rent items to your cart, then confirm payment from your digital wallet."
    >
        <x-slot:actions>
            <a
                href="{{ $actions['wallet_url'] }}"
                class="inline-flex items-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white"
            >
                Wallet · ₦{{ number_format($walletBalance) }}
            </a>
            <a
                href="{{ $actions['orders_url'] }}"
                class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
            >
                My orders ({{ $ordersCount }})
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

    <div class="flex flex-wrap items-center gap-3 border-b border-cyra-line pb-3" role="tablist" aria-label="Marketplace view">
        @foreach ([
            'browse' => 'Browse',
            'cart' => 'Cart ('.$cartCount.')',
            'orders' => 'Orders ('.$ordersCount.')',
        ] as $key => $label)
            <a
                href="{{ route('equipment.marketplace', array_filter([
                    'view' => $key,
                    'tab' => $key === 'browse' ? $activeTab : null,
                    'category' => $key === 'browse' ? $activeCategory : null,
                    'q' => $key === 'browse' && $query ? $query : null,
                ])) }}"
                role="tab"
                aria-selected="{{ $view === $key ? 'true' : 'false' }}"
                @class([
                    'border-b-2 pb-2 text-sm font-bold transition',
                    'border-cyra-forest text-cyra-ink' => $view === $key,
                    'border-transparent text-cyra-muted hover:text-cyra-ink' => $view !== $key,
                ])
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($view === 'browse')
        <div id="browse" class="mt-5 flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ $actions['search_url'] }}" class="relative min-w-0 flex-1">
                <input type="hidden" name="view" value="browse">
                @if ($activeTab)
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                @endif
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                @endif
                <label class="block">
                    <span class="sr-only">Search equipment</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cyra-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.3-4.3M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                    <input
                        type="search"
                        name="q"
                        value="{{ $query }}"
                        placeholder="Search equipment, brands..."
                        class="w-full rounded-xl border-0 bg-white py-2.5 pl-10 pr-4 text-sm text-cyra-ink shadow-sm ring-1 ring-cyra-line placeholder:text-cyra-muted focus:ring-2 focus:ring-cyra-forest"
                    >
                </label>
            </form>

            <a
                id="cart"
                href="{{ $actions['cart_url'] }}"
                class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-cyra-muted shadow-sm ring-1 ring-cyra-line hover:text-cyra-forest"
                aria-label="Shopping cart"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 6h14M10 19a1 1 0 100 2 1 1 0 000-2zm8 0a1 1 0 100 2 1 1 0 000-2z"/></svg>
                @if ($cartCount > 0)
                    <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-cyra-forest px-1 text-[10px] font-bold text-white">
                        {{ $cartCount }}
                    </span>
                @endif
            </a>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-[13rem_minmax(0,1fr)]">
            <aside class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5" aria-label="Equipment categories">
                <x-section-heading title="Categories" tone="mint" class="mb-3 !px-3 !py-2.5" />
                <ul class="mt-1 space-y-0.5">
                    <li>
                        <a
                            href="{{ route('equipment.marketplace', array_filter(['view' => 'browse', 'tab' => $activeTab, 'q' => $query ?: null])) }}"
                            @class([
                                'flex items-center justify-between rounded-xl px-2.5 py-2.5 text-sm font-semibold transition',
                                'bg-cyra-mint/70 text-cyra-forest' => ! $activeCategory,
                                'text-cyra-ink hover:bg-cyra-mint/70' => $activeCategory,
                            ])
                        >
                            <span>All categories</span>
                        </a>
                    </li>
                    @foreach ($categories as $category)
                        <li>
                            <a
                                href="{{ $category['url'] }}"
                                @class([
                                    'flex items-center justify-between rounded-xl px-2.5 py-2.5 text-sm font-semibold transition',
                                    'bg-cyra-mint/70 text-cyra-forest' => $category['active'],
                                    'text-cyra-ink hover:bg-cyra-mint/70' => ! $category['active'],
                                ])
                            >
                                <span>{{ $category['name'] }}</span>
                                <svg class="h-4 w-4 text-cyra-muted" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd"/></svg>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 px-2 text-xs text-cyra-muted">{{ $favoritesCount }} favorite{{ $favoritesCount === 1 ? '' : 's' }} saved</p>
            </aside>

            <div id="listings">
                <div class="flex flex-wrap items-center gap-5 border-b border-cyra-line" role="tablist" aria-label="Listing type">
                    @foreach ($tabs as $tab)
                        <a
                            href="{{ $tab['url'] }}"
                            role="tab"
                            aria-selected="{{ $tab['active'] ? 'true' : 'false' }}"
                            @class([
                                'border-b-2 pb-2.5 text-sm font-bold transition',
                                'border-cyra-forest text-cyra-ink' => $tab['active'],
                                'border-transparent text-cyra-muted hover:text-cyra-ink' => ! $tab['active'],
                            ])
                        >
                            {{ $tab['label'] }}
                        </a>
                    @endforeach
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @forelse ($listings as $item)
                        <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-cyra-line transition hover:ring-cyra-forest/30">
                            <div class="relative aspect-[4/3] bg-gradient-to-br from-cyra-panel to-cyra-mint">
                                <img
                                    src="{{ $item['image'] }}"
                                    alt="{{ $item['name'] }}"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                >
                                <form method="POST" action="{{ $item['favorite_url'] }}" class="absolute right-3 top-3">
                                    @csrf
                                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                                    @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
                                    @if ($query)<input type="hidden" name="q" value="{{ $query }}">@endif
                                    <button
                                        type="submit"
                                        @class([
                                            'inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/90 shadow-sm ring-1 ring-cyra-line',
                                            'text-rose-500' => $item['favorited'],
                                            'text-cyra-muted hover:text-rose-500' => ! $item['favorited'],
                                        ])
                                        aria-label="{{ $item['favorited'] ? 'Unfavorite' : 'Favorite' }} {{ $item['name'] }}"
                                    >
                                        <svg class="h-4 w-4" @if($item['favorited']) fill="currentColor" @else fill="none" @endif viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.3 12.3C2.9 10.9 2.9 8.6 4.3 7.2A3.5 3.5 0 019 7.2L12 10l3-2.8a3.5 3.5 0 014.7 0c1.4 1.4 1.4 3.7 0 5.1L12 21l-7.7-8.7z"/></svg>
                                    </button>
                                </form>
                                <span class="absolute left-3 top-3 rounded-lg bg-cyra-forest/90 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-white">
                                    {{ $item['category'] }}
                                </span>
                            </div>
                            <div class="bg-cyra-surface/40 p-4">
                                <h3 class="font-display truncate text-base font-bold text-cyra-ink">{{ $item['name'] }}</h3>
                                <p class="mt-1 font-display text-lg font-extrabold tabular-nums text-cyra-forest">{{ $item['price'] }}</p>
                                <p class="text-xs text-cyra-muted">≈ {{ $item['price_ngn'] }}{{ $item['is_rent'] ? '/day' : '' }} from wallet · {{ $item['stock'] }} in stock</p>
                                <div class="mt-2 flex items-center justify-between gap-2 text-sm">
                                    <span class="inline-flex min-w-0 items-center gap-1 text-cyra-muted">
                                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s7-5.2 7-11a7 7 0 10-14 0c0 5.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5" stroke-width="1.8"/></svg>
                                        <span class="truncate">{{ $item['location'] }}</span>
                                    </span>
                                    <span class="inline-flex shrink-0 items-center gap-1 font-semibold text-cyra-sun">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                        {{ $item['rating'] }}
                                    </span>
                                </div>

                                <form method="POST" action="{{ $item['cart_url'] }}" class="mt-3 space-y-2">
                                    @csrf
                                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                                    @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory }}">@endif
                                    @if ($query)<input type="hidden" name="q" value="{{ $query }}">@endif
                                    <div class="flex flex-wrap gap-2">
                                        <label class="min-w-0 flex-1">
                                            <span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-cyra-muted">Qty</span>
                                            <input
                                                type="number"
                                                name="quantity"
                                                value="1"
                                                min="1"
                                                max="{{ max(1, $item['stock']) }}"
                                                class="w-full rounded-lg border-0 bg-white px-2.5 py-2 text-sm ring-1 ring-cyra-line focus:ring-2 focus:ring-cyra-forest"
                                                @disabled(! $item['available'])
                                            >
                                        </label>
                                        @if ($item['is_rent'])
                                            <label class="min-w-0 flex-1">
                                                <span class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-cyra-muted">Days</span>
                                                <input
                                                    type="number"
                                                    name="rental_days"
                                                    value="1"
                                                    min="1"
                                                    max="365"
                                                    class="w-full rounded-lg border-0 bg-white px-2.5 py-2 text-sm ring-1 ring-cyra-line focus:ring-2 focus:ring-cyra-forest"
                                                    @disabled(! $item['available'])
                                                >
                                            </label>
                                        @endif
                                    </div>
                                    <button
                                        type="submit"
                                        @disabled(! $item['available'])
                                        @class([
                                            'inline-flex w-full items-center justify-center rounded-xl px-3 py-2.5 text-sm font-bold transition',
                                            'bg-cyra-forest text-white hover:bg-cyra-green' => $item['available'],
                                            'cursor-not-allowed bg-cyra-line text-cyra-muted' => ! $item['available'],
                                        ])
                                    >
                                        {{ $item['available'] ? $item['cta'] : 'Out of stock' }}
                                    </button>
                                    @if ($item['available'])
                                        <p class="text-center text-[11px] text-cyra-muted">Adds to cart — you pay at checkout</p>
                                    @endif
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="sm:col-span-2 rounded-2xl bg-white p-6 text-sm text-cyra-muted ring-1 ring-cyra-line">
                            No equipment matches this filter. Try another category or tab.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @elseif ($view === 'cart')
        <section class="mt-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Your equipment cart</h2>
                    <p class="mt-1 text-sm text-cyra-muted">
                        {{ $cartCount }} item{{ $cartCount === 1 ? '' : 's' }}
                        @if ($cartTotal > 0)
                            · Total ₦{{ number_format($cartTotal) }}
                        @endif
                    </p>
                </div>
                <a href="{{ $actions['browse_url'] }}" class="text-sm font-bold text-cyra-forest hover:underline">
                    Continue browsing
                </a>
            </div>

            @if (count($cartItems) === 0)
                <div class="mt-6 rounded-2xl bg-white px-6 py-12 text-center ring-1 ring-cyra-line">
                    <p class="text-sm font-semibold text-cyra-ink">Your cart is empty.</p>
                    <p class="mt-1 text-sm text-cyra-muted">Add equipment to buy or rent, then checkout with your wallet.</p>
                    <a
                        href="{{ $actions['browse_url'] }}"
                        class="mt-4 inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green"
                    >
                        Browse equipment
                    </a>
                </div>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($cartItems as $item)
                        <article class="rounded-2xl bg-white p-4 ring-1 ring-cyra-line sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex min-w-0 items-start gap-3">
                                    <img
                                        src="{{ $item['image'] }}"
                                        alt="{{ $item['name'] }}"
                                        class="h-16 w-16 rounded-xl object-cover ring-1 ring-cyra-line"
                                    >
                                    <div>
                                        <h3 class="text-sm font-extrabold text-cyra-ink">{{ $item['name'] }}</h3>
                                        <p class="mt-1 text-sm text-cyra-muted">{{ $item['type'] }} · {{ $item['unit_price'] }}</p>
                                        <p class="mt-1 text-sm font-bold text-cyra-ink">Line total {{ $item['line_total'] }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-end gap-2">
                                    <form method="POST" action="{{ $item['update_url'] }}" class="flex flex-wrap items-end gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label for="qty-{{ $item['id'] }}" class="mb-1 block text-xs font-bold text-cyra-muted">Qty</label>
                                            <input
                                                id="qty-{{ $item['id'] }}"
                                                type="number"
                                                min="1"
                                                max="{{ max(1, $item['stock']) }}"
                                                name="quantity"
                                                value="{{ $item['quantity'] }}"
                                                class="w-20 rounded-lg border border-cyra-line px-2.5 py-2 text-sm"
                                            >
                                        </div>
                                        @if ($item['is_rent'])
                                            <div>
                                                <label for="days-{{ $item['id'] }}" class="mb-1 block text-xs font-bold text-cyra-muted">Days</label>
                                                <input
                                                    id="days-{{ $item['id'] }}"
                                                    type="number"
                                                    min="1"
                                                    max="365"
                                                    name="rental_days"
                                                    value="{{ $item['rental_days'] }}"
                                                    class="w-20 rounded-lg border border-cyra-line px-2.5 py-2 text-sm"
                                                >
                                            </div>
                                        @endif
                                        <button type="submit" class="rounded-lg border border-cyra-line px-3 py-2 text-xs font-bold text-cyra-ink hover:bg-cyra-surface">
                                            Update
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ $item['remove_url'] }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <form method="POST" action="{{ $actions['checkout_url'] }}" class="mt-6 rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                    @csrf
                    <h3 class="text-sm font-extrabold text-cyra-ink">Review &amp; pay</h3>
                    <p class="mt-1 text-sm text-cyra-muted">
                        Confirm your cart below. Your wallet is charged only when you complete payment — stock updates after a successful charge.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-cyra-surface px-4 py-3 ring-1 ring-cyra-line">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-cyra-muted">Wallet balance</p>
                            <p class="mt-0.5 text-sm font-extrabold text-cyra-ink">₦{{ number_format($walletBalance) }}</p>
                        </div>
                        @if (! $walletCanPayCart)
                            <a href="{{ $actions['wallet_url'] }}" class="text-xs font-bold text-cyra-forest hover:underline">
                                Fund wallet to pay ₦{{ number_format($cartTotal) }}
                            </a>
                        @else
                            <span class="text-xs font-bold text-cyra-forest">Ready to pay this cart</span>
                        @endif
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-extrabold text-cyra-ink">
                            Order total ₦{{ number_format($cartTotal) }}
                        </p>
                        <button
                            type="submit"
                            @disabled(! $walletCanPayCart)
                            onclick="return confirm('Charge ₦{{ number_format($cartTotal) }} to your wallet and place this order?');"
                            class="inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Confirm &amp; pay
                        </button>
                    </div>
                </form>
            @endif
        </section>
    @else
        <section class="mt-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">My Equipment Orders</h2>
                    <p class="mt-1 text-sm text-cyra-muted">Paid buy, rent, and parts orders from your wallet.</p>
                </div>
                <a href="{{ $actions['browse_url'] }}" class="text-sm font-bold text-cyra-forest hover:underline">
                    Browse equipment
                </a>
            </div>

            <div class="mt-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <ul class="space-y-3">
                    @forelse ($orders as $order)
                        <li class="flex flex-wrap items-center justify-between gap-2 border-b border-cyra-line/70 pb-3 last:border-0 last:pb-0">
                            <div>
                                <p class="text-sm font-bold text-cyra-ink">{{ $order['reference'] }} · {{ $order['title'] }}</p>
                                <p class="text-xs text-cyra-muted">{{ $order['type'] }} · {{ $order['detail'] }} · {{ $order['when'] }} · {{ $order['status'] }}</p>
                            </div>
                            <span class="text-sm font-bold tabular-nums text-cyra-forest">{{ $order['amount'] }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-cyra-muted">No equipment orders yet. Buy, rent, or checkout from your cart.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    @endif
</x-dashboard-layout>
