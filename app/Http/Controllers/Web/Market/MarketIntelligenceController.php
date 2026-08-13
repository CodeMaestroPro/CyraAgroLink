<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Market;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\MarketplaceCommodity;
use App\Services\Market\MarketIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Market intelligence overview dashboard.
 */
class MarketIntelligenceController extends Controller
{
    public function __construct(
        protected MarketIntelligenceService $marketIntelligenceService
    ) {
    }

    /**
     * Display the market intelligence overview.
     */
    public function index(Request $request): View
    {
        $data = $this->marketIntelligenceService->getOverviewData(
            $request->user(),
            $request->string('tab', 'overview')->toString(),
            $request->integer('commodity') ?: null,
            $request->string('range', '6M')->toString()
        );

        return view('market.intelligence', [
            ...$data,
            'priceTrend' => $data['price_trend'],
            'demandForecast' => $data['demand_forecast'],
            'exportDestinations' => $data['export_destinations'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Add a commodity to the watchlist.
     */
    public function watch(Request $request, MarketplaceCommodity $commodity): RedirectResponse
    {
        try {
            $this->marketIntelligenceService->watch($request->user(), $commodity);
        } catch (BusinessLogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('market.intelligence', array_filter([
                'tab' => $request->string('tab', 'commodities')->toString(),
                'commodity' => $request->integer('commodity') ?: $commodity->id,
            ]))
            ->with('status', "{$commodity->name} added to your watchlist.");
    }

    /**
     * Remove a commodity from the watchlist.
     */
    public function unwatch(Request $request, MarketplaceCommodity $commodity): RedirectResponse
    {
        $this->marketIntelligenceService->unwatch($request->user(), $commodity);

        return redirect()
            ->route('market.intelligence', array_filter([
                'tab' => $request->string('tab', 'commodities')->toString(),
                'commodity' => $request->integer('commodity') ?: $commodity->id,
            ]))
            ->with('status', "{$commodity->name} removed from your watchlist.");
    }

    /**
     * Download the market intelligence CSV report.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->marketIntelligenceService->exportReport(
            $request->user(),
            $request->integer('commodity') ?: null
        );
    }
}
