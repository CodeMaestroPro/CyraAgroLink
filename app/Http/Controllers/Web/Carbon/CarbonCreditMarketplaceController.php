<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Carbon;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\CarbonListing;
use App\Services\Carbon\CarbonCreditMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Carbon credit marketplace overview and trade actions.
 */
class CarbonCreditMarketplaceController extends Controller
{
    public function __construct(
        protected CarbonCreditMarketplaceService $carbonCreditMarketplaceService
    ) {
    }

    /**
     * Display the carbon credit marketplace.
     */
    public function index(Request $request): View
    {
        $data = $this->carbonCreditMarketplaceService->getMarketplaceData($request->user());

        return view('carbon.marketplace', [
            'kpis' => $data['kpis'],
            'trend' => $data['trend'],
            'transactions' => $data['transactions'],
            'listings' => $data['listings'],
            'farms' => $data['farms'],
            'actions' => $data['actions'],
            'defaultListCredits' => $data['default_list_credits'],
            'unitPriceUsd' => $data['unit_price_usd'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Claim this month's field sequestration credits.
     */
    public function generate(Request $request): RedirectResponse
    {
        try {
            $tx = $this->carbonCreditMarketplaceService->generateCredits($request->user());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('carbon.marketplace')
                ->with('error', $e->getMessage())
                ->withFragment('credits');
        }

        return redirect()
            ->route('carbon.marketplace')
            ->with('status', 'Claimed '.$tx->credits_tco2e.' tCO2e field credits.')
            ->withFragment('credits');
    }

    /**
     * List available credits for sale on the marketplace.
     */
    public function list(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'credits' => ['required', 'numeric', 'min:1', 'max:100000'],
            'unit_price_usd' => ['nullable', 'numeric', 'min:1', 'max:500'],
        ]);

        try {
            $listing = $this->carbonCreditMarketplaceService->listCreditsForSale(
                $request->user(),
                (float) $data['credits'],
                isset($data['unit_price_usd']) ? (float) $data['unit_price_usd'] : null
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('carbon.marketplace')
                ->with('error', $e->getMessage())
                ->withFragment('listings');
        }

        return redirect()
            ->route('carbon.marketplace')
            ->with('status', 'Listed '.$listing->credits_tco2e.' tCO2e for sale.')
            ->withFragment('listings');
    }

    /**
     * Complete a sale against an open listing and credit the wallet.
     */
    public function sell(Request $request, CarbonListing $listing): RedirectResponse
    {
        try {
            $tx = $this->carbonCreditMarketplaceService->sellListing($request->user(), $listing);
        } catch (BusinessLogicException $e) {
            $status = $e->getStatusCode() === 403 ? 403 : null;

            if ($status === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('carbon.marketplace')
                ->with('error', $e->getMessage())
                ->withFragment('listings');
        }

        return redirect()
            ->route('carbon.marketplace')
            ->with('status', $tx->title.' completed. ₦'.number_format($tx->value_ngn).' credited to your wallet.')
            ->withFragment('transactions');
    }

    /**
     * Retire credits as a voluntary offset.
     */
    public function offset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'credits' => ['required', 'numeric', 'min:1', 'max:100000'],
        ]);

        try {
            $tx = $this->carbonCreditMarketplaceService->offsetCredits($request->user(), (float) $data['credits']);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('carbon.marketplace')
                ->with('error', $e->getMessage())
                ->withFragment('transactions');
        }

        return redirect()
            ->route('carbon.marketplace')
            ->with('status', 'Offset '.$tx->credits_tco2e.' tCO2e from your balance.')
            ->withFragment('transactions');
    }
}
