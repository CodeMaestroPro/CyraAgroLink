@php
    /** @var \App\Models\InvestmentOpportunity $opportunity */
    $gallery = $gallery ?? $opportunity->galleryUrls();
    $minTicket = (int) ($min_ticket ?? 10000);
    $hasHolding = (bool) ($has_holding ?? false);
    $canReview = (bool) ($can_review ?? false);
    $isOpen = (bool) ($is_open ?? ($opportunity->status === 'active'));
    $fundedPercent = min(100, max(0, (int) $opportunity->funded_percent));
    $title = $opportunity->localizedTitle();
    $location = $opportunity->localizedLocation();
    $summary = $opportunity->localizedSummary();
@endphp

<x-dashboard-layout
    title="{{ $title }}"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => __('nav.dashboard'), 'href' => route('dashboard')],
        ['label' => __('nav.investments'), 'href' => route('investments.index', ['all' => 1])],
        ['label' => $title],
    ]"
>
    @if (session('status'))
        <div class="mb-5 rounded-xl bg-cyra-mint px-4 py-3 text-sm font-medium text-cyra-forest ring-1 ring-cyra-soft/60" role="status">
            {{ session('status') }}
            <a href="{{ route('investor.dashboard') }}" class="ml-2 font-bold underline hover:no-underline">{{ __('ui.open_portfolio') }}</a>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
            @if (str_contains(strtolower((string) session('error')), 'wallet') || str_contains((string) session('error'), 'Insufficient'))
                <a href="{{ route('wallet.index') }}" class="ml-2 font-bold underline hover:no-underline">{{ __('ui.fund_wallet') }}</a>
            @endif
        </div>
    @endif

    @unless ($isOpen)
        <div class="mb-5 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900 ring-1 ring-amber-200" role="status">
            This opportunity is closed and no longer accepting new investments.
        </div>
    @endunless

    <x-page-header
        title="{{ $title }}"
        description="{{ $location }} · {{ $summary }}"
    >
        <x-slot:actions>
            <a
                href="{{ route('wallet.index') }}"
                class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-cyra-ink ring-1 ring-cyra-line hover:bg-cyra-surface"
            >
                {{ __('ui.wallet') }} ₦{{ number_format($wallet_balance) }}
            </a>
            <a
                href="{{ route('investments.index', ['all' => 1]) }}"
                class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyra-green"
            >
                {{ __('ui.all_farms') }}
            </a>
        </x-slot:actions>
    </x-page-header>

    <section
        class="overflow-hidden rounded-2xl bg-white ring-1 ring-cyra-line"
        aria-label="Farm photo gallery"
        x-data="{ active: 0, photos: @js($gallery) }"
    >
        <div class="aspect-[16/9] bg-cyra-surface sm:aspect-[21/9]">
            <img
                :src="photos[active]"
                :alt="'{{ addslashes($title) }} photo ' + (active + 1)"
                src="{{ $gallery[0] }}"
                alt="{{ __('ui.photo_n', ['title' => $title, 'n' => 1]) }}"
                class="h-full w-full object-cover"
            >
        </div>
        <div class="grid grid-cols-3 gap-2 p-3 sm:p-4">
            @foreach ($gallery as $index => $url)
                <button
                    type="button"
                    @click="active = {{ $index }}"
                    class="overflow-hidden rounded-xl ring-2 transition"
                    :class="active === {{ $index }} ? 'ring-cyra-forest' : 'ring-transparent hover:ring-cyra-line'"
                >
                    <img
                        src="{{ $url }}"
                        alt="{{ __('ui.photo_n', ['title' => $title, 'n' => $index + 1]) }}"
                        class="aspect-[4/3] w-full object-cover"
                        loading="lazy"
                    >
                </button>
            @endforeach
        </div>
        <p class="px-4 pb-4 text-xs font-medium text-cyra-muted">{{ count($gallery) }} farm photos — tap a thumbnail to switch views.</p>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-6">
            <section class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-base font-extrabold text-cyra-ink">Investor reviews</h2>
                        <p class="mt-1 text-sm text-cyra-muted">
                            @if ($reviews_count > 0)
                                ★ {{ number_format($average_rating, 1) }} average from {{ $reviews_count }} {{ Str::plural('review', $reviews_count) }}
                            @else
                                Be the first to review this farm.
                            @endif
                        </p>
                    </div>
                </div>

                @if ($canReview)
                    <form method="POST" action="{{ route('investments.reviews.store', $opportunity) }}" class="mt-5 space-y-3 rounded-xl bg-cyra-surface/70 p-4 ring-1 ring-cyra-line">
                        @csrf
                        <p class="text-sm font-bold text-cyra-ink">
                            {{ $user_review ? 'Update your review' : 'Write a review' }}
                        </p>
                        <div>
                            <label for="rating" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-cyra-muted">Rating</label>
                            <select
                                id="rating"
                                name="rating"
                                required
                                class="block w-full rounded-lg border border-cyra-line px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                            >
                                @for ($star = 5; $star >= 1; $star--)
                                    <option value="{{ $star }}" @selected((int) old('rating', $user_review->rating ?? 5) === $star)>
                                        {{ $star }} {{ Str::plural('star', $star) }}
                                    </option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                        </div>
                        <div>
                            <label for="title" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-cyra-muted">Title (optional)</label>
                            <input
                                id="title"
                                type="text"
                                name="title"
                                value="{{ old('title', $user_review->title ?? '') }}"
                                maxlength="120"
                                class="block w-full rounded-lg border border-cyra-line px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                            >
                        </div>
                        <div>
                            <label for="body" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-cyra-muted">Review</label>
                            <textarea
                                id="body"
                                name="body"
                                rows="3"
                                required
                                minlength="10"
                                maxlength="2000"
                                class="block w-full rounded-lg border border-cyra-line px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                            >{{ old('body', $user_review->body ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('body')" class="mt-2" />
                        </div>
                        <button type="submit" class="inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green">
                            {{ $user_review ? 'Update review' : 'Post review' }}
                        </button>
                    </form>
                @else
                    <div class="mt-5 rounded-xl bg-cyra-surface/70 px-4 py-4 text-sm text-cyra-muted ring-1 ring-cyra-line">
                        <p class="font-semibold text-cyra-ink">Invest to leave a review</p>
                        <p class="mt-1">Only investors with a holding in this farm can post a review.</p>
                        @if ($isOpen && $remaining > 0)
                            <a href="#invest" class="mt-3 inline-flex font-bold text-cyra-forest hover:underline">Invest in this farm</a>
                        @endif
                    </div>
                @endif

                <ul class="mt-5 divide-y divide-cyra-line/80">
                    @forelse ($reviews as $review)
                        <li class="py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-bold text-cyra-ink">
                                    {{ $review['author'] }}
                                    @if ($review['is_mine'])
                                        <span class="ml-1 text-xs font-semibold text-cyra-forest">(you)</span>
                                    @endif
                                </p>
                                <p class="text-xs font-semibold text-cyra-forest">★ {{ $review['rating'] }} · {{ $review['when'] }}</p>
                            </div>
                            @if ($review['title'])
                                <p class="mt-1 text-sm font-semibold text-cyra-ink">{{ $review['title'] }}</p>
                            @endif
                            <p class="mt-1 text-sm text-cyra-muted">{{ $review['body'] }}</p>
                        </li>
                    @empty
                        <li class="py-6 text-sm text-cyra-muted">No reviews yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <aside id="invest" class="scroll-mt-24 space-y-4 xl:sticky xl:top-6 xl:self-start">
            <div class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink">Invest in this farm</h2>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <dt class="text-cyra-muted">{{ __('ui.roi') }}</dt>
                        <dd class="mt-0.5 font-bold text-cyra-ink">{{ rtrim(rtrim(number_format($opportunity->roi_percent, 1), '0'), '.') }}%</dd>
                    </div>
                    <div>
                        <dt class="text-cyra-muted">{{ __('ui.duration') }}</dt>
                        <dd class="mt-0.5 font-bold text-cyra-ink">{{ $opportunity->durationLabel() }}</dd>
                    </div>
                    <div>
                        <dt class="text-cyra-muted">{{ __('ui.target') }}</dt>
                        <dd class="mt-0.5 font-bold text-cyra-ink">{{ $opportunity->formattedAmount() }}</dd>
                    </div>
                    <div>
                        <dt class="text-cyra-muted">{{ __('ui.remaining') }}</dt>
                        <dd class="mt-0.5 font-bold text-cyra-ink">₦{{ number_format($remaining) }}</dd>
                    </div>
                </dl>

                <div class="mt-4">
                    <div class="h-2 overflow-hidden rounded-full bg-cyra-line">
                        <div class="h-full rounded-full bg-cyra-sun" style="width: {{ $fundedPercent }}%"></div>
                    </div>
                    <p class="mt-1.5 text-xs font-semibold text-cyra-muted">
                        {{ __('ui.funded_left', [
                            'funded' => $opportunity->fundedLabel(),
                            'remaining' => number_format($remaining),
                        ]) }}
                    </p>
                </div>

                @if ($hasHolding)
                    <p class="mt-4 rounded-xl bg-cyra-mint/50 px-3 py-2 text-xs font-semibold text-cyra-forest ring-1 ring-cyra-forest/15">
                        You already hold a stake in this farm.
                    </p>
                @endif

                @if ($isOpen && $remaining >= 1000)
                    @php
                        $defaultAmount = (int) min(max($minTicket, 100000), $remaining);
                        if ($defaultAmount < $minTicket) {
                            $defaultAmount = $minTicket;
                        }
                    @endphp
                    <form method="POST" action="{{ route('investments.invest', $opportunity) }}" class="mt-5 space-y-3" x-data="{ amount: {{ (int) old('amount', $defaultAmount) }} }">
                        @csrf
                        <input type="hidden" name="detail" value="1">
                        <label for="amount" class="block text-xs font-bold uppercase tracking-wide text-cyra-muted">Amount (₦)</label>
                        <input
                            id="amount"
                            type="number"
                            name="amount"
                            x-model.number="amount"
                            min="{{ $minTicket }}"
                            max="{{ $remaining }}"
                            step="{{ $minTicket < 10000 ? 1 : 1000 }}"
                            required
                            class="block w-full rounded-lg border border-cyra-line px-3 py-2.5 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                        >
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ([25000, 50000, 100000] as $preset)
                                @if ($preset >= $minTicket && $preset <= $remaining)
                                    <button type="button" @click="amount = {{ $preset }}" class="rounded-md bg-cyra-surface px-2 py-1 text-[11px] font-semibold ring-1 ring-cyra-line">₦{{ number_format($preset) }}</button>
                                @endif
                            @endforeach
                            @if ($remaining >= $minTicket)
                                <button type="button" @click="amount = {{ $remaining }}" class="rounded-md bg-cyra-surface px-2 py-1 text-[11px] font-semibold ring-1 ring-cyra-line">Max</button>
                            @endif
                        </div>
                        <p class="text-xs text-cyra-muted">
                            Wallet ₦{{ number_format($wallet_balance) }}
                            · Min ₦{{ number_format($minTicket) }}
                        </p>
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                        @if ($wallet_balance < $minTicket)
                            <a href="{{ route('wallet.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border-2 border-cyra-forest px-4 py-3 text-sm font-bold text-cyra-forest hover:bg-cyra-forest hover:text-white">
                                Fund wallet to invest
                            </a>
                        @else
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-cyra-forest px-4 py-3 text-sm font-bold text-white hover:bg-cyra-green">
                                Confirm investment
                            </button>
                        @endif
                    </form>
                @elseif ($isOpen)
                    <p class="mt-5 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 ring-1 ring-amber-200">
                        This farm raise is fully funded.
                    </p>
                @else
                    <p class="mt-5 rounded-xl bg-cyra-surface px-4 py-3 text-sm font-medium text-cyra-muted ring-1 ring-cyra-line">
                        Investing is unavailable while this opportunity is closed.
                    </p>
                @endif
            </div>
        </aside>
    </div>
</x-dashboard-layout>
