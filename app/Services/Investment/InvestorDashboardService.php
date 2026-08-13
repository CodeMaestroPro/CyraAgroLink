<?php

declare(strict_types=1);

namespace App\Services\Investment;

use App\Exceptions\BusinessLogicException;
use App\Models\InvestmentOpportunity;
use App\Models\InvestmentPayout;
use App\Models\User;
use App\Models\UserInboxNotification;
use App\Models\UserInvestment;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Live investor portfolio metrics, holdings, and earnings collection.
 */
class InvestorDashboardService
{
    public function __construct(
        protected InvestmentMarketplaceService $marketplaceService,
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, ?string $search = null): array
    {
        $this->marketplaceService->ensureCatalog();
        $this->syncPortfolioAccruals($user);

        $search = trim((string) $search);

        $investments = UserInvestment::query()
            ->where('user_id', $user->id)
            ->with('opportunity')
            ->latest('id')
            ->get();

        $active = $investments->where('status', 'active');

        $filteredHoldings = $active;
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $filteredHoldings = $active->filter(function (UserInvestment $investment) use ($needle): bool {
                $opportunity = $investment->opportunity;
                $haystacks = [
                    (string) ($opportunity?->title ?? ''),
                    (string) ($opportunity?->location ?? ''),
                    (string) ($opportunity?->summary ?? ''),
                    (string) $investment->status,
                ];

                foreach ($haystacks as $haystack) {
                    if ($haystack !== '' && str_contains(mb_strtolower($haystack), $needle)) {
                        return true;
                    }
                }

                return false;
            });
        }
        $payouts = InvestmentPayout::query()
            ->where('user_id', $user->id)
            ->latest('paid_at')
            ->latest('id')
            ->limit(8)
            ->get();

        $totalValue = (int) $active->sum(fn (UserInvestment $i) => $i->currentValue());
        $totalPrincipal = (int) $active->sum('amount');
        $pendingEarnings = (int) $active->sum('accrued_earnings');
        $lifetimePayouts = (int) InvestmentPayout::query()->where('user_id', $user->id)->sum('amount');
        $totalEarnings = $pendingEarnings + $lifetimePayouts;

        $roi = $totalPrincipal > 0
            ? round(($totalEarnings / $totalPrincipal) * 100, 1)
            : 0.0;

        $performance = $this->buildPerformanceSeries($user, $active, $totalValue);

        $valueChange = $this->percentChange(
            (int) ($performance['values'][count($performance['values']) - 2] ?? 0),
            $totalValue
        );

        $recentPayoutTotal = (int) InvestmentPayout::query()
            ->where('user_id', $user->id)
            ->where('paid_at', '>=', now()->subDays(30))
            ->sum('amount');
        $priorPayoutTotal = (int) InvestmentPayout::query()
            ->where('user_id', $user->id)
            ->whereBetween('paid_at', [now()->subDays(60), now()->subDays(30)])
            ->sum('amount');

        $earningsChange = $this->percentChange($priorPayoutTotal, $recentPayoutTotal);

