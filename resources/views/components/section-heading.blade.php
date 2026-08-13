@props([
    'title',
    'description' => null,
    'tone' => 'mint',
])

@php
    $tones = [
        'mint' => 'bg-cyra-mint/80 ring-cyra-soft/60',
        'panel' => 'bg-cyra-panel ring-cyra-line',
        'cream' => 'bg-white ring-cyra-line',
        'forest' => 'bg-cyra-forest/10 ring-cyra-forest/20',
    ];
    $toneClass = $tones[$tone] ?? $tones['mint'];
@endphp

<div {{ $attributes->merge(['class' => 'mb-4 rounded-2xl px-4 py-3.5 ring-1 sm:px-5 '.$toneClass]) }}>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h2 class="font-display text-lg font-bold tracking-tight text-cyra-ink sm:text-xl">
                {{ $title }}
            </h2>
            @if ($description)
                <p class="mt-1 text-sm text-cyra-muted">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
