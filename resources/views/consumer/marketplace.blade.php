<x-dashboard-layout
    title="Consumer Marketplace"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Marketplace', 'href' => route('marketplace.index')],
        ['label' => 'Consumer Shop'],
    ]"
>
    <x-page-header
        title="Shop Fresh. Eat Healthy."
        description="Browse fresh farm products, manage your cart, and track retail orders."
    >
        <x-slot:actions>
            <a
                href="{{ route('consumer.marketplace', ['view' => 'cart']) }}"
                class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-cyra-ink shadow-sm ring-1 ring-cyra-line transition hover:text-cyra-forest"
                aria-label="Shopping cart"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 2.3c-.4.4-.1 1.1.4 1.1H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                @if ($cartCount > 0)
                    <span class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                        {{ $cartCount }}
                    </span>
                @endif
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-cyra-mint px-4 py-3 text-sm font-medium text-cyra-forest ring-1 ring-cyra-soft/60" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
            <a href="{{ route('wallet.index') }}" class="ml-2 font-bold underline">Fund wallet</a>
        </div>
    @endif

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Consumer marketplace views">
        @foreach ([
            'shop' => 'Shop',
            'cart' => 'Cart ('.$cart_count.')',
            'orders' => 'Orders ('.$orders_count.')',
        ] as $key => $label)
            <a
                href="{{ route('consumer.marketplace', array_filter(['view' => $key, 'q' => $query ?: null, 'category' => $category])) }}"
                @class([
                    'rounded-full px-3.5 py-1.5 text-sm font-bold ring-1 transition',
                    'bg-cyra-forest text-white ring-cyra-forest' => $view === $key,
                    'bg-white text-cyra-ink ring-cyra-line hover:bg-cyra-surface' => $view !== $key,
                ])
            >
                {{ $label }}
            </a>
        @endforeach
    </nav>

    @if ($view === 'shop')
        <form method="GET" action="{{ route('consumer.marketplace') }}" class="flex items-stretch gap-2" role="search">
            <input type="hidden" name="view" value="shop">
            @if ($category)
                <input type="hidden" name="category" value="{{ $category }}">
            @endif
            <div class="relative min-w-0 flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-cyra-muted" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                    </svg>
                </span>
                <input
                    type="search"
                    name="q"
                    value="{{ $query }}"
                    placeholder="Search for products..."
                    class="w-full rounded-xl border border-cyra-line bg-white py-3 pl-11 pr-4 text-sm text-cyra-ink placeholder:text-cyra-muted shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
            </div>
            <button
                type="submit"
                class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-cyra-forest text-white shadow-sm transition hover:bg-cyra-green"
                aria-label="Search products"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                </svg>
            </button>
        </form>

        <nav class="mt-6 flex flex-wrap gap-2" aria-label="Product categories">
            <a
                href="{{ route('consumer.marketplace', array_filter(['view' => 'shop', 'q' => $query ?: null])) }}"
                @class([
                    'rounded-xl px-3 py-2 text-sm font-semibold ring-1 transition',
                    'bg-cyra-forest text-white ring-cyra-forest' => empty($category),
                    'bg-white text-cyra-ink ring-cyra-line hover:bg-cyra-mint' => ! empty($category),
                ])
            >
                All
            </a>
            @foreach ($categories as $cat)
                <a
                    href="{{ route('consumer.marketplace', array_filter(['view' => 'shop', 'category' => $cat['id'], 'q' => $query ?: null])) }}"
                    @class([
                        'rounded-xl px-3 py-2 text-sm font-semibold ring-1 transition',
                        'bg-cyra-forest text-white ring-cyra-forest' => $category === $cat['id'],
                        'bg-white text-cyra-ink ring-cyra-line hover:bg-cyra-mint' => $category !== $cat['id'],
                    ])
                >
                    {{ $cat['label'] }}
                </a>
            @endforeach
        </nav>

        <x-section-heading
            class="mt-2"
            title="{{ ($query !== '' || $category) ? 'Search Results' : 'Best Sellers' }}"
            description="Fresh products from trusted farms and processors."
        />

        <section id="best-sellers" class="mt-1" aria-labelledby="best-sellers-heading">
            <h2 id="best-sellers-heading" class="sr-only">
                {{ ($query !== '' || $category) ? 'Search Results' : 'Best Sellers' }}
            </h2>

            @if ($products->isEmpty())
                <p class="mt-4 text-sm text-cyra-muted">No products matched your search.</p>
            @else
                <div class="mt-5 grid grid-cols-2 gap-4 sm:gap-5 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <article class="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-cyra-line transition hover:ring-cyra-forest/30">
                            <div class="aspect-square overflow-hidden bg-cyra-panel">
                                <img
                                    src="{{ $product->imageUrl() }}"
                                    alt="{{ $product->name }}"
                                    class="h-full w-full object-cover"
                                >
                            </div>
                            <div class="flex flex-1 flex-col p-3.5 text-center sm:p-4">
                                <h3 class="text-sm font-extrabold text-cyra-ink sm:text-base">
                                    {{ $product->name }}
                                </h3>
                                <p class="mt-1 text-sm font-bold tabular-nums text-cyra-ink">
                                    {{ $product->formattedPrice() }}
                                </p>
                                <p class="mt-1 text-xs text-cyra-muted">
                                    {{ number_format($product->stock_qty) }} in stock
                                </p>
                                <form method="POST" action="{{ route('consumer.cart.add', $product) }}" class="mt-3">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <button
                                        type="submit"
                                        @disabled(! $product->inStock())
                                        class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-3 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {{ $product->inStock() ? 'Add to Cart' : 'Out of stock' }}
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @elseif ($view === 'cart')
        <section>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Your cart</h2>
                    <p class="mt-1 text-sm text-cyra-muted">
                        {{ $cart_count }} item{{ $cart_count === 1 ? '' : 's' }}
                        @if ($cart_total > 0)
                            · Total {{ '₦'.number_format($cart_total) }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('consumer.marketplace', ['view' => 'shop']) }}" class="text-sm font-bold text-cyra-forest hover:underline">
                    Continue shopping
                </a>
            </div>

            @if ($cart_items->isEmpty())
                <div class="mt-6 rounded-2xl bg-white px-6 py-12 text-center ring-1 ring-cyra-line">
                    <p class="text-sm font-semibold text-cyra-ink">Your cart is empty.</p>
                    <p class="mt-1 text-sm text-cyra-muted">Add fresh products from the shop to get started.</p>
                    <a
                        href="{{ route('consumer.marketplace', ['view' => 'shop']) }}"
                        class="mt-4 inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green"
                    >
                        Browse products
                    </a>
                </div>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($cart_items as $item)
                        @continue(! $item->product)
                        <article class="rounded-2xl bg-white p-4 ring-1 ring-cyra-line sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="flex min-w-0 items-start gap-3">
                                    <img
                                        src="{{ $item->product->imageUrl() }}"
                                        alt="{{ $item->product->name }}"
                                        class="h-16 w-16 rounded-xl object-cover ring-1 ring-cyra-line"
                                    >
                                    <div>
                                        <h3 class="text-sm font-extrabold text-cyra-ink">{{ $item->product->name }}</h3>
                                        <p class="mt-1 text-sm text-cyra-muted">{{ $item->product->formattedPrice() }}</p>
                                        <p class="mt-1 text-sm font-bold text-cyra-ink">
                                            Line total ₦{{ number_format($item->lineTotal()) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-end gap-2">
                                    <form method="POST" action="{{ route('consumer.cart.update', $item) }}" class="flex items-end gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label for="qty-{{ $item->id }}" class="mb-1 block text-xs font-bold text-cyra-muted">Qty</label>
                                            <input
                                                id="qty-{{ $item->id }}"
                                                type="number"
                                                min="1"
                                                max="{{ $item->product->stock_qty }}"
                                                name="quantity"
                                                value="{{ $item->quantity }}"
                                                class="w-20 rounded-lg border border-cyra-line px-2.5 py-2 text-sm"
                                            >
                                        </div>
                                        <button type="submit" class="rounded-lg border border-cyra-line px-3 py-2 text-xs font-bold text-cyra-ink hover:bg-cyra-surface">
                                            Update
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('consumer.cart.remove', $item) }}">
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

                <form method="POST" action="{{ route('consumer.checkout') }}" class="mt-6 rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                    @csrf
                    <h3 class="text-sm font-extrabold text-cyra-ink">Checkout</h3>
                    <p class="mt-1 text-sm text-cyra-muted">
                        Orders are paid from your digital wallet. Place the order, then pay with wallet.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-xl bg-cyra-surface px-4 py-3 ring-1 ring-cyra-line">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-cyra-muted">Wallet balance</p>
                            <p class="mt-0.5 text-sm font-extrabold text-cyra-ink">₦{{ number_format($wallet_balance) }}</p>
                        </div>
                        @if ($wallet_balance < $cart_total)
                            <a href="{{ route('wallet.index') }}" class="text-xs font-bold text-cyra-forest hover:underline">
                                Fund wallet to pay ₦{{ number_format($cart_total) }}
                            </a>
                        @else
                            <span class="text-xs font-bold text-cyra-forest">Enough to pay this cart</span>
                        @endif
                    </div>

                    <div class="mt-4">
                        <label for="delivery_note" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Delivery note (optional)</label>
                        <input
                            id="delivery_note"
                            type="text"
                            name="delivery_note"
                            value="{{ old('delivery_note') }}"
                            maxlength="255"
                            placeholder="e.g. Drop at gate, call on arrival"
                            class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                        >
                        <x-input-error :messages="$errors->get('delivery_note')" class="mt-2" />
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-extrabold text-cyra-ink">
                            Total ₦{{ number_format($cart_total) }}
                        </p>
                        <button
                            type="submit"
                            class="inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green"
                        >
                            Place order
                        </button>
                    </div>
                </form>
            @endif
        </section>
    @else
        <section>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Your orders</h2>
                    <p class="mt-1 text-sm text-cyra-muted">
                        Pay pending orders from your wallet (₦{{ number_format($wallet_balance) }} available).
                    </p>
                </div>
                <a href="{{ route('wallet.index') }}" class="text-sm font-bold text-cyra-forest hover:underline">
                    Fund wallet
                </a>
            </div>

            @if ($orders->isEmpty())
                <div class="mt-6 rounded-2xl bg-white px-6 py-12 text-center ring-1 ring-cyra-line">
                    <p class="text-sm font-semibold text-cyra-ink">No orders yet.</p>
                    <p class="mt-1 text-sm text-cyra-muted">Checkout items from your cart to place an order.</p>
                </div>
            @else
                <div class="mt-4 space-y-4">
                    @foreach ($orders as $order)
                        @php
                            $statusClass = match ($order->status) {
                                'paid' => 'text-cyra-forest',
                                'cancelled' => 'text-rose-600',
                                'delivered' => 'text-cyra-forest',
                                default => 'text-amber-700',
                            };
                        @endphp
                        <article class="rounded-2xl bg-white p-4 ring-1 ring-cyra-line sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-extrabold text-cyra-ink">
                                        Order #{{ $order->id }}
                                        · {{ $order->formattedTotal() }}
                                    </p>
                                    <p class="mt-1 text-xs text-cyra-muted">
                                        {{ $order->created_at?->format('d M Y, H:i') }}
                                        ·
                                        <span class="font-bold {{ $statusClass }}">{{ ucfirst($order->status) }}</span>
                                    </p>
                                    @if ($order->delivery_note)
                                        <p class="mt-1 text-xs text-cyra-muted">Note: {{ $order->delivery_note }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <a
                                        href="{{ route('consumer.orders.receipt', $order) }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="rounded-lg border border-cyra-line bg-white px-3 py-2 text-xs font-bold text-cyra-ink hover:bg-cyra-surface"
                                    >
                                        Print receipt
                                    </a>
                                    @if ($order->status === 'pending')
                                        @if ($wallet_balance >= $order->total_amount)
                                            <form method="POST" action="{{ route('consumer.orders.confirm', $order) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white hover:bg-cyra-green">
                                                    Pay with wallet
                                                </button>
                                            </form>
                                        @else
                                            <a
                                                href="{{ route('wallet.index') }}"
                                                class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100"
                                            >
                                                Fund wallet (need ₦{{ number_format($order->total_amount) }})
                                            </a>
                                        @endif
                                        <form method="POST" action="{{ route('consumer.orders.cancel', $order) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50"
                                                onclick="return confirm('Cancel this order?')"
                                            >
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <ul class="mt-3 space-y-1 border-t border-cyra-line/70 pt-3 text-sm text-cyra-muted">
                                @foreach ($order->items as $line)
                                    <li>
                                        {{ $line->product_name }}
                                        · {{ number_format($line->quantity) }} {{ $line->unit }}
                                        @ ₦{{ number_format($line->unit_price) }}
                                        = ₦{{ number_format($line->line_total) }}
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</x-dashboard-layout>
