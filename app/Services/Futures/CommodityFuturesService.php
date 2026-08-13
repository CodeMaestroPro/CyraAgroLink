<?php

declare(strict_types=1);

namespace App\Services\Futures;

use App\Exceptions\BusinessLogicException;
use App\Models\FuturesContract;
use App\Models\FuturesOrder;
use App\Models\FuturesPosition;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Services\Marketplace\MarketplaceService;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Live commodity futures board: contracts, margin orders, matching, and positions.
 */
class CommodityFuturesService
{
    public const MARGIN_RATE = 0.10;

    /**
     * @var list<string>
     */
    public const RANGES = ['1D', '1W', '1M', '3M', '6M', '1Y'];

    public function __construct(
        protected DigitalWalletService $walletService,
        protected MarketplaceService $marketplaceService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getBoardData(User $user, ?int $contractId = null): array
    {
        $this->ensureContracts();

        $contracts = FuturesContract::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $contract = $this->resolveContract($contracts, $contractId);
        $change = $contract->changePercent();

        $orders = FuturesOrder::query()
            ->where('user_id', $user->id)
            ->where('contract_id', $contract->id)
            ->latest('id')
            ->limit(12)
            ->get();

        $positions = FuturesPosition::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->with('contract')
            ->latest('id')
            ->limit(12)
            ->get();

        $buyOrders = $this->buildDepth($contract, 'buy');
        $sellOrders = $this->buildDepth($contract, 'sell');

        return [
            'contract' => [
                'id' => $contract->id,
                'name' => $contract->name,
                'symbol' => $contract->symbol,
                'price' => '₦'.number_format($contract->last_price).' /Ton',
                'price_raw' => $contract->last_price,
                'change' => ($change >= 0 ? '+' : '').number_format($change, 2).'%',
                'change_tone' => $change >= 0 ? 'text-emerald-600' : 'text-rose-600',
                'expiry' => $contract->expiry_label,
            ],
            'contracts' => $contracts->map(fn (FuturesContract $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'symbol' => $item->symbol,
                'price' => '₦'.number_format($item->last_price),
                'active' => $item->id === $contract->id,
                'url' => route('futures.exchange', ['contract' => $item->id]),
            ])->all(),
            'stats' => [
                ['label' => 'Open Interest', 'value' => number_format($this->openInterest($contract))],
                ['label' => 'Volume', 'value' => number_format(max($contract->volume, $this->filledVolume($contract)))],
                ['label' => 'High', 'value' => number_format((int) ($contract->day_high ?? $contract->last_price))],
                ['label' => 'Low', 'value' => number_format((int) ($contract->day_low ?? $contract->last_price))],
            ],
            'ranges' => self::RANGES,
            'candles' => $this->candlesByRange($contract),
            'buy_orders' => $buyOrders,
            'sell_orders' => $sellOrders,
            'user_orders' => $orders->map(fn (FuturesOrder $order) => [
                'id' => $order->id,
                'reference' => $order->reference,
                'side' => strtoupper($order->side),
                'quantity' => $order->quantity,
                'filled' => $order->filled_quantity,
                'price' => '₦'.number_format($order->price),
                'margin' => '₦'.number_format($order->margin_ngn),
                'status' => ucfirst($order->status),
                'can_cancel' => in_array($order->status, ['open', 'partial'], true),
                'cancel_url' => route('futures.orders.cancel', $order),
            ])->all(),
            'positions' => $positions->map(function (FuturesPosition $position) {
                $mark = (int) ($position->contract?->last_price ?? $position->entry_price);
                $pnl = $position->unrealizedPnl($mark);

                return [
                    'id' => $position->id,
                    'reference' => $position->reference,
                    'contract' => $position->contract?->name ?? 'Contract',
                    'side' => strtoupper($position->side),
                    'quantity' => $position->quantity,
                    'entry' => '₦'.number_format($position->entry_price),
                    'mark' => '₦'.number_format($mark),
                    'pnl' => ($pnl >= 0 ? '+' : '').'₦'.number_format(abs($pnl)),
                    'pnl_tone' => $pnl >= 0 ? 'text-cyra-forest' : 'text-rose-600',
                    'margin' => '₦'.number_format($position->margin_ngn),
                    'close_url' => route('futures.positions.close', $position),
                ];
            })->all(),
            'wallet_balance' => $this->walletService->getBalance($user),
            'actions' => [
                'order_url' => route('futures.orders.store'),
                'wallet_url' => route('wallet.index'),
                'board_url' => route('futures.exchange', ['contract' => $contract->id]),
            ],
            'default_qty' => 1,
            'notifications_count' => max(2, $orders->whereIn('status', ['open', 'partial'])->count() + $positions->count()),
        ];
    }

    /**
     * Place a buy/sell futures order (locks margin, then matches).
     *
     * @param  array{side: string, quantity: int, price: int}  $data
     */
    public function placeOrder(User $user, FuturesContract $contract, array $data): FuturesOrder
    {
        if (! $contract->is_active) {
            throw new BusinessLogicException('This futures contract is not tradable.');
        }

        $side = strtolower($data['side']);
        if (! in_array($side, ['buy', 'sell'], true)) {
            throw new BusinessLogicException('Choose Buy or Sell.');
        }

        $quantity = max(1, (int) $data['quantity']);
        $price = max(1000, (int) $data['price']);
        $margin = $this->marginFor($quantity, $price);

        return DB::transaction(function () use ($user, $contract, $side, $quantity, $price, $margin): FuturesOrder {
            /** @var FuturesContract $locked */
            $locked = FuturesContract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();

            $this->walletService->ensureWallet($user);

            $order = FuturesOrder::query()->create([
                'user_id' => $user->id,
                'contract_id' => $locked->id,
                'reference' => $this->nextOrderReference($user),
                'side' => $side,
                'quantity' => $quantity,
                'filled_quantity' => 0,
                'price' => $price,
                'margin_ngn' => $margin,
                'status' => 'open',
            ]);

            $this->walletService->lockFuturesMargin(
                $user,
                $margin,
                $order,
                $order->reference.' · '.$locked->symbol.' margin'
            );

            $this->matchOrder($order, $locked);

            return $order->refresh()->load('contract');
        });
    }

    public function cancelOrder(User $user, FuturesOrder $order): FuturesOrder
    {
        $this->assertOwnedOrder($user, $order);

        if (! in_array($order->status, ['open', 'partial'], true)) {
            throw new BusinessLogicException('Only open orders can be cancelled.');
        }

        return DB::transaction(function () use ($user, $order): FuturesOrder {
            /** @var FuturesOrder $locked */
            $locked = FuturesOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, ['open', 'partial'], true)) {
                throw new BusinessLogicException('Only open orders can be cancelled.');
            }

            $remaining = $locked->remainingQuantity();
            $refund = $remaining > 0
                ? (int) round($locked->margin_ngn * ($remaining / max(1, $locked->quantity)))
                : 0;

            $locked->forceFill(['status' => 'cancelled'])->save();

            if ($refund > 0) {
                $this->walletService->creditFuturesSettlement(
                    $user,
                    $refund,
                    $locked,
                    $locked->reference.' · margin refund'
                );
            }

            return $locked->refresh();
        });
    }

    public function closePosition(User $user, FuturesPosition $position): FuturesPosition
    {
        $this->assertOwnedPosition($user, $position);

        if ($position->status !== 'open') {
            throw new BusinessLogicException('Only open positions can be closed.');
        }

        return DB::transaction(function () use ($user, $position): FuturesPosition {
            /** @var FuturesPosition $locked */
            $locked = FuturesPosition::query()->whereKey($position->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'open') {
                throw new BusinessLogicException('Only open positions can be closed.');
            }

            /** @var FuturesContract $contract */
            $contract = FuturesContract::query()->whereKey($locked->contract_id)->lockForUpdate()->firstOrFail();
            $mark = (int) $contract->last_price;
            $pnl = $locked->unrealizedPnl($mark);
            $settlement = $locked->margin_ngn + $pnl;

            if ($settlement > 0) {
                $this->walletService->creditFuturesSettlement(
                    $user,
                    $settlement,
                    $locked,
                    $locked->reference.' · close @ ₦'.number_format($mark)
                );
            } elseif ($settlement < 0) {
                $this->walletService->debitFuturesSettlement(
                    $user,
                    abs($settlement),
                    $locked,
                    $locked->reference.' · close loss @ ₦'.number_format($mark)
                );
            }

            $locked->forceFill([
                'status' => 'closed',
                'realized_pnl_ngn' => $pnl,
                'closed_at' => now(),
                'meta' => array_merge($locked->meta ?? [], [
                    'exit_price' => $mark,
                ]),
            ])->save();

            $contract->forceFill([
                'open_interest' => max(0, $contract->open_interest - $locked->quantity),
            ])->save();

            return $locked->refresh()->load('contract');
        });
    }

    protected function ensureContracts(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            if (FuturesContract::query()->exists()) {
                $this->syncContractPrices();
            }

            return;
        }

        $this->marketplaceService->getCatalog();

        if (FuturesContract::query()->exists()) {
            $this->syncContractPrices();

            return;
        }

        $names = ['Maize', 'Rice', 'Cocoa', 'Yam', 'Cassava'];
        $expiry = now()->addMonths(2)->startOfMonth()->addDays(14);

        foreach ($names as $index => $name) {
            $commodity = MarketplaceCommodity::query()
                ->where('status', 'active')
                ->where('name', $name)
                ->first();

            $spot = (int) ($commodity?->price_per_ton ?? (300000 + ($index * 25000)));
            $futuresPrice = (int) round($spot * 1.02);

            FuturesContract::query()->create([
                'name' => $name.' Futures ('.$expiry->format('M Y').')',
                'symbol' => strtoupper(substr($name, 0, 3)).'-'.$expiry->format('my'),
                'commodity_id' => $commodity?->id,
                'expiry_label' => $expiry->format('M Y'),
                'expires_on' => $expiry->toDateString(),
                'contract_size_tons' => 1,
                'last_price' => $futuresPrice,
                'day_high' => (int) round($futuresPrice * 1.01),
                'day_low' => (int) round($futuresPrice * 0.985),
                'volume' => 5000 + ($index * 800),
                'open_interest' => 8000 + ($index * 1000),
                'is_active' => true,
                'meta' => [
                    'previous_price' => (int) round($futuresPrice * 0.977),
                ],
            ]);
        }
    }

    protected function syncContractPrices(): void
    {
        $contracts = FuturesContract::query()
            ->where('is_active', true)
            ->whereNotNull('commodity_id')
            ->with('commodity')
            ->get();

        foreach ($contracts as $contract) {
            $spot = (int) ($contract->commodity?->price_per_ton ?? 0);
            if ($spot < 1) {
                continue;
            }

            // Keep futures near spot unless trades already moved the board today.
            $hasTrades = FuturesOrder::query()
                ->where('contract_id', $contract->id)
                ->where('status', 'filled')
                ->where('updated_at', '>=', now()->subDay())
                ->exists();

            if ($hasTrades) {
                continue;
            }

            $target = (int) round($spot * 1.02);
            if (abs($target - $contract->last_price) < 500) {
                continue;
            }

            $contract->forceFill([
                'meta' => array_merge($contract->meta ?? [], [
                    'previous_price' => $contract->last_price,
                ]),
                'last_price' => $target,
                'day_high' => max((int) ($contract->day_high ?? $target), $target),
                'day_low' => min((int) ($contract->day_low ?? $target), $target),
            ])->save();
        }
    }

    /**
     * @param  Collection<int, FuturesContract>  $contracts
     */
    protected function resolveContract(Collection $contracts, ?int $contractId): FuturesContract
    {
        if ($contractId !== null) {
            $match = $contracts->firstWhere('id', $contractId);
            if ($match) {
                return $match;
            }
        }

        return $contracts->first(fn (FuturesContract $c) => str_contains($c->name, 'Maize'))
            ?? $contracts->first()
            ?? throw new BusinessLogicException('No futures contracts available.');
    }

    protected function matchOrder(FuturesOrder $incoming, FuturesContract $contract): void
    {
        $remaining = $incoming->remainingQuantity();
        if ($remaining < 1) {
            return;
        }

        $opposingSide = $incoming->side === 'buy' ? 'sell' : 'buy';

        $opposing = FuturesOrder::query()
            ->where('contract_id', $contract->id)
            ->where('status', 'open')
            ->where('side', $opposingSide)
            ->where('id', '!=', $incoming->id)
            ->when(
                $incoming->side === 'buy',
                fn ($q) => $q->where('price', '<=', $incoming->price)->orderBy('price')->orderBy('id'),
                fn ($q) => $q->where('price', '>=', $incoming->price)->orderByDesc('price')->orderBy('id')
            )
            ->lockForUpdate()
            ->get();

        // Also match against synthetic market at last price when crossing the book.
        $crossedMarket = ($incoming->side === 'buy' && $incoming->price >= $contract->last_price)
            || ($incoming->side === 'sell' && $incoming->price <= $contract->last_price);

        foreach ($opposing as $other) {
            if ($remaining < 1) {
                break;
            }

            $fillQty = min($remaining, $other->remainingQuantity());
            if ($fillQty < 1) {
                continue;
            }

            $tradePrice = (int) $other->price;
            $this->fillPair($incoming, $other, $fillQty, $tradePrice, $contract);
            $remaining = $incoming->fresh()->remainingQuantity();
        }

        $incoming->refresh();
        $remaining = $incoming->remainingQuantity();

        if ($remaining > 0 && $crossedMarket) {
            $this->fillAgainstMarket($incoming, $remaining, (int) $contract->last_price, $contract);
        }
    }

    protected function fillPair(
        FuturesOrder $incoming,
        FuturesOrder $other,
        int $qty,
        int $tradePrice,
        FuturesContract $contract
    ): void {
        foreach ([$incoming, $other] as $order) {
            $filled = $order->filled_quantity + $qty;
            $status = $filled >= $order->quantity ? 'filled' : 'partial';
            $order->forceFill([
                'filled_quantity' => $filled,
                'status' => $status,
            ])->save();

            $this->openPositionFromFill($order, $qty, $tradePrice);
        }

        $this->bumpContract($contract, $tradePrice, $qty);
    }

    protected function fillAgainstMarket(
        FuturesOrder $order,
        int $qty,
        int $tradePrice,
        FuturesContract $contract
    ): void {
        $filled = $order->filled_quantity + $qty;
        $order->forceFill([
            'filled_quantity' => $filled,
            'status' => $filled >= $order->quantity ? 'filled' : 'partial',
        ])->save();

        $this->openPositionFromFill($order, $qty, $tradePrice);
        $this->bumpContract($contract, $tradePrice, $qty);
    }

    protected function openPositionFromFill(FuturesOrder $order, int $qty, int $tradePrice): void
    {
        $marginShare = (int) round($order->margin_ngn * ($qty / max(1, $order->quantity)));

        FuturesPosition::query()->create([
            'user_id' => $order->user_id,
            'contract_id' => $order->contract_id,
            'reference' => $this->nextPositionReference(
                User::query()->findOrFail($order->user_id)
            ),
            'side' => $order->side === 'buy' ? 'long' : 'short',
            'quantity' => $qty,
            'entry_price' => $tradePrice,
            'margin_ngn' => max(1, $marginShare),
            'status' => 'open',
            'opened_at' => now(),
            'meta' => [
                'order_id' => $order->id,
                'order_reference' => $order->reference,
            ],
        ]);
    }

    protected function bumpContract(FuturesContract $contract, int $tradePrice, int $qty): void
    {
        $contract->forceFill([
            'meta' => array_merge($contract->meta ?? [], [
                'previous_price' => $contract->last_price,
            ]),
            'last_price' => $tradePrice,
            'day_high' => max((int) ($contract->day_high ?? $tradePrice), $tradePrice),
            'day_low' => min((int) ($contract->day_low ?? $tradePrice), $tradePrice),
            'volume' => $contract->volume + $qty,
            'open_interest' => $contract->open_interest + $qty,
        ])->save();
    }

    /**
     * @return list<array{price: int, qty: int, depth: int}>
     */
    protected function buildDepth(FuturesContract $contract, string $side): array
    {
        $live = FuturesOrder::query()
            ->where('contract_id', $contract->id)
            ->whereIn('status', ['open', 'partial'])
            ->where('side', $side)
            ->selectRaw('price, SUM(quantity - filled_quantity) as qty')
            ->groupBy('price')
            ->when(
                $side === 'buy',
                fn ($q) => $q->orderByDesc('price'),
                fn ($q) => $q->orderBy('price')
            )
            ->limit(8)
            ->get()
            ->filter(fn ($row) => (int) $row->qty > 0)
            ->map(fn ($row) => [
                'price' => (int) $row->price,
                'qty' => (int) $row->qty,
            ])
            ->values()
            ->all();

        $base = $contract->last_price;
        $rows = $live;

        for ($i = 1; count($rows) < 8; $i++) {
            $offset = (int) round($base * (0.0015 + ($i * 0.0025)));
            $price = $side === 'buy' ? $base - $offset : $base + $offset;
            $price = max(1000, $price);
            if (collect($rows)->contains(fn ($r) => $r['price'] === $price)) {
                continue;
            }
            $rows[] = [
                'price' => $price,
                'qty' => 80 + ($i * 15) + (($i % 3) * 20),
            ];
        }

        $rows = array_slice($rows, 0, 8);
        $maxQty = max(1, collect($rows)->max('qty'));

        return collect($rows)->map(fn (array $row) => [
            'price' => $row['price'],
            'qty' => $row['qty'],
            'depth' => (int) max(12, round(($row['qty'] / $maxQty) * 100)),
        ])->all();
    }

    /**
     * @return array<string, array{labels: list<string>, ohlc: list<array{o: int, h: int, l: int, c: int}>}>
     */
    protected function candlesByRange(FuturesContract $contract): array
    {
        $out = [];
        foreach (self::RANGES as $range) {
            $out[$range] = $this->buildCandles($contract, $range);
        }

        return $out;
    }

    /**
     * @return array{labels: list<string>, ohlc: list<array{o: int, h: int, l: int, c: int}>}
     */
    protected function buildCandles(FuturesContract $contract, string $range): array
    {
        $points = match ($range) {
            '1W' => 5,
            '1M' => 4,
            '3M' => 3,
            '6M' => 6,
            '1Y' => 4,
            default => 15,
        };

        $base = $contract->last_price;
        $price = (int) round($base * 0.985);
        $labels = [];
        $ohlc = [];

        for ($i = 0; $i < $points; $i++) {
            $open = $price;
            $wave = (int) round(sin(($i + $contract->id) / 2.2) * ($base * 0.012));
            $close = (int) max(1000, $open + $wave + (($i % 4) - 1) * (int) round($base * 0.003));
            $high = max($open, $close) + (int) round($base * 0.004);
            $low = min($open, $close) - (int) round($base * 0.004);
            $ohlc[] = ['o' => $open, 'h' => $high, 'l' => max(1000, $low), 'c' => $close];
            $price = $close;
            $labels[] = match ($range) {
                '1W' => now()->subDays($points - $i - 1)->format('D'),
                '1M' => 'W'.($i + 1),
                '3M', '6M' => now()->subMonths($points - $i - 1)->format('M'),
                '1Y' => 'Q'.($i + 1),
                default => sprintf('%02d:%02d', 9 + intdiv($i, 2), ($i % 2) * 30),
            };
        }

        $ohlc[$points - 1]['c'] = $base;
        $ohlc[$points - 1]['h'] = max($ohlc[$points - 1]['h'], $base);
        $ohlc[$points - 1]['l'] = min($ohlc[$points - 1]['l'], $base);

        return ['labels' => $labels, 'ohlc' => $ohlc];
    }

    protected function marginFor(int $quantity, int $price): int
    {
        return (int) max(1, round($quantity * $price * self::MARGIN_RATE));
    }

    protected function filledVolume(FuturesContract $contract): int
    {
        return (int) FuturesOrder::query()
            ->where('contract_id', $contract->id)
            ->whereIn('status', ['filled', 'partial'])
            ->sum('filled_quantity');
    }

    protected function openInterest(FuturesContract $contract): int
    {
        $live = (int) FuturesPosition::query()
            ->where('contract_id', $contract->id)
            ->where('status', 'open')
            ->sum('quantity');

        return max($live, $contract->open_interest);
    }

    protected function nextOrderReference(User $user): string
    {
        $count = FuturesOrder::query()->where('user_id', $user->id)->count() + 1;

        return 'FO-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function nextPositionReference(User $user): string
    {
        $count = FuturesPosition::query()->where('user_id', $user->id)->count() + 1;

        return 'FP-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    protected function assertOwnedOrder(User $user, FuturesOrder $order): void
    {
        if ((int) $order->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own futures orders.', 'ORDER_FORBIDDEN', 403);
        }
    }

    protected function assertOwnedPosition(User $user, FuturesPosition $position): void
    {
        if ((int) $position->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own futures positions.', 'POSITION_FORBIDDEN', 403);
        }
    }
}
