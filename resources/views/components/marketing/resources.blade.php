@props([
    'tracks' => [],
])

@php
    $academyHref = route('academy.learning');
    $trackMap = $tracks ?: [
        'featured' => [
            'label' => __('home.resources.featured.label'),
            'blurb' => __('home.resources.fallback_blurb'),
            'courses' => [],
        ],
    ];
    $firstKey = array_key_first($trackMap) ?: 'featured';
    $first = $trackMap[$firstKey];
    $firstCourse = $first['courses'][0] ?? null;
    $courseCount = collect($trackMap)->sum(fn ($track) => count($track['courses'] ?? []));
@endphp

<section
    id="resources"
    class="cyra-section bg-cyra-card cyra-reveal"
    x-data="cyraReveal"
>
    <div
        class="cyra-container"
        x-data="{
            track: @js($firstKey),
            tracks: @js($trackMap),
            get active() { return this.tracks[this.track] || Object.values(this.tracks)[0] },
            get featured() { return (this.active?.courses || [])[0] || null },
            get secondary() { return (this.active?.courses || []).slice(1) },
        }"
    >
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="cyra-section-kicker inline-flex items-center gap-2">
                    <span class="cyra-icon-badge h-8 w-8">
                        <x-marketing.icon name="book" class="h-4 w-4" />
                    </span>
                    {{ __('home.resources.kicker') }}
                </p>
                <h2 class="cyra-section-heading mt-3">{{ __('home.resources.heading') }}</h2>
                <p class="cyra-section-copy">
                    {{ __('home.resources.copy') }}
                </p>
            </div>

            <div class="cyra-tabs" role="tablist" aria-label="{{ __('home.resources.tabs_aria') }}">
                <template x-for="key in Object.keys(tracks)" :key="key">
                    <button
                        type="button"
                        role="tab"
                        :aria-selected="track === key"
                        @click="track = key"
                        class="cyra-tab"
                        :class="track === key ? 'cyra-tab-active' : 'cyra-tab-idle'"
                        x-text="tracks[key].label"
                    ></button>
                </template>
            </div>
        </div>

        <template x-if="featured">
            <div class="mt-8 grid gap-5 lg:grid-cols-12 lg:gap-6">
                <a :href="featured.href || '{{ $academyHref }}'" href="{{ $academyHref }}" class="cyra-media group relative min-h-[280px] lg:col-span-7 lg:min-h-[420px]">
                    <img
                        :src="featured.image"
                        src="{{ $firstCourse['image'] ?? asset('images/academy/maize-farming.jpg') }}"
                        :alt="featured.title"
                        alt="{{ $firstCourse['title'] ?? __('home.resources.fallback_alt') }}"
                        class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                        loading="lazy"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-cyra-forest/90 via-cyra-forest/40 to-transparent"></div>

                    <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-white/85">
                            <span class="rounded-md bg-white/20 px-2 py-1 font-semibold uppercase tracking-wide text-white" x-text="featured.level"></span>
                            <span x-text="featured.duration"></span>
                            <span class="inline-flex items-center gap-1 text-cyra-soft">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                <span x-text="featured.rating"></span>
                            </span>
                        </div>
                        <h3 class="mt-3 font-display text-xl font-bold text-white sm:text-2xl" x-text="featured.title"></h3>
                        <p class="mt-1.5 max-w-md text-sm text-white/85" x-text="featured.focus"></p>
                        <span class="cyra-btn-primary mt-5 bg-white text-cyra-forest group-hover:bg-cyra-mint">
                            <x-marketing.icon name="arrow-right" class="h-4 w-4" />
                            {{ __('home.resources.cta_start') }}
                        </span>
                    </div>
                </a>

                <div class="flex flex-col gap-4 lg:col-span-5">
                    <p class="text-sm leading-relaxed text-cyra-muted" x-text="active.blurb"></p>

                    <template x-for="(course, index) in secondary" :key="track + '-' + index">
                        <a
                            :href="course.href || '{{ $academyHref }}'"
                            href="{{ $academyHref }}"
                            class="group flex gap-3 rounded-2xl bg-cyra-surface p-3 ring-1 ring-cyra-line/80 transition hover:bg-cyra-card hover:shadow-soft hover:ring-cyra-soft sm:gap-4"
                        >
                            <img
                                :src="course.image"
                                :alt="course.title"
                                class="h-20 w-24 shrink-0 rounded-xl object-cover sm:h-24 sm:w-28"
                                loading="lazy"
                            >
                            <span class="min-w-0 flex-1 py-0.5">
                                <span class="block text-[11px] text-cyra-muted">
                                    <span x-text="course.level"></span>
                                    <span aria-hidden="true"> · </span>
                                    <span x-text="course.duration"></span>
                                </span>
                                <span class="mt-1 block font-display text-sm font-bold text-cyra-ink sm:text-base" x-text="course.title"></span>
                                <span class="mt-1 block line-clamp-2 text-xs text-cyra-muted sm:text-sm" x-text="course.focus"></span>
                            </span>
                        </a>
                    </template>

                    <div class="mt-auto rounded-2xl bg-cyra-mint/70 p-5 ring-1 ring-cyra-soft/50 sm:p-6">
                        <p class="cyra-section-kicker">{{ __('home.resources.path.kicker') }}</p>
                        <ol class="mt-4 space-y-3">
                            @foreach ([
                                __('home.resources.path.step_1'),
                                __('home.resources.path.step_2'),
                                __('home.resources.path.step_3'),
                            ] as $step => $label)
                                <li class="flex items-start gap-3">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyra-forest font-display text-xs font-bold text-white">
                                        {{ $step + 1 }}
                                    </span>
                                    <span class="pt-1 text-sm leading-snug text-cyra-ink">{{ $label }}</span>
                                </li>
                            @endforeach
                        </ol>
                        <a href="{{ $academyHref }}" class="cyra-btn-primary mt-5 w-full">
                            <x-marketing.icon name="book" class="h-4 w-4" />
                            {{ __('home.resources.cta_academy') }}
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <template x-if="!featured">
            <div class="cyra-panel mt-8 px-6 py-10 text-center">
                <p class="text-sm text-cyra-muted">{{ __('home.resources.empty') }}</p>
                <a href="{{ $academyHref }}" class="cyra-btn-primary mt-5">{{ __('home.resources.cta_academy') }}</a>
            </div>
        </template>

        <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 border-t border-cyra-line pt-6 text-sm text-cyra-muted">
            <span class="inline-flex items-center gap-2"><x-marketing.icon name="check" class="h-4 w-4 text-cyra-forest" /> {{ __('home.resources.footer.live_lessons', ['count' => $courseCount]) }}</span>
            <span class="inline-flex items-center gap-2"><x-marketing.icon name="leaf" class="h-4 w-4 text-cyra-forest" /> {{ __('home.resources.footer.playbooks') }}</span>
            <span class="inline-flex items-center gap-2"><x-marketing.icon name="star" class="h-4 w-4 text-cyra-forest" /> {{ __('home.resources.footer.progress') }}</span>
        </div>
    </div>
</section>
