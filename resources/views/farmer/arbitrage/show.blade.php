<x-dashboard-layout
    title="AI Arbitrage Dashboard"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'AI Arbitrage Dashboard'],
    ]"
>
    {{-- Best opportunity header --}}
    <x-page-header title="{{ $opportunity->routeLabel() }}">
        <x-slot:actions>
            <div class="text-right">
                <p class="text-sm text-cyra-muted">Potential Profit</p>
                <p class="mt-1 text-2xl font-extrabold text-cyra-forest sm:text-3xl">
                    {{ $opportunity->formattedPotentialProfit() }}
                </p>
                <p class="mt-1 text-base font-bold text-cyra-green sm:text-lg">
                    {{ number_format($opportunity->roi_percent, 1) }}% ROI
                </p>
            </div>
        </x-slot:actions>
    </x-page-header>

    <p class="-mt-4 text-sm text-cyra-muted">Best Arbitrage Opportunity · {{ $opportunity->commodity_name }}</p>

    {{-- Buy / Sell markets --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2">
        <x-arbitrage.market-card
            label="Buy Market"
            :market="$opportunity->buy_market"
            :price="$opportunity->formattedBuyPrice()"
            icon="sack"
        />
        <x-arbitrage.market-card
            label="Sell Market"
            :market="$opportunity->sell_market"
            :price="$opportunity->formattedSellPrice()"
            icon="market"
        />
    </section>

    {{-- Costs + AI recommendation --}}
    <section class="mt-6 grid gap-5 lg:grid-cols-2">
        <x-arbitrage.cost-breakdown :costs="$costs" :total-cost="$total_cost" />
        <x-arbitrage.recommendation :opportunity="$opportunity" />
    </section>
</x-dashboard-layout>
