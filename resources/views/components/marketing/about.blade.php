<section id="about" class="cyra-section bg-cyra-forest text-white cyra-reveal" x-data="cyraReveal">
    <div class="cyra-container">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)] lg:items-end lg:gap-14">
            <div>
                <p class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-cyra-soft">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/15 text-white">
                        <x-marketing.icon name="users" class="h-4 w-4" />
                    </span>
                    {{ __('home.about.kicker') }}
                </p>
                <h2 class="mt-3 font-display text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">
                    {{ __('home.about.heading', ['company' => config('cyra.company')]) }}
                </h2>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-white/85 sm:text-base">
                    {{ __('home.about.copy', ['brand' => config('cyra.brand')]) }}
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap lg:justify-end">
                @auth
                    <a href="{{ route('dashboard') }}" class="cyra-btn-primary bg-white text-cyra-forest hover:bg-cyra-mint">
                        <x-marketing.icon name="arrow-right" class="h-4 w-4" />
                        {{ __('home.about.cta_dashboard') }}
                    </a>
                @else
                    <a href="{{ route('register') }}" class="cyra-btn-primary bg-white text-cyra-forest hover:bg-cyra-mint">
                        <x-marketing.icon name="arrow-right" class="h-4 w-4" />
                        {{ __('home.about.cta_join', ['brand' => config('cyra.brand')]) }}
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/40 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        {{ __('home.about.cta_sign_in') }}
                    </a>
                @endauth
                <a href="#solutions" class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/40 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                    <x-marketing.icon name="leaf" class="h-4 w-4" />
                    {{ __('home.about.cta_solutions') }}
                </a>
            </div>
        </div>
    </div>
</section>
