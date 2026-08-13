@props([
    'name',
    'quantity',
    'icon' => 'others',
])

@php
    $iconWrap = match ($icon) {
        'maize' => 'bg-amber-50 text-amber-600 ring-amber-100',
        'rice' => 'bg-cyra-mint text-cyra-forest ring-cyra-soft/50',
        'cassava' => 'bg-orange-50 text-orange-700 ring-orange-100',
        default => 'bg-sky-50 text-sky-600 ring-sky-100',
    };
@endphp

<li class="flex items-center gap-3.5 py-3.5">
    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-1 {{ $iconWrap }}">
        @switch($icon)
            @case('maize')
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c2.8 2.2 4 5.2 4 9s-1.2 6.8-4 9c-2.8-2.2-4-5.2-4-9s1.2-6.8 4-9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v12M9.5 9.5c1.5.8 3.5.8 5 0M9.5 14.5c1.5.8 3.5.8 5 0"/>
                </svg>
                @break
            @case('rice')
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 8c0-2.5 1.8-4.5 4-4.5S16 5.5 16 8c0 6-4 12-4 12S8 14 8 8z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 9.5h4M10 13h4"/>
                </svg>
                @break
            @case('cassava')
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 8.5c0-2.2 2.2-4 5-4s5 1.8 5 4c0 5.5-5 11.5-5 11.5S7 14 7 8.5z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.5 10h5M9.5 13.5h5"/>
                </svg>
                @break
            @default
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12v8M4 8.5l8 3.5 8-3.5"/>
                </svg>
        @endswitch
    </span>

    <p class="min-w-0 flex-1 text-sm font-semibold text-cyra-ink">{{ $name }}</p>
    <p class="shrink-0 text-sm font-bold tabular-nums text-cyra-ink">{{ $quantity }}</p>
</li>
