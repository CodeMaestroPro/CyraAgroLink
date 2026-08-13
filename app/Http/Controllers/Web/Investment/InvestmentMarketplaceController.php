<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Investment;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\InvestInOpportunityRequest;
use App\Http\Requests\Investment\StoreInvestmentReviewRequest;
use App\Models\InvestmentOpportunity;
use App\Services\Investment\InvestmentMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Investment Marketplace browsing, farm galleries, reviews, and investing.
 */
class InvestmentMarketplaceController extends Controller
{
    public function __construct(
        protected InvestmentMarketplaceService $investmentMarketplaceService
    ) {
    }

    /**
     * Display featured (or all) investment opportunities.
     */
    public function index(Request $request): View
    {
        $showAll = $request->boolean('all');

        $payload = $this->investmentMarketplaceService->getCatalog(
            $request->user(),
            $showAll,
            $request->string('q')->toString() ?: null
        );

        return view('farmer.investments.index', [
            ...$payload,
            'notificationsCount' => $payload['notifications_count'],
        ]);
    }

    /**
     * Show a farm opportunity with a 3-image gallery and reviews.
     */
    public function show(Request $request, InvestmentOpportunity $opportunity): View
    {
        $payload = $this->investmentMarketplaceService->getOpportunityDetails(
            $request->user(),
            $opportunity
        );

        return view('farmer.investments.show', [
            ...$payload,
            'notificationsCount' => $payload['notifications_count'],
        ]);
    }

    /**
     * Invest wallet funds into an opportunity.
     */
    public function invest(InvestInOpportunityRequest $request, InvestmentOpportunity $opportunity): RedirectResponse
    {
        $returnToDetail = $request->boolean('detail');

        try {
            $investment = $this->investmentMarketplaceService->invest(
                $request->user(),
                $opportunity,
                $request->validated()
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->to($returnToDetail
                    ? route('investments.show', $opportunity)
                    : route('investments.index', ['all' => 1]))
                ->with('error', $e->getMessage())
                ->withInput();
        }

        $amount = (int) $request->validated('amount');
        $title = $investment->opportunity?->title ?? 'farm opportunity';

        return redirect()
            ->route('investments.show', $opportunity)
            ->with('status', "Invested ₦".number_format($amount)." in {$title}.");
    }

    /**
     * Store or update an investor review for a farm opportunity.
     */
    public function review(StoreInvestmentReviewRequest $request, InvestmentOpportunity $opportunity): RedirectResponse
    {
        try {
            $this->investmentMarketplaceService->storeReview(
                $request->user(),
                $opportunity,
                $request->validated()
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('investments.show', $opportunity)
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()
            ->route('investments.show', $opportunity)
            ->with('status', 'Your review has been saved.');
    }
}
