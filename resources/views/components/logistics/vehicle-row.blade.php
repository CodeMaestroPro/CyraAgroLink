@props([
    'name',
    'route',
    'price',
    'status',
    'image',
    'href' => null,
    'actions' => null,
])

@php
    $isAvailable = strtolower($status) === 'available';
    $statusClass = match (true) {
        $isAvailable => 'bg-cyra-mint text-cyra-forest',
        strtolower($status) === 'delivered' => 'bg-slate-100 text-slate-600',
        strtolower($status) === 'cancelled' => 'bg-rose-50 text-rose-700',
        default => 'bg-amber-50 text-amber-700',
    };
@endphp

<article @class([
    'group flex flex-col gap-3 rounded-xl p-2.5 transition hover:bg-cyra-surface/90 sm:p-3',
    'ring-1 ring-cyra-forest/30 bg-cyra-mint/20' => $href && request()->fullUrlIs($href),
])>
    <div class="flex items-center gap-3.5 sm:gap-4">
        <div class="h-16 w-24 shrink-0 overflow-hidden rounded-xl bg-cyra-panel ring-1 ring-cyra-line/80 sm:h-[4.5rem] sm:w-28">
            <img
                src="{{ $image }}"
                alt="{{ $name }}"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                loading="lazy"
            >
        </div>

        <div class="min-w-0 flex-1">
            @if ($href)
                <a href="{{ $href }}" class="truncate text-sm font-bold text-cyra-ink hover:text-cyra-forest sm:text-[15px]">
                    {{ $name }}
                </a>
            @else
                <p class="truncate text-sm font-bold text-cyra-ink sm:text-[15px]">{{ $name }}</p>
            @endif
            <p class="mt-0.5 truncate text-sm text-cyra-muted">{{ $route }}</p>
        </div>

        <div class="shrink-0 text-right">
            <p class="text-sm font-bold tabular-nums text-cyra-ink sm:text-[15px]">{{ $price }}</p>
            <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $statusClass }}">
                {{ $status }}
            </span>
        </div>
    </div>

    @if ($actions)
        <div class="flex flex-wrap gap-2 border-t border-cyra-line/60 pt-2.5">
            {{ $actions }}
        </div>
    @endif
</article>
