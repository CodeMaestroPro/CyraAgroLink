@props([
    'tracks' => [],
])

@php
    $investHref = route('investments.index');
    $dashboardHref = route('investor.dashboard');
    $modes = $tracks ?: [
        'featured' => [
            'label' => __('home.invest.featured.label'),
            'kicker' => __('home.invest.featured.kicker'),
            'title' => __('home.invest.featured.title'),
            'copy' => __('home.invest.fallback_copy'),
            'cta' => __('home.invest.featured.cta'),
            'href' => $investHref,
            'network' => __('home.invest.featured.network'),
            'network_href' => $dashboardHref,
            'stats' => [],
            'items' => [],
        ],
    ];
    $firstKey = array_key_first($modes) ?: 'featured';
    $first = $modes[$firstKey];
    $firstItem = $first['items'][0] ?? null;
@endphp

<section
    id="invest"
    class="cyra-section bg-cyra-card cyra-reveal"
    x-data="cyraReveal"
>
    <div
        class="cyra-container"
        x-data="{
            mode: @js($firstKey),
            active: 0,
            modes: @js($modes),
            get current() { return this.modes[this.mode] || Object.values(this.modes)[0] },
            get selected() {
                const items = this.current?.items || [];
                return items[this.active] || items[0] || null;
            },
            selectMode(key) { this.mode = key; this.active = 0 },
        }"
    >
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="cyra-section-kicker inline-flex items-center gap-2">
                    <span class="cyra-icon-badge h-8 w-8">
                        <x-marketing.icon name="invest" class="h-4 w-4" />
                    </span>
                    {{ __('home.invest.kicker') }}
                </p>
                <h2 class="cyra-section-heading mt-3">{{ __('home.invest.heading') }}</h2>
                <p class="cyra-section-copy">
                    {{ __('home.invest.copy') }}
                </p>
            </div>

            <div class="cyra-tabs" role="tablist" aria-label="{{ __('home.invest.tabs_aria') }}">
                <template x-for="key in Object.keys(modes)" :key="key">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="mode === key"
                        @click="selectMode(key)"
                        class="cyra-tab"
                        :class="mode === key ? 'cyra-tab-active' : 'cyra-tab-idle'"
                        x-text="modes[key].label"
                    ></button>
                </template>
            </div>
        </div>

        <template x-if="selected">
            <div class="mt-8 grid gap-5 lg:grid-cols-12 lg:gap-6">
                <a
                    :href="selected.href || current.href"
                    href="{{ $firstItem['href'] ?? $investHref }}"
                    class="cyra-media group relative min-h-[280px] bg-cyra-forest lg:col-span-7 lg:min-h-[420px]"
                >
                    <img
                        :src="selected.image"
                        src="{{ $firstItem['image'] ?? asset('images/investments/maize-1.jpg') }}"
                        :alt="selected.name"
                        alt="{{ $firstItem['name'] ?? __('home.invest.fallback_alt') }}"
                        class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]"
                        loading="lazy"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-cyra-forest/95 via-cyra-forest/45 to-cyra-forest/20"></div>

                    <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyra-soft" x-text="current.kicker">
                            {{ $first['kicker'] }}
                        </p>
                        <h3 class="mt-2 font-display text-xl font-bold text-white sm:text-2xl" x-text="selected.name">
                            {{ $firstItem['name'] ?? '' }}
                        </h3>
                        <p class="mt-1.5 text-sm text-white/85">
                            <span x-text="selected.route">{{ $firstItem['route'] ?? '' }}</span>
                            <span class="mx-1.5 text-white/40">·</span>
                            <span x-text="selected.eta">{{ $firstItem['eta'] ?? '' }}</span>
                        </p>
                        <p class="mt-1 text-sm text-white/75" x-text="selected.meta">{{ $firstItem['meta'] ?? '' }}</p>
                        <span class="cyra-btn-primary mt-5 bg-white text-cyra-forest group-hover:bg-cyra-mint">
                            <x-marketing.icon name="arrow-right" class="h-4 w-4" />
                            <span x-text="current.cta">{{ $first['cta'] }}</span>
                        </span>
                    </div>
                </a>

                <div class="flex flex-col gap-4 lg:col-span-5">
                    <div>
                        <h3 class="font-display text-lg font-bold text-cyra-ink sm:text-xl" x-text="current.title">
                            {{ $first['title'] }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-cyra-muted" x-text="current.copy">
                            {{ $first['copy'] }}
                        </p>
                    </div>

                    <dl class="grid grid-cols-3 gap-2 sm:gap-3">
                        <template x-for="(stat, i) in current.stats" :key="mode + '-stat-' + i">
                            <div class="rounded-xl bg-cyra-surface px-2 py-3 text-center ring-1 ring-cyra-line/80 sm:px-3 sm:py-4">
                                <dd class="font-display text-base font-extrabold text-cyra-forest sm:text-lg" x-text="stat.value"></dd>
                                <dt class="mt-1 text-[10px] font-medium text-cyra-muted sm:text-xs" x-text="stat.label"></dt>
                            </div>
                        </template>
                    </dl>

                    <div class="space-y-2" role="listbox" aria-label="{{ __('home.invest.list_aria') }}">
                        <template x-for="(item, index) in current.items" :key="mode + '-' + index">
                            <button
                                type="button"
                                role="option"
                                :aria-selected="active === index"
                                @click="active = index"
                                class="flex w-full items-center gap-3 rounded-xl bg-cyra-surface p-2.5 text-left ring-1 transition sm:p-3"
                                :class="active === index ? 'bg-cyra-card ring-cyra-forest shadow-soft' : 'ring-cyra-line/80 hover:ring-cyra-soft'"
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
                        <a :href="current.href" href="{{ $investHref }}" class="cyra-btn-primary flex-1">
                            <x-marketing.icon name="invest" class="h-4 w-4" />
                            <span x-text="current.cta">{{ __('home.invest.cta_browse') }}</span>
                        </a>
                        <a :href="current.network_href" href="{{ $dashboardHref }}" class="cyra-btn-secondary flex-1">
                            <span x-text="current.network">{{ __('home.invest.cta_dashboard') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!selected">
            <div class="cyra-panel mt-8 px-6 py-10 text-center">
                <p class="text-sm text-cyra-muted">{{ __('home.invest.empty') }}</p>
                <a href="{{ $investHref }}" class="cyra-btn-primary mt-5">{{ __('home.invest.empty_cta') }}</a>
            </div>
        </template>
    </div>
</section>
