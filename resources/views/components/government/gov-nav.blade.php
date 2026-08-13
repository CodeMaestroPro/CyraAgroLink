@props([
    'active' => 'overview',
])

@php
    $items = [
        ['id' => 'overview', 'label' => 'Overview', 'href' => '#overview'],
        ['id' => 'farmers', 'label' => 'Farmers', 'href' => '#farmers'],
        ['id' => 'production', 'label' => 'Production', 'href' => '#production'],
        ['id' => 'food-security', 'label' => 'Food Security', 'href' => '#food-security'],
        ['id' => 'subsidies', 'label' => 'Subsidies', 'href' => '#subsidies'],
        ['id' => 'policies', 'label' => 'Policies', 'href' => '#policies'],
        ['id' => 'reports', 'label' => 'Reports', 'href' => route('reporting.analytics')],
        ['id' => 'analytics', 'label' => 'Analytics', 'href' => route('market.intelligence')],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit')],
    ];
@endphp

<aside class="flex w-full flex-col bg-gradient-to-b from-cyra-forest to-[#0A5C2E] lg:w-52 xl:w-56">
    <div class="flex items-center gap-2.5 px-4 py-4">
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-cyra-soft">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
            </svg>
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-extrabold text-white">Government</p>
            <p class="truncate text-[11px] font-medium text-white/70">National overview</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-x-auto px-3 pb-5 lg:overflow-y-auto" aria-label="Government dashboard sections">
        <div class="flex gap-1 lg:block lg:space-y-1">
            @foreach ($items as $item)
                <a
                    href="{{ $item['href'] }}"
                    @class([
                        'inline-flex shrink-0 items-center rounded-xl px-3 py-2.5 text-sm font-semibold transition lg:w-full',
                        'bg-white/20 text-white shadow-sm' => $item['id'] === $active,
                        'text-white/80 hover:bg-white/10 hover:text-white' => $item['id'] !== $active,
                    ])
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
</aside>
