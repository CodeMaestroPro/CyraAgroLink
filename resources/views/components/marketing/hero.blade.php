<section class="relative isolate overflow-hidden bg-cyra-forest">
    <div class="absolute inset-0">
        <img
            src="{{ asset('images/hero-farmer.jpg') }}"
            alt=""
            class="h-full w-full object-cover object-[center_28%] opacity-90 motion-safe:animate-[cyra-kenburns_18s_ease-out_forwards]"
            width="1600"
            height="1000"
            fetchpriority="high"
            decoding="async"
            aria-hidden="true"
        >
        <div class="absolute inset-0 bg-gradient-to-r from-cyra-forest/95 via-cyra-forest/75 to-cyra-ink/45"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-cyra-ink/50 via-transparent to-cyra-forest/20"></div>
    </div>

    <div class="relative cyra-container flex min-h-[78vh] flex-col justify-center px-4 py-20 sm:min-h-[82vh] sm:px-6 sm:py-24 lg:px-8 lg:py-28">
        <div class="max-w-2xl motion-safe:animate-[cyra-fade-up_0.8s_ease-out_both]">
            <p class="inline-flex items-center gap-2 font-display text-xs font-semibold uppercase tracking-[0.2em] text-cyra-soft sm:text-sm">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-white motion-safe:animate-[cyra-pulse-soft_3s_ease-in-out_infinite]">
                    <x-marketing.icon name="leaf" class="h-4 w-4" />
                </span>
                {{ config('cyra.brand') }}
            </p>

            <h1 class="mt-4 font-display text-3xl font-extrabold leading-[1.12] tracking-tight text-white sm:text-5xl lg:text-[3.25rem] lg:leading-[1.08]">
                {{ __('home.hero.headline') }}
            </h1>

            <p class="mt-5 max-w-xl text-sm leading-relaxed text-white/85 sm:mt-6 sm:text-lg">
                {{ __('home.hero.subcopy') }}
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:mt-9 sm:flex-row sm:flex-wrap sm:items-center">
                @auth
                    <a href="{{ route('dashboard') }}" class="cyra-btn-primary bg-white text-cyra-forest hover:bg-cyra-mint">
                        <x-marketing.icon name="arrow-right" class="h-4 w-4" />
                        {{ __('home.hero.cta_dashboard') }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="cyra-btn-primary bg-white text-cyra-forest hover:bg-cyra-mint">
                        <x-marketing.icon name="arrow-right" class="h-4 w-4" />
                        {{ __('home.hero.cta_get_started') }}
                    </a>
                @endauth

                <a
                    href="#solutions"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/40 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                >
                    <x-marketing.icon name="play" class="h-4 w-4" />
                    {{ __('home.hero.cta_explore') }}
                </a>
            </div>
        </div>
    </div>
</section>
