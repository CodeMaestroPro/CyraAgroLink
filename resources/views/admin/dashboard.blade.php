<x-dashboard-layout
    title="Enterprise Admin Dashboard"
    :notifications-count="$notificationsCount"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'href' => route('dashboard')],
        ['label' => 'Platform Overview'],
    ]"
>
    <x-page-header
        title="Platform Overview"
        description="Monitor and manage the entire ecosystem."
    />

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-cyra-mint/40 px-4 py-3 text-sm font-semibold text-cyra-forest ring-1 ring-cyra-forest/20" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 ring-1 ring-rose-200" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <x-section-tabs
        :active="$tab"
        :items="[
            ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => route('admin.dashboard', ['tab' => 'dashboard'])],
            ['id' => 'users', 'label' => 'User Management', 'href' => route('admin.dashboard', ['tab' => 'users'])],
            ['id' => 'roles', 'label' => 'Role Management', 'href' => route('admin.dashboard', ['tab' => 'roles'])],
            ['id' => 'verification', 'label' => 'Verification', 'href' => route('admin.dashboard', ['tab' => 'verification'])],
            ['id' => 'audit', 'label' => 'Audit Logs', 'href' => route('admin.dashboard', ['tab' => 'audit'])],
            ['id' => 'security', 'label' => 'Security Center', 'href' => route('admin.dashboard', ['tab' => 'security'])],
        ]"
    />

    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Platform metrics">
        @foreach ($kpis as $kpi)
            <x-government.stat-card
                :label="$kpi['label']"
                :value="$kpi['value']"
                :change="$kpi['change']"
            />
        @endforeach
    </section>

    @if ($tab === 'dashboard')
        <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-3" aria-label="Distribution, activity, and alerts">
            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <h2 class="text-base font-extrabold text-cyra-ink">User Distribution</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-center xl:grid-cols-1">
                    <div class="mx-auto h-44 w-full max-w-[14rem]">
                        <canvas
                            id="adminDistributionChart"
                            data-labels='@json($distribution['labels'])'
                            data-values='@json($distribution['values'])'
                            data-colors='@json($distribution['colors'])'
                            aria-label="User distribution chart"
                            role="img"
                        ></canvas>
                    </div>
                    <ul class="space-y-2">
                        @foreach ($distribution['labels'] as $index => $label)
                            <li class="flex items-center gap-2.5 text-sm">
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    style="background-color: {{ $distribution['colors'][$index] ?? '#0A5C2E' }}"
                                    aria-hidden="true"
                                ></span>
                                <span class="min-w-0 flex-1 font-medium text-cyra-ink">{{ $label }}</span>
                                <span class="font-bold tabular-nums text-cyra-ink">{{ $distribution['values'][$index] }}%</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </article>

            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <h2 class="text-base font-extrabold text-cyra-ink">System Activity</h2>
                <p class="mt-1 text-xs text-cyra-muted">New users and farms over the last 7 days</p>
                <div class="mt-4 h-56">
                    <canvas
                        id="adminActivityChart"
                        data-labels='@json($activity['labels'])'
                        data-values='@json($activity['values'])'
                        aria-label="System activity line chart"
                        role="img"
                    ></canvas>
                </div>
            </article>

            <article class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-cyra-line sm:p-5">
                <h2 class="text-base font-extrabold text-cyra-ink">Platform Alerts</h2>
                <ul class="mt-4 space-y-2.5">
                    @foreach ($alerts as $alert)
                        @php
                            $alertClass = match ($alert['tone']) {
                                'warning' => 'bg-orange-50 text-orange-800 ring-orange-100',
                                'caution' => 'bg-amber-50 text-amber-800 ring-amber-100',
                                'danger' => 'bg-rose-50 text-rose-800 ring-rose-100',
                                default => 'bg-cyra-mint/40 text-cyra-forest ring-cyra-forest/15',
                            };
                        @endphp
                        <li>
                            @if (! empty($alert['href']))
                                <a href="{{ $alert['href'] }}" class="block rounded-xl px-3.5 py-3 text-sm font-semibold ring-1 transition hover:opacity-90 {{ $alertClass }}">
                                    {{ $alert['message'] }}
                                </a>
                            @else
                                <div class="rounded-xl px-3.5 py-3 text-sm font-semibold ring-1 {{ $alertClass }}">
                                    {{ $alert['message'] }}
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </article>
        </section>

        <section class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-2" aria-label="Verifications and quick actions">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Recent Verifications</h2>
                    <a href="{{ route('admin.dashboard', ['tab' => 'verification']) }}" class="text-sm font-bold text-cyra-forest hover:underline">View all</a>
                </div>
                <ul class="mt-4 divide-y divide-cyra-line/80">
                    @foreach (array_slice($verifications, 0, 5) as $item)
                        @php
                            $statusClass = $item['status_tone'] === 'approved'
                                ? 'text-cyra-forest'
                                : 'text-orange-500';
                        @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-cyra-ink">{{ $item['name'] }}</p>
                                <p class="text-sm text-cyra-muted">{{ $item['type'] }} · {{ $item['owner'] ?? '—' }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold {{ $statusClass }}">{{ $item['status'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </article>

            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Quick Actions</h2>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($quickActions as $action)
                        <a
                            href="{{ $action['href'] }}"
                            class="inline-flex items-center justify-center rounded-xl border-2 border-cyra-forest px-4 py-4 text-center text-sm font-bold text-cyra-forest transition hover:bg-cyra-forest hover:text-white"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </article>
        </section>
    @endif

    @if ($tab === 'verification')
        <section class="mt-6" aria-label="Farm verification queue">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Farm Verification Queue</h2>
                <p class="mt-1 text-sm text-cyra-muted">Approve or reject farms waiting for platform review.</p>
                <ul class="mt-4 divide-y divide-cyra-line/80">
                    @foreach ($verifications as $item)
                        @php
                            $statusClass = $item['status_tone'] === 'approved'
                                ? 'text-cyra-forest'
                                : 'text-orange-500';
                        @endphp
                        <li class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-cyra-ink">{{ $item['name'] }}</p>
                                <p class="text-sm text-cyra-muted">{{ $item['type'] }} · {{ $item['owner'] ?? '—' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="shrink-0 text-sm font-bold {{ $statusClass }}">{{ $item['status'] }}</span>
                                @if (($item['actionable'] ?? false) && $item['id'])
                                    <form method="POST" action="{{ route('admin.farms.approve', $item['id']) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-1.5 text-xs font-bold text-white hover:bg-cyra-green">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.farms.reject', $item['id']) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50">Reject</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </article>
        </section>
    @endif

    @if ($tab === 'users')
        <section class="mt-6" aria-label="User management">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">User Management</h2>
                <p class="mt-1 text-sm text-cyra-muted">Update account status for platform users.</p>
                <div class="mt-4 space-y-3">
                    @forelse ($users as $account)
                        <div class="rounded-xl bg-cyra-surface/50 p-4 ring-1 ring-cyra-line/70">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <p class="font-semibold text-cyra-ink">{{ $account->name }}</p>
                                    <p class="truncate text-sm text-cyra-muted">{{ $account->email }}</p>
                                    <p class="mt-1 text-xs font-semibold text-cyra-ink/80">
                                        {{ $account->role->label() }} · {{ ucfirst($account->status->value) }}
                                    </p>
                                </div>
                                @if ($account->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.status', $account) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                        @csrf
                                        <input type="hidden" name="tab" value="users">
                                        <label class="sr-only" for="status-{{ $account->id }}">Status for {{ $account->name }}</label>
                                        <select
                                            id="status-{{ $account->id }}"
                                            name="status"
                                            class="rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm font-medium text-cyra-ink"
                                        >
                                            @foreach ($statusOptions as $status)
                                                <option value="{{ $status->value }}" @selected($account->status === $status)>
                                                    {{ ucfirst($status->value) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white hover:bg-cyra-green">
                                            Update status
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs font-semibold text-cyra-muted">Your account</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-cyra-muted">No users found.</p>
                    @endforelse
                </div>
            </article>
        </section>
    @endif

    @if ($tab === 'roles')
        <section class="mt-6" aria-label="Role management">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Role Management</h2>
                <p class="mt-1 text-sm text-cyra-muted">Assign platform roles. The last active admin cannot be demoted.</p>
                <div class="mt-4 space-y-3">
                    @forelse ($users as $account)
                        <div class="rounded-xl bg-cyra-surface/50 p-4 ring-1 ring-cyra-line/70">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <p class="font-semibold text-cyra-ink">{{ $account->name }}</p>
                                    <p class="truncate text-sm text-cyra-muted">{{ $account->email }}</p>
                                    <p class="mt-1 text-xs font-semibold text-cyra-ink/80">
                                        Current: {{ $account->role->label() }}
                                    </p>
                                </div>
                                @if ($account->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.role', $account) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                        @csrf
                                        <label class="sr-only" for="role-{{ $account->id }}">Role for {{ $account->name }}</label>
                                        <select
                                            id="role-{{ $account->id }}"
                                            name="role"
                                            class="rounded-lg border border-cyra-line bg-white px-3 py-2 text-sm font-medium text-cyra-ink"
                                        >
                                            @foreach ($roleOptions as $role)
                                                <option value="{{ $role->value }}" @selected($account->role === $role)>
                                                    {{ $role->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-lg bg-cyra-forest px-3 py-2 text-xs font-bold text-white hover:bg-cyra-green">
                                            Update role
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs font-semibold text-cyra-muted">Your account</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-cyra-muted">No users found.</p>
                    @endforelse
                </div>
            </article>
        </section>
    @endif

    @if ($tab === 'audit')
        <section class="mt-6" aria-label="Audit logs">
            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Audit Logs</h2>
                <p class="mt-1 text-sm text-cyra-muted">Recent admin actions across verification, roles, status, and sessions.</p>
                <ul class="mt-4 space-y-2">
                    @forelse ($auditLogs as $log)
                        <li class="rounded-xl bg-cyra-surface/70 px-4 py-3 ring-1 ring-cyra-line/70">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-cyra-ink">{{ $log->summary }}</p>
                                    <p class="mt-0.5 text-xs text-cyra-muted">
                                        {{ $log->actor?->name ?? 'System' }} · {{ str_replace('.', ' · ', $log->action) }}
                                    </p>
                                </div>
                                <time class="shrink-0 text-xs font-medium text-cyra-muted" datetime="{{ $log->created_at?->toIso8601String() }}">
                                    {{ $log->created_at?->diffForHumans() }}
                                </time>
                            </div>
                        </li>
                    @empty
                        <li class="rounded-xl bg-cyra-surface/70 px-4 py-3 text-sm text-cyra-muted ring-1 ring-cyra-line/70">
                            No audit events yet. Approve a farm or update a user to start the trail.
                        </li>
                    @endforelse
                </ul>
            </article>
        </section>
    @endif

    @if ($tab === 'security')
        <section class="mt-6 space-y-5" aria-label="Security center">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-government.stat-card label="Active sessions (24h)" :value="number_format($security['active_sessions'])" change="Signed-in devices" />
                <x-government.stat-card label="Suspended users" :value="number_format($security['suspended_users'])" change="Blocked access" />
                <x-government.stat-card label="Inactive users" :value="number_format($security['inactive_users'])" change="Disabled accounts" />
                <x-government.stat-card label="Active admins" :value="number_format($security['admins'])" change="Privileged roles" />
            </div>

            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Active Sessions</h2>
                <p class="mt-1 text-sm text-cyra-muted">Revoke stray sessions without ending your current login.</p>
                <div class="mt-4 space-y-3">
                    @forelse ($sessions as $session)
                        <div class="rounded-xl bg-cyra-surface/50 p-4 ring-1 ring-cyra-line/70">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="min-w-0">
                                    <p class="font-semibold text-cyra-ink">
                                        {{ $session['user'] }}
                                        @if ($session['is_current'])
                                            <span class="ml-1 text-xs font-bold text-cyra-forest">(this device)</span>
                                        @endif
                                    </p>
                                    <p class="truncate text-sm text-cyra-muted">{{ $session['email'] }}</p>
                                    <p class="mt-1 text-xs text-cyra-muted">
                                        {{ $session['ip'] }} · {{ $session['agent'] }} · {{ $session['last_activity'] }}
                                    </p>
                                </div>
                                @if (! $session['is_current'])
                                    <form method="POST" action="{{ route('admin.sessions.revoke') }}">
                                        @csrf
                                        <input type="hidden" name="session_id" value="{{ $session['id'] }}">
                                        <button type="submit" class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">
                                            Revoke
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-cyra-muted">No recent database sessions found.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-cyra-line sm:p-6">
                <h2 class="text-base font-extrabold text-cyra-ink sm:text-lg">Security Controls</h2>
                <ul class="mt-4 space-y-2 text-sm text-cyra-ink">
                    @foreach ($security['checks'] as $check)
                        <li class="rounded-xl bg-cyra-surface/70 px-4 py-3 ring-1 ring-cyra-line/70">{{ $check }}</li>
                    @endforeach
                </ul>
                <div class="mt-4">
                    <a
                        href="{{ route('admin.dashboard', ['tab' => 'users']) }}"
                        class="inline-flex rounded-xl border-2 border-cyra-forest px-4 py-2.5 text-sm font-bold text-cyra-forest transition hover:bg-cyra-forest hover:text-white"
                    >
                        Manage user status
                    </a>
                </div>
            </article>
        </section>
    @endif
</x-dashboard-layout>
