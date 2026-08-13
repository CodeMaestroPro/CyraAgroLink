@props([
    'title',
    'body',
    'time',
    'audience' => 'All users',
    'acknowledged' => false,
    'acknowledgeUrl' => null,
    'dismissUrl' => null,
    'featured' => false,
])

@if ($featured)
    <article {{ $attributes->merge(['class' => 'w-full min-w-0 rounded-2xl bg-cyra-forest p-5 text-white sm:p-6']) }}>
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide">Featured</span>
            <span class="rounded-full bg-white/10 px-2.5 py-1 text-[11px] font-semibold">{{ $audience }}</span>
            @if ($acknowledged)
                <span class="rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-bold">Acknowledged</span>
            @endif
        </div>

        <h3 class="mt-4 break-words font-display text-xl font-extrabold tracking-tight sm:text-2xl">{{ $title }}</h3>
        <p class="mt-2 break-words text-sm leading-relaxed text-white/90 sm:text-base">{{ $body }}</p>
        <p class="mt-3 text-xs text-white/70">Published {{ $time }}</p>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row">
            @unless ($acknowledged)
                @if ($acknowledgeUrl)
                    <form method="POST" action="{{ $acknowledgeUrl }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-cyra-forest hover:bg-cyra-mint sm:w-auto">
                            Acknowledge
                        </button>
                    </form>
                @endif
            @endunless
            @if ($dismissUrl)
                <form method="POST" action="{{ $dismissUrl }}" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-white/30 px-4 py-2.5 text-sm font-bold text-white hover:bg-white/10 sm:w-auto">
                        Dismiss
                    </button>
                </form>
            @endif
        </div>
    </article>
@else
    <article {{ $attributes->merge(['class' => 'w-full min-w-0 border-b border-cyra-line px-4 py-4 last:border-b-0 sm:px-5']) }}>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-semibold uppercase tracking-wide text-cyra-forest">{{ $audience }}</span>
                    @if ($acknowledged)
                        <span class="rounded-full bg-cyra-mint px-2 py-0.5 font-bold text-cyra-forest">Acknowledged</span>
                    @endif
                    <span class="text-cyra-muted">{{ $time }}</span>
                </div>
                <h3 class="mt-1.5 break-words font-display text-base font-extrabold text-cyra-ink">{{ $title }}</h3>
                <p class="mt-1 break-words text-sm leading-relaxed text-cyra-muted">{{ $body }}</p>
            </div>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                @unless ($acknowledged)
                    @if ($acknowledgeUrl)
                        <form method="POST" action="{{ $acknowledgeUrl }}">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-3.5 py-2 text-xs font-bold text-white hover:bg-cyra-green sm:w-auto">
                                Acknowledge
                            </button>
                        </form>
                    @endif
                @endunless
                @if ($dismissUrl)
                    <form method="POST" action="{{ $dismissUrl }}">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl border border-cyra-line px-3.5 py-2 text-xs font-bold text-cyra-muted hover:bg-cyra-surface sm:w-auto">
                            Dismiss
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </article>
@endif
