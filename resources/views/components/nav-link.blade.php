@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-cyra-forest text-sm font-medium leading-5 text-cyra-ink focus:outline-none focus:border-cyra-green transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-cyra-muted hover:text-cyra-ink hover:border-cyra-line focus:outline-none focus:text-cyra-ink focus:border-cyra-line transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
