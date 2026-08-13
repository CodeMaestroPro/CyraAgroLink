<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auction;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\CommodityAuction;
use App\Services\Auction\CommodityAuctionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Commodity auction system for live bidding and history.
 */
class CommodityAuctionController extends Controller
{
    public function __construct(
        protected CommodityAuctionService $commodityAuctionService
    ) {
    }

    /**
     * Display the commodity auction system.
     */
    public function index(Request $request): View
    {
        $data = $this->commodityAuctionService->getAuctionData(
            $request->user(),
            $request->string('commodity')->toString() ?: null
        );

        return view('auction.system', [
            'filters' => $data['filters'],
            'activeFilter' => $data['active_filter'],
            'live' => $data['live'],
            'history' => $data['history'],
            'myBids' => $data['my_bids'],
            'walletBalance' => $data['wallet_balance'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Place a bid on a live auction.
     */
    public function placeBid(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'auction_id' => ['required', 'integer', 'exists:commodity_auctions,id'],
            'amount_ngn' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'commodity' => ['nullable', 'string', 'max:80'],
        ]);

        $auction = CommodityAuction::query()->findOrFail((int) $data['auction_id']);

        try {
            $bid = $this->commodityAuctionService->placeBid(
                $request->user(),
                $auction,
                (int) $data['amount_ngn']
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('auction.system', array_filter(['commodity' => $data['commodity'] ?? null]))
                ->with('error', $e->getMessage())
                ->withFragment('live');
        }

        return redirect()
            ->route('auction.system', array_filter(['commodity' => $data['commodity'] ?? null]))
            ->with(
                'status',
                "{$bid->reference}: bid ₦".number_format($bid->amount_ngn).' placed on '.$auction->name.'. Funds held from wallet.'
            )
            ->withFragment('live');
    }
}
