<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Consumer;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Consumer\AddToCartRequest;
use App\Http\Requests\Consumer\CheckoutRequest;
use App\Http\Requests\Consumer\UpdateCartItemRequest;
use App\Models\ConsumerCartItem;
use App\Models\ConsumerOrder;
use App\Models\ConsumerProduct;
use App\Services\Consumer\ConsumerMarketplaceService;
use App\Services\Consumer\ConsumerOrderReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consumer retail marketplace for fresh farm products.
 */
class ConsumerMarketplaceController extends Controller
{
    public function __construct(
        protected ConsumerMarketplaceService $consumerMarketplaceService,
        protected ConsumerOrderReceiptService $consumerOrderReceiptService
    ) {
    }

    /**
     * Display the consumer marketplace storefront.
     */
    public function index(Request $request): View
    {
        $data = $this->consumerMarketplaceService->getStorefrontData(
            $request->user(),
            $request->string('q')->toString() ?: null,
            $request->string('category')->toString() ?: null,
            $request->string('view')->toString() ?: 'shop'
        );

        return view('consumer.marketplace', [
            ...$data,
            'cartCount' => $data['cart_count'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Add a product to the cart.
     */
    public function addToCart(AddToCartRequest $request, ConsumerProduct $product): RedirectResponse
    {
        $this->consumerMarketplaceService->addToCart(
            $request->user(),
            $product,
            (int) ($request->validated('quantity') ?? 1)
        );

        return redirect()
            ->route('consumer.marketplace', ['view' => 'cart'])
            ->with('status', "{$product->name} added to cart.");
    }

    /**
     * Update a cart line quantity.
     */
    public function updateCartItem(UpdateCartItemRequest $request, ConsumerCartItem $item): RedirectResponse
    {
        $this->consumerMarketplaceService->updateCartItem(
            $request->user(),
            $item,
            (int) $request->validated('quantity')
        );

        return redirect()
            ->route('consumer.marketplace', ['view' => 'cart'])
            ->with('status', 'Cart updated.');
    }

    /**
     * Remove a cart line.
     */
    public function removeCartItem(Request $request, ConsumerCartItem $item): RedirectResponse
    {
        $this->consumerMarketplaceService->removeCartItem($request->user(), $item);

        return redirect()
            ->route('consumer.marketplace', ['view' => 'cart'])
            ->with('status', 'Item removed from cart.');
    }

    /**
     * Checkout the cart.
     */
    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        $order = $this->consumerMarketplaceService->checkout(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('consumer.marketplace', ['view' => 'orders'])
            ->with('status', "Order #{$order->id} placed successfully.");
    }

    /**
     * Cancel a pending order.
     */
    public function cancelOrder(Request $request, ConsumerOrder $order): RedirectResponse
    {
        $this->consumerMarketplaceService->cancelOrder($request->user(), $order);

        return redirect()
            ->route('consumer.marketplace', ['view' => 'orders'])
            ->with('status', "Order #{$order->id} cancelled.");
    }

    /**
     * Pay a pending order from the buyer's wallet.
     */
    public function confirmOrder(Request $request, ConsumerOrder $order): RedirectResponse
    {
        try {
            $this->consumerMarketplaceService->confirmOrder($request->user(), $order);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('consumer.marketplace', ['view' => 'orders'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('consumer.marketplace', ['view' => 'orders'])
            ->with('status', "Order #{$order->id} paid from your wallet.");
    }

    /**
     * Printable order receipt with verification QR code.
     */
    public function receipt(Request $request, ConsumerOrder $order): View
    {
        try {
            $data = $this->consumerOrderReceiptService->getReceiptData($request->user(), $order);
        } catch (BusinessLogicException $e) {
            abort(403, $e->getMessage());
        }

        return view('consumer.receipt', $data);
    }

    /**
     * Public verification page opened by scanning the receipt QR code.
     */
    public function verify(Request $request, ConsumerOrder $order): View
    {
        $signature = (string) $request->query('sig', '');

        try {
            $data = $this->consumerOrderReceiptService->getVerificationData($order, $signature);
        } catch (BusinessLogicException $e) {
            abort(404, $e->getMessage());
        }

        return view('consumer.receipt-verify', $data);
    }
}
