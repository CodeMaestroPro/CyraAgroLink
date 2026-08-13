@props([
    'active' => 'overview',
])

@php
    $items = [
        ['id' => 'overview', 'label' => 'Overview', 'href' => '#overview', 'icon' => 'dashboard'],
        ['id' => 'commodities', 'label' => 'Commodities', 'href' => '#commodities', 'icon' => 'crops'],
        ['id' => 'trends', 'label' => 'Price Trends', 'href' => '#price-trend', 'icon' => 'reports'],
        ['id' => 'forecast', 'label' => 'Demand Forecast', 'href' => '#demand-forecast', 'icon' => 'market-intel'],
        ['id' => 'trade', 'label' => 'Import / Export', 'href' => '#exports', 'icon' => 'logistics'],
        ['id' => 'reports', 'label' => 'Reports', 'href' => '#reports', 'icon' => 'invoices'],
        ['id' => 'alerts', 'label' => 'Alerts', 'href' => '#alerts', 'icon' => 'watchlist'],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit'), 'icon' => 'settings'],
    ];
@endphp

<aside class="flex w-full flex-col border-b border-cyra-line bg-cyra-surface/40 lg:w-56 lg:border-b-0 lg:border-r xl:w-64">
    <div class="flex items-center gap-2.5 px-4 py-4 sm:px-5">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-extrabold text-cyra-forest">{{ config('cyra.brand') }}</p>
            <p class="truncate text-[11px] font-medium text-cyra-muted">Market Intelligence</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-x-auto px-3 pb-4 lg:overflow-y-auto" aria-label="Market intelligence sections">
        <div class="flex gap-1 lg:block lg:space-y-1">
            @foreach ($items as $item)
                <a
                    href="{{ $item['href'] }}"
                    @class([
                        'inline-flex shrink-0 items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold transition lg:w-full',
                        'bg-cyra-mint text-cyra-forest' => $item['id'] === $active,
                        'text-cyra-muted hover:bg-white hover:text-cyra-ink' => $item['id'] !== $active,
                    ])
                >
                    <span @class([
                        'shrink-0',
                        'text-cyra-forest' => $item['id'] === $active,
                        'text-cyra-muted' => $item['id'] !== $active,
                    ])>
                        @include('components.dashboard.icons.'.$item['icon'])
                    </span>
                    <span class="whitespace-nowrap">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <div class="border-t border-cyra-line p-3 sm:p-4">
        <a
            href="#reports"
            class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
        >
            View Full Report
        </a>
    </div>
</aside>
