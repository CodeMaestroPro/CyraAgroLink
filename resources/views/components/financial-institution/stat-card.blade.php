@props([
    'label',
    'value',
])

<article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line transition hover:ring-cyra-forest/25 sm:p-5">
    <p class="text-sm font-medium text-cyra-muted">{{ $label }}</p>
    <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-cyra-ink sm:text-[1.65rem]">
        {{ $value }}
    </p>
</article>
