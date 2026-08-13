<x-dashboard-layout
    title="Government Dashboard"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'National Agricultural Overview'],
    ]"
>
    <x-page-header
        title="National Agricultural Overview"
        description="Real-time agricultural data and insights"
    >
        <x-slot:actions>
            <a
                href="{{ route('government.export', array_filter(['state' => $state])) }}"
                class="inline-flex items-center rounded-xl bg-cyra-forest px-4 py-2.5 text-sm font-bold text-white transition hover:bg-cyra-green"
            >
                Export overview
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

    <form method="GET" action="{{ route('government.dashboard') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div>
            <label for="state" class="block text-xs font-semibold uppercase tracking-wide text-cyra-muted">Filter by state</label>
            <select id="state" name="state" class="mt-1 rounded-xl border-cyra-line text-sm shadow-sm focus:border-cyra-forest focus:ring-cyra-forest/20" onchange="this.form.submit()">
                <option value="">All states</option>
                @foreach ($states as $option)
                    <option value="{{ $option }}" @selected($state === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        @if ($state)
            <a href="{{ route('government.dashboard', ['tab' => $tab]) }}" class="text-sm font-semibold text-cyra-forest hover:underline">Clear filter</a>
        @endif
    </form>

    <x-section-tabs
        :active="$tab"
        :items="[
            ['id' => 'overview', 'label' => 'Overview', 'href' => route('government.dashboard', array_filter(['tab' => 'overview', 'state' => $state]))],
            ['id' => 'farmers', 'label' => 'Farmers', 'href' => route('government.dashboard', array_filter(['tab' => 'farmers', 'state' => $state]))],
            ['id' => 'production', 'label' => 'Production', 'href' => route('government.dashboard', array_filter(['tab' => 'production', 'state' => $state]))],
            ['id' => 'food-security', 'label' => 'Food Security', 'href' => route('government.dashboard', array_filter(['tab' => 'food-security', 'state' => $state]))],
            ['id' => 'subsidies', 'label' => 'Subsidies', 'href' => route('government.dashboard', array_filter(['tab' => 'subsidies', 'state' => $state]))],
            ['id' => 'policies', 'label' => 'Policies', 'href' => route('government.dashboard', array_filter(['tab' => 'policies', 'state' => $state]))],
        ]"
    />

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="National metrics">
        @foreach ($kpis as $kpi)
            <x-government.stat-card
                :label="$kpi['label']"
                :value="$kpi['value']"
                :change="$kpi['change']"
            />
        @endforeach
    </section>

    @if ($tab === 'overview' || $tab === 'production')
        <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Production and map">
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <h2 class="text-base font-extrabold text-cyra-ink">Production by Commodity</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center">
                    <div class="mx-auto h-56 w-full max-w-[16rem] sm:mx-0 sm:h-64">
                        <canvas
                            id="governmentProductionChart"
                            data-labels='@json($production['labels'])'
                            data-values='@json($production['values'])'
                            data-colors='@json($production['colors'])'
                            aria-label="Production by commodity doughnut chart"
                            role="img"
                        ></canvas>
                    </div>
                    <ul class="space-y-2.5">
                        @foreach ($production['labels'] as $index => $label)
                            <li class="flex items-center gap-2.5 text-sm">
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    style="background-color: {{ $production['colors'][$index] ?? '#0A5C2E' }}"
                                    aria-hidden="true"
                                ></span>
                                <span class="min-w-0 flex-1 font-medium text-cyra-ink">{{ $label }}</span>
                                <span class="font-bold tabular-nums text-cyra-ink">{{ $production['values'][$index] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </article>

            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <h2 class="text-base font-extrabold text-cyra-ink">Map Overview</h2>
                <div class="mt-4 overflow-hidden rounded-2xl bg-cyra-surface ring-1 ring-cyra-line/80">
                    <div
                        id="governmentNigeriaMap"
                        class="h-64 w-full sm:h-72"
                        data-zones='@json($mapZones)'
                        role="img"
                        aria-label="Nigeria agricultural activity map"
                    ></div>
                </div>
                <p class="mt-3 text-xs text-cyra-muted">
                    Darker greens indicate higher agricultural activity by state.
                </p>
            </article>
        </section>
    @endif

    @if ($tab === 'overview' || $tab === 'subsidies')
        <section class="mt-6" aria-labelledby="subsidy-programs-heading">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 id="subsidy-programs-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">
                    Subsidy Programs
                </h2>
                <div class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="rounded-xl bg-cyra-surface/70 px-4 py-4 ring-1 ring-cyra-line/70">
                        <p class="text-sm font-medium text-cyra-muted">Total Disbursed</p>
                        <p class="mt-2 text-xl font-extrabold tabular-nums text-cyra-ink sm:text-2xl">
                            {{ $subsidies['disbursed'] }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-cyra-surface/70 px-4 py-4 ring-1 ring-cyra-line/70">
                        <p class="text-sm font-medium text-cyra-muted">Beneficiaries</p>
                        <p class="mt-2 text-xl font-extrabold tabular-nums text-cyra-ink sm:text-2xl">
                            {{ $subsidies['beneficiaries'] }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-cyra-surface/70 px-4 py-4 ring-1 ring-cyra-line/70">
                        <p class="text-sm font-medium text-cyra-muted">Pending Approval</p>
                        <p class="mt-2 text-xl font-extrabold tabular-nums text-cyra-ink sm:text-2xl">
                            {{ $subsidies['pending'] }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[40rem] text-left text-sm">
                        <thead>
                            <tr class="border-b border-cyra-line text-cyra-muted">
                                <th class="pb-3 pr-4 font-semibold">Program</th>
                                <th class="pb-3 pr-4 font-semibold">Beneficiary</th>
                                <th class="pb-3 pr-4 font-semibold">State</th>
                                <th class="pb-3 pr-4 font-semibold">Amount</th>
                                <th class="pb-3 pr-4 font-semibold">Status</th>
                                <th class="pb-3 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($subsidyApplications as $application)
                                <tr class="border-b border-cyra-line/70 last:border-0">
                                    <td class="py-3.5 pr-4 font-semibold text-cyra-ink">{{ $application->program }}</td>
                                    <td class="py-3.5 pr-4 text-cyra-muted">{{ $application->beneficiary_name }}</td>
                                    <td class="py-3.5 pr-4 text-cyra-muted">{{ $application->state ?: '—' }}</td>
                                    <td class="py-3.5 pr-4 font-medium tabular-nums text-cyra-ink">{{ $application->formattedAmount() }}</td>
                                    <td class="py-3.5 pr-4 font-semibold text-cyra-ink">{{ $application->status->label() }}</td>
                                    <td class="py-3.5">
                                        @if (in_array($application->status->value, ['pending', 'under_review'], true))
                                            <div class="flex flex-wrap gap-2">
                                                <form method="POST" action="{{ route('government.subsidies.approve', $application) }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">Approve</button>
                                                </form>
                                                <form method="POST" action="{{ route('government.subsidies.reject', $application) }}">
                                                    @csrf
                                                    <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50">Reject</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-xs text-cyra-muted">Reviewed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-cyra-muted">No subsidy applications yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div id="apply-subsidy" class="mt-6 rounded-xl bg-cyra-surface/70 p-4 ring-1 ring-cyra-line/70">
                    <h3 class="text-sm font-extrabold text-cyra-ink">Apply for subsidy</h3>
                    <form method="POST" action="{{ $actions['apply_url'] }}" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @csrf
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-cyra-muted">Program</span>
                            <select name="program" required class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                                @foreach ($programs as $program)
                                    <option value="{{ $program }}">{{ $program }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-cyra-muted">Beneficiary</span>
                            <input type="text" name="beneficiary_name" required maxlength="150" value="{{ auth()->user()->name }}" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-cyra-muted">State</span>
                            <select name="state" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                                <option value="">Select state</option>
                                @foreach ($states as $option)
                                    <option value="{{ $option }}" @selected($state === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-bold text-cyra-muted">Amount (NGN)</span>
                            <input type="number" name="amount" min="50000" step="10000" required value="1500000" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                        </label>
                        <div class="sm:col-span-2">
                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green">
                                Submit application
                            </button>
                        </div>
                    </form>
                </div>
            </article>
        </section>
    @endif

    @if ($tab === 'farmers')
        <section class="mt-6" aria-labelledby="registered-farmers-heading">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 id="registered-farmers-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">Registered Farmers</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[32rem] text-left text-sm">
                        <thead>
                            <tr class="border-b border-cyra-line text-cyra-muted">
                                <th class="pb-3 pr-4 font-semibold">Name</th>
                                <th class="pb-3 pr-4 font-semibold">Email</th>
                                <th class="pb-3 font-semibold">Farms</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($farmers as $farmer)
                                <tr class="border-b border-cyra-line/70 last:border-0">
                                    <td class="py-3.5 pr-4 font-semibold text-cyra-ink">{{ $farmer->name }}</td>
                                    <td class="py-3.5 pr-4 text-cyra-muted">{{ $farmer->email }}</td>
                                    <td class="py-3.5 tabular-nums text-cyra-ink">{{ $farmer->farms_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-cyra-muted">No registered farmers match this filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    @endif

    @if ($tab === 'food-security')
        <section class="mt-6" aria-labelledby="food-security-heading">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 id="food-security-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">Food Security Snapshot</h2>
                <p class="mt-3 text-4xl font-extrabold tabular-nums text-cyra-forest">{{ $foodSecurity['score'] }}%</p>
                <p class="mt-1 text-sm font-semibold text-cyra-muted">Status: {{ $foodSecurity['status'] }}</p>
                <ul class="mt-4 space-y-2 text-sm text-cyra-ink">
                    @foreach ($foodSecurity['notes'] as $note)
                        <li class="rounded-xl bg-cyra-surface/70 px-4 py-3 ring-1 ring-cyra-line/70">{{ $note }}</li>
                    @endforeach
                </ul>
                <a href="{{ $actions['food_security_url'] }}" class="mt-5 inline-flex rounded-xl border-2 border-cyra-forest px-4 py-2.5 text-sm font-bold text-cyra-forest transition hover:bg-cyra-forest hover:text-white">
                    Open Food Security module
                </a>
            </article>
        </section>
    @endif

    @if ($tab === 'policies')
        <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-labelledby="policies-heading">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 id="policies-heading" class="text-base font-extrabold text-cyra-ink sm:text-lg">Agricultural Policies</h2>
                <ul class="mt-4 divide-y divide-cyra-line/80">
                    @foreach ($policies as $policy)
                        <li class="flex flex-col gap-2 py-4 first:pt-0 last:pb-0">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                                <div>
                                    <p class="font-semibold text-cyra-ink">{{ $policy['title'] }}</p>
                                    <p class="mt-1 text-sm text-cyra-muted">{{ $policy['summary'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-cyra-mint/50 px-3 py-1 text-xs font-bold text-cyra-forest">{{ $policy['status'] }}</span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($policy['can_review'])
                                    <form method="POST" action="{{ $policy['status_url'] }}">
                                        @csrf
                                        <input type="hidden" name="status" value="under_review">
                                        <button type="submit" class="rounded-lg border border-cyra-line bg-white px-3 py-1.5 text-xs font-bold text-cyra-ink hover:bg-cyra-surface">Send to review</button>
                                    </form>
                                @endif
                                @if ($policy['can_activate'])
                                    <form method="POST" action="{{ $policy['status_url'] }}">
                                        @csrf
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">Activate</button>
                                    </form>
                                @endif
                                @if ($policy['can_archive'])
                                    <form method="POST" action="{{ $policy['status_url'] }}">
                                        @csrf
                                        <input type="hidden" name="status" value="archived">
                                        <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100">Archive</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </article>

            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Draft a policy</h2>
                <form method="POST" action="{{ $actions['policy_url'] }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Title</span>
                        <input type="text" name="title" required maxlength="160" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20" placeholder="National Irrigation Expansion Act">
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Summary</span>
                        <textarea name="summary" required maxlength="500" rows="4" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20" placeholder="What does this policy unlock for farmers?"></textarea>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-xs font-bold text-cyra-muted">Initial status</span>
                        <select name="status" class="w-full rounded-lg border border-cyra-line px-3 py-2 text-sm focus:border-cyra-forest focus:outline-none focus:ring-2 focus:ring-cyra-forest/20">
                            <option value="draft">Draft</option>
                            <option value="under_review">Under review</option>
                            <option value="active">Active</option>
                        </select>
                    </label>
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-cyra-forest px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-cyra-green">
                        Save policy
                    </button>
                </form>
            </article>
        </section>
    @endif
</x-dashboard-layout>
