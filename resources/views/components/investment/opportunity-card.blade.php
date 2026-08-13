@props([
    'opportunity',
    'walletBalance' => 0,
    'showAll' => true,
])

@php
    $remaining = $opportunity->remainingCapacity();
    $gallery = $opportunity->galleryUrls();
    $rating = (float) ($opportunity->reviews_avg_rating ?? $opportunity->averageRating());
    $reviewsCount = (int) ($opportunity->reviews_count ?? $opportunity->reviewsCount());
    $title = $opportunity->localizedTitle();
    $location = $opportunity->localizedLocation();
    $summary = $opportunity->localizedSummary();
@endphp

<article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-cyra-line transition hover:-translate-y-0.5 hover:shadow-soft">
    <a href="{{ route('investments.show', $opportunity) }}" class="block" aria-label="{{ __('ui.view_opportunity', ['title' => $title]) }}">
        <div class="grid grid-cols-3 gap-0.5 bg-cyra-line">
            @foreach (array_pad($gallery, 3, $gallery[0] ?? asset('images/investments/maize-expansion.jpg')) as $index => $url)
                @if ($index < 3)
                    <img
                        src="{{ $url }}"
                        alt="{{ __('ui.photo_n', ['title' => $title, 'n' => $index + 1]) }}"
                        class="aspect-[4/3] w-full object-cover"
                        loading="lazy"
                    >
                @endif
            @endforeach
        </div>
    </a>

    <div class="p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <h3 class="text-base font-extrabold text-cyra-ink">
                    <a href="{{ route('investments.show', $opportunity) }}" class="hover:text-cyra-forest">
                        {{ $title }}
                    </a>
                </h3>
                <p class="mt-1 text-sm text-cyra-muted">{{ $location }}</p>
            </div>
            <p class="shrink-0 text-xs font-semibold text-cyra-forest">
                @if ($reviewsCount > 0)
                    ★ {{ number_format($rating, 1) }}
                    <span class="font-medium text-cyra-muted">({{ $reviewsCount }})</span>
                @else
                    <span class="text-cyra-muted">{{ __('ui.no_reviews') }}</span>
                @endif
            </p>
        </div>

        <p class="mt-3 line-clamp-2 text-sm leading-relaxed text-cyra-muted">{{ $summary }}</p>

        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-cyra-muted">{{ __('ui.roi') }}</p>
                <p class="mt-0.5 font-bold text-cyra-ink">{{ rtrim(rtrim(number_format($opportunity->roi_percent, 1), '0'), '.') }}%</p>
            </div>
            <div>
                <p class="text-cyra-muted">{{ __('ui.duration') }}</p>
                <p class="mt-0.5 font-bold text-cyra-ink">{{ $opportunity->durationLabel() }}</p>
            </div>
        </div>

        <p class="mt-4 text-lg font-extrabold text-cyra-ink">{{ $opportunity->formattedAmount() }} {{ __('ui.target') }}</p>

        <div class="mt-3">
            <div class="h-2 overflow-hidden rounded-full bg-cyra-line">
                <div
                    class="h-full rounded-full bg-cyra-sun"
                    style="width: {{ min(100, max(0, $opportunity->funded_percent)) }}%"
                ></div>
            </div>
            <p class="mt-1.5 text-xs font-semibold text-cyra-muted">
                {{ __('ui.funded_left', [
                    'funded' => $opportunity->fundedLabel(),
                    'remaining' => number_format($remaining),
                ]) }}
            </p>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <a
                href="{{ route('investments.show', $opportunity) }}"
                class="inline-flex items-center justify-center rounded-lg bg-white px-3.5 py-2 text-xs font-bold text-cyra-ink ring-1 ring-cyra-line hover:bg-cyra-surface"
            >
                {{ __('ui.view_photos_reviews') }}
            </a>
            @if ($remaining > 0 && (int) $walletBalance >= 10000)
                <a
                    href="{{ route('investments.show', $opportunity) }}#invest"
                    class="inline-flex items-center justify-center rounded-lg bg-cyra-forest px-3.5 py-2 text-xs font-bold text-white hover:bg-cyra-green"
                >
                    {{ __('ui.invest') }}
                </a>
            @elseif ($remaining > 0)
                <a
                    href="{{ route('wallet.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-cyra-forest px-3.5 py-2 text-xs font-bold text-cyra-forest hover:bg-cyra-mint"
                >
                    {{ __('ui.fund_wallet') }}
                </a>
            @else
                <span class="inline-flex items-center rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 ring-1 ring-amber-200">
                    {{ __('ui.fully_funded') }}
                </span>
            @endif
        </div>
    </div>
</article>
