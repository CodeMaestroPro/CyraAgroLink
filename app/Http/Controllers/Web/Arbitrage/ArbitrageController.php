<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Arbitrage;

use App\Http\Controllers\Controller;
use App\Models\ArbitrageOpportunity;
use App\Services\Arbitrage\ArbitrageService;
use Illuminate\View\View;

/**
 * AI Arbitrage Dashboard screens.
 */
class ArbitrageController extends Controller
{
    public function __construct(
        protected ArbitrageService $arbitrageService
    ) {
    }

    /**
     * Display the best arbitrage opportunity dashboard.
     */
    public function show(): View
    {
        $payload = $this->arbitrageService->getDashboard();

        return view('farmer.arbitrage.show', [
            ...$payload,
            'notificationsCount' => 3,
        ]);
    }

    /**
     * Display a fuller analysis for an opportunity.
     */
    public function analysis(ArbitrageOpportunity $opportunity): View
    {
        $resolved = $this->arbitrageService->resolveOpportunity($opportunity->id);

        return view('farmer.arbitrage.analysis', [
            'opportunity' => $resolved,
            'costs' => [
                ['label' => 'Transportation', 'amount' => $resolved->transport_cost],
                ['label' => 'Warehouse', 'amount' => $resolved->warehouse_cost],
                ['label' => 'Charges & Fees', 'amount' => $resolved->fees_cost],
            ],
            'total_cost' => $resolved->totalCost(),
            'net_profit' => $resolved->potentialProfitPerTon() - $resolved->totalCost(),
            'notificationsCount' => 3,
        ]);
    }
}
