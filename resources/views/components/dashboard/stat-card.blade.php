@props([
    'label',
    'value',
    'meta' => null,
    'metaTone' => 'amber',
    'metaHref' => null,
])

@php
    $metaClass = match ($metaTone) {
        'green' => 'text-cyra-leaf',
        default => 'text-cyra-amber',
    };
@endphp

<article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
    <p class="text-sm font-medium text-cyra-muted">{{ $label }}</p>
    <p class="mt-2 text-3xl font-extrabold tracking-tight text-cyra-ink">{{ $value }}</p>
    @if ($meta)
        @if ($metaHref)
            <a href="{{ $metaHref }}" class="mt-2 inline-block text-sm font-semibold {{ $metaClass }} hover:underline">
                {{ $meta }}
            </a>
        @else
            <p class="mt-2 text-sm font-semibold {{ $metaClass }}">{{ $meta }}</p>
        @endif
    @endif
</article>
