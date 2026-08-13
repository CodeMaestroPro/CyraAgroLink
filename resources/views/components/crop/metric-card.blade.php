@props([
    'label',
    'value',
    'meta' => null,
    'valueClass' => 'text-cyra-ink',
    'progress' => null,
])

<article {{ $attributes->merge(['class' => 'rounded-xl bg-white p-4 ring-1 ring-cyra-line sm:p-5']) }}>
    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-cyra-muted">{{ $label }}</p>
    <p @class(['mt-2 text-xl font-extrabold tracking-tight sm:text-2xl', $valueClass])>
        {{ $value }}
    </p>

    @if ($progress !== null)
        <div class="mt-3">
            <div class="mb-1 flex items-center justify-between text-xs font-semibold text-cyra-forest">
                <span>{{ $progress }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-cyra-line">
                <div class="h-full rounded-full bg-cyra-forest" style="width: {{ max(0, min(100, (int) $progress)) }}%"></div>
            </div>
            <p class="mt-1 text-xs font-medium text-cyra-muted">Progress</p>
        </div>
    @elseif ($meta)
        <p class="mt-2 text-sm text-cyra-muted">{{ $meta }}</p>
    @endif
</article>
