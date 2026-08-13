@props([
    'notificationsCount' => 0,
])

@php
    use App\Enums\UserRole;

    $user = auth()->user();
    $isInvestor = $user?->hasRole(UserRole::Investor) ?? false;
    $isFarmer = $user?->hasRole(UserRole::Farmer) ?? false;
    $onPortfolio = request()->routeIs('investor.*');
    $onInvestments = request()->routeIs('investments.*');
    $onMessaging = request()->routeIs('messaging.*');
    $onMarketplace = request()->routeIs('marketplace.*', 'consumer.*');

    if ($onMessaging) {
        $searchAction = route('messaging.index');
        $searchPlaceholder = __('ui.search_messages');
        $searchPreserve = ['tab' => request('tab', 'messages')];
    } elseif ($onPortfolio) {
        $searchAction = route('investor.dashboard');
        $searchPlaceholder = __('ui.search_portfolio');
        $searchPreserve = [];
    } elseif ($onInvestments || (($isInvestor || $isFarmer) && ! $onMarketplace)) {
        $searchAction = route('investments.index');
        $searchPlaceholder = __('ui.search_investments');
        $searchPreserve = ['all' => 1];
    } else {
        $searchAction = route('marketplace.index');
        $searchPlaceholder = __('ui.search_marketplace');
        $searchPreserve = array_filter([
            'view' => request('view'),
            'category' => request('category'),
            'state' => request('state'),
        ]);
    }
@endphp

<header class="sticky top-0 z-20 border-b border-cyra-line/80 bg-cyra-surface/95 backdrop-blur">
    <div class="flex items-center gap-2 px-4 py-3 sm:gap-4 sm:px-6 lg:px-8">
        <button
            type="button"
            class="inline-flex rounded-lg p-2 text-cyra-ink hover:bg-cyra-card lg:hidden"
            @click="sidebarOpen = true"
            aria-label="Open sidebar"
        >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <a href="{{ route('dashboard') }}" class="hidden shrink-0 items-center gap-2 lg:inline-flex" aria-label="{{ config('cyra.brand') }}">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyra-forest/10">
                <svg class="h-4 w-4 text-cyra-forest" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
                </svg>
            </span>
            <span class="text-sm font-extrabold tracking-tight text-cyra-ink">{{ config('cyra.brand') }}</span>
        </a>

        <x-search-bar
            :action="$searchAction"
            :placeholder="$searchPlaceholder"
            :preserve="$searchPreserve"
        />

        <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
            <a
                href="{{ route('messaging.index') }}"
                class="relative hidden rounded-xl bg-cyra-card p-2.5 text-cyra-muted shadow-sm ring-1 ring-cyra-line transition hover:text-cyra-forest sm:inline-flex"
                aria-label="Messages"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h5m7-3a9 9 0 11-3.3-6.9L21 3l-1.2 4.2A8.96 8.96 0 0121 11z"/>
                </svg>
            </a>

            <x-notification-dropdown :count="$notificationsCount" />
            <x-language-switcher class="hidden sm:block" />
            <x-theme-toggle class="inline-flex" />
            <x-user-menu />
        </div>
    </div>
</header>
