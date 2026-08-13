<x-dashboard-layout
    title="Investment Marketplace"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Investment Marketplace'],
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
            @if (str_contains(session('error'), 'wallet') || str_contains(session('error'), 'Insufficient'))
                <a href="{{ route('wallet.index') }}" class="ml-2 font-bold underline hover:no-underline">{{ __('ui.fund_wallet') }}</a>
            @endif
        </div>
    @endif

    <x-page-header
        :title="__('ui.invest_quality_farms')"
        :description="__('ui.invest_quality_farms_desc')"
    >
        <x-slot:actions>
            <a
                href="{{ route('investor.dashboard') }}"
                class="inline-flex items-center justify-center rounded-xl bg-cyra-card px-4 py-2.5 text-sm font-semibold text-cyra-ink ring-1 ring-cyra-line transition hover:bg-cyra-surface"
            >
                {{ __('nav.portfolio') }}
            </a>
            <a
                href="{{ route('wallet.index') }}"
                class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-cyra-green"
            >
                {{ __('ui.wallet') }} ₦{{ number_format($wallet_balance) }}
            </a>
        </x-slot:actions>
    </x-page-header>

    @unless ($can_invest)
        <div class="mb-5 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900 ring-1 ring-amber-200">
            {{ __('ui.fund_wallet_hint') }}
            <a href="{{ route('wallet.index') }}" class="font-bold underline hover:no-underline">{{ __('ui.go_to_wallet') }}</a>
        </div>
    @endunless

    <div>
        <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">
            @if (! empty($query))
                {{ __('ui.search_results_for', ['query' => $query]) }}
            @elseif ($show_all)
                {{ __('ui.all_opportunities') }}
            @else
                {{ __('ui.featured_opportunities') }}
            @endif
        </h2>

        @if ($opportunities->isEmpty())
            <p class="mt-4 text-sm text-cyra-muted">
                @if (! empty($query))
                    {{ __('ui.no_investment_matches', ['query' => $query]) }}
                    <a href="{{ route('investments.index', ['all' => 1]) }}" class="font-semibold text-cyra-forest hover:underline">{{ __('ui.clear_search') }}</a>
                @else
                    {{ __('ui.no_opportunities') }}
                @endif
            </p>
        @else
            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($opportunities as $opportunity)
                    <x-investment.opportunity-card
                        :opportunity="$opportunity"
                        :wallet-balance="$wallet_balance"
                        :show-all="$show_all"
                    />
                @endforeach
            </div>
        @endif

        <div class="mt-8 flex justify-center gap-3">
            @if (! empty($query))
                <a
                    href="{{ route('investments.index', ['all' => 1]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-cyra-line bg-cyra-card px-5 py-2.5 text-sm font-semibold text-cyra-ink transition hover:bg-cyra-mint hover:text-cyra-forest"
                >
                    {{ __('ui.clear_search') }}
                </a>
            @elseif ($show_all)
                <a
                    href="{{ route('investments.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-cyra-line bg-cyra-card px-5 py-2.5 text-sm font-semibold text-cyra-ink transition hover:bg-cyra-mint hover:text-cyra-forest"
                >
                    {{ __('ui.show_featured') }}
                </a>
            @else
                <a
                    href="{{ route('investments.index', ['all' => 1]) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-cyra-line bg-cyra-card px-5 py-2.5 text-sm font-semibold text-cyra-ink transition hover:bg-cyra-mint hover:text-cyra-forest"
                >
                    {{ __('ui.view_all_opportunities') }}
                </a>
            @endif
        </div>
    </div>
</x-dashboard-layout>
