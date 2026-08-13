@props([
    'icon' => 'partly_cloudy',
    'size' => 'md',
])

@php
    $class = $size === 'lg' ? 'h-14 w-14' : ($size === 'sm' ? 'h-8 w-8' : 'h-10 w-10');
@endphp

<span class="inline-flex {{ $class }} items-center justify-center text-cyra-forest" aria-hidden="true">
    @switch($icon)
        @case('sunny')
            <svg class="h-full w-full" viewBox="0 0 48 48" fill="none">
                <circle cx="24" cy="24" r="8" fill="#E6A817"/>
                <path stroke="#E6A817" stroke-width="2.5" stroke-linecap="round" d="M24 8v4M24 36v4M8 24h4M36 24h4M12.5 12.5l2.8 2.8M32.7 32.7l2.8 2.8M12.5 35.5l2.8-2.8M32.7 15.3l2.8-2.8"/>
            </svg>
            @break
        @case('rain')
            <svg class="h-full w-full" viewBox="0 0 48 48" fill="none">
                <path fill="#90A4AE" d="M16 20a8 8 0 0 1 15.3-3.3A6.5 6.5 0 0 1 34 29H16.5A6.5 6.5 0 0 1 16 20z"/>
                <path stroke="#3B82F6" stroke-width="2.5" stroke-linecap="round" d="M18 33v5M24 34v5M30 33v5"/>
            </svg>
            @break
        @default
            <svg class="h-full w-full" viewBox="0 0 48 48" fill="none">
                <circle cx="17" cy="18" r="7" fill="#E6A817"/>
                <path fill="#90A4AE" d="M18 24a8 8 0 0 1 15.4-3A6.5 6.5 0 0 1 36 34H18.5A6.5 6.5 0 0 1 18 24z"/>
            </svg>
    @endswitch
</span>
