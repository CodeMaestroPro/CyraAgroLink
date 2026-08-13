@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'w-full min-w-0 rounded-2xl bg-cyra-surface/60 p-4 ring-1 ring-cyra-line/80 sm:p-5']) }}>
    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-cyra-forest">Compose</p>
    <h3 class="mt-1 font-display text-base font-extrabold text-cyra-ink">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-sm text-cyra-muted">{{ $description }}</p>
    @endif
    <div class="mt-4 space-y-4">
        {{ $slot }}
    </div>
</div>
