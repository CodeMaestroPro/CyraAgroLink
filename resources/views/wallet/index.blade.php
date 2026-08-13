@php
    $initialPanel = old('_wallet_panel', $panel ?: null);
    if ($errors->any() && ! $initialPanel) {
        $initialPanel = 'deposit';
    }
@endphp

<x-dashboard-layout
    title="Digital Wallet"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Digital Wallet'],
    ]"
>
    <x-page-header
        title="Digital Wallet"
        description="Fund, withdraw, and track payments for marketplace, logistics, and farm investments."
    >
        <x-slot:actions>
            <a
                href="{{ route('investments.index', ['all' => 1]) }}"
                class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-cyra-ink ring-1 ring-cyra-line hover:bg-cyra-surface"
            >
                Invest
            </a>
            <a
                href="{{ route('consumer.marketplace', ['view' => 'shop']) }}"
                class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyra-green"
            >
                Shop
            </a>
        </x-slot:actions>
    </x-page-header>

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

    <div x-data="{ panel: @js($initialPanel) }" class="space-y-7">
        <section
            class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0D6B34] via-cyra-forest to-[#0A5C2E] p-6 text-white sm:p-8"
            aria-labelledby="wallet-balance-heading"
        >
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.14]"
                style="background-image: radial-gradient(circle at 18% 20%, rgba(255,255,255,0.55) 0%, transparent 42%), radial-gradient(circle at 88% 78%, rgba(184,224,196,0.45) 0%, transparent 40%);"
                aria-hidden="true"
            ></div>

            <div class="relative">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p id="wallet-balance-heading" class="text-sm font-medium text-white/80">
                            Wallet Balance
                        </p>
                        <p class="mt-3 text-3xl font-extrabold tracking-tight tabular-nums sm:text-4xl">
                            {{ $balance['amount'] }}
                        </p>
                        <p class="mt-2 text-sm font-medium text-white/70">
                            {{ $balance['currency_label'] }}
                        </p>
                    </div>

                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold text-white ring-1 ring-white/20 backdrop-blur-sm">
                        <span class="h-1.5 w-1.5 rounded-full bg-cyra-soft" aria-hidden="true"></span>
                        Available
                    </span>
                </div>

                <dl class="mt-6 grid grid-cols-3 gap-2 text-center sm:gap-3">
                    <div class="rounded-xl bg-white/10 px-2 py-3 ring-1 ring-white/15">
                        <dt class="text-[11px] font-medium text-white/70">Money in</dt>
                        <dd class="mt-1 text-sm font-bold tabular-nums">{{ $stats['in'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-white/10 px-2 py-3 ring-1 ring-white/15">
                        <dt class="text-[11px] font-medium text-white/70">Money out</dt>
                        <dd class="mt-1 text-sm font-bold tabular-nums">{{ $stats['out'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-white/10 px-2 py-3 ring-1 ring-white/15">
                        <dt class="text-[11px] font-medium text-white/70">Ledger</dt>
                        <dd class="mt-1 text-sm font-bold tabular-nums">{{ number_format($stats['count']) }}</dd>
                    </div>
                </dl>

                <div class="mt-6 grid grid-cols-2 gap-2.5 sm:gap-3">
                    <button
                        type="button"
                        @click="panel = panel === 'deposit' ? null : 'deposit'"
                        class="group flex flex-col items-center justify-center gap-1.5 rounded-xl bg-white px-2 py-3.5 text-cyra-ink shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-cyra-forest sm:py-4"
                    >
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest transition group-hover:bg-cyra-forest group-hover:text-white">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14"/>
                            </svg>
                        </span>
                        <span class="text-sm font-semibold">Deposit</span>
                    </button>

                    <button
                        type="button"
                        @click="panel = panel === 'withdraw' ? null : 'withdraw'"
                        class="group flex flex-col items-center justify-center gap-1.5 rounded-xl bg-white px-2 py-3.5 text-cyra-ink shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-cyra-forest sm:py-4"
                    >
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest transition group-hover:bg-cyra-forest group-hover:text-white">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5M5 12h14"/>
                            </svg>
                        </span>
                        <span class="text-sm font-semibold">Withdraw</span>
                    </button>
                </div>
            </div>
        </section>

        <section x-show="panel === 'deposit'" x-cloak class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6" x-data="{ amount: {{ (int) old('amount', 5000) }} }">
            <h2 class="text-base font-extrabold text-cyra-ink">Fund wallet</h2>
            <p class="mt-1 text-sm text-cyra-muted">Add money before investing, booking logistics, or shopping.</p>
            <form method="POST" action="{{ route('wallet.deposit') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="_wallet_panel" value="deposit">
                <div>
                    <label for="deposit_amount" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Amount (₦)</label>
                    <input
                        id="deposit_amount"
                        type="number"
                        name="amount"
                        x-model.number="amount"
                        min="100"
                        max="50000000"
                        step="100"
                        required
                        class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ([1000, 5000, 10000, 50000, 100000, 500000] as $preset)
                            <button
                                type="button"
                                @click="amount = {{ $preset }}"
                                class="rounded-md bg-cyra-surface px-2.5 py-1 text-[11px] font-semibold text-cyra-ink ring-1 ring-cyra-line hover:bg-cyra-mint hover:text-cyra-forest"
                            >
                                ₦{{ number_format($preset) }}
                            </button>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>
                <div>
                    <label for="deposit_note" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Note (optional)</label>
                    <input
                        id="deposit_note"
                        type="text"
                        name="note"
                        value="{{ old('note') }}"
                        maxlength="255"
                        placeholder="e.g. Card top-up"
                        class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                </div>
                <button type="submit" class="inline-flex rounded-lg bg-cyra-forest px-5 py-3 text-sm font-bold text-white hover:bg-cyra-green">
                    Fund wallet
                </button>
            </form>
        </section>

        <section x-show="panel === 'withdraw'" x-cloak class="rounded-2xl bg-white p-5 ring-1 ring-cyra-line sm:p-6" x-data="{ amount: {{ (int) old('amount', min(1000, max(100, $balance['raw']))) }} }">
            <h2 class="text-base font-extrabold text-cyra-ink">Withdraw</h2>
            <p class="mt-1 text-sm text-cyra-muted">
                Move available funds out.
                @if ($balance['raw'] > 0)
                    Available {{ $balance['amount'] }}.
                @else
                    Deposit first — your balance is ₦0.
                @endif
            </p>
            <form method="POST" action="{{ route('wallet.withdraw') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="_wallet_panel" value="withdraw">
                <div>
                    <label for="withdraw_amount" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Amount (₦)</label>
                    <input
                        id="withdraw_amount"
                        type="number"
                        name="amount"
                        x-model.number="amount"
                        min="100"
                        max="{{ max(100, $balance['raw']) }}"
                        step="100"
                        required
                        @disabled($balance['raw'] < 100)
                        class="block w-full rounded-lg border border-cyra-line bg-white px-3.5 py-3 text-sm text-cyra-ink shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20 disabled:opacity-50"
                    >
                    @if ($balance['raw'] >= 100)
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ([1000, 5000, 10000] as $preset)
                                @if ($preset <= $balance['raw'])
                                    <button type="button" @click="amount = {{ $preset }}" class="rounded-md bg-cyra-surface px-2.5 py-1 text-[11px] font-semibold ring-1 ring-cyra-line">₦{{ number_format($preset) }}</button>
                                @endif
                            @endforeach
                            <button type="button" @click="amount = {{ $balance['raw'] }}" class="rounded-md bg-cyra-surface px-2.5 py-1 text-[11px] font-semibold ring-1 ring-cyra-line">Max</button>
                        </div>
                    @endif
                    <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                </div>
                <div>
                    <label for="withdraw_note" class="mb-1.5 block text-sm font-semibold text-cyra-ink">Note (optional)</label>
                    <input
                        id="withdraw_note"
                        type="text"
                        name="note"
                        value="{{ old('note') }}"
                        maxlength="255"
                        placeholder="e.g. To GTBank ****1234"
                        class="block w-full rounded-lg border border-cyra-line px-3.5 py-3 text-sm shadow-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                </div>
                <button
                    type="submit"
                    @disabled($balance['raw'] < 100)
                    class="inline-flex rounded-lg border border-cyra-line px-5 py-3 text-sm font-bold text-cyra-ink hover:bg-cyra-surface disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Withdraw
                </button>
            </form>
        </section>

        <section id="transactions" aria-labelledby="recent-transactions-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="recent-transactions-heading" class="text-base font-extrabold tracking-tight text-cyra-ink sm:text-lg">
                    Recent Transactions
                </h2>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['all' => 'All', 'credit' => 'In', 'debit' => 'Out'] as $key => $label)
                        <a
                            href="{{ route('wallet.index', array_filter(['filter' => $key === 'all' ? null : $key, 'panel' => $panel ?: null])) }}"
                            @class([
                                'rounded-lg px-3 py-1.5 text-xs font-bold ring-1 transition',
                                'bg-cyra-forest text-white ring-cyra-forest' => $filter === $key,
                                'bg-white text-cyra-ink ring-cyra-line hover:bg-cyra-surface' => $filter !== $key,
                            ])
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if (count($transactions) === 0)
                <div class="mt-3 rounded-2xl bg-white px-6 py-10 text-center ring-1 ring-cyra-line">
                    <p class="text-sm font-semibold text-cyra-ink">
                        {{ $filter === 'all' ? 'No transactions yet.' : 'No '.$filter.' transactions yet.' }}
                    </p>
                    <p class="mt-1 text-sm text-cyra-muted">Deposit funds to start using your wallet for payments.</p>
                    <button
                        type="button"
                        @click="panel = 'deposit'"
                        class="mt-4 inline-flex rounded-lg bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green"
                    >
                        Fund wallet
                    </button>
                </div>
            @else
                <ul class="mt-3 divide-y divide-cyra-line/90 rounded-2xl bg-white p-1.5 ring-1 ring-cyra-line sm:p-2">
                    @foreach ($transactions as $transaction)
                        <x-wallet.transaction-item
                            :title="$transaction['title']"
                            :detail="$transaction['detail'].' · Balance '.$transaction['balance_after']"
                            :amount="$transaction['amount']"
                            :time="$transaction['time']"
                            :tone="$transaction['tone']"
                            :icon="$transaction['icon']"
                        />
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-dashboard-layout>
