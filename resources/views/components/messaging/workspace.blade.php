@props([])

<section {{ $attributes->merge(['class' => 'w-full min-w-0 rounded-2xl bg-white ring-1 ring-cyra-line']) }}>
    {{ $slot }}
</section>
