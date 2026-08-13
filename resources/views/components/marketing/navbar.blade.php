@php
    $home = route('home');
    $links = [
        ['label' => __('home.nav.home'), 'href' => $home, 'active' => request()->routeIs('home')],
        ['label' => __('home.nav.solutions'), 'href' => $home.'#solutions', 'active' => false],
        ['label' => __('home.nav.marketplace'), 'href' => $home.'#marketplace', 'active' => false],
        ['label' => __('home.nav.invest'), 'href' => $home.'#invest', 'active' => false],
        ['label' => __('home.nav.logistics'), 'href' => $home.'#logistics', 'active' => false],
        ['label' => __('home.nav.resources'), 'href' => $home.'#resources', 'active' => false],
        ['label' => __('home.nav.about'), 'href' => $home.'#about', 'active' => false],
    ];
@endphp

<header
    x-data="{ open: false }"
    class="sticky top-0 z-40 border-b border-cyra-line/70 bg-cyra-card/90 backdrop-blur-md"
>
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:gap-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center" aria-label="{{ __('home.nav.brand_home_aria', ['brand' => config('cyra.brand')]) }}">
            <x-marketing.logo />
        </a>

        <nav class="hidden items-center gap-5 xl:gap-7 lg:flex" aria-label="{{ __('home.nav.primary_aria') }}">
            @foreach ($links as $link)
                <a
                    href="{{ $link['href'] }}"
                    @class([
                        'text-sm font-medium transition-colors',
                        'text-cyra-forest' => $link['active'],
                        'text-cyra-ink/80 hover:text-cyra-forest' => ! $link['active'],
                    ])
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2 sm:gap-3">
            <x-language-switcher class="hidden sm:block" />
            <x-theme-toggle />

            <div class="hidden items-center gap-3 lg:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="cyra-btn-primary py-2.5">
                        {{ __('home.nav.dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-cyra-ink/80 transition hover:text-cyra-forest">
                        {{ __('home.nav.sign_in') }}
                    </a>
                    <a href="{{ route('register') }}" class="cyra-btn-primary py-2.5">
                        {{ __('home.nav.get_started') }}
                    </a>
                @endauth
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg p-2 text-cyra-ink transition hover:bg-cyra-mint lg:hidden"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-controls="mobile-nav"
                aria-label="{{ __('home.nav.toggle_aria') }}"
            >
                <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-cloak x-show="open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div
        id="mobile-nav"
        x-cloak
        x-show="open"
        x-transition
        class="border-t border-cyra-line bg-cyra-card lg:hidden"
    >
        <nav class="mx-auto flex max-w-7xl flex-col gap-1 px-4 py-3 sm:px-6" aria-label="{{ __('home.nav.mobile_aria') }}">
            @foreach ($links as $link)
                <a
                    href="{{ $link['href'] }}"
                    class="rounded-lg px-3 py-2.5 text-sm font-semibold text-cyra-ink hover:bg-cyra-mint"
                    @click="open = false"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach

            <div class="mt-2 space-y-3 border-t border-cyra-line px-3 pt-3">
                <div class="sm:hidden">
                    <x-language-switcher class="block w-full" />
                </div>
                <div class="flex items-center justify-between gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="cyra-btn-primary">{{ __('home.nav.dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-cyra-muted">{{ __('home.nav.sign_in') }}</a>
                        <a href="{{ route('register') }}" class="cyra-btn-primary">{{ __('home.nav.get_started') }}</a>
                    @endauth
                </div>
            </div>
        </nav>
    </div>
</header>
