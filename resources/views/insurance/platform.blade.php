<x-dashboard-layout
    title="Farm Insurance Platform"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Finance'],
        ['label' => 'Insurance'],
    ]"
>
    <x-page-header
        title="Farm Insurance Platform"
        description="Buy coverage for crops, poultry, aquaculture, and equipment. File claims and settle payouts to your wallet."
    >
        <x-slot:actions>
            <a
                href="{{ $actions['wallet_url'] }}"
                class="inline-flex items-center rounded-xl border-2 border-cyra-forest/30 bg-white px-4 py-2 text-sm font-semibold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-forest hover:text-white"
            >
                Wallet · ₦{{ number_format($walletBalance) }}
            </a>
            <a
                href="#buy"
                class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-cyra-green"
            >
                Buy New Policy
            </a>
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

    <div id="overview">
        <h2 class="text-xl font-extrabold tracking-tight text-cyra-ink sm:text-2xl">
            Insurance Overview
        </h2>
        <p class="mt-1 text-sm text-cyra-muted">
            Policies, coverage, and claim activity
            @if (count($farms))
                · Farms: {{ collect($farms)->pluck('name')->join(', ') }}
            @endif
        </p>
    </div>

    <section id="coverage" class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Insurance metrics">
        @foreach ($kpis as $kpi)
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <p class="text-sm font-medium text-cyra-muted">{{ $kpi['label'] }}</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-cyra-ink sm:text-[1.65rem]">
                    {{ $kpi['value'] }}
                </p>
            </article>
        @endforeach
    </section>

    <section id="buy" class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Buy New Policy</h2>
                <p class="mt-1 text-sm text-cyra-muted">Choose a plan and farm. Premium is paid from your wallet after confirmation.</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ($plans as $plan)
                <article class="rounded-xl bg-cyra-surface/50 p-4 ring-1 ring-cyra-line">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-cyra-muted">{{ $plan['category'] }}</p>
                            <h3 class="mt-1 text-sm font-extrabold text-cyra-ink">{{ $plan['name'] }}</h3>
                        </div>
                        <p class="text-sm font-extrabold tabular-nums text-cyra-forest">{{ $plan['premium'] }}</p>
                    </div>
                    <p class="mt-2 text-sm text-cyra-muted">{{ $plan['description'] }}</p>
                    <p class="mt-2 text-xs text-cyra-muted">
                        Coverage {{ $plan['coverage'] }} · {{ $plan['duration'] }}
                        @if ($plan['enterprises'])
                            · {{ $plan['enterprises'] }}
                        @endif
                    </p>

                    <form method="POST" action="{{ $actions['purchase_url'] }}" class="mt-3 space-y-2">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan['id'] }}">
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-cyra-muted">Farm / enterprise</span>
                            <select
                                name="farm_id"
                                required
                                class="w-full rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm text-cyra-ink focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                            >
                                @foreach ($farms as $farm)
                                    <option value="{{ $farm['id'] }}">
                                        {{ $farm['name'] }}@if($farm['enterprises']) — {{ $farm['enterprises'] }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <button
                            type="submit"
                            onclick="return confirm('Pay {{ $plan['premium'] }} premium from your wallet to activate this policy?');"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-3 py-2.5 text-sm font-bold text-white transition hover:bg-cyra-green"
                        >
                            Buy policy
                        </button>
                    </form>
                </article>
            @endforeach
        </div>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Policies and claims">
        <article id="policies" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">My Policies</h2>
            <ul class="mt-4 divide-y divide-cyra-line/80">
                @forelse ($policies as $policy)
                    <li class="flex items-start gap-3 py-3.5 first:pt-0 last:pb-0">
                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l8 3v6c0 5-3.5 8.5-8 9.5C7.5 20.5 4 17 4 12V6l8-3z"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-cyra-ink">{{ $policy['name'] }}</p>
                            <p class="mt-0.5 text-sm">
                                <span class="font-semibold text-cyra-forest">{{ $policy['status'] }}</span>
                                <span class="text-cyra-muted"> · {{ $policy['reference'] }} · {{ $policy['farm'] }} · Expires {{ $policy['expires'] }}</span>
                            </p>
                            <p class="mt-0.5 text-xs text-cyra-muted">Coverage {{ $policy['coverage'] }}</p>
                        </div>
                    </li>
                @empty
                    <li class="py-2 text-sm text-cyra-muted">No policies yet. Buy coverage below to protect your enterprises.</li>
                @endforelse
            </ul>
        </article>

        <article id="claims" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Recent Claims</h2>
            <ul class="mt-4 divide-y divide-cyra-line/80">
                @forelse ($claims as $claim)
                    @php
                        $statusClass = match ($claim['status_tone']) {
                            'approved' => 'text-cyra-forest',
                            'review' => 'text-amber-500',
                            default => 'text-rose-600',
                        };
                        $iconClass = match ($claim['status_tone']) {
                            'approved' => 'bg-cyra-mint text-cyra-forest',
                            'review' => 'bg-amber-50 text-amber-600',
                            default => 'bg-rose-50 text-rose-600',
                        };
                    @endphp
                    <li class="py-3.5 first:pt-0 last:pb-0">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $iconClass }}">
                                @if ($claim['status_tone'] === 'approved')
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                                @elseif ($claim['status_tone'] === 'review')
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @else
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/></svg>
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-cyra-ink">{{ $claim['name'] }}</p>
                                <p class="mt-0.5 text-sm font-semibold {{ $statusClass }}">{{ $claim['status'] }}</p>
                                <p class="mt-0.5 text-xs text-cyra-muted">{{ $claim['reference'] }} · {{ $claim['policy'] }}</p>
                            </div>
                            @if ($claim['amount'])
                                <span class="shrink-0 text-sm font-bold tabular-nums text-cyra-ink">{{ $claim['amount'] }}</span>
                            @endif
                        </div>
                        @if ($claim['can_advance'] || $claim['can_reject'])
                            <div class="mt-2 flex flex-wrap gap-2 pl-12">
                                @if ($claim['can_advance'])
                                    <form method="POST" action="{{ $claim['advance_url'] }}">
                                        @csrf
                                        <input type="hidden" name="action" value="next">
                                        <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">
                                            {{ $claim['next_label'] }}
                                        </button>
                                    </form>
                                @endif
                                @if ($claim['can_reject'])
                                    <form method="POST" action="{{ $claim['advance_url'] }}">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50">
                                            Reject
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="py-2 text-sm text-cyra-muted">No claims yet. File a claim against an active policy.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section id="file-claim" class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
        <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">File a Claim</h2>
        <p class="mt-1 text-sm text-cyra-muted">Submit loss details for an active policy. Payouts credit your wallet after approval.</p>

        @if (count($claimablePolicies) === 0)
            <p class="mt-4 text-sm text-cyra-muted">Buy an active policy first to file a claim.</p>
        @else
            <form method="POST" action="{{ $actions['claim_url'] }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @csrf
                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Policy</span>
                    <select
                        name="policy_id"
                        required
                        class="w-full rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                        @foreach ($claimablePolicies as $policy)
                            <option value="{{ $policy['id'] }}">{{ $policy['label'] }} (max ₦{{ number_format($policy['max_amount']) }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Claim title</span>
                    <input
                        type="text"
                        name="title"
                        required
                        maxlength="160"
                        placeholder="e.g. Flood damage to maize field"
                        class="w-full rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                </label>
                <label class="block sm:col-span-2">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Description (optional)</span>
                    <textarea
                        name="description"
                        rows="3"
                        maxlength="500"
                        class="w-full rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    ></textarea>
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Amount requested (₦)</span>
                    <input
                        type="number"
                        name="amount_requested_ngn"
                        required
                        min="1"
                        step="1"
                        class="w-full rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                    >
                </label>
                <div class="flex items-end">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white hover:bg-cyra-green"
                    >
                        Submit claim
                    </button>
                </div>
            </form>
        @endif
    </section>
</x-dashboard-layout>
