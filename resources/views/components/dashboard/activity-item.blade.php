@props([
    'title',
    'detail',
    'time',
    'icon' => 'order',
])

<li class="flex gap-3 py-3">
    <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
        @switch($icon)
            @case('payment')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v18M17 8H9.5a2.5 2.5 0 000 5H14a2.5 2.5 0 010 5H7"/></svg>
                @break
            @case('ai')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v2M12 18v2M4 12h2M18 12h2"/></svg>
                @break
            @case('harvest')
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3c0 4-3 7-7 7 4 0 7 3 7 7 0-4 3-7 7-7-4 0-7-3-7-7z"/></svg>
                @break
            @default
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18l-1.5 12a2 2 0 01-2 1.5H6.5A2 2 0 014.5 19L3 7z"/></svg>
        @endswitch
    </span>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-cyra-ink">{{ $title }}</p>
        <p class="mt-0.5 text-sm text-cyra-muted">{{ $detail }}</p>
    </div>
    <span class="shrink-0 text-xs font-medium text-cyra-muted">{{ $time }}</span>
</li>
