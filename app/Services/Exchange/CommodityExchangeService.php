<?php

declare(strict_types=1);

namespace App\Services\Exchange;

use App\Exceptions\BusinessLogicException;
use App\Models\ExchangeOrder;
use App\Models\ExchangeTrade;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Models\UserInboxNotification;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Live commodity exchange: order book, matching, wallet settlement, and trade ledger.
 */
class CommodityExchangeService
{
    public function __construct(
        protected MarketplaceService $marketplaceService,
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * Resolve a commodity for the exchange screen.
     */
    public function resolveCommodity(?int $commodityId = null): MarketplaceCommodity
    {
        $this->marketplaceService->getCatalog();

        if ($commodityId !== null) {
            return MarketplaceCommodity::query()
                ->where('status', 'active')
                ->findOrFail($commodityId);
        }

        return MarketplaceCommodity::query()
            ->where('status', 'active')
            ->where('name', 'Maize')
            ->first()
            ?? MarketplaceCommodity::query()
                ->where('status', 'active')
                ->orderBy('name')
                ->firstOrFail();
    }

    /**
     * Build exchange page payload from live orders and trades only.
     *
     * @return array<string, mixed>
     */
    public function getMarketBoard(MarketplaceCommodity $commodity, string $range = '1D', ?User $user = null): array
    {
        $range = strtoupper($range);
        $allowed = ['1D', '7D', '1M', '3M', '1Y'];

        if (! in_array($range, $allowed, true)) {
            $range = '1D';
        }

        $change = $commodity->changePercent();
        $userOrders = $user
            ? ExchangeOrder::query()
                ->where('user_id', $user->id)
                ->where('commodity_id', $commodity->id)
                ->latest('id')
                ->limit(20)
                ->get()
            : collect();

        $recentTrades = ExchangeTrade::query()
            ->where('commodity_id', $commodity->id)
            ->latest('traded_at')
            ->latest('id')
            ->limit(12)
            ->get();

        $dayStats = $this->dayStats($commodity);

        return [
            'commodity' => $commodity,
            'commodities' => $this->tradableCommodities(),
            'range' => $range,
            'change_percent' => $change,
            'chart' => $this->buildChartSeries($commodity, $range),
            'buy_orders' => $this->buildDepth($commodity, 'buy'),
            'sell_orders' => $this->buildDepth($commodity, 'sell'),
            'recent_trades' => $recentTrades,
            'user_orders' => $userOrders,
            'open_orders_count' => $userOrders->where('status', 'open')->count(),
            'wallet_balance' => $user ? $this->walletService->getBalance($user) : 0,
            'notifications_count' => $user
                ? UserInboxNotification::query()
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count()
                : 0,
            'summary' => [
                'day_high' => $dayStats['high'],
                'day_low' => $dayStats['low'],
                'volume' => $this->tradedVolume($commodity),
                'open_interest' => $this->openInterest($commodity),
            ],
        ];
    }

    /**
     * Place a buy or sell order and attempt immediate matching with wallet settlement.
     *
     * @param  array{side: string, quantity_tons: int, price_per_ton: int}  $data
     */
    public function placeOrder(User $user, MarketplaceCommodity $commodity, array $data): ExchangeOrder
    {
        if ($commodity->status !== 'active') {
            throw new BusinessLogicException('This commodity is not available for trading.');
        }

        $side = strtolower($data['side']);

        if (! in_array($side, ['buy', 'sell'], true)) {
            throw new BusinessLogicException('Invalid order side.');
        }

        $quantity = (int) $data['quantity_tons'];
        $price = (int) $data['price_per_ton'];

        if ($quantity < 1 || $price < 1) {
            throw new BusinessLogicException('Quantity and price must be at least 1.');
        }

        return DB::transaction(function () use ($user, $commodity, $side, $quantity, $price): ExchangeOrder {
            $reserved = $side === 'buy' ? $quantity * $price : 0;

            $order = ExchangeOrder::query()->create([
                'user_id' => $user->id,
                'commodity_id' => $commodity->id,
                'side' => $side,
                'quantity_tons' => $quantity,
                'original_quantity_tons' => $quantity,
                'filled_quantity_tons' => 0,
                'price_per_ton' => $price,
                'reserved_amount' => $reserved,
                'status' => 'open',
            ]);

            if ($side === 'buy') {
                $this->walletService->lockExchangeBuy(
                    $user,
                    $reserved,
                    $order,
                    sprintf(
                        'Hold for %s buy %s tons @ ₦%s',
                        $commodity->name,
                        number_format($quantity),
                        number_format($price)
                    )
                );
            }

            $lastTradePrice = $this->matchOrder($order->fresh(), $commodity);

            if ($lastTradePrice !== null) {
                $commodity->refresh();
                $commodity->forceFill([
                    'previous_price_per_ton' => $commodity->price_per_ton,
                    'price_per_ton' => $lastTradePrice,
                    'day_high' => max((int) ($commodity->day_high ?? $lastTradePrice), $lastTradePrice),
                    'day_low' => min((int) ($commodity->day_low ?? $lastTradePrice), $lastTradePrice),
                    'volume_tons' => $this->tradedVolume($commodity),
                    'open_interest_tons' => $this->openInterest($commodity),
                ])->save();
            } else {
                $commodity->forceFill([
                    'open_interest_tons' => $this->openInterest($commodity),
                ])->save();
            }

            return $order->refresh();
        });
    }

    /**
     * Cancel an open exchange order and release any unused buy hold.
     */
    public function cancelOrder(User $user, ExchangeOrder $order): ExchangeOrder
    {
        $this->assertOwnedOrder($user, $order);

        if ($order->status !== 'open') {
            throw new BusinessLogicException('Only open orders can be cancelled.');
        }

        return DB::transaction(function () use ($user, $order): ExchangeOrder {
            $locked = ExchangeOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'open') {
                throw new BusinessLogicException('Only open orders can be cancelled.');
            }

            if ($locked->isBuy() && (int) $locked->reserved_amount > 0) {
                $this->walletService->releaseExchangeHold(
                    $user,
                    (int) $locked->reserved_amount,
                    $locked,
                    'Unused buy hold released on cancel'
                );
            }

            $locked->forceFill([
                'status' => 'cancelled',
                'reserved_amount' => 0,
            ])->save();

            if ($locked->commodity) {
                $locked->commodity->forceFill([
                    'open_interest_tons' => $this->openInterest($locked->commodity),
                ])->save();
            }

            return $locked->refresh();
        });
    }

