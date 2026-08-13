@props([
    'stage' => 'seedling',
    'active' => false,
])

@php
    $tone = $active ? 'text-cyra-forest' : 'text-cyra-muted/70';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex h-14 w-14 items-center justify-center '.$tone]) }}>
    @if ($stage === 'seedling')
        <svg viewBox="0 0 64 64" fill="none" class="h-12 w-12" aria-hidden="true">
            <ellipse cx="32" cy="52" rx="14" ry="4" fill="currentColor" opacity="0.2"/>
            <path d="M32 48V30" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            <path d="M32 36c-8-2-12-8-12-14 8 1 12 7 12 14Z" fill="currentColor"/>
            <path d="M32 34c6-3 10-8 10-13-6 2-9 7-10 13Z" fill="currentColor" opacity="0.75"/>
        </svg>
    @elseif ($stage === 'vegetative')
        <svg viewBox="0 0 64 64" fill="none" class="h-12 w-12" aria-hidden="true">
            <ellipse cx="32" cy="54" rx="16" ry="4" fill="currentColor" opacity="0.2"/>
            <path d="M32 50V22" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
            <path d="M32 28c-10-1-16-8-16-15 10 1 15 8 16 15Z" fill="currentColor"/>
            <path d="M32 26c9-2 14-8 15-14-9 2-13 8-15 14Z" fill="currentColor" opacity="0.8"/>
            <path d="M32 38c-9 0-14-6-14-12 8 0 13 6 14 12Z" fill="currentColor" opacity="0.7"/>
            <path d="M32 36c8 1 13-4 14-10-8 1-12 5-14 10Z" fill="currentColor" opacity="0.65"/>
        </svg>
    @elseif ($stage === 'flowering')
        <svg viewBox="0 0 64 64" fill="none" class="h-12 w-12" aria-hidden="true">
            <ellipse cx="32" cy="54" rx="16" ry="4" fill="currentColor" opacity="0.2"/>
            <path d="M32 50V18" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
            <path d="M32 24c-11 0-17-7-17-14 11 0 16 7 17 14Z" fill="currentColor"/>
            <path d="M32 22c10-1 16-7 17-13-10 1-15 7-17 13Z" fill="currentColor" opacity="0.8"/>
            <path d="M32 36c-10 1-15-5-15-11 9 0 14 6 15 11Z" fill="currentColor" opacity="0.7"/>
            <circle cx="32" cy="14" r="4" fill="#E6A817"/>
            <circle cx="32" cy="14" r="2" fill="#F7F4EF"/>
        </svg>
    @else
        <svg viewBox="0 0 64 64" fill="none" class="h-12 w-12" aria-hidden="true">
            <ellipse cx="32" cy="54" rx="16" ry="4" fill="currentColor" opacity="0.2"/>
            <path d="M32 50V16" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"/>
            <path d="M32 22c-11 0-17-7-17-14 11 0 16 7 17 14Z" fill="currentColor"/>
            <path d="M32 20c10-1 16-7 17-13-10 1-15 7-17 13Z" fill="currentColor" opacity="0.8"/>
            <path d="M26 28c-2 6-1 12 2 16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            <path d="M38 28c2 6 1 12-2 16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            <path d="M28 44h8" stroke="#E6A817" stroke-width="3" stroke-linecap="round"/>
            <path d="M29 40h6" stroke="#E6A817" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
    @endif
</span>
