<footer {{ $attributes->merge(['class' => 'mt-12 border-t border-cyra-leaf/40 bg-cyra-forest text-white']) }}>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-14 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                    <img
                        src="{{ asset('images/logo.svg') }}"
                        alt=""
                        class="h-10 w-10 brightness-0 invert"
                        width="40"
                        height="40"
                        aria-hidden="true"
                    >
                    <span class="font-display text-xl font-bold tracking-tight">{{ config('cyra.brand') }}</span>
                </a>
                <p class="mt-4 max-w-xs text-sm leading-relaxed text-white/75">
                    {{ __('home.footer.tagline') }}
                </p>
            </div>

            <div>
                <h2 class="font-display text-sm font-semibold uppercase tracking-[0.14em] text-cyra-soft">{{ __('home.footer.explore.heading') }}</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-white/80">
                    <li><a href="{{ route('home') }}#marketplace" class="transition hover:text-white">{{ __('home.footer.explore.marketplace') }}</a></li>
                    <li><a href="{{ route('home') }}#invest" class="transition hover:text-white">{{ __('home.footer.explore.invest') }}</a></li>
                    <li><a href="{{ route('home') }}#logistics" class="transition hover:text-white">{{ __('home.footer.explore.logistics') }}</a></li>
                    <li><a href="{{ route('home') }}#resources" class="transition hover:text-white">{{ __('home.footer.explore.resources') }}</a></li>
                    <li><a href="{{ route('home') }}#about" class="transition hover:text-white">{{ __('home.footer.explore.about') }}</a></li>
                </ul>
            </div>

            <div>
                <h2 class="font-display text-sm font-semibold uppercase tracking-[0.14em] text-cyra-soft">{{ __('home.footer.platform.heading') }}</h2>
                <ul class="mt-4 space-y-2.5 text-sm text-white/80">
                    @auth
                        <li><a href="{{ route('dashboard') }}" class="transition hover:text-white">{{ __('home.footer.platform.dashboard') }}</a></li>
                        <li><a href="{{ route('marketplace.index') }}" class="transition hover:text-white">{{ __('home.footer.platform.marketplace') }}</a></li>
                        <li><a href="{{ route('investments.index') }}" class="transition hover:text-white">{{ __('home.footer.platform.investments') }}</a></li>
                        <li><a href="{{ route('logistics.index') }}" class="transition hover:text-white">{{ __('home.footer.platform.logistics_network') }}</a></li>
                        <li><a href="{{ route('academy.learning') }}" class="transition hover:text-white">{{ __('home.footer.platform.academy') }}</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="transition hover:text-white">{{ __('home.footer.platform.sign_in') }}</a></li>
                        <li><a href="{{ route('register') }}" class="transition hover:text-white">{{ __('home.footer.platform.create_account') }}</a></li>
                        <li><a href="{{ route('home') }}#solutions" class="transition hover:text-white">{{ __('home.footer.platform.solutions') }}</a></li>
                        <li><a href="{{ route('home') }}#marketplace" class="transition hover:text-white">{{ __('home.footer.platform.live_listings') }}</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <h2 class="font-display text-sm font-semibold uppercase tracking-[0.14em] text-cyra-soft">{{ __('home.footer.get_started.heading') }}</h2>
                <p class="mt-4 text-sm leading-relaxed text-white/75">
                    {{ __('home.footer.get_started.copy') }}
                </p>
                <div class="mt-5">
                    @auth
                        <a href="{{ route('dashboard') }}" class="cyra-btn-primary bg-white text-cyra-forest hover:bg-cyra-mint">
                            {{ __('home.footer.get_started.cta_dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="cyra-btn-primary bg-white text-cyra-forest hover:bg-cyra-mint">
                            {{ __('home.footer.get_started.cta') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        <div class="mt-10 flex flex-col gap-2 border-t border-white/15 pt-6 text-xs text-white/60 sm:flex-row sm:items-center sm:justify-between">
            <p>{{ __('home.footer.copyright', ['year' => date('Y'), 'company' => config('cyra.company'), 'brand' => config('cyra.brand')]) }}</p>
            <p class="font-medium text-white/75">{{ __('home.footer.slogan') }}</p>
        </div>
    </div>
</footer>
