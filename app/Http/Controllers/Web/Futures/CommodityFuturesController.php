<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Futures;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\FuturesContract;
use App\Models\FuturesOrder;
use App\Models\FuturesPosition;
use App\Services\Futures\CommodityFuturesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Commodity futures exchange board for contract trading.
 */
class CommodityFuturesController extends Controller
{
    public function __construct(
        protected CommodityFuturesService $commodityFuturesService
    ) {
    }

    /**
     * Display the commodity futures exchange.
     */
    public function index(Request $request): View
    {
        $data = $this->commodityFuturesService->getBoardData(
            $request->user(),
            $request->integer('contract') ?: null
        );

        return view('futures.exchange', [
            'contract' => $data['contract'],
            'contracts' => $data['contracts'],
            'stats' => $data['stats'],
            'ranges' => $data['ranges'],
            'candles' => $data['candles'],
            'buyOrders' => $data['buy_orders'],
            'sellOrders' => $data['sell_orders'],
            'userOrders' => $data['user_orders'],
            'positions' => $data['positions'],
            'walletBalance' => $data['wallet_balance'],
            'actions' => $data['actions'],
            'defaultQty' => $data['default_qty'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Place a buy or sell futures order.
     */
    public function placeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contract_id' => ['required', 'integer', 'exists:futures_contracts,id'],
            'side' => ['required', 'in:buy,sell'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'price' => ['required', 'integer', 'min:1000', 'max:100000000'],
        ]);

        $contract = FuturesContract::query()->findOrFail((int) $data['contract_id']);

        try {
            $order = $this->commodityFuturesService->placeOrder($request->user(), $contract, $data);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('futures.exchange', ['contract' => $contract->id])
                ->with('error', $e->getMessage())
                ->withFragment('depth');
        }

        $verb = $order->side === 'buy' ? 'Buy' : 'Sell';

        return redirect()
            ->route('futures.exchange', ['contract' => $contract->id])
            ->with(
                'status',
                "{$order->reference}: {$verb} {$order->quantity} @ ₦".number_format($order->price).' · status '.$order->status.'.'
            )
            ->withFragment('orders');
    }

    /**
     * Cancel an open futures order and refund unused margin.
     */
    public function cancelOrder(Request $request, FuturesOrder $order): RedirectResponse
    {
        try {
            $cancelled = $this->commodityFuturesService->cancelOrder($request->user(), $order);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('futures.exchange', ['contract' => $order->contract_id])
                ->with('error', $e->getMessage())
                ->withFragment('orders');
        }

        return redirect()
            ->route('futures.exchange', ['contract' => $cancelled->contract_id])
            ->with('status', "{$cancelled->reference} cancelled. Unused margin returned to wallet.")
            ->withFragment('orders');
    }

    /**
     * Close an open position at the current mark price.
     */
    public function closePosition(Request $request, FuturesPosition $position): RedirectResponse
    {
        try {
            $closed = $this->commodityFuturesService->closePosition($request->user(), $position);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('futures.exchange', ['contract' => $position->contract_id])
                ->with('error', $e->getMessage())
                ->withFragment('positions');
        }

        $pnl = (int) $closed->realized_pnl_ngn;

        return redirect()
            ->route('futures.exchange', ['contract' => $closed->contract_id])
            ->with(
                'status',
                "{$closed->reference} closed. PnL ".($pnl >= 0 ? '+' : '-').'₦'.number_format(abs($pnl)).' settled to wallet.'
            )
            ->withFragment('positions');
    }
}
