<?php

declare(strict_types=1);

namespace App\Services\Auction;

use App\Exceptions\BusinessLogicException;
use App\Models\AuctionBid;
use App\Models\CommodityAuction;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Facades\DB;

/**
 * Live commodity auctions: wallet-escrow bids, outbid refunds, and settlement.
 */
class CommodityAuctionService
{
    /**
     * @var list<string>
     */
    public const FILTERS = ['All Commodities', 'Maize', 'Rice', 'Cassava', 'Sorghum', 'Soybean'];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getAuctionData(User $user, ?string $commodity = null): array
    {
        $this->ensureSeedAuctions();
        $this->settleExpiredAuctions();

        $filter = $commodity && in_array($commodity, self::FILTERS, true) && $commodity !== 'All Commodities'
            ? $commodity
            : null;

        $live = CommodityAuction::query()
            ->where('status', 'live')
            ->where('ends_at', '>', now())
            ->when($filter, fn ($q) => $q->where('commodity', $filter))
            ->orderBy('ends_at')
            ->get();

        $history = CommodityAuction::query()
            ->where('status', 'ended')
            ->latest('settled_at')
            ->latest('id')
            ->limit(20)
            ->get();

        $myBids = AuctionBid::query()
            ->where('user_id', $user->id)
            ->with('auction')
            ->latest('id')
            ->limit(12)
            ->get();

        return [
            'filters' => collect(self::FILTERS)->map(fn (string $name) => [
                'label' => $name,
                'active' => ($filter === null && $name === 'All Commodities') || $filter === $name,
                'url' => route('auction.system', array_filter([
                    'commodity' => $name === 'All Commodities' ? null : $name,
                ])),
            ])->all(),
            'active_filter' => $filter ?? 'All Commodities',
            'live' => $live->map(fn (CommodityAuction $auction) => $this->presentLive($auction, $user))->all(),
            'history' => $history->map(fn (CommodityAuction $auction) => [
                'commodity' => $auction->name,
                'status' => ($auction->winner_id || $auction->winning_bid_ngn) ? 'Completed' : 'Ended (no sale)',
                'price' => $auction->winning_bid_ngn
                    ? '₦'.number_format($auction->winning_bid_ngn)
                    : '—',
                'winner' => $auction->highest_bidder_name,
            ])->all(),
            'my_bids' => $myBids->map(fn (AuctionBid $bid) => [
                'reference' => $bid->reference,
                'auction' => $bid->auction?->name ?? 'Auction',
                'amount' => '₦'.number_format($bid->amount_ngn),
                'status' => ucfirst($bid->status),
                'when' => $bid->created_at?->format('M j, g:i A') ?? '',
            ])->all(),
            'wallet_balance' => $this->walletService->getBalance($user),
            'actions' => [
                'wallet_url' => route('wallet.index'),
                'bid_url' => route('auction.bids.store'),
            ],
            'notifications_count' => max(2, $live->count() + $myBids->where('status', 'leading')->count()),
        ];
    }

