@props([
    'active' => 'overview',
])

@php
    $items = [
        ['id' => 'overview', 'label' => 'Overview', 'href' => '#overview', 'icon' => 'home'],
        ['id' => 'soil', 'label' => 'Soil', 'href' => '#soil', 'icon' => 'soil'],
        ['id' => 'ndvi', 'label' => 'NDVI Map', 'href' => '#ndvi', 'icon' => 'map'],
        ['id' => 'irrigation', 'label' => 'Irrigation', 'href' => '#irrigation', 'icon' => 'water'],
        ['id' => 'fertilizer', 'label' => 'Fertilizer', 'href' => '#fertilizer', 'icon' => 'flask'],
        ['id' => 'fields', 'label' => 'Fields', 'href' => '#fields', 'icon' => 'grid'],
        ['id' => 'weather', 'label' => 'Weather', 'href' => route('weather.intelligence'), 'icon' => 'sun'],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit'), 'icon' => 'settings'],
    ];
@endphp

<aside class="flex w-14 shrink-0 flex-col items-center gap-2 bg-gradient-to-b from-cyra-forest to-[#0A5C2E] py-4 sm:w-16" aria-label="Precision agriculture navigation">
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
            @elseif ($item['icon'] === 'soil')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c2 3 3 5 3 7a3 3 0 11-6 0c0-2 1-4 3-7zM5 20h14M7 20c1-3 3-5 5-5s4 2 5 5"/></svg>
            @elseif ($item['icon'] === 'map')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A2 2 0 013 15.382V6.618a2 2 0 011.553-1.894L9 2.764m0 17.236l6-3V5.528l-6 3M9 20V5.764m6 11.236l5.447 2.724A2 2 0 0121 17.382V8.618a2 2 0 00-1.553-1.894L15 5.528"/></svg>
            @elseif ($item['icon'] === 'water')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c3 4 6 7.2 6 10.5A6 6 0 116 13.5C6 10.2 9 7 12 3z"/></svg>
            @elseif ($item['icon'] === 'flask')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 3h6M10 3v6l-5 9a2 2 0 001.7 3h10.6a2 2 0 001.7-3l-5-9V3"/></svg>
            @elseif ($item['icon'] === 'grid')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
            @elseif ($item['icon'] === 'sun')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4V2m0 20v-2m8-8h2M2 12h2m13.657-5.657l1.414-1.414M4.929 19.071l1.414-1.414m0-11.314L4.929 4.929m14.142 14.142l-1.414-1.414M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1.724 1.724 0 013.35 0l.10.02a1.724 1.724 0 002.573 1.066l.11-.06a1.724 1.724 0 012.37 2.37l-.06.11a1.724 1.724 0 001.066 2.573l.02.10a1.724 1.724 0 010 3.35l-.02.10a1.724 1.724 0 00-1.066 2.573l.06.11a1.724 1.724 0 01-2.37 2.37l-.11-.06a1.724 1.724 0 00-2.573 1.066l-.10.02a1.724 1.724 0 01-3.35 0l-.10-.02a1.724 1.724 0 00-2.573-1.066l-.11.06a1.724 1.724 0 01-2.37-2.37l.06-.11a1.724 1.724 0 00-1.066-2.573l-.02-.10a1.724 1.724 0 010-3.35l.02-.10a1.724 1.724 0 001.066-2.573l-.06-.11a1.724 1.724 0 012.37-2.37l.11.06a1.724 1.724 0 002.573-1.066l.10-.02z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @endif
        </a>
    @endforeach
</aside>
