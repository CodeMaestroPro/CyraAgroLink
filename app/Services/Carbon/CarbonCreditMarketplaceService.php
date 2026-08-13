<?php

declare(strict_types=1);

namespace App\Services\Carbon;

use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\CarbonAccount;
use App\Models\CarbonListing;
use App\Models\CarbonTransaction;
use App\Models\Farm;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Live carbon credit marketplace backed by farm sequestration estimates,
 * ledger transactions, sale listings, and wallet payouts.
 */
class CarbonCreditMarketplaceService
{
    public const USD_TO_NGN = 1550;

    public const DEFAULT_UNIT_PRICE_USD = 14.0;

    /**
     * @var list<string>
     */
    protected array $buyers = ['EcoMarket', 'GreenFuture', 'ClimateBridge', 'AgriOffset NG'];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getMarketplaceData(User $user): array
    {
        $farms = $this->farmsForUser($user);
        if ($farms->isEmpty()) {
            if (! \App\Support\DemoSeeding::allowed()) {
                throw new BusinessLogicException('Register a farm before using Carbon Credits.', 'FARM_REQUIRED', 422);
            }
            $this->ensureSeedFarm($user);
            $farms = $this->farmsForUser($user);
        }

        $account = $this->ensureAccount($user, $farms);
        $transactions = CarbonTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(12)
            ->get();

        $listings = CarbonListing::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('id')
            ->get();

        return [
            'account' => [
                'balance' => (float) $account->balance_tco2e,
                'lifetime_earned' => (float) $account->lifetime_earned_tco2e,
                'score' => (int) $account->sustainability_score,
            ],
            'farms' => $farms->map(fn (Farm $farm) => [
                'id' => $farm->id,
                'name' => $farm->name ?: 'Untitled farm',
                'hectares' => (float) ($farm->size_hectares ?: 0),
            ])->values()->all(),
            'kpis' => $this->kpis($account, $user),
            'trend' => $this->creditsTrend($user),
            'transactions' => $transactions->map(fn (CarbonTransaction $tx) => $this->presentTransaction($tx))->all(),
            'listings' => $listings->map(fn (CarbonListing $listing) => [
                'id' => $listing->id,
                'credits' => $this->formatCredits((float) $listing->credits_tco2e),
                'credits_raw' => (float) $listing->credits_tco2e,
                'price' => '$'.number_format((float) $listing->unit_price_usd, 2).'/tCO2e',
                'value' => $this->formatUsd((float) $listing->credits_tco2e * (float) $listing->unit_price_usd),
                'sell_url' => route('carbon.sell', $listing),
            ])->all(),
            'actions' => [
                'generate_url' => route('carbon.generate'),
                'list_url' => route('carbon.list'),
                'offset_url' => route('carbon.offset'),
                'wallet_url' => route('wallet.index'),
            ],
            'default_list_credits' => $this->suggestedListAmount($account),
            'unit_price_usd' => self::DEFAULT_UNIT_PRICE_USD,
            'notifications_count' => 4,
        ];
    }

    /**
     * Claim / generate sequestration credits from registered farm hectares.
     */
    public function generateCredits(User $user): CarbonTransaction
    {
        $farms = $this->farmsForUser($user);
        if ($farms->isEmpty()) {
            if (! \App\Support\DemoSeeding::allowed()) {
                throw new BusinessLogicException('Register a farm before claiming carbon credits.', 'FARM_REQUIRED', 422);
            }
            $this->ensureSeedFarm($user);
            $farms = $this->farmsForUser($user);
        }

        $already = CarbonTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'earn')
            ->where('title', 'Field sequestration claim')
            ->where('created_at', '>=', now()->startOfMonth())
            ->exists();

        if ($already) {
            throw new BusinessLogicException('Field credits for this month are already claimed.');
        }

        $hectares = (float) $farms->sum(fn (Farm $farm) => (float) ($farm->size_hectares ?: 0));
        $credits = max(20.0, round($hectares * 2.4, 1));
        $farm = $farms->sortByDesc(fn (Farm $item) => (float) ($item->size_hectares ?: 0))->first();