    /**
     * Place a wallet-escrowed bid on a live auction.
     */
    public function placeBid(User $user, CommodityAuction $auction, int $amount): AuctionBid
    {
        return DB::transaction(function () use ($user, $auction, $amount): AuctionBid {
            $this->settleExpiredAuctions();

            /** @var CommodityAuction $locked */
            $locked = CommodityAuction::query()->whereKey($auction->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'live' || $locked->ends_at->isPast()) {
                throw new BusinessLogicException('This auction is no longer accepting bids.');
            }

            $minBid = $locked->nextMinBid();
            if ($amount < $minBid) {
                throw new BusinessLogicException('Bid must be at least ₦'.number_format($minBid).'.');
            }

            if ($locked->highest_bidder_id && (int) $locked->highest_bidder_id === (int) $user->id) {
                throw new BusinessLogicException('You are already the highest bidder.');
            }

            $this->walletService->ensureWallet($user);

            $previous = null;
            if ($locked->highest_bidder_id) {
                $previous = AuctionBid::query()
                    ->where('auction_id', $locked->id)
                    ->where('user_id', $locked->highest_bidder_id)
                    ->where('status', 'leading')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();
            }

            $bid = AuctionBid::query()->create([
                'auction_id' => $locked->id,
                'user_id' => $user->id,
                'reference' => $this->nextBidReference($user),
                'amount_ngn' => $amount,
                'bidder_label' => $user->name ?: 'Bidder',
                'status' => 'leading',
            ]);

            $this->walletService->lockAuctionBid(
                $user,
                $amount,
                $bid,
                $bid->reference.' · '.$locked->name
            );

            if ($previous) {
                $previous->forceFill(['status' => 'outbid'])->save();
                $this->walletService->releaseAuctionBid(
                    User::query()->findOrFail($previous->user_id),
                    (int) $previous->amount_ngn,
                    $previous,
                    $previous->reference.' · outbid refund'
                );
            }

            $locked->forceFill([
                'current_bid_ngn' => $amount,
                'highest_bidder_id' => $user->id,
                'highest_bidder_name' => $user->name ?: 'Bidder',
            ])->save();

            // Soft extension if bid lands in the final 2 minutes.
            if ($locked->ends_at->diffInSeconds(now()) <= 120) {
                $locked->forceFill([
                    'ends_at' => now()->addMinutes(2),
                ])->save();
            }

            return $bid->load('auction');
        });
    }

    /**
     * End expired live auctions and mark winners (funds already held).
     */
    public function settleExpiredAuctions(): int
    {
        $expired = CommodityAuction::query()
            ->where('status', 'live')
            ->where('ends_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expired as $auction) {
            DB::transaction(function () use ($auction, &$count): void {
                /** @var CommodityAuction $locked */
                $locked = CommodityAuction::query()->whereKey($auction->id)->lockForUpdate()->first();
                if (! $locked || $locked->status !== 'live') {
                    return;
                }

                if ($locked->highest_bidder_id) {
                    AuctionBid::query()
                        ->where('auction_id', $locked->id)
                        ->where('user_id', $locked->highest_bidder_id)
                        ->where('status', 'leading')
                        ->update(['status' => 'won']);

                    $locked->forceFill([
                        'status' => 'ended',
                        'winner_id' => $locked->highest_bidder_id,
                        'winning_bid_ngn' => $locked->current_bid_ngn,
                        'settled_at' => now(),
                    ])->save();
                } else {
                    $locked->forceFill([
                        'status' => 'ended',
                        'settled_at' => now(),
                    ])->save();
                }

                $count++;
            });
        }

        return $count;
    }