    /**
     * @return Collection<int, MarketplaceCommodity>
     */
    protected function tradableCommodities(): Collection
    {
        return MarketplaceCommodity::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name', 'scientific_name', 'price_per_ton']);
    }

    /**
     * Match an incoming order against opposing open orders and settle wallets.
     *
     * @return int|null Last traded price when any fill occurred
     */
    protected function matchOrder(ExchangeOrder $incoming, MarketplaceCommodity $commodity): ?int
    {
        $remaining = (int) $incoming->quantity_tons;
        $lastTradePrice = null;

        $opposing = ExchangeOrder::query()
            ->where('commodity_id', $commodity->id)
            ->where('status', 'open')
            ->where('id', '!=', $incoming->id)
            ->where('user_id', '!=', $incoming->user_id)
            ->where('side', $incoming->side === 'buy' ? 'sell' : 'buy')
            ->when(
                $incoming->side === 'buy',
                fn ($q) => $q->where('price_per_ton', '<=', $incoming->price_per_ton)->orderBy('price_per_ton')->orderBy('id'),
                fn ($q) => $q->where('price_per_ton', '>=', $incoming->price_per_ton)->orderByDesc('price_per_ton')->orderBy('id')
            )
            ->lockForUpdate()
            ->get();

        foreach ($opposing as $counter) {
            if ($remaining <= 0) {
                break;
            }

            $available = (int) $counter->quantity_tons;
            $fillQty = min($remaining, $available);
            $tradePrice = (int) $counter->price_per_ton;

            if ($fillQty < 1) {
                continue;
            }

            $this->executeTrade($incoming, $counter, $commodity, $fillQty, $tradePrice);

            $remaining -= $fillQty;
            $lastTradePrice = $tradePrice;

            $incoming->refresh();
        }

        return $lastTradePrice;
    }

