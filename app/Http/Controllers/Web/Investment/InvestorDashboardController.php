<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Investment;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\UserInvestment;
use App\Services\Investment\InvestorDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Investor portfolio overview dashboard.
 */
class InvestorDashboardController extends Controller
{
    public function __construct(
        protected InvestorDashboardService $investorDashboardService
    ) {
    }

    /**
     * Display the investor dashboard.
     */
    public function index(Request $request): View
    {
        $data = $this->investorDashboardService->getDashboardData(
            $request->user(),
            $request->string('q')->toString() ?: null
        );

        return view('investor.dashboard', [
            'greetingName' => $data['greeting_name'],
            'portfolio' => $data['portfolio'],
            'performance' => $data['performance'],
            'holdings' => $data['holdings'],
            'recentEarnings' => $data['recent_earnings'],
            'walletBalance' => $data['wallet_balance'],
            'notificationsCount' => $data['notifications_count'],
            'query' => $data['query'] ?? '',
        ]);
    }

    /**
     * Collect period earnings from an active holding into the wallet.
     */
    public function collect(Request $request, UserInvestment $investment): RedirectResponse
    {
        try {
            $payout = $this->investorDashboardService->collectEarnings(
                $request->user(),
                $investment
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('investor.dashboard')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('investor.dashboard')
            ->with('status', 'Collected ₦'.number_format($payout->amount).' from '.$payout->title.'.');
    }
}
