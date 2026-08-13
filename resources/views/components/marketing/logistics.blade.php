@props([
    'modes' => [],
])

@php
    $logisticsHref = route('logistics.index');
    $modeMap = $modes ?: [
        'fleet' => [
            'label' => __('home.logistics.fleet.label'),
            'kicker' => __('home.logistics.fleet.kicker'),
            'title' => __('home.logistics.fleet.title'),
            'copy' => __('home.logistics.fallback_copy'),
            'cta' => __('home.logistics.fleet.cta'),
            'href' => $logisticsHref,
            'network' => __('home.logistics.network_cta'),
            'stats' => [],
            'items' => [],
        ],
    ];
    $firstKey = array_key_first($modeMap) ?: 'fleet';
    $first = $modeMap[$firstKey];
    $firstItem = $first['items'][0] ?? null;
@endphp

<section
    id="logistics"
    class="cyra-section bg-cyra-surface cyra-reveal"
    x-data="cyraReveal"
>
    <div
        class="cyra-container"
        x-data="{
            mode: @js($firstKey),
            active: 0,
            modes: @js($modeMap),
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
                        <x-marketing.icon name="truck" class="h-4 w-4" />
                    </span>
                    {{ __('home.logistics.kicker') }}
                </p>
                <h2 class="cyra-section-heading mt-3">{{ __('home.logistics.heading') }}</h2>
                <p class="cyra-section-copy">
                    {{ __('home.logistics.copy') }}
                </p>
            </div>

            <div class="cyra-tabs" role="tablist" aria-label="{{ __('home.logistics.tabs_aria') }}">
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

        <div class="mt-8 grid gap-5 lg:grid-cols-12 lg:gap-6">
            <a
                :href="selected ? (selected.href || current.href) : current.href"
                href="{{ $firstItem['href'] ?? $logisticsHref }}"
                class="cyra-media relative min-h-[280px] bg-cyra-forest lg:col-span-7 lg:min-h-[420px]"
            >
                <template x-if="selected">
                    <img
                        :src="selected.image"
                        src="{{ $firstItem['image'] ?? asset('images/logistics/truck-10t.jpg') }}"
                        :alt="selected.name"
                        alt="{{ $firstItem['name'] ?? __('home.logistics.fallback_alt') }}"
                        class="absolute inset-0 h-full w-full object-cover object-[center_65%] saturate-[0.9]"
                        loading="lazy"
                    >
                </template>
                <div class="absolute inset-0 bg-gradient-to-b from-cyra-forest/70 via-cyra-forest/25 to-transparent"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-cyra-forest/95 via-cyra-forest/50 to-cyra-mint/30"></div>

                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyra-soft" x-text="current.kicker">
                        {{ $first['kicker'] }}
                    </p>
                    <template x-if="selected">
                        <div>
                            <h3 class="mt-2 font-display text-xl font-bold text-white sm:text-2xl" x-text="selected.name"></h3>
                            <p class="mt-1.5 text-sm text-white/85">
                                <span x-text="selected.route"></span>
                                <span class="mx-1.5 text-white/40">·</span>
                                <span x-text="selected.eta"></span>
                            </p>
                        </div>
                    </template>
                    <template x-if="!selected">
                        <div>
                            <h3 class="mt-2 font-display text-xl font-bold text-white sm:text-2xl" x-text="current.title"></h3>
                            <p class="mt-1.5 text-sm text-white/85">{{ __('home.logistics.empty_media') }}</p>
                        </div>
                    </template>
                    <span class="cyra-btn-primary mt-5 bg-white text-cyra-forest hover:bg-cyra-mint">
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
                        <div class="rounded-xl bg-cyra-card px-2 py-3 text-center ring-1 ring-cyra-line/80 sm:px-3 sm:py-4">
                            <dd class="font-display text-base font-extrabold text-cyra-forest sm:text-lg" x-text="stat.value"></dd>
                            <dt class="mt-1 text-[10px] font-medium text-cyra-muted sm:text-xs" x-text="stat.label"></dt>
                        </div>
                    </template>
                </dl>

                <div class="space-y-2" role="listbox" aria-label="{{ __('home.logistics.list_aria') }}">
                    <template x-if="(current.items || []).length === 0">
                        <div class="rounded-xl bg-cyra-card px-4 py-6 text-center text-sm text-cyra-muted ring-1 ring-cyra-line/80">
                            {{ __('home.logistics.empty_panel') }}
                        </div>
                    </template>
                    <template x-for="(item, index) in current.items" :key="mode + '-' + index">
                        <button
                            type="button"
                            role="option"
                            :aria-selected="active === index"
                            @click="active = index"
                            class="flex w-full items-center gap-3 rounded-xl bg-cyra-card p-2.5 text-left ring-1 transition sm:p-3"
                            :class="active === index ? 'ring-cyra-forest shadow-soft' : 'ring-cyra-line/80 hover:ring-cyra-soft'"
                        >
                            <img :src="item.image" :alt="item.name" class="h-14 w-16 shrink-0 rounded-lg object-cover sm:h-16 sm:w-20" loading="lazy">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-display text-sm font-bold text-cyra-ink" x-text="item.name"></span>
                                <span class="mt-0.5 block truncate text-xs text-cyra-forest" x-text="item.route"></span>
                                <span class="mt-0.5 block truncate text-xs text-cyra-muted" x-text="item.meta"></span>
                            </span>
                        </button>
                    </template>
                </div>

                <a :href="current.href" href="{{ $logisticsHref }}" class="cyra-btn-secondary mt-auto w-full sm:w-auto">
                    <x-marketing.icon name="truck" class="h-4 w-4" />
                    <span x-text="current.network">{{ __('home.logistics.network_cta') }}</span>
                </a>
            </div>
        </div>
    </div>
</section>
