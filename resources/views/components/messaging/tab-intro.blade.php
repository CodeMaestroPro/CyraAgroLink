@props([
    'kicker' => 'Messaging',
    'title',
    'description' => null,
    'stats' => [],
])

<div {{ $attributes->merge(['class' => 'mb-5 min-w-0 sm:mb-6']) }}>
    <div class="relative overflow-hidden rounded-2xl bg-white ring-1 ring-cyra-line">
        <div class="pointer-events-none absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-cyra-forest via-cyra-green to-cyra-soft" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-8 -top-10 h-28 w-28 rounded-full bg-cyra-mint/50 blur-2xl" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-end sm:justify-between sm:px-6 sm:py-5">
            <div class="min-w-0 max-w-2xl">
                <p class="cyra-section-kicker">{{ $kicker }}</p>
                <h2 class="mt-1 font-display text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-1.5 text-sm leading-relaxed text-cyra-muted">{{ $description }}</p>
                @endif
            </div>

            @if (count($stats) > 0)
                <dl class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:gap-3">
                    @foreach ($stats as $stat)
                        <div class="min-w-[6.5rem] rounded-xl bg-cyra-surface/80 px-3 py-2.5 ring-1 ring-cyra-line/70 sm:min-w-[7.5rem]">
                            <dt class="text-[10px] font-semibold uppercase tracking-[0.14em] text-cyra-muted">{{ $stat['label'] }}</dt>
                            <dd class="mt-0.5 font-display text-lg font-extrabold tabular-nums text-cyra-forest">{{ $stat['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </div>
</div>
