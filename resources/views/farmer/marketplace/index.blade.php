<x-dashboard-layout
    title="Smart Marketplace"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Smart Marketplace'],
    ]"
>
    <x-page-header
        title="Smart Marketplace"
        description="Discover commodities, place buy orders, and publish listings to the home page."
    />

    @if (session('status'))
        <div class="mb-5 rounded-xl bg-cyra-mint px-4 py-3 text-sm font-medium text-cyra-forest ring-1 ring-cyra-soft/60" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Marketplace views">
        @foreach ([
            'commodities' => 'Commodities',
            'suppliers' => 'Suppliers',
            'listings' => 'My listings',
            'orders' => 'Orders ('.$orders_count.')',
        ] as $key => $label)
            <a
                href="{{ route('marketplace.index', array_filter(['view' => $key, 'q' => $query ?: null, 'category' => $category, 'state' => $state])) }}"
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

    @if ($can_list && in_array($view, ['commodities', 'listings'], true))
        <section id="list-product" class="mb-6 scroll-mt-24 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6" aria-labelledby="list-product-heading">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="list-product-heading" class="font-display text-lg font-bold text-cyra-ink">List a product</h2>
                    <p class="mt-1 text-sm text-cyra-muted">Upload a commodity listing — new items appear on the public home page automatically.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('marketplace.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Product name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required
                        class="mt-1.5 w-full rounded-xl border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20"
                        placeholder="e.g. Premium Maize">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="category_id" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Category</label>
                    <select id="category_id" name="category_id"
                        class="mt-1.5 w-full rounded-xl border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                        <option value="">Select category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="price_per_ton" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Price per ton (₦)</label>
                    <input id="price_per_ton" name="price_per_ton" type="number" min="1" value="{{ old('price_per_ton') }}" required
                        class="mt-1.5 w-full rounded-xl border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20"
                        placeholder="320000">
                    @error('price_per_ton') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="city" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">City</label>
                    <input id="city" name="city" type="text" value="{{ old('city') }}"
                        class="mt-1.5 w-full rounded-xl border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20"
                        placeholder="Ibadan">
                    @error('city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="state" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">State</label>
                    <select id="state" name="state"
                        class="mt-1.5 w-full rounded-xl border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20">
                        <option value="">Select state</option>
                        @foreach ($states as $option)
                            <option value="{{ $option }}" @selected(old('state') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('state') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="image" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Product image</label>
                    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                        class="mt-1.5 block w-full text-sm text-cyra-muted file:mr-3 file:rounded-lg file:border-0 file:bg-cyra-mint file:px-3 file:py-2 file:text-sm file:font-semibold file:text-cyra-forest">
                    @error('image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-end sm:col-span-2 lg:col-span-3">
                    <button type="submit" class="ml-auto inline-flex rounded-lg bg-cyra-forest px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-cyra-green">
                        Publish listing
                    </button>
                </div>
            </form>
        </section>
    @endif

    <form method="GET" action="{{ route('marketplace.index') }}" class="grid gap-3 sm:grid-cols-[1fr_180px_auto]">
        <input type="hidden" name="view" value="{{ $view === 'suppliers' ? 'commodities' : $view }}">
        @if ($category)
            <input type="hidden" name="category" value="{{ $category }}">
        @endif

        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-cyra-muted" aria-hidden="true">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
                </svg>
            </span>
            <input
                type="search"
                name="q"
                value="{{ $query }}"
                placeholder="Search commodities, farms, or sellers..."
                class="w-full rounded-xl border border-cyra-line bg-white py-3 pl-11 pr-4 text-sm text-cyra-ink placeholder:text-cyra-muted shadow-sm transition focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
            >
        </div>

        <select name="state" class="rounded-xl border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20" onchange="this.form.submit()">
            <option value="">All states</option>
            @foreach ($states as $option)
                <option value="{{ $option }}" @selected($state === $option)>{{ $option }}</option>
            @endforeach
        </select>

        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-3 text-sm font-bold text-white transition hover:bg-cyra-green">
            Search
        </button>
    </form>

    <div @class([
        'mt-6 grid gap-6',
        'lg:grid-cols-[220px_minmax(0,1fr)]' => ! in_array($view, ['orders', 'listings'], true),
    ])>
        @if (! in_array($view, ['orders', 'listings'], true))
            <x-marketplace.category-nav
                :categories="$categories"
                :active="$category"
                :query="$query"
                :state="$state"
            />
        @endif

        <div class="min-w-0 space-y-8">
            @if ($view === 'commodities')
                <section>
                    <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">
                        {{ $query !== '' || $category || $state ? 'Search Results' : 'Featured Commodities' }}
                    </h2>

                    @if ($commodities->isEmpty())
                        <p class="mt-4 text-sm text-cyra-muted">No commodities matched your filters.</p>
                    @else
                        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                            @foreach ($commodities as $commodity)
                                <x-marketplace.commodity-card :commodity="$commodity" />
                            @endforeach
                        </div>
                    @endif
                </section>

                <section>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Top Suppliers</h2>
                        <a
                            href="{{ route('marketplace.index', ['view' => 'suppliers']) }}"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-cyra-forest transition hover:text-cyra-green"
                        >
                            View all
                        </a>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ($suppliers as $supplier)
                            <x-marketplace.supplier-card :supplier="$supplier" />
                        @endforeach
                    </div>
                </section>
            @elseif ($view === 'suppliers')
                <section>
                    <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">All Suppliers</h2>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        @forelse ($suppliers as $supplier)
                            <x-marketplace.supplier-card :supplier="$supplier" />
                        @empty
                            <p class="text-sm text-cyra-muted">No suppliers available.</p>
                        @endforelse
                    </div>
                </section>
            @elseif ($view === 'listings')
                <section>
                    <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">My listings</h2>
                    @if ($my_listings->isEmpty())
                        <p class="mt-4 text-sm text-cyra-muted">You have not published any listings yet.</p>
                    @else
                        <div class="mt-4 space-y-4">
                            @foreach ($my_listings as $listing)
                                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold text-cyra-ink">{{ $listing->name }}</h3>
                                            <p class="text-sm text-cyra-muted">
                                                {{ $listing->formattedPrice() }} · {{ $listing->status }}
                                                @if ($listing->locationLabel()) · {{ $listing->locationLabel() }} @endif
                                            </p>
                                        </div>
                                        <a href="{{ route('exchange.show', $listing) }}" class="text-sm font-bold text-cyra-forest hover:underline">Open exchange</a>
                                    </div>

                                    @if ($listing->status === 'active')
                                        <form method="POST" action="{{ route('marketplace.update', $listing) }}" class="mt-4 grid gap-3 sm:grid-cols-4">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label class="text-xs font-semibold text-cyra-muted">Price / ton</label>
                                                <input type="number" name="price_per_ton" min="1" value="{{ $listing->price_per_ton }}" required class="mt-1 w-full rounded-lg border-cyra-line text-sm">
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-cyra-muted">City</label>
                                                <input type="text" name="city" value="{{ $listing->city }}" class="mt-1 w-full rounded-lg border-cyra-line text-sm">
                                            </div>
                                            <div>
                                                <label class="text-xs font-semibold text-cyra-muted">State</label>
                                                <select name="state" class="mt-1 w-full rounded-lg border-cyra-line text-sm">
                                                    <option value="">—</option>
                                                    @foreach ($states as $option)
                                                        <option value="{{ $option }}" @selected($listing->state === $option)>{{ $option }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="flex items-end gap-2">
                                                <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white hover:bg-cyra-green">Update</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('marketplace.destroy', $listing) }}" class="mt-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-rose-700 hover:underline" onclick="return confirm('Deactivate this listing?')">Deactivate listing</button>
                                        </form>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            @elseif ($view === 'orders')
                <section>
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Your orders</h2>
                            <p class="mt-1 text-sm text-cyra-muted">
                                {{ $orders_count }} open
                                @if ($orders_value > 0)
                                    · Open value ₦{{ number_format($orders_value) }}
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach (['all' => 'All', 'open' => 'Open', 'filled' => 'Filled', 'cancelled' => 'Cancelled'] as $key => $label)
                                <a
                                    href="{{ route('marketplace.index', ['view' => 'orders', 'order_status' => $key]) }}"
                                    @class([
                                        'rounded-full px-3 py-1 text-xs font-bold ring-1 transition',
                                        'bg-cyra-forest text-white ring-cyra-forest' => ($order_status ?? 'all') === $key,
                                        'bg-white text-cyra-ink ring-cyra-line hover:bg-cyra-surface' => ($order_status ?? 'all') !== $key,
                                    ])
                                >
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if ($orders->isEmpty())
                        <div class="mt-6 rounded-2xl bg-white px-6 py-12 text-center ring-1 ring-cyra-line">
                            <p class="text-sm font-semibold text-cyra-ink">No orders in this filter.</p>
                            <p class="mt-1 text-sm text-cyra-muted">Use Buy on a commodity card to place an order.</p>
                            <a
                                href="{{ route('marketplace.index', ['view' => 'commodities']) }}"
                                class="mt-5 inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green"
                            >
                                Browse commodities
                            </a>
                        </div>
                    @else
                        <div class="mt-4 space-y-4">
                            @foreach ($orders as $order)
                                @php
                                    $statusClass = match ($order->status) {
                                        'filled' => 'text-cyra-forest',
                                        'cancelled' => 'text-rose-600',
                                        default => 'text-amber-600',
                                    };
                                    $lineTotal = $order->quantity_tons * $order->price_per_ton;
                                @endphp
                                <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h3 class="font-semibold text-cyra-ink">
                                                <a href="{{ route('exchange.show', $order->commodity_id) }}" class="hover:text-cyra-forest">
                                                    {{ $order->commodity?->name ?? 'Commodity #'.$order->commodity_id }}
                                                </a>
                                            </h3>
                                            <p class="mt-1 text-sm text-cyra-muted">
                                                {{ strtoupper($order->side) }} ·
                                                ₦{{ number_format($order->price_per_ton) }}/ton ·
                                                Total ₦{{ number_format($lineTotal) }}
                                            </p>
                                            <p class="mt-1 text-xs text-cyra-muted">
                                                Placed {{ $order->created_at?->format('d M Y H:i') }}
                                            </p>
                                        </div>
                                        <span class="rounded-full bg-cyra-surface px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </div>

                                    @if ($order->status === 'open')
                                        <div class="mt-4 flex flex-wrap items-end gap-3 border-t border-cyra-line/70 pt-4">
                                            <form method="POST" action="{{ route('marketplace.orders.update', $order) }}" class="flex items-end gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <label class="block text-xs font-semibold text-cyra-muted" for="qty-{{ $order->id }}">Qty (tons)</label>
                                                    <input
                                                        id="qty-{{ $order->id }}"
                                                        type="number"
                                                        name="quantity_tons"
                                                        min="1"
                                                        value="{{ $order->quantity_tons }}"
                                                        required
                                                        class="mt-1 w-24 rounded-lg border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20"
                                                    >
                                                </div>
                                                <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white hover:bg-cyra-green">
                                                    Update
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('marketplace.orders.cancel', $order) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50"
                                                    onclick="return confirm('Cancel this order?')"
                                                >
                                                    Cancel
                                                </button>
                                            </form>

                                            <a
                                                href="{{ route('exchange.show', $order->commodity_id) }}"
                                                class="ml-auto text-xs font-bold text-cyra-forest hover:underline"
                                            >
                                                Open exchange
                                            </a>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-dashboard-layout>
