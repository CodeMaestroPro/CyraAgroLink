@php
    use App\Enums\UserRole;

    $user = auth()->user();

    $isAdmin = $user?->isAdmin() ?? false;
    $isBuyer = $user?->hasRole(UserRole::Buyer) ?? false;
    $isInvestor = $user?->hasRole(UserRole::Investor) ?? false;

    $farmerItems = [
        ['label' => __('nav.dashboard'), 'href' => route('dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('dashboard')],
        ['label' => __('nav.my_farms'), 'href' => route('farms.register'), 'icon' => 'farms', 'active' => request()->routeIs('farms.*')],
        ['label' => __('nav.crops'), 'href' => route('crops.manage'), 'icon' => 'crops', 'active' => request()->routeIs('crops.*')],
        ['label' => __('nav.marketplace'), 'href' => route('marketplace.index'), 'icon' => 'marketplace', 'active' => request()->routeIs('marketplace.*')],
        ['label' => __('nav.investments'), 'href' => route('investments.index'), 'icon' => 'finance', 'active' => request()->routeIs('investments.*')],
        ['label' => __('nav.portfolio'), 'href' => route('investor.dashboard'), 'icon' => 'finance', 'active' => request()->routeIs('investor.*')],
        ['label' => __('nav.wallet'), 'href' => route('wallet.index'), 'icon' => 'wallet', 'active' => request()->routeIs('wallet.*')],
        ['label' => __('nav.logistics'), 'href' => route('logistics.index'), 'icon' => 'logistics', 'active' => request()->routeIs('logistics.*')],
        ['label' => __('nav.warehouse'), 'href' => route('warehouse.index'), 'icon' => 'warehouse', 'active' => request()->routeIs('warehouse.*')],
        ['label' => __('nav.weather'), 'href' => route('weather.intelligence'), 'icon' => 'weather', 'active' => request()->routeIs('weather.*')],
        ['label' => __('nav.ai_assistant'), 'href' => route('ai.assistant'), 'icon' => 'ai', 'active' => request()->routeIs('ai.assistant')],
        ['label' => __('nav.insurance'), 'href' => route('insurance.platform'), 'icon' => 'insurance', 'active' => request()->routeIs('insurance.*')],
        ['label' => __('nav.equipment'), 'href' => route('equipment.marketplace'), 'icon' => 'equipment', 'active' => request()->routeIs('equipment.*')],
        ['label' => __('nav.cooperative'), 'href' => route('cooperative.management'), 'icon' => 'cooperative', 'active' => request()->routeIs('cooperative.*')],
        ['label' => __('nav.academy'), 'href' => route('academy.learning'), 'icon' => 'academy', 'active' => request()->routeIs('academy.*')],
        ['label' => __('nav.messages'), 'href' => route('messaging.index'), 'icon' => 'messages', 'active' => request()->routeIs('messaging.*')],
        ['label' => __('nav.settings'), 'href' => route('profile.edit'), 'icon' => 'settings', 'active' => request()->routeIs('profile.*')],
    ];

    $buyerItems = [
        ['label' => __('nav.dashboard'), 'href' => route('buyer.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('buyer.dashboard') || request()->routeIs('dashboard')],
        ['label' => __('nav.marketplace'), 'href' => route('marketplace.index'), 'icon' => 'marketplace', 'active' => request()->routeIs('marketplace.*')],
        ['label' => __('nav.shop'), 'href' => route('consumer.marketplace'), 'icon' => 'consumer', 'active' => request()->routeIs('consumer.*')],
        ['label' => __('nav.exchange'), 'href' => route('exchange.show'), 'icon' => 'market-intel', 'active' => request()->routeIs('exchange.*')],
        ['label' => __('nav.shipments'), 'href' => route('logistics.index'), 'icon' => 'logistics', 'active' => request()->routeIs('logistics.*') || request()->routeIs('supply-chain.*')],
        ['label' => __('nav.wallet'), 'href' => route('wallet.index'), 'icon' => 'wallet', 'active' => request()->routeIs('wallet.*')],
        ['label' => __('nav.messages'), 'href' => route('messaging.index'), 'icon' => 'messages', 'active' => request()->routeIs('messaging.*')],
        ['label' => __('nav.settings'), 'href' => route('profile.edit'), 'icon' => 'settings', 'active' => request()->routeIs('profile.*')],
    ];

    $investorItems = [
        ['label' => __('nav.dashboard'), 'href' => route('dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('dashboard') || request()->routeIs('investor.*')],
        ['label' => __('nav.investments'), 'href' => route('investments.index'), 'icon' => 'finance', 'active' => request()->routeIs('investments.*')],
        ['label' => __('nav.portfolio'), 'href' => route('investor.dashboard'), 'icon' => 'finance', 'active' => request()->routeIs('investor.*')],
        ['label' => __('nav.wallet'), 'href' => route('wallet.index'), 'icon' => 'wallet', 'active' => request()->routeIs('wallet.*')],
        ['label' => __('nav.messages'), 'href' => route('messaging.index'), 'icon' => 'messages', 'active' => request()->routeIs('messaging.*')],
        ['label' => __('nav.settings'), 'href' => route('profile.edit'), 'icon' => 'settings', 'active' => request()->routeIs('profile.*')],
    ];

    $adminPlatformItems = [
        ['label' => __('nav.dashboard'), 'href' => route('dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('dashboard')],
        ['label' => __('nav.my_farms'), 'href' => route('farms.register'), 'icon' => 'farms', 'active' => request()->routeIs('farms.*')],
        ['label' => __('nav.crops'), 'href' => route('crops.manage'), 'icon' => 'crops', 'active' => request()->routeIs('crops.*')],
        ['label' => __('nav.marketplace'), 'href' => route('marketplace.index'), 'icon' => 'marketplace', 'active' => request()->routeIs('marketplace.*')],
        ['label' => __('nav.consumer'), 'href' => route('consumer.marketplace'), 'icon' => 'consumer', 'active' => request()->routeIs('consumer.*')],
        ['label' => __('nav.market_intel'), 'href' => route('market.intelligence'), 'icon' => 'market-intel', 'active' => request()->routeIs('market.*')],
        ['label' => __('nav.logistics'), 'href' => route('logistics.index'), 'icon' => 'logistics', 'active' => request()->routeIs('logistics.*')],
        ['label' => __('nav.city_food'), 'href' => route('distribution.smart-city'), 'icon' => 'distribution', 'active' => request()->routeIs('distribution.*')],
        ['label' => __('nav.warehouse'), 'href' => route('warehouse.index'), 'icon' => 'warehouse', 'active' => request()->routeIs('warehouse.*')],
        ['label' => __('nav.supply_chain'), 'href' => route('supply-chain.index'), 'icon' => 'supply-chain', 'active' => request()->routeIs('supply-chain.*')],
        ['label' => __('nav.investments'), 'href' => route('investments.index'), 'icon' => 'finance', 'active' => request()->routeIs('investor.*') || request()->routeIs('investments.*')],
        ['label' => __('nav.wallet'), 'href' => route('wallet.index'), 'icon' => 'wallet', 'active' => request()->routeIs('wallet.*')],
        ['label' => __('nav.ai_assistant'), 'href' => route('ai.assistant'), 'icon' => 'ai', 'active' => request()->routeIs('ai.assistant')],
        ['label' => __('nav.cyra_ai'), 'href' => route('ai.command'), 'icon' => 'ai', 'active' => request()->routeIs('ai.command')],
        ['label' => __('nav.digital_twin'), 'href' => route('digital.twin'), 'icon' => 'digital-twin', 'active' => request()->routeIs('digital.*')],
        ['label' => __('nav.precision'), 'href' => route('precision.agriculture'), 'icon' => 'precision', 'active' => request()->routeIs('precision.*')],
        ['label' => __('nav.carbon'), 'href' => route('carbon.marketplace'), 'icon' => 'carbon', 'active' => request()->routeIs('carbon.*')],
        ['label' => __('nav.export'), 'href' => route('export.hub'), 'icon' => 'export', 'active' => request()->routeIs('export.*')],
        ['label' => __('nav.processing'), 'href' => route('processing.network'), 'icon' => 'processing', 'active' => request()->routeIs('processing.*')],
        ['label' => __('nav.equipment'), 'href' => route('equipment.marketplace'), 'icon' => 'equipment', 'active' => request()->routeIs('equipment.*')],
        ['label' => __('nav.insurance'), 'href' => route('insurance.platform'), 'icon' => 'insurance', 'active' => request()->routeIs('insurance.*')],
        ['label' => __('nav.risk'), 'href' => route('risk.intelligence'), 'icon' => 'risk', 'active' => request()->routeIs('risk.*')],
        ['label' => __('nav.futures'), 'href' => route('futures.exchange'), 'icon' => 'futures', 'active' => request()->routeIs('futures.*')],
        ['label' => __('nav.auctions'), 'href' => route('auction.system'), 'icon' => 'auction', 'active' => request()->routeIs('auction.*')],
        ['label' => __('nav.food_security'), 'href' => route('food.security'), 'icon' => 'food-security', 'active' => request()->routeIs('food.*')],
        ['label' => __('nav.cooperative'), 'href' => route('cooperative.management'), 'icon' => 'cooperative', 'active' => request()->routeIs('cooperative.*')],
        ['label' => __('nav.academy'), 'href' => route('academy.learning'), 'icon' => 'academy', 'active' => request()->routeIs('academy.*')],
        ['label' => __('nav.weather'), 'href' => route('weather.intelligence'), 'icon' => 'weather', 'active' => request()->routeIs('weather.*')],
        ['label' => __('nav.reports'), 'href' => route('reporting.analytics'), 'icon' => 'reports', 'active' => request()->routeIs('reporting.*')],
        ['label' => __('nav.bi_center'), 'href' => route('intelligence.command'), 'icon' => 'intelligence', 'active' => request()->routeIs('intelligence.*')],
        ['label' => __('nav.government'), 'href' => route('government.dashboard'), 'icon' => 'government', 'active' => request()->routeIs('government.*')],
        ['label' => __('nav.lending'), 'href' => route('financial.dashboard'), 'icon' => 'lending', 'active' => request()->routeIs('financial.*')],
        ['label' => __('nav.mobile'), 'href' => route('mobile.preview'), 'icon' => 'mobile', 'active' => request()->routeIs('mobile.*')],
        ['label' => __('nav.messages'), 'href' => route('messaging.index'), 'icon' => 'messages', 'active' => request()->routeIs('messaging.*')],
        ['label' => __('nav.settings'), 'href' => route('profile.edit'), 'icon' => 'settings', 'active' => request()->routeIs('profile.*')],
    ];

    $adminItems = array_merge(
        [
            ['label' => __('nav.admin'), 'href' => route('admin.dashboard'), 'icon' => 'dashboard', 'active' => request()->routeIs('admin.*')],
        ],
        $adminPlatformItems
    );

    if ($isAdmin) {
        $items = $adminItems;
        $navLabel = __('nav.admin_nav');
    } elseif ($isBuyer) {
        $items = $buyerItems;
        $navLabel = __('nav.buyer_nav');
    } elseif ($isInvestor) {
        $items = $investorItems;
        $navLabel = __('nav.investor_nav');
    } else {
        $items = $farmerItems;
        $navLabel = __('nav.farmer_nav');
    }
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-cyra-forest transition-transform duration-200 lg:static lg:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }"
>
    <div class="flex h-16 items-center gap-2.5 px-5">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10">
            <svg class="h-5 w-5 text-cyra-soft" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
            </svg>
        </span>
        <a href="{{ route('home') }}" class="text-lg font-extrabold tracking-tight text-white">
            {{ config('cyra.brand') }}
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-6" aria-label="{{ $navLabel }}">
        @foreach ($items as $item)
            <a
                href="{{ $item['href'] }}"
                @class([
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                    'bg-white text-cyra-forest shadow-sm' => $item['active'],
                    'text-white/85 hover:bg-white/10 hover:text-white' => ! $item['active'],
                ])
            >
                <span @class(['shrink-0', 'text-cyra-forest' => $item['active'], 'text-white/90' => ! $item['active']])>
                    @include('components.dashboard.icons.'.$item['icon'])
                </span>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
</aside>
