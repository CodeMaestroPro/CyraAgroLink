<x-dashboard-layout
    title="Arbitrage Analysis"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'AI Arbitrage Dashboard', 'href' => route('arbitrage.show')],
        ['label' => 'Full Analysis'],
    ]"
>
    <x-page-header title="Full Analysis · {{ $opportunity->routeLabel() }}" />

    <div class="grid gap-5 sm:grid-cols-3">
        <article class="rounded-xl bg-white p-4 ring-1 ring-cyra-line">
            <p class="text-xs text-cyra-muted">Gross Spread</p>
            <p class="mt-1 text-lg font-extrabold text-cyra-forest">{{ $opportunity->formattedPotentialProfit() }}</p>
        </article>
        <article class="rounded-xl bg-white p-4 ring-1 ring-cyra-line">
            <p class="text-xs text-cyra-muted">Total Logistics Cost</p>
            <p class="mt-1 text-lg font-extrabold text-cyra-ink">₦{{ number_format($total_cost) }}</p>
        </article>
        <article class="rounded-xl bg-white p-4 ring-1 ring-cyra-line">
            <p class="text-xs text-cyra-muted">Net Profit / Ton</p>
            <p class="mt-1 text-lg font-extrabold text-cyra-forest">₦{{ number_format($net_profit) }}</p>
        </article>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-2">
        <x-arbitrage.cost-breakdown :costs="$costs" :total-cost="$total_cost" />

        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
            <h2 class="text-base font-extrabold text-cyra-ink">Trade Summary</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-cyra-muted">Commodity</dt>
                    <dd class="font-semibold text-cyra-ink">{{ $opportunity->commodity_name }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-cyra-muted">Buy in {{ $opportunity->buy_market }}</dt>
                    <dd class="font-semibold text-cyra-ink">{{ $opportunity->formattedBuyPrice() }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-cyra-muted">Sell in {{ $opportunity->sell_market }}</dt>
                    <dd class="font-semibold text-cyra-ink">{{ $opportunity->formattedSellPrice() }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-cyra-muted">Projected ROI</dt>
                    <dd class="font-bold text-cyra-forest">{{ number_format($opportunity->roi_percent, 1) }}%</dd>
                </div>
            </dl>

            <p class="mt-5 text-sm leading-relaxed text-cyra-forest">
                <span class="font-bold text-cyra-green">{{ $opportunity->recommendation_title }}</span>
                {{ $opportunity->recommendation_body }}
            </p>
        </article>
    </div>
</x-dashboard-layout>