    protected function executeTrade(
        ExchangeOrder $incoming,
        ExchangeOrder $counter,
        MarketplaceCommodity $commodity,
        int $fillQty,
        int $tradePrice
    ): ExchangeTrade {
        $buyOrder = $incoming->isBuy() ? $incoming : $counter;
        $sellOrder = $incoming->isBuy() ? $counter : $incoming;

        $notional = $fillQty * $tradePrice;
        $buyerLimit = (int) $buyOrder->price_per_ton;
        $buyerReservedRelease = $fillQty * $buyerLimit;
        $buyerRefund = max(0, $buyerReservedRelease - $notional);

        if ($buyerReservedRelease > (int) $buyOrder->reserved_amount) {
            throw new BusinessLogicException('Buy order reservation is insufficient for this fill.');
        }

        $trade = ExchangeTrade::query()->create([
            'commodity_id' => $commodity->id,
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyOrder->user_id,
            'seller_id' => $sellOrder->user_id,
            'quantity_tons' => $fillQty,
            'price_per_ton' => $tradePrice,
            'notional_amount' => $notional,
            'traded_at' => now(),
        ]);

        $this->walletService->creditExchangeSale(
            $sellOrder->user,
            $notional,
            $trade,
            sprintf(
                'Sold %s tons of %s @ ₦%s',
                number_format($fillQty),
                $commodity->name,
                number_format($tradePrice)
            )
        );

        if ($buyerRefund > 0) {
            $this->walletService->releaseExchangeHold(
                $buyOrder->user,
                $buyerRefund,
                $trade,
                sprintf(
                    'Price improvement on %s tons of %s',
                    number_format($fillQty),
                    $commodity->name
                )
            );
        }

        $this->applyFill($buyOrder, $fillQty, $buyerReservedRelease);
        $this->applyFill($sellOrder, $fillQty, 0);

        UserInboxNotification::query()->create([
            'user_id' => $buyOrder->user_id,
            'title' => 'Buy order filled',
            'body' => sprintf(
                'Bought %s tons of %s at ₦%s/ton.',
                number_format($fillQty),
                $commodity->name,
                number_format($tradePrice)
            ),
            'tone' => 'success',
            'category' => 'trade',
        ]);

        UserInboxNotification::query()->create([
            'user_id' => $sellOrder->user_id,
            'title' => 'Sell order filled',
            'body' => sprintf(
                'Sold %s tons of %s at ₦%s/ton. ₦%s credited to your wallet.',
                number_format($fillQty),
                $commodity->name,
                number_format($tradePrice),
                number_format($notional)
            ),
            'tone' => 'success',
            'category' => 'trade',
        ]);

        return $trade;
    }

    protected function applyFill(ExchangeOrder $order, int $fillQty, int $reservedConsumed): void
    {
        $remaining = max(0, (int) $order->quantity_tons - $fillQty);
        $filled = (int) $order->filled_quantity_tons + $fillQty;
        $reserved = max(0, (int) $order->reserved_amount - $reservedConsumed);

        $order->forceFill([
            'quantity_tons' => $remaining,
            'filled_quantity_tons' => $filled,
            'reserved_amount' => $order->isBuy() ? $reserved : 0,
            'status' => $remaining === 0 ? 'filled' : 'open',
        ])->save();
    }

