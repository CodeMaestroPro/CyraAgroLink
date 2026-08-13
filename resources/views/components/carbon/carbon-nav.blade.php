@props([
    'active' => 'overview',
])

@php
    $items = [
        ['id' => 'overview', 'label' => 'Overview', 'href' => '#overview', 'icon' => 'home'],
        ['id' => 'credits', 'label' => 'Credits', 'href' => '#credits', 'icon' => 'leaf'],
        ['id' => 'trend', 'label' => 'Trend', 'href' => '#trend', 'icon' => 'chart'],
        ['id' => 'transactions', 'label' => 'Transactions', 'href' => '#transactions', 'icon' => 'list'],
        ['id' => 'wallet', 'label' => 'Wallet', 'href' => route('wallet.index'), 'icon' => 'wallet'],
        ['id' => 'reports', 'label' => 'Reports', 'href' => route('reporting.analytics'), 'icon' => 'reports'],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit'), 'icon' => 'settings'],
    ];
@endphp

<aside class="flex w-14 shrink-0 flex-col items-center gap-2 bg-gradient-to-b from-cyra-forest to-[#0A5C2E] py-4 sm:w-16" aria-label="Carbon marketplace navigation">
    <span class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/15 text-cyra-soft">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/>
        </svg>
    </span>

    @foreach ($items as $item)
        <a
            href="{{ $item['href'] }}"
            title="{{ $item['label'] }}"
            aria-label="{{ $item['label'] }}"
            @class([
                'inline-flex h-10 w-10 items-center justify-center rounded-full transition',
                'bg-white/20 text-white shadow-sm' => $item['id'] === $active,
                'text-white/75 hover:bg-white/10 hover:text-white' => $item['id'] !== $active,
            ])
        >
            @if ($item['icon'] === 'home')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10.5L12 4l9 6.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/></svg>
            @elseif ($item['icon'] === 'leaf')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3c0 4.5-3.2 7.5-7.5 7.5C8.8 10.5 12 13.7 12 18.2 12 13.7 15.2 10.5 19.5 10.5 15.2 10.5 12 7.5 12 3z"/></svg>
            @elseif ($item['icon'] === 'chart')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V5m0 14h16M8 15l3-3 2 2 5-5"/></svg>
            @elseif ($item['icon'] === 'list')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            @elseif ($item['icon'] === 'wallet')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h14a2 2 0 012 2v2H3V7zm0 4h18v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6zm12 3h3"/></svg>
            @elseif ($item['icon'] === 'reports')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.37 2.37 1.724 1.724 0 001.065 2.572 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.37 2.37 1.724 1.724 0 00-2.572 1.065 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.37-2.37 1.724 1.724 0 00-1.065-2.572 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.37-2.37c.83.5 1.89.24 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @endif
        </a>
    @endforeach
</aside>
