@props([
    'active' => 'board',
])

@php
    $items = [
        ['id' => 'board', 'label' => 'Board', 'href' => '#board', 'icon' => 'home'],
        ['id' => 'chart', 'label' => 'Chart', 'href' => '#chart', 'icon' => 'chart'],
        ['id' => 'depth', 'label' => 'Depth', 'href' => '#depth', 'icon' => 'layers'],
        ['id' => 'contracts', 'label' => 'Contracts', 'href' => '#contracts', 'icon' => 'list'],
        ['id' => 'spot', 'label' => 'Spot Exchange', 'href' => route('exchange.show'), 'icon' => 'exchange'],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit'), 'icon' => 'settings'],
    ];
@endphp

<aside class="flex w-14 shrink-0 flex-col items-center gap-2 bg-gradient-to-b from-cyra-forest to-[#0A5C2E] py-4 sm:w-16" aria-label="Futures exchange navigation">
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
            @elseif ($item['icon'] === 'chart')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19V5m0 14h16M8 15l3-3 2 2 5-5"/></svg>
            @elseif ($item['icon'] === 'layers')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l9 4.5-9 4.5L3 7.5 12 3zm0 9l9 4.5-9 4.5-9-4.5L12 12z"/></svg>
            @elseif ($item['icon'] === 'list')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            @elseif ($item['icon'] === 'exchange')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.37 2.37 1.724 1.724 0 001.065 2.572 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.37 2.37 1.724 1.724 0 00-2.572 1.065 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.37-2.37 1.724 1.724 0 00-1.065-2.572 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.37-2.37c.83.5 1.89.24 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @endif
        </a>
    @endforeach
</aside>