    protected function assertOwnedOrder(User $user, ExchangeOrder $order): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ((int) $order->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own orders.', 'ORDER_FORBIDDEN', 403);
        }
    }

    /**
     * Chart series from executed trades in the selected range.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    protected function buildChartSeries(MarketplaceCommodity $commodity, string $range): array
    {
        [$from, $labelFormat, $bucket] = match ($range) {
            '7D' => [now()->subDays(7), 'D', 'day'],
            '1M' => [now()->subDays(30), 'd M', 'day'],
            '3M' => [now()->subMonths(3), 'M', 'week'],
            '1Y' => [now()->subYear(), 'M', 'month'],
            default => [now()->startOfDay(), 'H:i', 'hour'],
        };

        $trades = ExchangeTrade::query()
            ->where('commodity_id', $commodity->id)
            ->where('traded_at', '>=', $from)
            ->orderBy('traded_at')
            ->orderBy('id')
            ->get(['price_per_ton', 'traded_at']);

        if ($trades->isEmpty()) {
            $label = now()->format($labelFormat);

            return [
                'labels' => [$label],
                'values' => [(int) $commodity->price_per_ton],
            ];
        }

        $buckets = [];
        foreach ($trades as $trade) {
            $key = match ($bucket) {
                'hour' => $trade->traded_at?->format('Y-m-d H:00') ?? '',
                'week' => $trade->traded_at?->format('o-W') ?? '',
                'month' => $trade->traded_at?->format('Y-m') ?? '',
                default => $trade->traded_at?->format('Y-m-d') ?? '',
            };
            $buckets[$key] = (int) $trade->price_per_ton;
        }

        $labels = [];
        $values = [];
        foreach ($buckets as $key => $price) {
            $labels[] = match ($bucket) {
                'hour' => \Carbon\Carbon::createFromFormat('Y-m-d H:00', $key)?->format($labelFormat) ?? $key,
                'week' => 'W'.substr($key, -2),
                'month' => \Carbon\Carbon::createFromFormat('Y-m', $key)?->format($labelFormat) ?? $key,
                default => \Carbon\Carbon::createFromFormat('Y-m-d', $key)?->format($labelFormat) ?? $key,
            };
            $values[] = $price;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Live order-book depth only (no synthetic padding).
     *
     * @return list<array{price: int, qty: int, live: bool}>
     */
    protected function buildDepth(MarketplaceCommodity $commodity, string $side): array
    {
        return ExchangeOrder::query()
            ->where('commodity_id', $commodity->id)
            ->where('status', 'open')
            ->where('side', $side)
            ->where('quantity_tons', '>', 0)
            ->selectRaw('price_per_ton as price, SUM(quantity_tons) as qty')
            ->groupBy('price_per_ton')
            ->when(
                $side === 'buy',
                fn ($q) => $q->orderByDesc('price_per_ton'),
                fn ($q) => $q->orderBy('price_per_ton')
            )
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'price' => (int) $row->price,
                'qty' => (int) $row->qty,
                'live' => true,
            ])
            ->all();
    }

    /**
     * @return array{high: int, low: int}
     */
    protected function dayStats(MarketplaceCommodity $commodity): array
    {
        $today = ExchangeTrade::query()
            ->where('commodity_id', $commodity->id)
            ->where('traded_at', '>=', now()->startOfDay())
            ->selectRaw('MAX(price_per_ton) as high_price, MIN(price_per_ton) as low_price')
            ->first();

        $spot = (int) $commodity->price_per_ton;

        return [
            'high' => (int) ($today?->high_price ?: $spot),
            'low' => (int) ($today?->low_price ?: $spot),
        ];
    }

    protected function tradedVolume(MarketplaceCommodity $commodity): int
    {
        return (int) ExchangeTrade::query()
            ->where('commodity_id', $commodity->id)
            ->sum('quantity_tons');
    }

    protected function openInterest(MarketplaceCommodity $commodity): int
    {
        return (int) ExchangeOrder::query()
            ->where('commodity_id', $commodity->id)
            ->where('status', 'open')
            ->sum('quantity_tons');
    }
}
