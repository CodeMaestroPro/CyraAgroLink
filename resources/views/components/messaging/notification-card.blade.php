@props([
    'title',
    'body',
    'time',
    'tone' => 'system',
    'unread' => false,
    'flush' => false,
])

@php
    $iconWrap = match ($tone) {
        'payment', 'investment' => 'bg-cyra-mint text-cyra-forest',
        'order' => 'bg-amber-50 text-amber-600',
        'weather', 'message' => 'bg-sky-50 text-sky-600',
        default => 'bg-violet-50 text-violet-600',
    };
@endphp

<article @class([
    'min-w-0 transition',
    'rounded-2xl bg-white p-4 shadow-sm ring-1 hover:ring-cyra-forest/20' => ! $flush,
    'p-4' => $flush,
    'ring-cyra-soft/80' => ! $flush && $unread,
    'ring-cyra-line/80' => ! $flush && ! $unread,
])>
    <div class="flex gap-3">
        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $iconWrap }} sm:h-10 sm:w-10">
            @switch($tone)
                @case('payment')
                @case('investment')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v18M17 8H9.5a2.5 2.5 0 0 0 0 5H14a2.5 2.5 0 0 1 0 5H7"/></svg>
                    @break
                @case('order')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v10H3V7z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 10h4l3 3v4h-7v-7z"/></svg>
                    @break
                @case('weather')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 15a4 4 0 0 1 7.7-1.5A3.2 3.2 0 0 1 17 19H8.3A3.2 3.2 0 0 1 8 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 20v2M14 20v2"/></svg>
                    @break
                @case('message')
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8M8 14h5M7 5h10a2 2 0 0 1 2 2v9.5L15 14H7a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z"/></svg>
                    @break
                @default
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="2.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v2.5M12 17.5V20M4 12h2.5M17.5 12H20"/></svg>
            @endswitch
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                <p class="min-w-0 break-words text-sm font-bold text-cyra-ink">
                    {{ $title }}
                    @if ($unread)
                        <span class="ml-1 inline-flex h-1.5 w-1.5 translate-y-[-1px] rounded-full bg-cyra-forest" aria-label="Unread"></span>
                    @endif
                </p>
                <p class="shrink-0 text-xs font-medium text-cyra-muted">{{ $time }}</p>
            </div>
            <p class="mt-1 break-words text-sm leading-relaxed text-cyra-muted">{{ $body }}</p>
        </div>
    </div>
</article>
