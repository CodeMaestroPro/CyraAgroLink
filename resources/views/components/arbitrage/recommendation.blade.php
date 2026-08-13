@props([
    'opportunity',
])

<article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
    <h2 class="text-base font-extrabold text-cyra-ink">AI Recommendation</h2>

    <p class="mt-3 text-sm leading-relaxed text-cyra-forest">
        @if (filled($opportunity->recommendation_title))
            <span class="font-bold text-cyra-green">{{ $opportunity->recommendation_title }}</span>
        @endif
        {{ $opportunity->recommendation_body }}
    </p>

    <a
        href="{{ route('arbitrage.analysis', $opportunity) }}"
        class="mt-5 inline-flex w-full items-center justify-center rounded-lg border border-cyra-line bg-white px-4 py-2.5 text-sm font-semibold text-cyra-ink transition hover:bg-cyra-mint hover:text-cyra-forest"
    >
        View Full Analysis
    </a>
</article>
