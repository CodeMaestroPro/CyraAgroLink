<x-dashboard-layout
    title="Smart Cooperative Management"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Community'],
        ['label' => 'Cooperative'],
    ]"
>
    <x-page-header
        title="Smart Cooperative Management"
        description="{{ $cooperative['name'] }} · Wallet {{ $walletBalance }}"
    />

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
            Cooperative Overview
        </h2>
        <p class="mt-1 text-sm text-cyra-muted">
            {{ $cooperative['name'] }} · {{ $cooperative['location'] }} · Pool {{ $cooperative['pool'] }} · Your savings {{ $cooperative['my_savings'] }}
        </p>
    </div>

    <section class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Cooperative metrics">
        @foreach ($kpis as $kpi)
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <p class="text-sm font-medium text-cyra-muted">{{ $kpi['label'] }}</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight tabular-nums text-cyra-forest sm:text-[1.65rem]">
                    {{ $kpi['value'] }}
                </p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Activities and vote">
        <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Recent Activities</h2>
            <ul class="mt-4 divide-y divide-cyra-line/80">
                @foreach ($activities as $activity)
                    <li class="flex items-center gap-3 py-3.5 first:pt-0 last:pb-0">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyra-mint text-cyra-forest">
                            @if ($activity['icon'] === 'loan')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-2.5 0-4 1.2-4 3s1.5 3 4 3 4 1.2 4 3-1.5 3-4 3m0-12V4m0 16v-2"/></svg>
                            @elseif ($activity['icon'] === 'contribution')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14m0 0l-5-5m5 5l5-5"/></svg>
                            @elseif ($activity['icon'] === 'equipment')
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 17h16M6 17V9l3 2 3-4 3 3 3-2v9"/></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v8m-4-4h8M4 10h2m12 0h2M4 14h2m12 0h2"/></svg>
                            @endif
                        </span>
                        <p class="min-w-0 flex-1 truncate font-semibold text-cyra-ink">{{ $activity['title'] }}</p>
                        <span class="shrink-0 text-sm font-bold tabular-nums text-cyra-ink">{{ $activity['value'] }}</span>
                    </li>
                @endforeach
            </ul>
        </article>

        <article id="vote" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">{{ $vote['title'] }}</h2>
            <p class="mt-4 text-base font-semibold leading-snug text-cyra-ink">
                {{ $vote['description'] }}
            </p>
            @if ($vote['detail'])
                <p class="mt-2 text-sm text-cyra-muted">{{ $vote['detail'] }}</p>
            @endif
            <p class="mt-3 text-sm text-cyra-muted">
                Date: <span class="font-semibold text-cyra-ink">{{ $vote['date'] }}</span>
                · Yes {{ $vote['yes'] }} / No {{ $vote['no'] }}
            </p>

            @if ($vote['can_vote'] && $actions['vote_cast_url'])
                <div class="mt-6 flex flex-wrap gap-2">
                    <form method="POST" action="{{ $actions['vote_cast_url'] }}">
                        @csrf
                        <input type="hidden" name="choice" value="yes">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green">
                            Vote Yes
                        </button>
                    </form>
                    <form method="POST" action="{{ $actions['vote_cast_url'] }}">
                        @csrf
                        <input type="hidden" name="choice" value="no">
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl border-2 border-cyra-forest/30 bg-white px-5 py-2.5 text-sm font-bold text-cyra-forest transition hover:border-cyra-forest hover:bg-cyra-mint/40">
                            Vote No
                        </button>
                    </form>
                </div>
            @elseif ($vote['has_voted'])
                <p class="mt-6 text-sm font-semibold text-cyra-forest">
                    You voted {{ strtoupper($vote['my_choice']) }}.
                </p>
            @elseif ($vote['id'] === null && $cooperative['is_admin'])
                <form method="POST" action="{{ $actions['vote_create_url'] }}" class="mt-6 space-y-3">
                    @csrf
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Proposal</span>
                        <input type="text" name="title" required maxlength="160" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20" placeholder="Approve purchase of new thresher">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Details</span>
                        <textarea name="description" required maxlength="500" rows="2" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20" placeholder="Why should members approve this?"></textarea>
                    </label>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green">
                        Open Vote
                    </button>
                </form>
            @else
                <p class="mt-6 text-sm text-cyra-muted">Waiting for the next group proposal.</p>
            @endif
        </article>
    </section>

    <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Savings and loans">
        <article id="savings" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Savings</h2>
            <p class="mt-1 text-sm text-cyra-muted">Contribute from your wallet into the group pool.</p>
            <form method="POST" action="{{ $actions['contribute_url'] }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf
                <label class="block flex-1">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Amount (NGN)</span>
                    <input type="number" name="amount" min="1000" step="1000" required value="150000" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                </label>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green">
                    Contribute
                </button>
            </form>
        </article>

        <article id="loans" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
            <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Loans</h2>
            <p class="mt-1 text-sm text-cyra-muted">Request funding from the cooperative savings pool.</p>
            <form method="POST" action="{{ $actions['loan_url'] }}" class="mt-4 space-y-3">
                @csrf
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Amount (NGN)</span>
                    <input type="number" name="amount" min="5000" step="1000" required value="2500000" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                </label>
                <label class="block">
                    <span class="mb-1 block text-xs font-bold text-cyra-muted">Purpose</span>
                    <input type="text" name="purpose" required maxlength="200" value="Seasonal inputs for group members" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                </label>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green">
                    Request Loan
                </button>
            </form>

            <ul class="mt-5 divide-y divide-cyra-line/80">
                @forelse ($loans as $loan)
                    <li class="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-cyra-ink">{{ $loan['purpose'] }}</p>
                            <p class="text-xs text-cyra-muted">{{ $loan['reference'] }} · {{ $loan['amount'] }} · {{ ucfirst($loan['status']) }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($loan['can_review'])
                                <form method="POST" action="{{ $loan['approve_url'] }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="approve">
                                    <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">Approve</button>
                                </form>
                                <form method="POST" action="{{ $loan['approve_url'] }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="reject">
                                    <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100">Reject</button>
                                </form>
                            @endif
                            @if ($loan['can_repay'])
                                <form method="POST" action="{{ $loan['repay_url'] }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">Repay</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-cyra-muted">No loan requests yet.</li>
                @endforelse
            </ul>
        </article>
    </section>

    <section id="members" class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6" aria-label="Members">
        <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Members</h2>
        <p class="mt-1 text-sm text-cyra-muted">{{ $cooperative['member_count'] }} active members in this cooperative.</p>
        <ul class="mt-4 divide-y divide-cyra-line/80">
            @foreach ($members as $member)
                <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-cyra-ink">{{ $member['name'] }}</p>
                        <p class="text-xs text-cyra-muted">{{ $member['role'] }} · Joined {{ $member['joined'] }}</p>
                    </div>
                    <span class="shrink-0 text-sm font-bold tabular-nums text-cyra-forest">{{ $member['savings'] }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section
        class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"
        aria-label="Quick actions"
    >
        @foreach ($actions as $key => $action)
            @continue(! is_array($action) || ! isset($action['label']))
            @php
                $actionId = match ($action['icon'] ?? '') {
                    'loans', 'savings' => $action['icon'],
                    default => null,
                };
            @endphp
            <a
                href="{{ $action['href'] }}"
                @if ($actionId) id="nav-{{ $actionId }}" @endif
                class="flex flex-col items-center gap-2 rounded-2xl bg-white px-3 py-4 text-center shadow-sm ring-1 ring-cyra-line transition hover:ring-cyra-forest/30"
            >
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-cyra-mint text-cyra-forest">
                    @if (($action['icon'] ?? '') === 'members')
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm12 10v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                    @elseif (($action['icon'] ?? '') === 'loans')
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-2.5 0-4 1.2-4 3s1.5 3 4 3 4 1.2 4 3-1.5 3-4 3m0-12V4m0 16v-2M3 10h3m12 0h3M3 14h3m12 0h3"/></svg>
                    @elseif (($action['icon'] ?? '') === 'savings')
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7a2 2 0 012-2h14a2 2 0 012 2v2H3V7zm0 4h18v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6zm12 3h3"/></svg>
                    @elseif (($action['icon'] ?? '') === 'equipment')
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 17h16M6 17V9l3 2 3-4 3 3 3-2v9M8 21h8"/></svg>
                    @else
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    @endif
                </span>
                <span class="text-xs font-bold text-cyra-ink sm:text-sm">{{ $action['label'] }}</span>
            </a>
        @endforeach
    </section>
</x-dashboard-layout>