        $unread = UserInboxNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return [
            'greeting_name' => $this->firstName($user->name),
            'portfolio' => [
                'total_value' => '₦'.number_format($totalValue),
                'total_value_raw' => $totalValue,
                'value_change' => $this->formatSignedPercent($valueChange),
                'value_change_tone' => $this->changeTone($valueChange),
                'total_earnings' => '₦'.number_format($totalEarnings),
                'total_earnings_raw' => $totalEarnings,
                'earnings_change' => $this->formatSignedPercent($earningsChange),
                'earnings_change_tone' => $this->changeTone($earningsChange),
                'active_investments' => (string) $active->count(),
                'total_roi' => rtrim(rtrim(number_format($roi, 1), '0'), '.').'%',
                'pending_earnings' => '₦'.number_format($pendingEarnings),
                'lifetime_payouts' => '₦'.number_format($lifetimePayouts),
            ],
            'performance' => $performance,
            'holdings' => $filteredHoldings->values()->map(function (UserInvestment $investment) {
                $collectible = $this->collectibleAmount($investment);
                $opportunity = $investment->opportunity;

                return [
                    'id' => $investment->id,
                    'opportunity_id' => $opportunity?->id,
                    'title' => $opportunity?->localizedTitle() ?? 'Investment',
                    'location' => $opportunity?->localizedLocation() ?? '—',
                    'amount' => $investment->formattedAmount(),
                    'value' => $investment->formattedValue(),
                    'earnings' => '₦'.number_format((int) $investment->accrued_earnings),
                    'collectible' => $collectible,
                    'collectible_label' => '₦'.number_format($collectible),
                    'roi' => $opportunity
                        ? rtrim(rtrim(number_format((float) $opportunity->roi_percent, 1), '0'), '.').'%'
                        : '—',
                    'invested_at' => $investment->invested_at?->format('M j, Y') ?? '—',
                    'can_collect' => $investment->isActive()
                        && $opportunity !== null
                        && $collectible > 0,
                ];
            })->all(),
            'recent_earnings' => $this->mapRecentEarnings($payouts),
            'wallet_balance' => $this->walletService->getBalance($user),
            'notifications_count' => $unread,
            'query' => $search,
        ];
    }

    /**
     * Move collectible earnings from a holding into the user's wallet.
     */
    public function collectEarnings(User $user, UserInvestment $investment): InvestmentPayout
    {
        $this->assertOwned($user, $investment);

        if (! $investment->isActive()) {
            throw new BusinessLogicException('Only active investments can pay out earnings.');
        }

        $opportunity = $investment->opportunity;
        if ($opportunity === null) {
            throw new BusinessLogicException('Investment opportunity not found.');
        }

        return DB::transaction(function () use ($user, $investment, $opportunity): InvestmentPayout {
            $locked = UserInvestment::query()
                ->whereKey($investment->id)
                ->with('opportunity')
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isActive()) {
                throw new BusinessLogicException('Only active investments can pay out earnings.');
            }

            $this->syncAccruedEarnings($locked);
            $locked->refresh();

            $payoutAmount = $this->collectibleAmount($locked);

            if ($payoutAmount < 1) {
                throw new BusinessLogicException('No earnings are available to collect on this holding yet.');
            }

            $locked->forceFill([
                'accrued_earnings' => 0,
            ])->save();

            $payout = InvestmentPayout::query()->create([
                'user_id' => $user->id,
                'user_investment_id' => $locked->id,
                'investment_opportunity_id' => $opportunity->id,
                'title' => $opportunity->title,
                'location' => $opportunity->location,
                'amount' => $payoutAmount,
                'paid_at' => now(),
                'note' => 'Earnings collected to wallet',
            ]);

            $this->walletService->creditEarnings(
                $user,
                $payoutAmount,
                $payout,
                'Earnings from '.$opportunity->title
            );

            UserInboxNotification::query()->create([
                'user_id' => $user->id,
                'title' => 'Earnings collected',
                'body' => '₦'.number_format($payoutAmount).' from '.$opportunity->title.' was credited to your wallet.',
                'tone' => 'success',
                'category' => 'earnings',
                'notification_key' => 'payout-'.$payout->id,
                'read_at' => null,
            ]);

            return $payout;
        });
    }

    /**
     * Amount available to collect now (synced accrued balance only).
     */
    public function collectibleAmount(UserInvestment $investment): int
    {
        return max(0, (int) $investment->accrued_earnings);
    }

    /**
     * Recalculate accrued earnings for all active holdings.
     */
    public function syncPortfolioAccruals(User $user): void
    {
        $investments = UserInvestment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with('opportunity')
            ->get();

        foreach ($investments as $investment) {
            $this->syncAccruedEarnings($investment);
        }
    }

    /**
     * Accrue ROI periods based on months held minus lifetime payouts.
     */
    public function syncAccruedEarnings(UserInvestment $investment): void
    {
        $opportunity = $investment->opportunity;
        if ($opportunity === null || ! $investment->isActive()) {
            return;
        }

        $investedAt = $investment->invested_at ?? $investment->created_at;
        if ($investedAt === null) {
            return;
        }

        $durationMonths = max(1, (int) $opportunity->duration_months);
        $monthsElapsed = (int) $investedAt->diffInMonths(now());
        $monthsEarned = min($monthsElapsed, $durationMonths);

        $monthly = $this->monthlyPeriodEarnings($investment, $opportunity);
        $earnedToDate = $monthly * $monthsEarned;

        $alreadyPaid = (int) InvestmentPayout::query()
            ->where('user_investment_id', $investment->id)
            ->sum('amount');

        $pending = max(0, $earnedToDate - $alreadyPaid);

        if ((int) $investment->accrued_earnings !== $pending) {
            $investment->forceFill(['accrued_earnings' => $pending])->save();
        }

        if ($monthsElapsed >= $durationMonths && $pending === 0) {
            $investment->forceFill([
                'status' => 'matured',
                'matured_at' => $investment->matured_at ?? now(),
            ])->save();
        }
    }

    protected function monthlyPeriodEarnings(UserInvestment $investment, InvestmentOpportunity $opportunity): int
    {
        $months = max(1, (int) $opportunity->duration_months);

        return (int) max(
            0,
            (int) round($investment->amount * ((float) $opportunity->roi_percent / 100) / $months)
        );
    }

    /**
     * @param  Collection<int, InvestmentPayout>  $payouts
     * @return list<array{title: string, location: string, amount: string, paid_at: string}>
     */
    protected function mapRecentEarnings(Collection $payouts): array
    {
        return $payouts->take(5)->map(fn (InvestmentPayout $payout) => [
            'title' => $payout->title,
            'location' => $payout->location,
            'amount' => $payout->formattedAmount(),
            'paid_at' => $payout->paid_at?->diffForHumans(short: true) ?? '',
        ])->all();
    }

    /**
     * Portfolio value by month-end from real principal + unpaid accrued schedule.
     *
     * @param  Collection<int, UserInvestment>  $active
     * @return array{labels: list<string>, values: list<int>}
     */
    protected function buildPerformanceSeries(User $user, Collection $active, int $totalValue): array
    {
        $labels = [];
        $values = [];

        $allHoldings = UserInvestment::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'matured'])
            ->with('opportunity')
            ->get();

        for ($i = 5; $i >= 0; $i--) {
            $monthEnd = now()->copy()->subMonths($i)->endOfMonth();
            if ($i === 0) {
                $monthEnd = now();
            }

            $labels[] = ($i === 0 ? now() : now()->copy()->subMonths($i))->format('M');

            $valueAt = 0;
            foreach ($allHoldings as $investment) {
                $investedAt = $investment->invested_at ?? $investment->created_at;
                if ($investedAt === null || $investedAt->greaterThan($monthEnd)) {
                    continue;
                }

                $opportunity = $investment->opportunity;
                if ($opportunity === null) {
                    $valueAt += (int) $investment->amount;
                    continue;
                }

                $durationMonths = max(1, (int) $opportunity->duration_months);
                $monthsElapsed = (int) $investedAt->diffInMonths($monthEnd);
                $monthsEarned = min($monthsElapsed, $durationMonths);
                $monthly = $this->monthlyPeriodEarnings($investment, $opportunity);
                $earnedToDate = $monthly * $monthsEarned;

                $paidByThen = (int) InvestmentPayout::query()
                    ->where('user_investment_id', $investment->id)
                    ->where('paid_at', '<=', $monthEnd)
                    ->sum('amount');

                $pendingThen = max(0, $earnedToDate - $paidByThen);
                $valueAt += (int) $investment->amount + $pendingThen;
            }

            $values[] = $valueAt;
        }

        if ($totalValue > 0 && $values !== []) {
            $values[count($values) - 1] = $totalValue;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    protected function percentChange(int $from, int $to): float
    {
        if ($from < 1) {
            return $to > 0 ? 100.0 : 0.0;
        }

        return round((($to - $from) / $from) * 100, 1);
    }

    protected function formatSignedPercent(float $value): string
    {
        if ($value > 0) {
            return '+'.rtrim(rtrim(number_format($value, 1), '0'), '.').'%';
        }

        if ($value < 0) {
            return rtrim(rtrim(number_format($value, 1), '0'), '.').'%';
        }

        return '0%';
    }

    protected function changeTone(float $value): string
    {
        if ($value > 0) {
            return 'up';
        }

        if ($value < 0) {
            return 'down';
        }

        return 'flat';
    }

    protected function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';

        return $first !== '' ? $first : 'Investor';
    }

    protected function assertOwned(User $user, UserInvestment $investment): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ((int) $investment->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only manage your own investments.', 'INVESTMENT_FORBIDDEN', 403);
        }
    }
}
