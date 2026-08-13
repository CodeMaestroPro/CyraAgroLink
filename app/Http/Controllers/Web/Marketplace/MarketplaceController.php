<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Marketplace;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\QuickBuyRequest;
use App\Http\Requests\Marketplace\StoreCommodityRequest;
use App\Http\Requests\Marketplace\UpdateCommodityRequest;
use App\Http\Requests\Marketplace\UpdateOrderRequest;
use App\Models\ExchangeOrder;
use App\Models\MarketplaceCommodity;
use App\Services\Marketplace\MarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Smart Marketplace browsing, listing, and order experience.
 */
class MarketplaceController extends Controller
{
    public function __construct(
        protected MarketplaceService $marketplaceService
    ) {
    }

    /**
     * Display the Smart Marketplace catalog.
     */
    public function index(Request $request): View
    {
        $payload = $this->marketplaceService->getCatalog(
            $request->string('q')->toString() ?: null,
            $request->string('category')->toString() ?: null,
            $request->string('state')->toString() ?: null,
            $request->string('view')->toString() ?: 'commodities',
            $request->user(),
            $request->string('order_status')->toString() ?: 'all'
        );

        return view('farmer.marketplace.index', [
            ...$payload,
            'notificationsCount' => max(3, (int) $payload['orders_count']),
        ]);
    }

    /**
     * Publish a commodity listing from the dashboard.
     */
    public function store(StoreCommodityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;

        $this->marketplaceService->createListing(
            $data,
            $request->file('image')
        );

        return redirect()
            ->route('marketplace.index', ['view' => 'listings'])
            ->with('status', 'Your product listing is live on the marketplace and home page.');
    }

    /**
     * Update an owned listing.
     */
    public function update(UpdateCommodityRequest $request, MarketplaceCommodity $commodity): RedirectResponse
    {
        $this->marketplaceService->updateListing($request->user(), $commodity, $request->validated());

        return redirect()
            ->route('marketplace.index', ['view' => 'listings'])
            ->with('status', 'Listing updated.');
    }

    /**
     * Deactivate an owned listing.
     */
    public function destroy(Request $request, MarketplaceCommodity $commodity): RedirectResponse
    {
        $this->marketplaceService->deactivateListing($request->user(), $commodity);

        return redirect()
            ->route('marketplace.index', ['view' => 'listings'])
            ->with('status', 'Listing deactivated.');
    }

    /**
     * Place a quick buy order against a listing (live exchange + wallet).
     */
    public function quickBuy(QuickBuyRequest $request, MarketplaceCommodity $commodity): RedirectResponse
    {
        try {
            $order = $this->marketplaceService->placeQuickBuy(
                $request->user(),
                $commodity,
                $request->validated()
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('marketplace.index')
                ->with('error', $e->getMessage());
        }

        $status = $order->status === 'filled' ? 'filled' : 'open';

        return redirect()
            ->route('marketplace.index', ['view' => 'orders', 'order_status' => $status])
            ->with(
                'status',
                $order->status === 'filled'
                    ? "Buy order matched for {$order->filled_quantity_tons} ton(s) of {$commodity->name}."
                    : "Buy order placed for {$order->original_quantity_tons} ton(s) of {$commodity->name}."
            );
    }

    /**
     * Update quantity on an open order.
     */
    public function updateOrder(UpdateOrderRequest $request, ExchangeOrder $order): RedirectResponse
    {
        try {
            $this->marketplaceService->updateOrder($request->user(), $order, $request->validated());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('marketplace.index', ['view' => 'orders', 'order_status' => 'open'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('marketplace.index', ['view' => 'orders', 'order_status' => 'open'])
            ->with('status', 'Order quantity updated.');
    }

    /**
     * Cancel an open order.
     */
    public function cancelOrder(Request $request, ExchangeOrder $order): RedirectResponse
    {
        try {
            $this->marketplaceService->cancelOrder($request->user(), $order);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('marketplace.index', ['view' => 'orders', 'order_status' => 'open'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('marketplace.index', ['view' => 'orders', 'order_status' => 'cancelled'])
            ->with('status', 'Order cancelled. Unused buy holds were returned to your wallet.');
    }
}
