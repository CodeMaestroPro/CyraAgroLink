@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-cyra-forest text-start text-base font-medium text-cyra-forest bg-cyra-mint focus:outline-none focus:text-cyra-forest focus:bg-cyra-mint focus:border-cyra-green transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-cyra-muted hover:text-cyra-ink hover:bg-cyra-surface hover:border-cyra-line focus:outline-none focus:text-cyra-ink focus:bg-cyra-surface focus:border-cyra-line transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
