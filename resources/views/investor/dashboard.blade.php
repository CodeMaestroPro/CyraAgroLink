<x-dashboard-layout
    title="Investor Dashboard"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Portfolio'],
    ]"
>
    @if (session('status'))
        <div class="mb-5 rounded-xl bg-cyra-mint px-4 py-3 text-sm font-medium text-cyra-forest ring-1 ring-cyra-soft/60" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <x-page-header
        :title="__('ui.welcome_investor', ['name' => $greetingName])"
        :description="__('ui.investor_overview')"
    >
        <x-slot:actions>
            <a
                href="{{ route('investments.index', ['all' => 1]) }}"
                class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
            >
                {{ __('ui.browse_farms') }}
            </a>
            <a
                href="{{ route('wallet.index') }}"
                class="inline-flex items-center justify-center rounded-xl border-2 border-cyra-forest px-4 py-2.5 text-sm font-semibold text-cyra-forest transition hover:bg-cyra-forest hover:text-white"
            >
                {{ __('ui.wallet') }} ₦{{ number_format($walletBalance) }}
            </a>
        </x-slot:actions>
    </x-page-header>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Portfolio summary">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:col-span-2 xl:col-span-2">
            <p class="text-sm font-medium text-cyra-muted">{{ __('ui.total_portfolio_value') }}</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-cyra-ink sm:text-4xl">
                {{ $portfolio['total_value'] }}
            </p>
            @php $valueTone = $portfolio['value_change_tone'] ?? 'flat'; @endphp
            <p @class([
                'mt-2 inline-flex items-center gap-1 text-sm font-semibold',
                'text-cyra-leaf' => $valueTone === 'up',
                'text-rose-600' => $valueTone === 'down',
                'text-cyra-muted' => $valueTone === 'flat',
            ])>
                @if ($valueTone === 'up')
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 01.53.22l5 5a.75.75 0 11-1.06 1.06L10.75 5.81V16.5a.75.75 0 01-1.5 0V5.81L5.53 9.28A.75.75 0 014.47 8.22l5-5A.75.75 0 0110 3z" clip-rule="evenodd"/></svg>
                @elseif ($valueTone === 'down')
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 17a.75.75 0 01-.53-.22l-5-5a.75.75 0 111.06-1.06l3.72 3.72V3.5a.75.75 0 011.5 0v10.94l3.72-3.72a.75.75 0 111.06 1.06l-5 5A.75.75 0 0110 17z" clip-rule="evenodd"/></svg>
                @endif
                {{ $portfolio['value_change'] }} vs last month
            </p>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:col-span-2 xl:col-span-2">
            <p class="text-sm font-medium text-cyra-muted">{{ __('ui.total_earnings') }}</p>
            <p class="mt-2 text-3xl font-extrabold tracking-tight text-cyra-forest sm:text-4xl">
                {{ $portfolio['total_earnings'] }}
            </p>
            @php $earnTone = $portfolio['earnings_change_tone'] ?? 'flat'; @endphp
            <p @class([
                'mt-2 inline-flex items-center gap-1 text-sm font-semibold',
                'text-cyra-leaf' => $earnTone === 'up',
                'text-rose-600' => $earnTone === 'down',
                'text-cyra-muted' => $earnTone === 'flat',
            ])>
                {{ $portfolio['earnings_change'] }} payouts (30d)
            </p>
            <p class="mt-2 text-xs text-cyra-muted">
                Pending {{ $portfolio['pending_earnings'] ?? '₦0' }} · Collected {{ $portfolio['lifetime_payouts'] ?? '₦0' }}
            </p>
        </article>
    </section>

    <section class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3" aria-label="Performance and activity">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line lg:col-span-2">
            <h2 class="text-base font-extrabold text-cyra-ink">Portfolio Performance</h2>
            <p class="mt-1 text-xs text-cyra-muted">Principal plus unpaid accrued earnings over the last six months</p>
            <div class="mt-4 h-64 sm:h-72">
                <canvas
                    id="portfolioPerformanceChart"
                    data-labels='@json($performance['labels'])'
                    data-values='@json($performance['values'])'
                    aria-label="Portfolio performance over the last six months"
                    role="img"
                ></canvas>
            </div>
        </article>

        <div class="flex flex-col gap-4">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
                <p class="text-sm font-medium text-cyra-muted">{{ __('ui.active_investments') }}</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-cyra-ink">
                    {{ $portfolio['active_investments'] }}
                </p>
            </article>

            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
                <p class="text-sm font-medium text-cyra-muted">{{ __('ui.total_roi') }}</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-cyra-forest">
                    {{ $portfolio['total_roi'] }}
                </p>
            </article>

            <article class="flex flex-1 flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-base font-extrabold text-cyra-ink">{{ __('ui.recent_payouts') }}</h2>
                    <a href="{{ route('wallet.index') }}" class="text-xs font-semibold text-cyra-forest hover:underline">{{ __('ui.wallet') }}</a>
                </div>
                <ul class="mt-3 flex-1 space-y-3">
                    @forelse ($recentEarnings as $earning)
                        <li class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v18M17 8H9.5a2.5 2.5 0 000 5H14a2.5 2.5 0 010 5H7"/>
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-cyra-ink">{{ $earning['title'] }}</p>
                                <p class="text-xs text-cyra-muted">
                                    {{ $earning['location'] }}
                                    @if (! empty($earning['amount']))
                                        · {{ $earning['amount'] }}
                                    @endif
                                </p>
                            </div>
                            @if (! empty($earning['paid_at']))
                                <span class="shrink-0 text-[11px] font-medium text-cyra-muted">{{ $earning['paid_at'] }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="text-sm text-cyra-muted">No payouts yet. Collect from a holding when earnings accrue.</li>
                    @endforelse
                </ul>
            </article>
        </div>
    </section>

    <section class="mt-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6" aria-label="Active holdings">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-extrabold text-cyra-ink">{{ __('ui.active_holdings') }}</h2>
                <p class="mt-1 text-sm text-cyra-muted">Earnings accrue monthly from your invest date. Collect into your wallet anytime they are due.</p>
                @if (! empty($query))
                    <p class="mt-2 text-sm text-cyra-ink">{{ __('ui.holdings_results_for', ['query' => $query]) }}</p>
                @endif
            </div>
            <a
                href="{{ route('investments.index', ['all' => 1]) }}"
                class="text-sm font-semibold text-cyra-forest hover:underline"
            >
                Find opportunities
            </a>
        </div>

        <div class="mt-4 divide-y divide-cyra-line/80">
            @forelse ($holdings as $holding)
                <article class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        @if (! empty($holding['opportunity_id']))
                            <a href="{{ route('investments.show', $holding['opportunity_id']) }}" class="text-sm font-bold text-cyra-ink hover:text-cyra-forest hover:underline">
                                {{ $holding['title'] }}
                            </a>
                        @else
                            <p class="text-sm font-bold text-cyra-ink">{{ $holding['title'] }}</p>
                        @endif
                        <p class="mt-0.5 text-xs text-cyra-muted">
                            {{ $holding['location'] }} · Principal {{ $holding['amount'] }} · Value {{ $holding['value'] }} · ROI {{ $holding['roi'] }}
                        </p>
                        <p class="mt-0.5 text-xs text-cyra-muted">
                            Invested {{ $holding['invested_at'] ?? '—' }} · Pending {{ $holding['earnings'] }}
                        </p>
                    </div>
                    @if ($holding['can_collect'])
                        <form method="POST" action="{{ route('investor.collect', $holding['id']) }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-cyra-forest px-3.5 py-2 text-xs font-bold text-white hover:bg-cyra-green sm:w-auto"
                            >
                                Collect {{ $holding['collectible_label'] }}
                            </button>
                        </form>
                    @else
                        <span class="text-xs font-semibold text-cyra-muted">{{ __('ui.no_earnings_due') }}</span>
                    @endif
                </article>
            @empty
                <div class="py-8 text-center">
                    @if (! empty($query))
                        <p class="text-sm text-cyra-muted">{{ __('ui.no_holding_matches', ['query' => $query]) }}</p>
                        <div class="mt-4">
                            <a href="{{ route('investor.dashboard') }}" class="inline-flex rounded-xl border-2 border-cyra-forest px-4 py-2.5 text-sm font-bold text-cyra-forest hover:bg-cyra-forest hover:text-white">
                                {{ __('ui.clear_search') }}
                            </a>
                        </div>
                    @else
                        <p class="text-sm text-cyra-muted">{{ __('ui.no_active_holdings') }}</p>
                        <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                            <a href="{{ route('wallet.index') }}" class="inline-flex rounded-xl border-2 border-cyra-forest px-4 py-2.5 text-sm font-bold text-cyra-forest hover:bg-cyra-forest hover:text-white">
                                {{ __('ui.fund_wallet') }}
                            </a>
                            <a href="{{ route('investments.index', ['all' => 1]) }}" class="inline-flex rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green">
                                {{ __('ui.browse_farms') }}
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>
    </section>
</x-dashboard-layout>