        return DB::transaction(function () use ($user, $farms, $credits, $farm): CarbonTransaction {
            $account = $this->lockAccount($user);
            $account->forceFill([
                'balance_tco2e' => round((float) $account->balance_tco2e + $credits, 2),
                'lifetime_earned_tco2e' => round((float) $account->lifetime_earned_tco2e + $credits, 2),
                'sustainability_score' => $this->scoreForFarms($farms),
            ])->save();

            return CarbonTransaction::query()->create([
                'user_id' => $user->id,
                'farm_id' => $farm?->id,
                'type' => 'earn',
                'title' => 'Field sequestration claim',
                'counterparty' => 'Cyra Registry',
                'credits_tco2e' => $credits,
                'unit_price_usd' => self::DEFAULT_UNIT_PRICE_USD,
                'value_ngn' => $this->usdToNgn($credits * self::DEFAULT_UNIT_PRICE_USD),
                'status' => 'completed',
                'meta' => ['source' => 'monthly_claim', 'hectares' => (float) $farms->sum(fn (Farm $f) => (float) ($f->size_hectares ?: 0))],
            ]);
        });
    }

    /**
     * Reserve credits on an open marketplace listing.
     */
    public function listCreditsForSale(User $user, float $credits, ?float $unitPriceUsd = null): CarbonListing
    {
        $credits = round($credits, 1);
        $unitPriceUsd = round($unitPriceUsd ?? self::DEFAULT_UNIT_PRICE_USD, 2);

        if ($credits < 1) {
            throw new BusinessLogicException('List at least 1 tCO2e for sale.');
        }

        if ($unitPriceUsd < 1) {
            throw new BusinessLogicException('Unit price must be at least $1.');
        }

        $farms = $this->farmsForUser($user);
        $farm = $farms->sortByDesc(fn (Farm $item) => (float) ($item->size_hectares ?: 0))->first();

        return DB::transaction(function () use ($user, $credits, $unitPriceUsd, $farm): CarbonListing {
            $account = $this->lockAccount($user);

            if ((float) $account->balance_tco2e < $credits) {
                throw new BusinessLogicException('Insufficient carbon credit balance to list for sale.');
            }

            $account->forceFill([
                'balance_tco2e' => round((float) $account->balance_tco2e - $credits, 2),
            ])->save();

            $listing = CarbonListing::query()->create([
                'user_id' => $user->id,
                'farm_id' => $farm?->id,
                'credits_tco2e' => $credits,
                'unit_price_usd' => $unitPriceUsd,
                'status' => 'open',
                'listed_at' => now(),
            ]);

            CarbonTransaction::query()->create([
                'user_id' => $user->id,
                'farm_id' => $farm?->id,
                'listing_id' => $listing->id,
                'type' => 'list',
                'title' => 'Listed credits for sale',
                'counterparty' => 'Marketplace',
                'credits_tco2e' => $credits,
                'unit_price_usd' => $unitPriceUsd,
                'value_ngn' => $this->usdToNgn($credits * $unitPriceUsd),
                'status' => 'listed',
                'meta' => ['listing_id' => $listing->id],
            ]);

            return $listing;
        });
    }

    /**
     * Complete an open listing sale and credit the farmer wallet.
     */
    public function sellListing(User $user, CarbonListing $listing): CarbonTransaction
    {
        if ($listing->user_id !== $user->id) {
            throw new BusinessLogicException('You are not authorized to sell this listing.', 'CARBON_FORBIDDEN', 403);
        }

        if (! $listing->isOpen()) {
            throw new BusinessLogicException('This listing is no longer open.');
        }

        $buyer = $this->buyers[array_rand($this->buyers)];
        $credits = (float) $listing->credits_tco2e;
        $unitPrice = (float) $listing->unit_price_usd;
        $valueUsd = $credits * $unitPrice;
        $valueNgn = $this->usdToNgn($valueUsd);

        return DB::transaction(function () use ($user, $listing, $buyer, $credits, $unitPrice, $valueNgn): CarbonTransaction {
            /** @var CarbonListing $locked */
            $locked = CarbonListing::query()->whereKey($listing->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw new BusinessLogicException('This listing is no longer open.');
            }

            $locked->forceFill([
                'status' => 'sold',
                'sold_at' => now(),
                'buyer_name' => $buyer,
            ])->save();

            $tx = CarbonTransaction::query()->create([
                'user_id' => $user->id,
                'farm_id' => $locked->farm_id,
                'listing_id' => $locked->id,
                'type' => 'sale',
                'title' => 'Sale to '.$buyer,
                'counterparty' => $buyer,
                'credits_tco2e' => $credits,
                'unit_price_usd' => $unitPrice,
                'value_ngn' => $valueNgn,
                'status' => 'completed',
                'meta' => ['buyer' => $buyer],
            ]);

            $this->walletService->creditCarbonSale(
                $user,
                $valueNgn,
                $tx,
                'Carbon credit sale to '.$buyer.' ('.$this->formatCredits($credits).')'
            );

            $account = $this->lockAccount($user);
            $account->forceFill([
                'sustainability_score' => min(100, (int) $account->sustainability_score + 1),
            ])->save();

            return $tx;
        });
    }

    /**
     * Retire / offset credits from the available balance.
     */
    public function offsetCredits(User $user, float $credits): CarbonTransaction
    {
        $credits = round($credits, 1);

        if ($credits < 1) {
            throw new BusinessLogicException('Offset at least 1 tCO2e.');
        }

        return DB::transaction(function () use ($user, $credits): CarbonTransaction {
            $account = $this->lockAccount($user);

            if ((float) $account->balance_tco2e < $credits) {
                throw new BusinessLogicException('Insufficient carbon credit balance to offset.');
            }

            $account->forceFill([
                'balance_tco2e' => round((float) $account->balance_tco2e - $credits, 2),
                'sustainability_score' => min(100, (int) $account->sustainability_score + 2),
            ])->save();

            return CarbonTransaction::query()->create([
                'user_id' => $user->id,
                'type' => 'offset',
                'title' => 'Purchase/Offset',
                'counterparty' => 'Self offset',
                'credits_tco2e' => $credits,
                'unit_price_usd' => self::DEFAULT_UNIT_PRICE_USD,
                'value_ngn' => $this->usdToNgn($credits * self::DEFAULT_UNIT_PRICE_USD),
                'status' => 'completed',
            ]);
        });
    }

    /**
     * @param  Collection<int, Farm>  $farms
     */
    protected function ensureAccount(User $user, Collection $farms): CarbonAccount
    {
        $account = CarbonAccount::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance_tco2e' => 0,
                'lifetime_earned_tco2e' => 0,
                'sustainability_score' => $this->scoreForFarms($farms),
            ]
        );

        $hasLedger = CarbonTransaction::query()->where('user_id', $user->id)->exists();

        if (! $hasLedger) {
            $this->seedStarterLedger($user, $account, $farms);
            $account->refresh();
        }

        return $account;
    }

    /**
     * @param  Collection<int, Farm>  $farms
     */
    protected function seedStarterLedger(User $user, CarbonAccount $account, Collection $farms): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        $hectares = (float) $farms->sum(fn (Farm $farm) => (float) ($farm->size_hectares ?: 0));
        $total = max(80.0, round($hectares * 28, 1));
        $earnedMonth = max(20.0, round($total * 0.22, 1));
        $saleA = max(10.0, round($total * 0.12, 1));
        $saleB = max(8.0, round($total * 0.08, 1));
        $offset = max(5.0, round($total * 0.05, 1));
        $balance = round($total - $saleA - $saleB - $offset, 1);
        $farm = $farms->first();

        DB::transaction(function () use ($user, $account, $farms, $farm, $total, $earnedMonth, $saleA, $saleB, $offset, $balance): void {
            $account->forceFill([
                'balance_tco2e' => $balance,
                'lifetime_earned_tco2e' => $total,
                'sustainability_score' => $this->scoreForFarms($farms),
            ])->save();

            $rows = [
                [
                    'type' => 'earn',
                    'title' => 'Verified sequestration batch',
                    'counterparty' => 'Cyra Registry',
                    'credits' => $total,
                    'at' => now()->subMonths(2)->subDays(5),
                ],
                [
                    'type' => 'earn',
                    'title' => 'Monthly field accrual',
                    'counterparty' => 'Cyra Registry',
                    'credits' => $earnedMonth,
                    'at' => now()->subMonth()->startOfMonth()->addDays(3),
                ],
                [
                    'type' => 'sale',
                    'title' => 'Sale to EcoMarket',
                    'counterparty' => 'EcoMarket',
                    'credits' => $saleA,
                    'at' => now()->subDays(20),
                ],
                [
                    'type' => 'sale',
                    'title' => 'Sale to GreenFuture',
                    'counterparty' => 'GreenFuture',
                    'credits' => $saleB,
                    'at' => now()->subDays(9),
                ],
                [
                    'type' => 'offset',
                    'title' => 'Purchase/Offset',
                    'counterparty' => 'Self offset',
                    'credits' => $offset,
                    'at' => now()->subDays(4),
                ],
            ];

            foreach ($rows as $row) {
                $tx = new CarbonTransaction([
                    'user_id' => $user->id,
                    'farm_id' => $farm?->id,
                    'type' => $row['type'],
                    'title' => $row['title'],
                    'counterparty' => $row['counterparty'],
                    'credits_tco2e' => $row['credits'],
                    'unit_price_usd' => self::DEFAULT_UNIT_PRICE_USD,
                    'value_ngn' => $this->usdToNgn($row['credits'] * self::DEFAULT_UNIT_PRICE_USD),
                    'status' => 'completed',
                    'meta' => ['seeded' => true],
                ]);
                $tx->created_at = $row['at'];
                $tx->updated_at = $row['at'];
                $tx->save();
            }
        });
    }

    /**
     * @return list<array{label: string, value: string, meta: string|null, tone: string}>
     */
    protected function kpis(CarbonAccount $account, User $user): array
    {
        $monthEarned = (float) CarbonTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'earn')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('credits_tco2e');

        $potentialUsd = (float) $account->balance_tco2e * self::DEFAULT_UNIT_PRICE_USD;

        return [
            [
                'label' => 'Total Credits',
                'value' => $this->formatCredits((float) $account->balance_tco2e),
                'meta' => null,
                'tone' => 'green',
            ],
            [
                'label' => 'Credits Earned',
                'value' => '+'.$this->formatCredits($monthEarned, false),
                'meta' => '(This Month)',
                'tone' => 'green',
            ],
            [
                'label' => 'Potential Value',
                'value' => $this->formatUsd($potentialUsd),
                'meta' => null,
                'tone' => 'ink',
            ],
            [
                'label' => 'Sustainability Score',
                'value' => ((int) $account->sustainability_score).'/100',
                'meta' => null,
                'tone' => 'green',
            ],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<float>}
     */
    protected function creditsTrend(User $user): array
    {
        $labels = [];
        $values = [];
        $running = 0.0;

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M');

            $earned = (float) CarbonTransaction::query()
                ->where('user_id', $user->id)
                ->where('type', 'earn')
                ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum('credits_tco2e');

            $running = round($running + $earned, 1);
            $values[] = $running > 0 ? $running : round(max(10, $earned), 1);
        }

        if (array_sum($values) <= 0) {
            return [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'values' => [40, 70, 110, 160, 210, 260],
            ];
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * @return array{title: string, credits: string, value: string, tone: string}
     */
    protected function presentTransaction(CarbonTransaction $tx): array
    {
        $credits = (float) $tx->credits_tco2e;
        $tone = $tx->type === 'offset' ? 'debit' : 'credit';
        $sign = $tone === 'debit' ? '-' : '+';

        $value = $tx->value_ngn > 0
            ? '₦'.number_format($tx->value_ngn)
            : $this->formatUsd($credits * (float) ($tx->unit_price_usd ?: self::DEFAULT_UNIT_PRICE_USD));

        return [
            'title' => $tx->title,
            'credits' => $sign.$this->formatCredits($credits, false),
            'value' => $value,
            'tone' => $tone,
        ];
    }

    /**
     * @return Collection<int, Farm>
     */
    protected function farmsForUser(User $user): Collection
    {
        return Farm::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [FarmStatus::Active, FarmStatus::PendingReview, FarmStatus::Draft])
            ->orderByDesc('registered_at')
            ->orderByDesc('id')
            ->get();
    }

    protected function ensureSeedFarm(User $user): Farm
    {
        return Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Green Valley Farm',
            'state' => 'Oyo',
            'local_government' => 'Ibadan North',
            'address' => 'Carbon demo field',
            'latitude' => '7.3775000',
            'longitude' => '3.9470000',
            'size_hectares' => '4.50',
            'soil_type' => 'Loamy',
            'crops' => ['Maize', 'Cassava'],
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);
    }

    /**
     * @param  Collection<int, Farm>  $farms
     */
    protected function scoreForFarms(Collection $farms): int
    {
        $hectares = (float) $farms->sum(fn (Farm $farm) => (float) ($farm->size_hectares ?: 0));
        $base = 72;
        $sizeBonus = min(18, (int) floor($hectares * 2));
        $soilBonus = $farms->contains(fn (Farm $farm) => in_array(strtolower((string) $farm->soil_type), ['loamy', 'loam', 'clay loam'], true))
            ? 6
            : 2;

        return min(100, $base + $sizeBonus + $soilBonus);
    }

    protected function lockAccount(User $user): CarbonAccount
    {
        $account = CarbonAccount::query()->where('user_id', $user->id)->lockForUpdate()->first();

        if (! $account) {
            $account = CarbonAccount::query()->create([
                'user_id' => $user->id,
                'balance_tco2e' => 0,
                'lifetime_earned_tco2e' => 0,
                'sustainability_score' => 70,
            ]);
            $account = CarbonAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();
        }

        return $account;
    }

    protected function suggestedListAmount(CarbonAccount $account): float
    {
        $balance = (float) $account->balance_tco2e;

        if ($balance < 1) {
            return 1.0;
        }

        return min(50.0, max(1.0, round($balance * 0.2, 1)));
    }

    protected function usdToNgn(float $usd): int
    {
        return (int) max(1, round($usd * self::USD_TO_NGN));
    }

    protected function formatCredits(float $credits, bool $withUnit = true): string
    {
        $formatted = number_format($credits, $credits == floor($credits) ? 0 : 1);

        return $withUnit ? $formatted.' tCO2e' : $formatted.' tCO2e';
    }

    protected function formatUsd(float $amount): string
    {
        return '$'.number_format($amount, $amount >= 100 ? 0 : 2);
    }
}
