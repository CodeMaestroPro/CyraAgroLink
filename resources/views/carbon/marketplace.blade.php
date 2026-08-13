<x-dashboard-layout
    title="Carbon Credit Marketplace"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Sustainability'],
        ['label' => 'Carbon Credits'],
    ]"
>
    <x-page-header
        title="Carbon Credit Marketplace"
        description="Track carbon credit generation from your farms, list credits for sale, and settle trades into your wallet."
    >
        <x-slot:actions>
            <form method="POST" action="{{ $actions['generate_url'] }}">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green">
                    Claim field credits
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/50 px-4 py-3 text-sm text-cyra-forest ring-1 ring-cyra-line" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <h2 id="overview" class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
        Carbon Impact Overview
    </h2>

    @if (count($farms))
        <p class="mt-2 text-sm text-cyra-muted">
            Linked farms:
            {{ collect($farms)->pluck('name')->join(', ') }}
            ({{ number_format(collect($farms)->sum('hectares'), 1) }} ha)
        </p>
    @endif

    <section id="credits" class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Carbon impact metrics">
        @foreach ($kpis as $kpi)
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <p class="text-sm font-medium text-cyra-muted">{{ $kpi['label'] }}</p>
                <p @class([
                    'mt-2 text-2xl font-extrabold tracking-tight tabular-nums sm:text-[1.65rem]',
                    'text-cyra-forest' => $kpi['tone'] === 'green',
                    'text-cyra-ink' => $kpi['tone'] === 'ink',
                ])>
                    {{ $kpi['value'] }}
                </p>
                @if ($kpi['meta'])
                    <p class="mt-1 text-xs font-medium text-cyra-muted">{{ $kpi['meta'] }}</p>
                @endif
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-3" aria-label="Trend and transactions">
        <article id="trend" class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5 xl:col-span-2">
            <h2 class="text-base font-extrabold text-cyra-ink">Credits Trend</h2>
            <div class="mt-4 h-64 sm:h-72">
                <canvas
                    id="carbonCreditsTrendChart"
                    data-labels='@json($trend['labels'])'
                    data-values='@json($trend['values'])'
                    aria-label="Carbon credits trend chart"
                    role="img"
                ></canvas>
            </div>
        </article>

        <article id="transactions" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink">Recent Transactions</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($transactions as $tx)
                    <li class="flex items-start gap-3 border-b border-cyra-line/70 pb-3 last:border-0 last:pb-0">
                        <span @class([
                            'mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full',
                            'bg-cyra-panel text-cyra-muted' => $tx['tone'] === 'credit',
                            'bg-rose-50 text-rose-600' => $tx['tone'] === 'debit',
                        ])>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                @if ($tx['tone'] === 'credit')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 17l5-5 5 5M7 7l5 5 5-5"/>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14m0 0l-5-5m5 5l5-5"/>
                                @endif
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-cyra-ink">{{ $tx['title'] }}</p>
                            <p @class([
                                'mt-0.5 text-sm font-bold tabular-nums',
                                'text-cyra-forest' => $tx['tone'] === 'credit',
                                'text-rose-600' => $tx['tone'] === 'debit',
                            ])>{{ $tx['credits'] }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-bold tabular-nums text-cyra-ink">{{ $tx['value'] }}</span>
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No carbon transactions yet.</li>
                @endforelse
            </ul>

            <form method="POST" action="{{ $actions['offset_url'] }}" class="mt-5 space-y-2 border-t border-cyra-line/70 pt-4">
                @csrf
                <label for="offset-credits" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Offset credits</label>
                <div class="flex gap-2">
                    <input
                        id="offset-credits"
                        type="number"
                        name="credits"
                        min="1"
                        step="0.1"
                        value="5"
                        class="w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                    <button type="submit" class="shrink-0 rounded-xl border-2 border-cyra-forest/30 px-3 py-2 text-sm font-bold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white">
                        Offset
                    </button>
                </div>
            </form>
        </article>
    </section>

    <section id="listings" class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-2" aria-label="List and sell credits">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink">List Credits for Sale</h2>
            <p class="mt-2 text-sm text-cyra-muted">
                Reserve credits from your balance at ${{ number_format($unitPriceUsd, 2) }}/tCO2e. Completing a sale credits your digital wallet in naira.
            </p>
            <form method="POST" action="{{ $actions['list_url'] }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label for="list-credits" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Credits (tCO2e)</label>
                    <input
                        id="list-credits"
                        type="number"
                        name="credits"
                        min="1"
                        step="0.1"
                        value="{{ $defaultListCredits }}"
                        required
                        class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                </div>
                <div>
                    <label for="unit-price" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Unit price (USD)</label>
                    <input
                        id="unit-price"
                        type="number"
                        name="unit_price_usd"
                        min="1"
                        step="0.01"
                        value="{{ $unitPriceUsd }}"
                        class="mt-1 w-full rounded-xl border-cyra-line text-sm text-cyra-ink focus:border-cyra-forest focus:ring-cyra-forest"
                    >
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white transition hover:bg-cyra-green">
                        List Credits for Sale
                    </button>
                </div>
            </form>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink">Open Listings</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($listings as $listing)
                    <li class="flex flex-wrap items-center justify-between gap-3 border-b border-cyra-line/70 pb-3 last:border-0 last:pb-0">
                        <div>
                            <p class="text-sm font-semibold text-cyra-ink">{{ $listing['credits'] }}</p>
                            <p class="text-xs text-cyra-muted">{{ $listing['price'] }} · {{ $listing['value'] }}</p>
                        </div>
                        <form method="POST" action="{{ $listing['sell_url'] }}">
                            @csrf
                            <button type="submit" class="rounded-xl bg-cyra-forest px-3 py-2 text-sm font-bold text-white transition hover:bg-cyra-green">
                                Complete sale
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No open listings. List credits above to start a trade.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <div class="mt-6 flex flex-wrap gap-3">
        <a
            href="#credits"
            class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
        >
            View All Credits
        </a>
        <a
            href="#listings"
            class="inline-flex items-center justify-center rounded-xl border-2 border-cyra-forest/30 bg-white px-5 py-2.5 text-sm font-bold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white"
        >
            List Credits for Sale
        </a>
        <a
            href="{{ $actions['wallet_url'] }}"
            class="inline-flex items-center justify-center rounded-xl border-2 border-cyra-forest/30 bg-white px-5 py-2.5 text-sm font-bold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white"
        >
            Open Wallet
        </a>
    </div>
</x-dashboard-layout>
