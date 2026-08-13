<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\FarmStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\AuditLog;
use App\Models\Farm;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform operations overview with live verification and account controls.
 */
class EnterpriseAdminDashboardService
{
    /** @var list<string> */
    private const TABS = ['dashboard', 'users', 'roles', 'verification', 'audit', 'security'];

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, string $tab = 'dashboard'): array
    {
        $tab = in_array($tab, self::TABS, true) ? $tab : 'dashboard';
        $pendingFarms = $this->pendingFarms();

        return [
            'tab' => $tab,
            'kpis' => $this->kpis(),
            'distribution' => $this->userDistribution(),
            'activity' => $this->systemActivity(),
            'alerts' => $this->platformAlerts($pendingFarms->count()),
            'verifications' => $this->recentVerifications($pendingFarms),
            'users' => $this->users(),
            'role_options' => UserRole::cases(),
            'status_options' => UserStatus::cases(),
            'audit_logs' => $this->auditLogs(),
            'sessions' => $this->activeSessions(),
            'security' => $this->securitySnapshot(),
            'quick_actions' => $this->quickActions(),
            'notifications_count' => $pendingFarms->count(),
        ];
    }

    public function approveFarm(Farm $farm, User $actor): Farm
    {
        if ($farm->status !== FarmStatus::PendingReview) {
            throw new BusinessLogicException('Only farms pending review can be approved.');
        }

        $farm->forceFill([
            'status' => FarmStatus::Active,
            'registered_at' => $farm->registered_at ?? now(),
        ])->save();

        $this->log($actor, 'farm.approved', $farm, 'Approved farm "'.$farm->name.'"');

        return $farm->refresh();
    }

    public function rejectFarm(Farm $farm, User $actor): Farm
    {
        if ($farm->status !== FarmStatus::PendingReview) {
            throw new BusinessLogicException('Only farms pending review can be rejected.');
        }

        $farm->forceFill([
            'status' => FarmStatus::Inactive,
        ])->save();

        $this->log($actor, 'farm.rejected', $farm, 'Rejected farm "'.$farm->name.'"');

        return $farm->refresh();
    }

    public function updateUserStatus(User $actor, User $target, string $status): User
    {
        $next = UserStatus::tryFrom($status);
        if ($next === null) {
            throw new BusinessLogicException('Invalid account status.');
        }

        if ($actor->id === $target->id) {
            throw new BusinessLogicException('You cannot change your own account status.');
        }

        if (
            $target->role === UserRole::Admin
            && $next !== UserStatus::Active
            && $this->activeAdminCount() <= 1
        ) {
            throw new BusinessLogicException('Cannot suspend or deactivate the last active admin.');
        }

        $previous = $target->status?->value;
        $target->forceFill(['status' => $next])->save();

        $this->log(
            $actor,
            'user.status_updated',
            $target,
            'Changed status for '.$target->email.' from '.($previous ?? 'unknown').' to '.$next->value,
            ['from' => $previous, 'to' => $next->value]
        );

        return $target->refresh();
    }

    public function updateUserRole(User $actor, User $target, string $role): User
    {
        $next = UserRole::tryFrom($role);
        if ($next === null) {
            throw new BusinessLogicException('Invalid role.');
        }

        if ($actor->id === $target->id) {
            throw new BusinessLogicException('You cannot change your own role.');
        }

        if (
            $target->role === UserRole::Admin
            && $next !== UserRole::Admin
            && $this->activeAdminCount() <= 1
        ) {
            throw new BusinessLogicException('Cannot demote the last active admin.');
        }

        $previous = $target->role?->value;
        $target->forceFill(['role' => $next])->save();

        $this->log(
            $actor,
            'user.role_updated',
            $target,
            'Changed role for '.$target->email.' from '.($previous ?? 'unknown').' to '.$next->value,
            ['from' => $previous, 'to' => $next->value]
        );

        return $target->refresh();
    }

    public function revokeSession(User $actor, string $sessionId): void
    {
        if (! Schema::hasTable('sessions')) {
            throw new BusinessLogicException('Session storage is not available.');
        }

        if ($sessionId === (string) session()->getId()) {
            throw new BusinessLogicException('You cannot revoke your current session from here.');
        }

        $deleted = DB::table('sessions')->where('id', $sessionId)->delete();
        if ($deleted === 0) {
            throw new BusinessLogicException('Session not found or already revoked.');
        }

        $this->log($actor, 'session.revoked', null, 'Revoked session '.$sessionId, [
            'session_id' => $sessionId,
        ]);
    }

    /**
     * @return list<array{label: string, value: string, change: string}>
     */
    protected function kpis(): array
    {
        $totalUsers = User::query()->count();
        $activeUsers = User::query()->where('status', UserStatus::Active)->count();
        $newUsersWeek = User::query()->where('created_at', '>=', now()->subDays(7))->count();
        $pendingFarms = Farm::query()->where('status', FarmStatus::PendingReview)->count();

        $volume = (float) WalletTransaction::query()
            ->where('type', 'credit')
            ->sum('amount');

        $volumeWeek = (float) WalletTransaction::query()
            ->where('type', 'credit')
            ->where('created_at', '>=', now()->subDays(7))
            ->sum('amount');

        return [
            [
                'label' => 'Total Users',
                'value' => number_format($totalUsers),
                'change' => $newUsersWeek.' new this week',
            ],
            [
                'label' => 'Active Users',
                'value' => number_format($activeUsers),
                'change' => $totalUsers > 0
                    ? round(($activeUsers / $totalUsers) * 100).'% of accounts'
                    : 'No accounts yet',
            ],
            [
                'label' => 'Wallet Volume',
                'value' => '₦'.$this->compactNumber($volume),
                'change' => '₦'.$this->compactNumber($volumeWeek).' credited (7d)',
            ],
            [
                'label' => 'Pending Farms',
                'value' => number_format($pendingFarms),
                'change' => $pendingFarms > 0 ? 'Needs review' : 'Queue clear',
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    protected function userDistribution(): array
    {
        $counts = [
            'Farmers' => User::query()->where('role', UserRole::Farmer)->count(),
            'Buyers' => User::query()->where('role', UserRole::Buyer)->count(),
            'Investors' => User::query()->where('role', UserRole::Investor)->count(),
            'Admins' => User::query()->where('role', UserRole::Admin)->count(),
            'Others' => User::query()->whereNotIn('role', [
                UserRole::Farmer,
                UserRole::Buyer,
                UserRole::Investor,
                UserRole::Admin,
            ])->count(),
        ];

        $sum = max(array_sum($counts), 1);
        $values = array_map(fn (int $c) => (int) round(($c / $sum) * 100), array_values($counts));
        $drift = 100 - array_sum($values);
        if ($values !== []) {
            $values[0] += $drift;
        }

        return [
            'labels' => array_keys($counts),
            'values' => array_values($values),
            'colors' => ['#1E3A8A', '#F97316', '#0A5C2E', '#22C55E', '#78716C'],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    protected function systemActivity(): array
    {
        $labels = [];
        $values = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $labels[] = $day->format('D');
            $values[] = (float) User::query()
                ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                ->count()
                + Farm::query()
                    ->whereBetween('created_at', [$day, $day->copy()->endOfDay()])
                    ->count();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return list<array{message: string, tone: string, href: string|null}>
     */
    protected function platformAlerts(int $pendingFarms): array
    {
        $suspended = User::query()->where('status', UserStatus::Suspended)->count();
        $pendingUsers = User::query()->where('status', UserStatus::Pending)->count();
        $withdrawals = WalletTransaction::query()
            ->where('category', 'withdrawal')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $alerts = [
            [
                'message' => $pendingFarms.' farm verification'.($pendingFarms === 1 ? '' : 's').' pending',
                'tone' => $pendingFarms > 0 ? 'warning' : 'info',
                'href' => route('admin.dashboard', ['tab' => 'verification']),
            ],
            [
                'message' => $withdrawals.' withdrawal'.($withdrawals === 1 ? '' : 's').' in the last 7 days',
                'tone' => $withdrawals > 0 ? 'caution' : 'info',
                'href' => route('wallet.index'),
            ],
            [
                'message' => $suspended.' suspended account'.($suspended === 1 ? '' : 's'),
                'tone' => $suspended > 0 ? 'danger' : 'info',
                'href' => route('admin.dashboard', ['tab' => 'security']),
            ],
            [
                'message' => $pendingUsers.' user'.($pendingUsers === 1 ? '' : 's').' awaiting activation',
                'tone' => $pendingUsers > 0 ? 'warning' : 'info',
                'href' => route('admin.dashboard', ['tab' => 'users']),
            ],
        ];

        return $alerts;
    }

    /**
     * @param  Collection<int, Farm>  $pendingFarms
     * @return list<array{id: int|null, name: string, type: string, owner: string, status: string, status_tone: string, actionable: bool}>
     */
    protected function recentVerifications(Collection $pendingFarms): array
    {
        $items = $pendingFarms->take(12)->map(fn (Farm $farm) => [
            'id' => $farm->id,
            'name' => $farm->name ?: ('Farm #'.$farm->id),
            'type' => 'Farm',
            'owner' => $farm->user?->name ?? 'Unknown owner',
            'status' => 'Pending',
            'status_tone' => 'pending',
            'actionable' => true,
        ])->all();

        if ($items === []) {
            $items[] = [
                'id' => null,
                'name' => 'No farms awaiting review',
                'type' => 'Queue',
                'owner' => '—',
                'status' => 'Clear',
                'status_tone' => 'approved',
                'actionable' => false,
            ];
        }

        return $items;
    }

    /**
     * @return Collection<int, Farm>
     */
    protected function pendingFarms(): Collection
    {
        return Farm::query()
            ->with('user')
            ->where('status', FarmStatus::PendingReview)
            ->latest('id')
            ->limit(20)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    protected function users(): Collection
    {
        return User::query()->latest('id')->limit(40)->get();
    }

    /**
     * @return Collection<int, AuditLog>
     */
    protected function auditLogs(): Collection
    {
        if (! Schema::hasTable('audit_logs')) {
            return collect();
        }

        return AuditLog::query()
            ->with('actor')
            ->latest('id')
            ->limit(40)
            ->get();
    }

    /**
     * @return list<array{id: string, user: string, email: string, ip: string, agent: string, last_activity: string, is_current: bool}>
     */
    protected function activeSessions(): array
    {
        if (! Schema::hasTable('sessions')) {
            return [];
        }

        $currentId = (string) session()->getId();
        $cutoff = now()->subDays(7)->getTimestamp();

        $rows = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $cutoff)
            ->orderByDesc('last_activity')
            ->limit(30)
            ->get();

        $userIds = $rows->pluck('user_id')->unique()->filter()->all();
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        return $rows->map(function ($row) use ($users, $currentId): array {
            $user = $users->get($row->user_id);

            return [
                'id' => (string) $row->id,
                'user' => $user?->name ?? 'User #'.$row->user_id,
                'email' => $user?->email ?? '—',
                'ip' => (string) ($row->ip_address ?? '—'),
                'agent' => $this->shortAgent((string) ($row->user_agent ?? '')),
                'last_activity' => Carbon::createFromTimestamp((int) $row->last_activity)->diffForHumans(),
                'is_current' => (string) $row->id === $currentId,
            ];
        })->all();
    }

    /**
     * @return array{active_sessions: int, suspended_users: int, inactive_users: int, admins: int, checks: list<string>}
     */
    protected function securitySnapshot(): array
    {
        $activeSessions = Schema::hasTable('sessions')
            ? (int) DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subDays(1)->getTimestamp())
                ->count()
            : 0;

        return [
            'active_sessions' => $activeSessions,
            'suspended_users' => User::query()->where('status', UserStatus::Suspended)->count(),
            'inactive_users' => User::query()->where('status', UserStatus::Inactive)->count(),
            'admins' => User::query()->where('role', UserRole::Admin)->where('status', UserStatus::Active)->count(),
            'checks' => [
                'Role middleware guards privileged dashboards (`role:admin`).',
                'Security headers (CSP, frame deny, nosniff) applied on every response.',
                'Inactive and suspended accounts cannot keep an authenticated session.',
                'Admin write endpoints are rate-limited (`throttle:writes`).',
                'Account role and status changes are recorded in the audit log.',
            ],
        ];
    }

    /**
     * @return list<array{label: string, href: string}>
     */
    protected function quickActions(): array
    {
        return [
            [
                'label' => 'Approve Farms',
                'href' => route('admin.dashboard', ['tab' => 'verification']),
            ],
            [
                'label' => 'Manage Users',
                'href' => route('admin.dashboard', ['tab' => 'users']),
            ],
            [
                'label' => 'Manage Investments',
                'href' => route('investments.index'),
            ],
            [
                'label' => 'View Reports',
                'href' => route('reporting.analytics'),
            ],
            [
                'label' => 'Security Center',
                'href' => route('admin.dashboard', ['tab' => 'security']),
            ],
            [
                'label' => 'Account Settings',
                'href' => route('profile.edit'),
            ],
        ];
    }

    protected function activeAdminCount(): int
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->where('status', UserStatus::Active)
            ->count();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    protected function log(User $actor, string $action, ?object $subject, string $summary, ?array $meta = null): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::query()->create([
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject !== null && isset($subject->id) ? (int) $subject->id : null,
            'summary' => $summary,
            'meta' => $meta,
        ]);
    }

    protected function compactNumber(float $value): string
    {
        $abs = abs($value);

        if ($abs >= 1_000_000_000) {
            return rtrim(rtrim(number_format($value / 1_000_000_000, 2), '0'), '.').'B';
        }

        if ($abs >= 1_000_000) {
            return rtrim(rtrim(number_format($value / 1_000_000, 2), '0'), '.').'M';
        }

        if ($abs >= 1_000) {
            return rtrim(rtrim(number_format($value / 1_000, 1), '0'), '.').'K';
        }

        return number_format($value, 0);
    }

    protected function shortAgent(string $agent): string
    {
        if ($agent === '') {
            return 'Unknown device';
        }

        return strlen($agent) > 64 ? substr($agent, 0, 61).'…' : $agent;
    }
}
