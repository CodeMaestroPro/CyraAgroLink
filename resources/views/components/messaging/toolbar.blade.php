@props([
    'title',
    'description' => null,
    'stats' => [],
])

<header {{ $attributes->merge(['class' => 'flex w-full min-w-0 flex-col gap-3 border-b border-cyra-line px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6']) }}>
    <div class="min-w-0">
        <h2 class="font-display text-xl font-extrabold text-cyra-ink sm:text-2xl">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 text-sm text-cyra-muted">{{ $description }}</p>
        @endif
    </div>

    @if (count($stats) > 0 || isset($actions))
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($stats as $stat)
                <div class="rounded-full bg-cyra-surface px-3 py-1.5 text-sm ring-1 ring-cyra-line">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-cyra-muted">{{ $stat['label'] }}</span>
                    <span class="ml-1 font-display font-extrabold tabular-nums text-cyra-forest">{{ $stat['value'] }}</span>
                </div>
            @endforeach
            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    @endif
</header>
