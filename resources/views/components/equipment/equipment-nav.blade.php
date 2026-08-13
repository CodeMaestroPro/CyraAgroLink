@props([
    'active' => 'browse',
])

@php
    $items = [
        ['id' => 'browse', 'label' => 'Browse', 'href' => route('equipment.marketplace'), 'icon' => 'home'],
        ['id' => 'sale', 'label' => 'For Sale', 'href' => route('equipment.marketplace', ['tab' => 'sale']), 'icon' => 'tag'],
        ['id' => 'rent', 'label' => 'For Rent', 'href' => route('equipment.marketplace', ['tab' => 'rent']), 'icon' => 'key'],
        ['id' => 'parts', 'label' => 'Spare Parts', 'href' => route('equipment.marketplace', ['tab' => 'parts']), 'icon' => 'wrench'],
        ['id' => 'cart', 'label' => 'Cart', 'href' => route('equipment.marketplace', ['view' => 'cart']), 'icon' => 'cart'],
        ['id' => 'favorites', 'label' => 'Favorites', 'href' => route('equipment.marketplace', ['view' => 'browse']), 'icon' => 'heart'],
        ['id' => 'orders', 'label' => 'Orders', 'href' => route('equipment.marketplace', ['view' => 'orders']), 'icon' => 'box'],
        ['id' => 'wallet', 'label' => 'Wallet', 'href' => route('wallet.index'), 'icon' => 'wallet'],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit'), 'icon' => 'settings'],
    ];
@endphp

<aside class="flex w-14 shrink-0 flex-col items-center gap-2 bg-gradient-to-b from-cyra-forest to-[#0A5C2E] py-4 sm:w-16" aria-label="Equipment marketplace navigation">
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
            @elseif ($item['icon'] === 'tag')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01M3 11l8.5-8.5a2 2 0 012.8 0L21 9.2a2 2 0 010 2.8L12.5 20.5a2 2 0 01-2.8 0L3 14V11z"/></svg>
            @elseif ($item['icon'] === 'key')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/></svg>
            @elseif ($item['icon'] === 'wrench')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.7 6.3a4.5 4.5 0 01-5.9 5.9L4 17.7V20h2.3l4.5-4.5a4.5 4.5 0 015.9-5.9l-1.5-1.5-1.5-1.8z"/></svg>
            @elseif ($item['icon'] === 'cart')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 6h14M10 19a1 1 0 100 2 1 1 0 000-2zm8 0a1 1 0 100 2 1 1 0 000-2z"/></svg>
            @elseif ($item['icon'] === 'heart')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.3 12.3C2.9 10.9 2.9 8.6 4.3 7.2A3.5 3.5 0 019 7.2L12 10l3-2.8a3.5 3.5 0 014.7 0c1.4 1.4 1.4 3.7 0 5.1L12 21l-7.7-8.7z"/></svg>
            @elseif ($item['icon'] === 'box')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 8l-9-5-9 5v8l9 5 9-5V8zm-9 5l9-5m-9 5L3 8m9 5v8"/></svg>
            @elseif ($item['icon'] === 'wallet')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h14a2 2 0 012 2v2H3V7zm0 4h18v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6zm12 3h3"/></svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.37 2.37 1.724 1.724 0 001.065 2.572 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.37 2.37 1.724 1.724 0 00-2.572 1.065 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.37-2.37 1.724 1.724 0 00-1.065-2.572 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.37-2.37c.83.5 1.89.24 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @endif
        </a>
    @endforeach
</aside>
