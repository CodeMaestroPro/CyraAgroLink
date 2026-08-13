@props([
    'solutionStats' => [],
])

@php
    $liveStats = $solutionStats;
    $tracks = [
        'grow' => [
            'label' => __('home.solutions.grow.label'),
            'icon' => 'farmer',
            'kicker' => __('home.solutions.grow.kicker'),
            'title' => __('home.solutions.grow.title'),
            'copy' => __('home.solutions.grow.copy'),
            'cta' => auth()->check() ? __('home.solutions.grow.cta_auth') : __('home.solutions.grow.cta'),
            'href' => route('farms.register'),
            'network' => auth()->check() ? __('home.solutions.grow.secondary_auth') : __('home.solutions.grow.secondary'),
            'network_href' => auth()->check() ? route('crops.manage') : route('register'),
            'stats' => [
                ['label' => __('home.solutions.grow.stats.farms'), 'value' => $liveStats['grow'][0]['value'] ?? '0'],
                ['label' => __('home.solutions.grow.stats.farmers'), 'value' => $liveStats['grow'][1]['value'] ?? '0'],
                ['label' => __('home.solutions.grow.stats.courses'), 'value' => $liveStats['grow'][2]['value'] ?? '0'],
            ],
            'items' => [
                [
                    'name' => __('home.solutions.grow.items.farm_hub.name'),
                    'route' => __('home.solutions.grow.items.farm_hub.route'),
                    'meta' => __('home.solutions.grow.items.farm_hub.meta'),
                    'image' => asset('images/farms/green-valley.jpg'),
                    'eta' => __('home.solutions.grow.items.farm_hub.eta'),
                    'icon' => 'farmer',
                    'cta' => auth()->check()
                        ? __('home.solutions.grow.items.farm_hub.cta_auth')
                        : __('home.solutions.grow.items.farm_hub.cta'),
                    'href' => route('farms.register'),
                ],
                [
                    'name' => __('home.solutions.grow.items.precision.name'),
                    'route' => __('home.solutions.grow.items.precision.route'),
                    'meta' => __('home.solutions.grow.items.precision.meta'),
                    'image' => asset('images/academy/irrigation.jpg'),
                    'eta' => __('home.solutions.grow.items.precision.eta'),
                    'icon' => 'leaf',
                    'cta' => auth()->check()
                        ? __('home.solutions.grow.items.precision.cta_auth')
                        : __('home.solutions.grow.items.precision.cta'),
                    'href' => route('precision.agriculture'),
                ],
                [
                    'name' => __('home.solutions.grow.items.academy.name'),
                    'route' => __('home.solutions.grow.items.academy.route'),
                    'meta' => __('home.solutions.grow.items.academy.meta'),
                    'image' => asset('images/academy/maize-farming.jpg'),
                    'eta' => __('home.solutions.grow.items.academy.eta'),
                    'icon' => 'book',
                    'cta' => auth()->check()
                        ? __('home.solutions.grow.items.academy.cta_auth')
                        : __('home.solutions.grow.items.academy.cta'),
                    'href' => '#resources',
                ],
            ],
        ],
        'trade' => [
            'label' => __('home.solutions.trade.label'),
            'icon' => 'market',
            'kicker' => __('home.solutions.trade.kicker'),
            'title' => __('home.solutions.trade.title'),
            'copy' => __('home.solutions.trade.copy'),
            'cta' => auth()->check() ? __('home.solutions.trade.cta_auth') : __('home.solutions.trade.cta'),
            'href' => route('marketplace.index'),
            'network' => auth()->check() ? __('home.solutions.trade.secondary_auth') : __('home.solutions.trade.secondary'),
            'network_href' => route('marketplace.index'),
            'stats' => [
                ['label' => __('home.solutions.trade.stats.buyers'), 'value' => $liveStats['trade'][0]['value'] ?? '0'],
                ['label' => __('home.solutions.trade.stats.listings'), 'value' => $liveStats['trade'][1]['value'] ?? '0'],
                ['label' => __('home.solutions.trade.stats.trades'), 'value' => $liveStats['trade'][2]['value'] ?? '0'],
            ],
            'items' => [
                [
                    'name' => __('home.solutions.trade.items.marketplace.name'),
                    'route' => __('home.solutions.trade.items.marketplace.route'),
                    'meta' => __('home.solutions.trade.items.marketplace.meta'),
                    'image' => asset('images/marketplace/maize.jpg'),
                    'eta' => __('home.solutions.trade.items.marketplace.eta'),
                    'icon' => 'market',
                    'cta' => auth()->check()
                        ? __('home.solutions.trade.items.marketplace.cta_auth')
                        : __('home.solutions.trade.items.marketplace.cta'),
                    'href' => route('marketplace.index'),
                ],
                [
                    'name' => __('home.solutions.trade.items.exchange.name'),
                    'route' => __('home.solutions.trade.items.exchange.route'),
                    'meta' => __('home.solutions.trade.items.exchange.meta'),
                    'image' => asset('images/marketplace/rice.jpg'),
                    'eta' => __('home.solutions.trade.items.exchange.eta'),
                    'icon' => 'invest',
                    'cta' => auth()->check()
                        ? __('home.solutions.trade.items.exchange.cta_auth')
                        : __('home.solutions.trade.items.exchange.cta'),
                    'href' => route('exchange.show'),
                ],
                [
                    'name' => __('home.solutions.trade.items.storefront.name'),
                    'route' => __('home.solutions.trade.items.storefront.route'),
                    'meta' => __('home.solutions.trade.items.storefront.meta'),
                    'image' => asset('images/consumer/rice-ofada.jpg'),
                    'eta' => __('home.solutions.trade.items.storefront.eta'),
                    'icon' => 'cart',
                    'cta' => auth()->check()
                        ? __('home.solutions.trade.items.storefront.cta_auth')
                        : __('home.solutions.trade.items.storefront.cta'),
                    'href' => route('consumer.marketplace'),
                ],
            ],
        ],
        'capital' => [
            'label' => __('home.solutions.capital.label'),
            'icon' => 'invest',
            'kicker' => __('home.solutions.capital.kicker'),
            'title' => __('home.solutions.capital.title'),
            'copy' => __('home.solutions.capital.copy'),
            'cta' => auth()->check() ? __('home.solutions.capital.cta_auth') : __('home.solutions.capital.cta'),
            'href' => route('investments.index'),
            'network' => auth()->check() ? __('home.solutions.capital.secondary_auth') : __('home.solutions.capital.secondary'),
            'network_href' => route('investor.dashboard'),
            'stats' => [
                ['label' => __('home.solutions.capital.stats.roi_range'), 'value' => $liveStats['capital'][0]['value'] ?? __('home.common.em_dash')],
                ['label' => __('home.solutions.capital.stats.projects'), 'value' => $liveStats['capital'][1]['value'] ?? '0'],
                ['label' => __('home.solutions.capital.stats.investors'), 'value' => $liveStats['capital'][2]['value'] ?? '0'],
            ],
            'items' => [
                [
                    'name' => __('home.solutions.capital.items.marketplace.name'),
                    'route' => __('home.solutions.capital.items.marketplace.route'),
                    'meta' => __('home.solutions.capital.items.marketplace.meta'),
                    'image' => asset('images/investments/maize-expansion.jpg'),
                    'eta' => __('home.solutions.capital.items.marketplace.eta'),
                    'icon' => 'invest',
                    'cta' => auth()->check()
                        ? __('home.solutions.capital.items.marketplace.cta_auth')
                        : __('home.solutions.capital.items.marketplace.cta'),
                    'href' => route('investments.index'),
                ],
                [
                    'name' => __('home.solutions.capital.items.wallet.name'),
                    'route' => __('home.solutions.capital.items.wallet.route'),
                    'meta' => __('home.solutions.capital.items.wallet.meta'),
                    'image' => asset('images/investments/hero-field.jpg'),
                    'eta' => __('home.solutions.capital.items.wallet.eta'),
                    'icon' => 'wallet',
                    'cta' => auth()->check()
                        ? __('home.solutions.capital.items.wallet.cta_auth')
                        : __('home.solutions.capital.items.wallet.cta'),
                    'href' => route('wallet.index'),
                ],
                [
                    'name' => __('home.solutions.capital.items.insurance.name'),
                    'route' => __('home.solutions.capital.items.insurance.route'),
                    'meta' => __('home.solutions.capital.items.insurance.meta'),
                    'image' => asset('images/investments/rice-production.jpg'),
                    'eta' => __('home.solutions.capital.items.insurance.eta'),
                    'icon' => 'shield',
                    'cta' => auth()->check()
                        ? __('home.solutions.capital.items.insurance.cta_auth')
                        : __('home.solutions.capital.items.insurance.cta'),
                    'href' => route('insurance.platform'),
                ],
            ],
        ],
        'network' => [
            'label' => __('home.solutions.network.label'),
            'icon' => 'truck',
            'kicker' => __('home.solutions.network.kicker'),
            'title' => __('home.solutions.network.title'),
            'copy' => __('home.solutions.network.copy'),
            'cta' => auth()->check() ? __('home.solutions.network.cta_auth') : __('home.solutions.network.cta'),
            'href' => route('logistics.index'),
            'network' => auth()->check() ? __('home.solutions.network.secondary_auth') : __('home.solutions.network.secondary'),
            'network_href' => route('ai.command'),
            'stats' => [
                ['label' => __('home.solutions.network.stats.fleet'), 'value' => $liveStats['network'][0]['value'] ?? '0'],
                ['label' => __('home.solutions.network.stats.warehouses'), 'value' => $liveStats['network'][1]['value'] ?? '0'],
                ['label' => __('home.solutions.network.stats.in_transit'), 'value' => $liveStats['network'][2]['value'] ?? '0'],
            ],
            'items' => [
                [
                    'name' => __('home.solutions.network.items.logistics.name'),
                    'route' => __('home.solutions.network.items.logistics.route'),
                    'meta' => __('home.solutions.network.items.logistics.meta'),
                    'image' => asset('images/logistics/truck-10t.jpg'),
                    'eta' => __('home.solutions.network.items.logistics.eta'),
                    'icon' => 'truck',
                    'cta' => auth()->check()
                        ? __('home.solutions.network.items.logistics.cta_auth')
                        : __('home.solutions.network.items.logistics.cta'),
                    'href' => route('logistics.index'),
                ],
                [
                    'name' => __('home.solutions.network.items.warehouse.name'),
                    'route' => __('home.solutions.network.items.warehouse.route'),
                    'meta' => __('home.solutions.network.items.warehouse.meta'),
                    'image' => asset('images/logistics/truck-20t.jpg'),
                    'eta' => __('home.solutions.network.items.warehouse.eta'),
                    'icon' => 'warehouse',
                    'cta' => auth()->check()
                        ? __('home.solutions.network.items.warehouse.cta_auth')
                        : __('home.solutions.network.items.warehouse.cta'),
                    'href' => route('warehouse.index'),
                ],
                [
                    'name' => __('home.solutions.network.items.ai.name'),
                    'route' => __('home.solutions.network.items.ai.route'),
                    'meta' => __('home.solutions.network.items.ai.meta'),
                    'image' => asset('images/dashboard/ai-recommendation.jpg'),
                    'eta' => __('home.solutions.network.items.ai.eta'),
                    'icon' => 'ai',
                    'cta' => auth()->check()
                        ? __('home.solutions.network.items.ai.cta_auth')
                        : __('home.solutions.network.items.ai.cta'),
                    'href' => route('ai.command'),
                ],
            ],
        ],
    ];
