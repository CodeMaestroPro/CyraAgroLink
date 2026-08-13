@props([
    'label',
    'value',
    'change',
    'tone' => 'up',
])

@php
    $changeClass = $tone === 'down' ? 'text-red-600' : 'text-cyra-forest';
@endphp

<article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
    <p class="text-sm font-medium text-cyra-muted">{{ $label }}</p>
    <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-cyra-ink sm:text-[1.65rem]">
        {{ $value }}
    </p>
    <p class="mt-2 inline-flex items-center gap-1 text-sm font-semibold {{ $changeClass }}">
        @if ($tone === 'down')
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.53-.22l-5-5a.75.75 0 111.06-1.06l3.72 3.72V3.75a.75.75 0 011.5 0v10.69l3.72-3.72a.75.75 0 111.06 1.06l-5 5A.75.75 0 0110 17z" clip-rule="evenodd"/>
            </svg>
        @else
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 3a.75.75 0 01.53.22l5 5a.75.75 0 11-1.06 1.06l-3.72-3.72v10.69a.75.75 0 01-1.5 0V5.56L5.53 9.28A.75.75 0 014.47 8.22l5-5A.75.75 0 0110 3z" clip-rule="evenodd"/>
            </svg>
        @endif
        {{ $change }}
    </p>
</article>
