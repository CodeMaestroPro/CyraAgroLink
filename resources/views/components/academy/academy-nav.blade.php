@props([
    'active' => 'courses',
])

@php
    $items = [
        ['id' => 'courses', 'label' => 'Courses', 'href' => '#courses', 'icon' => 'home'],
        ['id' => 'continue', 'label' => 'Continue', 'href' => '#continue', 'icon' => 'play'],
        ['id' => 'library', 'label' => 'Library', 'href' => '#library', 'icon' => 'book'],
        ['id' => 'community', 'label' => 'Community', 'href' => '#community', 'icon' => 'users'],
        ['id' => 'certificates', 'label' => 'Certificates', 'href' => '#certificates', 'icon' => 'badge'],
        ['id' => 'profile', 'label' => 'Profile', 'href' => route('profile.edit'), 'icon' => 'user'],
        ['id' => 'settings', 'label' => 'Settings', 'href' => route('profile.edit'), 'icon' => 'settings'],
    ];
@endphp

<aside class="flex w-14 shrink-0 flex-col items-center gap-2 bg-gradient-to-b from-cyra-forest to-[#0A5C2E] py-4 sm:w-16" aria-label="Learning academy navigation">
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
            @elseif ($item['icon'] === 'play')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 5v14l11-7L8 5z"/></svg>
            @elseif ($item['icon'] === 'book')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a2 2 0 012-2h5v18H6a2 2 0 01-2-2V5zm10-2h4a2 2 0 012 2v14a2 2 0 01-2 2h-4V3z"/></svg>
            @elseif ($item['icon'] === 'users')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm12 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            @elseif ($item['icon'] === 'badge')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l2.5 5 5.5.8-4 3.9.9 5.5L12 15.8 7.1 18.2l.9-5.5-4-3.9L9.5 8 12 3z"/></svg>
            @elseif ($item['icon'] === 'user')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12a4 4 0 100-8 4 4 0 000 8zm0 2c-4 0-7 2-7 4v1h14v-1c0-2-3-4-7-4z"/></svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.066 1.724 1.724 0 012.37 2.37 1.724 1.724 0 001.065 2.572 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.066 2.573 1.724 1.724 0 01-2.37 2.37 1.724 1.724 0 00-2.572 1.065 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.066 1.724 1.724 0 01-2.37-2.37 1.724 1.724 0 00-1.065-2.572 1.724 1.724 0 010-3.35 1.724 1.724 0 001.066-2.573 1.724 1.724 0 012.37-2.37c.83.5 1.89.24 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @endif
        </a>
    @endforeach
</aside>
