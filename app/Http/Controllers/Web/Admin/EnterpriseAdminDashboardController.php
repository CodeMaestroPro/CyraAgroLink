<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\User;
use App\Services\Admin\EnterpriseAdminDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Platform-wide operations overview for enterprise administrators.
 */
class EnterpriseAdminDashboardController extends Controller
{
    public function __construct(
        protected EnterpriseAdminDashboardService $enterpriseAdminDashboardService
    ) {
    }

    /**
     * Display the enterprise admin dashboard.
     */
    public function __invoke(Request $request): View
    {
        $data = $this->enterpriseAdminDashboardService->getDashboardData(
            $request->user(),
            $request->string('tab')->toString() ?: 'dashboard'
        );

        return view('admin.dashboard', $this->viewData($data));
    }

    public function approveFarm(Request $request, Farm $farm): RedirectResponse
    {
        try {
            $this->enterpriseAdminDashboardService->approveFarm($farm, $request->user());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'verification'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.dashboard', ['tab' => 'verification'])
            ->with('status', 'Farm approved and marked active.');
    }

    public function rejectFarm(Request $request, Farm $farm): RedirectResponse
    {
        try {
            $this->enterpriseAdminDashboardService->rejectFarm($farm, $request->user());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'verification'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.dashboard', ['tab' => 'verification'])
            ->with('status', 'Farm rejected.');
    }

    public function updateUserStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(\App\Enums\UserStatus::values())],
        ]);

        $tab = $request->string('tab')->toString() ?: 'users';

        try {
            $this->enterpriseAdminDashboardService->updateUserStatus(
                $request->user(),
                $user,
                $validated['status']
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('admin.dashboard', ['tab' => $tab])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.dashboard', ['tab' => $tab])
            ->with('status', 'Updated status for '.$user->name.'.');
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(\App\Enums\UserRole::values())],
        ]);

        try {
            $this->enterpriseAdminDashboardService->updateUserRole(
                $request->user(),
                $user,
                $validated['role']
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'roles'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.dashboard', ['tab' => 'roles'])
            ->with('status', 'Updated role for '.$user->name.'.');
    }

    public function revokeSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:191'],
        ]);

        try {
            $this->enterpriseAdminDashboardService->revokeSession(
                $request->user(),
                $validated['session_id']
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('admin.dashboard', ['tab' => 'security'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.dashboard', ['tab' => 'security'])
            ->with('status', 'Session revoked.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function viewData(array $data): array
    {
        return [
            'tab' => $data['tab'],
            'kpis' => $data['kpis'],
            'distribution' => $data['distribution'],
            'activity' => $data['activity'],
            'alerts' => $data['alerts'],
            'verifications' => $data['verifications'],
            'users' => $data['users'],
            'roleOptions' => $data['role_options'],
            'statusOptions' => $data['status_options'],
            'auditLogs' => $data['audit_logs'],
            'sessions' => $data['sessions'],
            'security' => $data['security'],
            'quickActions' => $data['quick_actions'],
            'notificationsCount' => $data['notifications_count'],
        ];
    }
}
