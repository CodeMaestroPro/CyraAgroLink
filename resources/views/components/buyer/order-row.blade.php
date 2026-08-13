@props([
    'product',
    'supplier',
    'quantity',
    'status',
    'statusTone' => 'pending',
    'image',
])

@php
    $statusClass = match ($statusTone) {
        'transit' => 'text-cyra-forest',
        'delivered' => 'text-cyra-leaf',
        'processing' => 'text-amber-600',
        default => 'text-cyra-sun',
    };
    $dotClass = match ($statusTone) {
        'transit' => 'bg-cyra-forest',
        'delivered' => 'bg-cyra-leaf',
        'processing' => 'bg-amber-500',
        default => 'bg-cyra-sun',
    };
@endphp

<li class="flex items-center gap-3 py-3.5 sm:gap-4">
    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl bg-cyra-panel ring-1 ring-cyra-line/80">
        <img
            src="{{ $image }}"
            alt="{{ $product }}"
            class="h-full w-full object-cover"
            loading="lazy"
        >
    </div>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-bold text-cyra-ink">{{ $product }}</p>
        <p class="mt-0.5 truncate text-sm text-cyra-muted">{{ $supplier }}</p>
    </div>

    <p class="hidden shrink-0 text-sm font-semibold tabular-nums text-cyra-ink sm:block">
        {{ $quantity }}
    </p>

    <p class="inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold {{ $statusClass }}">
        <span class="h-1.5 w-1.5 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
        {{ $status }}
    </p>
</li>