@endphp

<section
    id="solutions"
    class="cyra-section bg-cyra-card cyra-reveal"
    x-data="cyraReveal"
>
    <div
        class="cyra-container"
        x-data="{
            mode: 'grow',
            active: 0,
            modes: @js($tracks),
            get current() { return this.modes[this.mode] },
            get selected() { return this.current.items[this.active] || this.current.items[0] },
            selectMode(key) { this.mode = key; this.active = 0 },
        }"
    >
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="cyra-section-kicker">{{ __('home.solutions.kicker') }}</p>
                <h2 class="cyra-section-heading mt-3">{{ __('home.solutions.heading') }}</h2>
                <p class="cyra-section-copy">
                    {{ __('home.solutions.copy') }}
                </p>
            </div>

            <div class="cyra-tabs" role="tablist" aria-label="{{ __('home.solutions.tabs_aria') }}">
                <template x-for="key in Object.keys(modes)" :key="key">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === key"
                        @click="selectMode(key)"
                        class="cyra-tab inline-flex items-center gap-1.5"
                        :class="mode === key ? 'cyra-tab-active' : 'cyra-tab-idle'"
                    >
                        <span x-text="modes[key].label"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="mt-8 grid gap-5 lg:grid-cols-12 lg:gap-6">
            <a
                :href="selected.href"
                href="{{ $tracks['grow']['items'][0]['href'] }}"
                class="cyra-media group relative min-h-[280px] bg-cyra-forest lg:col-span-7 lg:min-h-[420px]"
            >
                <img
                    :src="selected.image"
                    src="{{ $tracks['grow']['items'][0]['image'] }}"
                    :alt="selected.name"
                    alt="{{ $tracks['grow']['items'][0]['name'] }}"
                    class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]"
                    loading="lazy"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-cyra-forest/95 via-cyra-forest/45 to-cyra-forest/15"></div>

                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7">
                    <p class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-cyra-soft">
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/15 text-white">
                            <x-marketing.icon name="leaf" class="h-3.5 w-3.5" />
                        </span>
                        <span x-text="current.kicker">{{ $tracks['grow']['kicker'] }}</span>
                    </p>
                    <h3 class="mt-2 font-display text-xl font-bold text-white sm:text-2xl" x-text="selected.name">
                        {{ $tracks['grow']['items'][0]['name'] }}
                    </h3>
                    <p class="mt-1.5 text-sm text-white/85">
                        <span x-text="selected.route">{{ $tracks['grow']['items'][0]['route'] }}</span>
                        <span class="mx-1.5 text-white/40">·</span>
                        <span x-text="selected.eta">{{ $tracks['grow']['items'][0]['eta'] }}</span>
                    </p>
                    <p class="mt-1 text-sm text-white/75" x-text="selected.meta">{{ $tracks['grow']['items'][0]['meta'] }}</p>
                    <span class="cyra-btn-primary mt-5 bg-white text-cyra-forest group-hover:bg-cyra-mint">
                        <x-marketing.icon name="arrow-right" class="h-4 w-4" />
                        <span x-text="selected.cta">{{ $tracks['grow']['items'][0]['cta'] }}</span>
                    </span>
                </div>
            </a>

            <div class="flex flex-col gap-4 lg:col-span-5">
                <div>
                    <h3 class="font-display text-lg font-bold text-cyra-ink sm:text-xl" x-text="current.title">
                        {{ $tracks['grow']['title'] }}
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-cyra-muted" x-text="current.copy">
                        {{ $tracks['grow']['copy'] }}
                    </p>
                </div>

                <dl class="grid grid-cols-3 gap-2 sm:gap-3">
                    <template x-for="(stat, i) in current.stats" :key="mode + '-stat-' + i">
                        <div class="rounded-xl bg-cyra-surface px-2 py-3 text-center ring-1 ring-cyra-line/80 transition hover:-translate-y-0.5 hover:shadow-soft sm:px-3 sm:py-4">
                            <dd class="font-display text-base font-extrabold text-cyra-forest sm:text-lg" x-text="stat.value"></dd>
                            <dt class="mt-1 text-[10px] font-medium text-cyra-muted sm:text-xs" x-text="stat.label"></dt>
                        </div>
                    </template>
                </dl>

                <div class="space-y-2" role="listbox" aria-label="{{ __('home.solutions.list_aria') }}">
                    <template x-for="(item, index) in current.items" :key="mode + '-' + index">
                        <button
                            type="button"
                            role="option"
                            :aria-selected="active === index"
                            @click="active = index"
                            class="flex w-full items-center gap-3 rounded-xl bg-cyra-surface p-2.5 text-left ring-1 transition sm:p-3"
                            :class="active === index ? 'bg-white ring-cyra-forest shadow-soft' : 'ring-cyra-line/80 hover:ring-cyra-soft'"
                        >
                            <img :src="item.image" :alt="item.name" class="h-14 w-16 shrink-0 rounded-lg object-cover sm:h-16 sm:w-20" loading="lazy">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-display text-sm font-bold text-cyra-ink" x-text="item.name"></span>
                                <span class="mt-0.5 block truncate text-xs text-cyra-forest" x-text="item.route"></span>
                                <span class="mt-0.5 block truncate text-xs text-cyra-muted" x-text="item.meta"></span>
                            </span>
                            <span class="hidden shrink-0 rounded-full bg-cyra-mint px-2.5 py-1 text-[11px] font-semibold text-cyra-forest sm:inline-flex" x-text="item.eta"></span>
                        </button>
                    </template>
                </div>

                <div class="mt-auto flex flex-col gap-2 sm:flex-row">
                    <a :href="selected.href" href="{{ $tracks['grow']['items'][0]['href'] }}" class="cyra-btn-primary flex-1">
                        <x-marketing.icon name="arrow-right" class="h-4 w-4" />
                        <span x-text="selected.cta">{{ $tracks['grow']['items'][0]['cta'] }}</span>
                    </a>
                    <a :href="current.network_href" href="{{ $tracks['grow']['network_href'] }}" class="cyra-btn-secondary flex-1">
                        <span x-text="current.network">{{ $tracks['grow']['network'] }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
