@props([
    'title',
    'detail',
    'amount',
    'time',
    'tone' => 'credit',
    'icon' => 'payment',
])

@php
    $amountClass = $tone === 'debit' ? 'text-red-600' : 'text-cyra-forest';
    $iconWrapClass = match ($icon) {
        'sent' => 'bg-red-50 text-red-600 ring-red-100',
        'withdrawal' => 'bg-slate-100 text-slate-600 ring-slate-200',
        default => 'bg-cyra-mint text-cyra-forest ring-cyra-soft/60',
    };
@endphp

<li class="group flex items-center gap-4 rounded-xl px-2 py-3.5 transition hover:bg-cyra-surface/80 sm:px-3">
    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full ring-1 {{ $iconWrapClass }}">
        @switch($icon)
            @case('investment')
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 20V10"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 14c0-4 3.5-7 8-7-1 5-4 8-8 8z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12c0-3.5-2.8-6-6.5-6 1 4 3 6.5 6.5 6.5z"/>
                </svg>
                @break
            @case('sent')
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 17 17 7"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 7h7v7"/>
                </svg>
                @break
            @case('withdrawal')
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10.5 12 4l9 6.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 10v8M9 10v8M15 10v8M19 10v8M4 18h16"/>
                </svg>
                @break
            @default
                <svg class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8.5A2.5 2.5 0 0 1 5.5 6H18a1 1 0 0 1 1 1v1.5"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8.5v9A2.5 2.5 0 0 0 5.5 20H19a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2h-5.5"/>
                    <circle cx="16.5" cy="14" r="1.25" fill="currentColor" stroke="none"/>
                </svg>
        @endswitch
    </span>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-cyra-ink">{{ $title }}</p>
        <p class="mt-0.5 truncate text-sm text-cyra-muted">{{ $detail }}</p>
    </div>

    <div class="shrink-0 text-right">
        <p class="text-sm font-bold tabular-nums {{ $amountClass }}">{{ $amount }}</p>
        <p class="mt-0.5 text-xs text-cyra-muted">{{ $time }}</p>
    </div>
</li>
