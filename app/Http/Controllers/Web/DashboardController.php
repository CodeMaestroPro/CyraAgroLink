<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\Admin\EnterpriseAdminDashboardService;
use App\Services\Buyer\BuyerDashboardService;
use App\Services\Farmer\FarmerDashboardService;
use App\Services\Investment\InvestorDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Authenticated web dashboard entrypoint.
 *
 * Dispatches role-specific dashboards via dedicated services.
 */
class DashboardController extends Controller
{
    public function __construct(
        protected FarmerDashboardService $farmerDashboardService,
        protected InvestorDashboardService $investorDashboardService,
        protected BuyerDashboardService $buyerDashboardService,
        protected EnterpriseAdminDashboardService $enterpriseAdminDashboardService
    ) {
    }

    /**
     * Display the authenticated user dashboard.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole(UserRole::Admin)) {
            $data = $this->enterpriseAdminDashboardService->getDashboardData(
                $user,
                $request->string('tab')->toString() ?: 'dashboard'
            );

            return view('admin.dashboard', [
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
            ]);
        }

        if ($user->hasRole(UserRole::Buyer)) {
            $data = $this->buyerDashboardService->getDashboardData($user);

            return view('buyer.dashboard', [
                'greetingName' => $data['greeting_name'],
                'stats' => $data['stats'],
                'recentOrders' => $data['recent_orders'],
                'spend' => $data['spend'],
                'favoriteSuppliers' => $data['favorite_suppliers'],
                'notificationsCount' => $data['notifications_count'],
            ]);
        }

        if ($user->hasRole(UserRole::Investor)) {
            $data = $this->investorDashboardService->getDashboardData($user);

            return view('investor.dashboard', [
                'greetingName' => $data['greeting_name'],
                'portfolio' => $data['portfolio'],
                'performance' => $data['performance'],
                'holdings' => $data['holdings'],
                'recentEarnings' => $data['recent_earnings'],
                'walletBalance' => $data['wallet_balance'],
                'notificationsCount' => $data['notifications_count'],
            ]);
        }

        $data = $this->farmerDashboardService->getDashboardData($user);

        return view('farmer.dashboard', [
            'greetingName' => $data['greeting_name'],
            'stats' => $data['stats'],
            'farms' => $data['farms'],
            'activities' => $data['activities'],
            'earnings' => $data['earnings'],
            'aiRecommendation' => $data['ai_recommendation'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }
}
