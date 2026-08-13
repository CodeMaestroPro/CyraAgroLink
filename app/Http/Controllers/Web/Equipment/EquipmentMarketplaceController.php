<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Equipment;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Equipment\AddToCartRequest;
use App\Http\Requests\Equipment\UpdateCartItemRequest;
use App\Models\EquipmentCartItem;
use App\Models\EquipmentListing;
use App\Services\Equipment\EquipmentMarketplaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agricultural equipment marketplace for sale, rent, spare parts, and cart checkout.
 */
class EquipmentMarketplaceController extends Controller
{
    public function __construct(
        protected EquipmentMarketplaceService $equipmentMarketplaceService
    ) {
    }

    /**
     * Display the agricultural equipment marketplace.
     */
    public function index(Request $request): View
    {
        $data = $this->equipmentMarketplaceService->getMarketplaceData(
            $request->user(),
            $request->string('tab', 'sale')->toString(),
            $request->string('category')->toString() ?: null,
            $request->string('q')->toString() ?: null,
            $request->string('view', 'browse')->toString()
        );

        return view('equipment.marketplace', [
            'view' => $data['view'],
            'categories' => $data['categories'],
            'tabs' => $data['tabs'],
            'listings' => $data['listings'],
            'cartItems' => $data['cart_items'],
            'cartCount' => $data['cart_count'],
            'cartTotal' => $data['cart_total'],
            'walletCanPayCart' => $data['wallet_can_pay_cart'],
            'orders' => $data['orders'],
            'activeTab' => $data['active_tab'],
            'activeCategory' => $data['active_category'],
            'query' => $data['query'],
            'favoritesCount' => $data['favorites_count'],
            'ordersCount' => $data['orders_count'],
            'walletBalance' => $data['wallet_balance'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Toggle favorite status for a listing.
     */
    public function favorite(Request $request, EquipmentListing $listing): RedirectResponse
    {
        try {
            $favorited = $this->equipmentMarketplaceService->toggleFavorite($request->user(), $listing);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('equipment.marketplace', $this->browseParams($request))
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('equipment.marketplace', $this->browseParams($request))
            ->with('status', $favorited ? "Saved {$listing->name} to favorites." : "Removed {$listing->name} from favorites.");
    }

    /**
     * Add a listing to the cart (Buy / Rent never charges the wallet here).
     */
    public function addToCart(AddToCartRequest $request, EquipmentListing $listing): RedirectResponse
    {
        try {
            $this->equipmentMarketplaceService->addToCart(
                $request->user(),
                $listing,
                (int) $request->input('quantity', 1),
                (int) $request->input('rental_days', 1)
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('equipment.marketplace', $this->browseParams($request))
                ->with('error', $e->getMessage());
        }

        $verb = match ($listing->listing_type) {
            'rent' => 'rental',
            'parts' => 'parts order',
            default => 'purchase',
        };

        return redirect()
            ->route('equipment.marketplace', ['view' => 'cart'])
            ->with('status', "{$listing->name} added to your cart for {$verb}. Review and pay when ready.");
    }

    /**
     * Update a cart line.
     */
    public function updateCartItem(UpdateCartItemRequest $request, EquipmentCartItem $item): RedirectResponse
    {
        try {
            $this->equipmentMarketplaceService->updateCartItem(
                $request->user(),
                $item,
                (int) $request->input('quantity'),
                $request->filled('rental_days') ? (int) $request->input('rental_days') : null
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('equipment.marketplace', ['view' => 'cart'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('equipment.marketplace', ['view' => 'cart'])
            ->with('status', 'Cart updated.');
    }

    /**
     * Remove a cart line.
     */
    public function removeCartItem(Request $request, EquipmentCartItem $item): RedirectResponse
    {
        try {
            $this->equipmentMarketplaceService->removeCartItem($request->user(), $item);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('equipment.marketplace', ['view' => 'cart'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('equipment.marketplace', ['view' => 'cart'])
            ->with('status', 'Item removed from cart.');
    }

    /**
     * Checkout the entire cart via wallet.
     */
    public function checkout(Request $request): RedirectResponse
    {
        try {
            $orders = $this->equipmentMarketplaceService->checkoutCart($request->user());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('equipment.marketplace', ['view' => 'cart'])
                ->with('error', $e->getMessage());
        }

        $total = $orders->sum('amount_ngn');
        $count = $orders->count();

        return redirect()
            ->route('equipment.marketplace', ['view' => 'orders'])
            ->with(
                'status',
                "Checked out {$count} item".($count === 1 ? '' : 's').'. ₦'.number_format((int) $total).' charged to wallet.'
            );
    }

    /**
     * @return array<string, string>
     */
    protected function browseParams(Request $request): array
    {
        return array_filter([
            'view' => 'browse',
            'tab' => $request->input('tab'),
            'category' => $request->input('category'),
            'q' => $request->input('q'),
        ], fn ($value) => filled($value));
    }
}
