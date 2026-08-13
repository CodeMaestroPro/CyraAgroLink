@props([
    'label',
    'market',
    'price',
    'icon' => 'sack',
])

<article class="rounded-xl bg-cyra-surface px-4 py-4 ring-1 ring-cyra-line sm:px-5">
    <p class="text-xs font-medium text-cyra-muted sm:text-sm">{{ $label }}</p>

    <div class="mt-3 flex items-start justify-between gap-3">
        <div>
            <p class="text-base font-extrabold text-cyra-ink sm:text-lg">{{ $market }}</p>
            <p class="mt-1 text-lg font-extrabold text-cyra-ink sm:text-xl">{{ $price }}</p>
        </div>

        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-cyra-forest ring-1 ring-cyra-line">
            @if ($icon === 'market')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10l1.5-4h15L21 10M4 10v9h6v-5h4v5h6v-9M3 10h18" />
                </svg>
            @else
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 8V6a2 2 0 012-2h4a2 2 0 012 2v2m-9 0h12l1 4v6a2 2 0 01-2 2H7a2 2 0 01-2-2v-6l1-4zm3 4h6" />
                </svg>
            @endif
        </span>
    </div>
</article>
