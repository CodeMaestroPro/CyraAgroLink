<x-dashboard-layout
    title="Financial Institution Dashboard"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Loan Portfolio Overview'],
    ]"
>
    <x-page-header
        title="Loan Portfolio Overview"
        description="Agricultural lending performance and applications"
    >
        <x-slot:actions>
            <form method="GET" action="{{ $actions['filter_url'] }}" class="inline-flex items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label class="sr-only" for="fi-sector">Sector</label>
                <select
                    id="fi-sector"
                    name="sector"
                    onchange="this.form.submit()"
                    class="rounded-xl border border-cyra-line bg-white px-3 py-2 text-sm font-semibold text-cyra-ink focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20"
                >
                    <option value="">All sectors</option>
                    @foreach ($sectors as $option)
                        <option value="{{ $option }}" @selected($sector === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
            <a
                href="{{ $actions['export_url'] }}"
                class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green"
            >
                Export portfolio
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/40 px-4 py-3 text-sm font-semibold text-cyra-forest ring-1 ring-cyra-forest/20" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 ring-1 ring-rose-200" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <x-section-tabs
        :active="$tab"
        :items="[
            ['id' => 'overview', 'label' => 'Overview', 'href' => route('financial.dashboard', array_filter(['tab' => 'overview', 'sector' => $sector]))],
            ['id' => 'loan-applications', 'label' => 'Loan Applications', 'href' => route('financial.dashboard', array_filter(['tab' => 'loan-applications', 'sector' => $sector]))],
            ['id' => 'loan-portfolio', 'label' => 'Loan Portfolio', 'href' => route('financial.dashboard', array_filter(['tab' => 'loan-portfolio', 'sector' => $sector]))],
            ['id' => 'repayments', 'label' => 'Repayments', 'href' => route('financial.dashboard', array_filter(['tab' => 'repayments', 'sector' => $sector]))],
            ['id' => 'risk-assessment', 'label' => 'Risk Assessment', 'href' => route('financial.dashboard', array_filter(['tab' => 'risk-assessment', 'sector' => $sector]))],
            ['id' => 'farmers', 'label' => 'Farmers', 'href' => route('financial.dashboard', array_filter(['tab' => 'farmers', 'sector' => $sector]))],
        ]"
    />

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Portfolio metrics">
        @foreach ($kpis as $kpi)
            <x-financial-institution.stat-card
                :label="$kpi['label']"
                :value="$kpi['value']"
            />
        @endforeach
    </section>

    @if ($tab === 'overview' || $tab === 'loan-portfolio')
        <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Portfolio charts">
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <h2 class="text-base font-extrabold text-cyra-ink">Loan Portfolio by Sector</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                    <div class="mx-auto h-56 w-full max-w-[16rem] sm:mx-0 sm:h-64">
                        <canvas
                            id="fiPortfolioChart"
                            data-labels='@json($portfolio['labels'])'
                            data-values='@json($portfolio['values'])'
                            data-colors='@json($portfolio['colors'])'
                            aria-label="Loan portfolio by sector doughnut chart"
                            role="img"
                        ></canvas>
                    </div>
                    <ul class="space-y-2.5">
                        @foreach ($portfolio['labels'] as $index => $label)
                            <li class="flex items-center gap-2.5 text-sm">
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    style="background-color: {{ $portfolio['colors'][$index] ?? '#0A5C2E' }}"
                                    aria-hidden="true"
                                ></span>
                                <span class="min-w-0 flex-1 font-medium text-cyra-ink">{{ $label }}</span>
                                <span class="font-bold tabular-nums text-cyra-ink">{{ $portfolio['values'][$index] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </article>

            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <h2 class="text-base font-extrabold text-cyra-ink">Repayment Trend</h2>
                <div class="mt-4 h-56 sm:h-64">
                    <canvas
                        id="fiRepaymentChart"
                        data-labels='@json($repayment['labels'])'
                        data-values='@json($repayment['values'])'
                        aria-label="Repayment trend line chart"
                        role="img"
                    ></canvas>
                </div>
            </article>
        </section>
    @endif

    @if ($tab === 'overview' || $tab === 'loan-applications' || $tab === 'repayments')
        <section class="mt-6" aria-labelledby="recent-applications-heading">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 id="recent-applications-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">
                    Recent Loan Applications
                </h2>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[44rem] text-left text-sm">
                        <thead>
                            <tr class="border-b border-cyra-line text-cyra-muted">
                                <th class="pb-3 pr-4 font-semibold">Borrower</th>
                                <th class="pb-3 pr-4 font-semibold">Sector</th>
                                <th class="pb-3 pr-4 font-semibold">Amount</th>
                                <th class="pb-3 pr-4 font-semibold">Outstanding</th>
                                <th class="pb-3 pr-4 font-semibold">Status</th>
                                <th class="pb-3 pr-4 font-semibold">Date</th>
                                <th class="pb-3 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($applications as $application)
                                @php
                                    $statusClass = match ($application['status_value']) {
                                        'approved' => 'text-cyra-forest',
                                        'rejected' => 'text-rose-600',
                                        'under_review' => 'text-teal-600',
                                        default => 'text-orange-500',
                                    };
                                @endphp
                                <tr class="border-b border-cyra-line/70 last:border-0">
                                    <td class="py-3.5 pr-4 font-semibold text-cyra-ink">
                                        {{ $application['borrower'] }}
                                        @if ($application['purpose'])
                                            <span class="mt-0.5 block text-xs font-normal text-cyra-muted">{{ $application['purpose'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 pr-4 text-cyra-muted">{{ $application['sector'] }}</td>
                                    <td class="py-3.5 pr-4 font-medium tabular-nums text-cyra-ink">{{ $application['amount'] }}</td>
                                    <td class="py-3.5 pr-4 font-medium tabular-nums text-cyra-ink">{{ $application['outstanding'] }}</td>
                                    <td class="py-3.5 pr-4 font-semibold {{ $statusClass }}">{{ $application['status'] }}</td>
                                    <td class="py-3.5 pr-4 text-cyra-muted">{{ $application['date'] }}</td>
                                    <td class="py-3.5">
                                        @if ($application['can_review'])
                                            <div class="flex flex-wrap gap-2">
                                                <form method="POST" action="{{ $application['approve_url'] }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ $application['reject_url'] }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50">Reject</button>
                                                </form>
                                            </div>
                                        @elseif ($application['can_repay'])
                                            <form method="POST" action="{{ $application['repay_url'] }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                <input type="number" name="amount" min="1000" step="1000" required placeholder="Amount" class="w-28 rounded-lg border border-cyra-line px-2 py-1 text-xs">
                                                <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">Repay</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-cyra-muted">Reviewed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-cyra-muted">No loan applications match this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($tab === 'loan-applications')
                    <div id="apply-loan" class="mt-6 rounded-xl bg-cyra-surface/70 p-4 ring-1 ring-cyra-line/70">
                        <h3 class="text-sm font-extrabold text-cyra-ink">Apply for a loan</h3>
                        <form method="POST" action="{{ $actions['apply_url'] }}" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @csrf
                            <label class="block">
                                <span class="mb-1 block text-xs font-bold text-cyra-muted">Borrower</span>
                                <input type="text" name="borrower" required maxlength="150" value="{{ auth()->user()->name }}" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-bold text-cyra-muted">Sector</span>
                                <select name="sector" required class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                                    @foreach ($sectors as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-bold text-cyra-muted">Amount (NGN)</span>
                                <input type="number" name="amount" min="100000" step="50000" required value="1500000" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            </label>
                            <label class="block">
                                <span class="mb-1 block text-xs font-bold text-cyra-muted">Purpose</span>
                                <input type="text" name="purpose" maxlength="200" value="Working capital for the season" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            </label>
                            <div class="sm:col-span-2">
                                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green">
                                    Submit application
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </article>
        </section>
    @endif

    @if ($tab === 'repayments')
        <section class="mt-6">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Recent Repayments</h2>
                <ul class="mt-4 divide-y divide-cyra-line/80">
                    @forelse ($recentRepayments as $row)
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-semibold text-cyra-ink">{{ $row['borrower'] }}</p>
                                <p class="text-xs text-cyra-muted">{{ $row['note'] }} · {{ $row['paid_at'] }}</p>
                            </div>
                            <span class="text-sm font-extrabold tabular-nums text-cyra-forest">{{ $row['amount'] }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-sm text-cyra-muted">No repayments recorded yet. Approve a loan, then post a repayment.</li>
                    @endforelse
                </ul>
            </article>
        </section>
    @endif

    @if ($tab === 'risk-assessment')
        <section class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3" aria-label="Risk assessment">
            @foreach ($risk as $item)
                <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line">
                    <p class="text-sm font-medium text-cyra-muted">{{ $item['label'] }}</p>
                    <p class="mt-2 text-2xl font-extrabold text-cyra-ink">{{ $item['value'] }}</p>
                </article>
            @endforeach
        </section>
    @endif

    @if ($tab === 'farmers')
        <section class="mt-6">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Registered Farmers</h2>
                <ul class="mt-4 divide-y divide-cyra-line/80">
                    @forelse ($farmers as $farmer)
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="font-semibold text-cyra-ink">{{ $farmer->name }}</p>
                                <p class="text-sm text-cyra-muted">{{ $farmer->email }}</p>
                            </div>
                            <span class="text-xs font-bold text-cyra-forest">{{ $farmer->status->value }}</span>
                        </li>
                    @empty
                        <li class="py-4 text-sm text-cyra-muted">No farmers registered yet.</li>
                    @endforelse
                </ul>
            </article>
        </section>
    @endif
</x-dashboard-layout>