    protected function ensureSeedAuctions(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (CommodityAuction::query()->exists()) {
            return;
        }

        $liveSeeds = [
            [
                'name' => 'Maize (White)',
                'commodity' => 'Maize',
                'image_path' => 'images/marketplace/maize.jpg',
                'starting_bid_ngn' => 300_000,
                'current_bid_ngn' => 310_000,
                'min_increment_ngn' => 5_000,
                'quantity_tons' => 20,
                'hours' => 2,
                'seed_bidder' => 'GreenLands Ltd',
            ],
            [
                'name' => 'Rice (Parboiled)',
                'commodity' => 'Rice',
                'image_path' => 'images/marketplace/rice.jpg',
                'starting_bid_ngn' => 400_000,
                'current_bid_ngn' => 420_000,
                'min_increment_ngn' => 10_000,
                'quantity_tons' => 15,
                'hours' => 5,
                'seed_bidder' => 'Bright Farms',
            ],
            [
                'name' => 'Cassava (Fresh)',
                'commodity' => 'Cassava',
                'image_path' => 'images/marketplace/cassava.jpg',
                'starting_bid_ngn' => 160_000,
                'current_bid_ngn' => 160_000,
                'min_increment_ngn' => 4_000,
                'quantity_tons' => 30,
                'hours' => 8,
                'seed_bidder' => null,
            ],
            [
                'name' => 'Soybean (Grade A)',
                'commodity' => 'Soybean',
                'image_path' => 'images/marketplace/soybean.jpg',
                'starting_bid_ngn' => 380_000,
                'current_bid_ngn' => 390_000,
                'min_increment_ngn' => 8_000,
                'quantity_tons' => 12,
                'hours' => 12,
                'seed_bidder' => 'AgroTrade Hub',
            ],
        ];

        foreach ($liveSeeds as $seed) {
            CommodityAuction::query()->create([
                'name' => $seed['name'],
                'commodity' => $seed['commodity'],
                'image_path' => $seed['image_path'],
                'quantity_tons' => $seed['quantity_tons'],
                'starting_bid_ngn' => $seed['starting_bid_ngn'],
                'current_bid_ngn' => $seed['current_bid_ngn'],
                'min_increment_ngn' => $seed['min_increment_ngn'],
                'highest_bidder_id' => null,
                'highest_bidder_name' => $seed['seed_bidder'],
                'status' => 'live',
                'starts_at' => now()->subHour(),
                'ends_at' => now()->addHours($seed['hours'])->addMinutes(15),
                'meta' => [
                    'seed_display_bidder' => $seed['seed_bidder'],
                ],
            ]);
        }

        $historySeeds = [
            ['name' => 'Sorghum', 'commodity' => 'Sorghum', 'price' => 235_000, 'image' => 'images/marketplace/maize.jpg'],
            ['name' => 'Soybean', 'commodity' => 'Soybean', 'price' => 400_000, 'image' => 'images/marketplace/soybean.jpg'],
            ['name' => 'Cassava', 'commodity' => 'Cassava', 'price' => 180_000, 'image' => 'images/marketplace/cassava.jpg'],
            ['name' => 'Sesame', 'commodity' => 'Sorghum', 'price' => 550_000, 'image' => 'images/marketplace/maize.jpg'],
        ];

        foreach ($historySeeds as $index => $seed) {
            CommodityAuction::query()->create([
                'name' => $seed['name'],
                'commodity' => $seed['commodity'],
                'image_path' => $seed['image'],
                'quantity_tons' => 10,
                'starting_bid_ngn' => (int) round($seed['price'] * 0.9),
                'current_bid_ngn' => $seed['price'],
                'min_increment_ngn' => 5_000,
                'highest_bidder_name' => 'Market Buyer',
                'status' => 'ended',
                'starts_at' => now()->subDays($index + 2),
                'ends_at' => now()->subDays($index + 1),
                'winning_bid_ngn' => $seed['price'],
                'settled_at' => now()->subDays($index + 1),
                'meta' => ['seed_history' => true],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentLive(CommodityAuction $auction, User $user): array
    {
        $minBid = $auction->nextMinBid();
        $isLeading = $auction->highest_bidder_id && (int) $auction->highest_bidder_id === (int) $user->id;

        return [
            'id' => $auction->id,
            'name' => $auction->name,
            'commodity' => $auction->commodity,
            'image' => $auction->image_path ?: 'images/marketplace/maize.jpg',
            'quantity' => $auction->quantity_tons.' ton'.($auction->quantity_tons === 1 ? '' : 's'),
            'highest_bid' => '₦'.number_format($auction->current_bid_ngn).' /Ton',
            'highest_bid_raw' => $auction->current_bid_ngn,
            'bidder' => $auction->highest_bidder_name ?: 'No bids yet',
            'min_bid' => $minBid,
            'min_bid_label' => '₦'.number_format($minBid),
            'ends_at' => $auction->ends_at?->toIso8601String() ?? now()->toIso8601String(),
            'is_leading' => $isLeading,
            'can_bid' => $auction->isLive() && ! $isLeading,
            'bid_url' => route('auction.bids.store'),
        ];
    }

    protected function nextBidReference(User $user): string
    {
        $count = AuctionBid::query()->where('user_id', $user->id)->count() + 1;

        return 'AB-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
