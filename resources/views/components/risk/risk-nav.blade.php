@props([
    'active' => 'overview',
])

@php
    $items = [
        ['id' => 'overview', 'label' => 'Overview', 'href' => '#overview', 'icon' => 'home'],
        ['id' => 'score', 'label' => 'Risk Score', 'href' => '#score', 'icon' => 'gauge'],
        ['id' => 'alerts', 'label' => 'Alerts', 'href' => '#alerts', 'icon' => 'alert'],
        ['id' => 'categories', 'label' => 'Categories', 'href' => '#categories', 'icon' => 'list'],
        ['id' => 'report', 'label' => 'Reports', 'href' => '#report', 'icon' => 'doc'],
        ['id' => 'weather', 'label' => 'Weather', 'href' => route('weather.intelligence'), 'icon' => 'cloud'],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit'), 'icon' => 'settings'],
    ];
@endphp

<aside class="flex w-14 shrink-0 flex-col items-center gap-2 bg-gradient-to-b from-cyra-forest to-[#0A5C2E] py-4 sm:w-16" aria-label="Risk intelligence navigation">
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
            @elseif ($item['icon'] === 'gauge')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14a3 3 0 100-6 3 3 0 000 6zm0 0l4-4M5.6 18.4A9 9 0 1118.4 5.6"/></svg>
            @elseif ($item['icon'] === 'alert')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 4.3L2.8 18a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 4.3a2 2 0 00-3.4 0z"/></svg>
            @elseif ($item['icon'] === 'list')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            @elseif ($item['icon'] === 'doc')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 3h6l4 4v14H8V3zm6 0v4h4"/></svg>
            @elseif ($item['icon'] === 'cloud')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 15a4 4 0 004 4h10a4 4 0 100-8 5.5 5.5 0 00-10.7 1.5A4 4 0 003 15z"/></svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.37 2.37 1.724 1.724 0 001.065 2.572 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.37 2.37 1.724 1.724 0 00-2.572 1.065 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.37-2.37 1.724 1.724 0 00-1.065-2.572 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.37-2.37c.83.5 1.89.24 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @endif
        </a>
    @endforeach
</aside>
