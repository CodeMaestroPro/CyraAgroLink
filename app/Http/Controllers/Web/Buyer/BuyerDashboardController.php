<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Buyer;

use App\Http\Controllers\Controller;
use App\Services\Buyer\BuyerDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Buyer procurement overview dashboard.
 */
class BuyerDashboardController extends Controller
{
    public function __construct(
        protected BuyerDashboardService $buyerDashboardService
    ) {
    }

    /**
     * Display the buyer dashboard.
     */
    public function __invoke(Request $request): View
    {
        $data = $this->buyerDashboardService->getDashboardData($request->user());

        return view('buyer.dashboard', [
            'greetingName' => $data['greeting_name'],
            'stats' => $data['stats'],
            'recentOrders' => $data['recent_orders'],
            'spend' => $data['spend'],
            'favoriteSuppliers' => $data['favorite_suppliers'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }
}
