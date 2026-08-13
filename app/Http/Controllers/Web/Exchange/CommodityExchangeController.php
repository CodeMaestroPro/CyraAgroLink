<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Exchange;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exchange\PlaceExchangeOrderRequest;
use App\Models\ExchangeOrder;
use App\Models\MarketplaceCommodity;
use App\Services\Exchange\CommodityExchangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Live commodity exchange trading screens.
 */
class CommodityExchangeController extends Controller
{
    public function __construct(
        protected CommodityExchangeService $exchangeService
    ) {
    }

    /**
     * Display the exchange board for a commodity.
     */
    public function show(Request $request, ?MarketplaceCommodity $commodity = null): View
    {
        $resolved = $commodity?->exists
            ? $commodity
            : $this->exchangeService->resolveCommodity(
                $request->integer('commodity') ?: null
            );

        if ($resolved->status !== 'active') {
            abort(404);
        }

        $board = $this->exchangeService->getMarketBoard(
            $resolved,
            $request->string('range', '1D')->toString(),
            $request->user()
        );

        return view('farmer.exchange.show', [
            ...$board,
            'notificationsCount' => (int) $board['notifications_count'],
        ]);
    }

    /**
     * Place a buy or sell order.
     */
    public function storeOrder(
        PlaceExchangeOrderRequest $request,
        MarketplaceCommodity $commodity
    ): RedirectResponse {
        try {
            $order = $this->exchangeService->placeOrder(
                $request->user(),
                $commodity,
                $request->validated()
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('exchange.show', ['commodity' => $commodity->id])
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $label = $order->isBuy() ? 'Buy' : 'Sell';
        $statusNote = match (true) {
            $order->status === 'filled' => ' and fully matched.',
            (int) $order->filled_quantity_tons > 0 => ' and partially matched.',
            default => '. Waiting for a counterparty.',
        };

        return redirect()
            ->route('exchange.show', ['commodity' => $commodity->id])
            ->with('status', "{$label} order placed{$statusNote}");
    }

    /**
     * Cancel an open order from the exchange board.
     */
    public function cancelOrder(Request $request, ExchangeOrder $order): RedirectResponse
    {
        try {
            $this->exchangeService->cancelOrder($request->user(), $order);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('exchange.show', ['commodity' => $order->commodity_id])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('exchange.show', ['commodity' => $order->commodity_id])
            ->with('status', 'Order cancelled. Any unused buy hold was returned to your wallet.');
    }
}
