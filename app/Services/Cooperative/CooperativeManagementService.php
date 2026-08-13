<?php

declare(strict_types=1);

namespace App\Services\Cooperative;

use App\Exceptions\BusinessLogicException;
use App\Models\Cooperative;
use App\Models\CooperativeActivity;
use App\Models\CooperativeBallot;
use App\Models\CooperativeLoan;
use App\Models\CooperativeMember;
use App\Models\CooperativeVote;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Live cooperative management: members, savings, loans, and votes.
 */
class CooperativeManagementService
{
    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user): array
    {
        $membership = $this->ensureMembership($user);
        $coop = $membership->cooperative;
        $this->closeExpiredVotes($coop);

        $groups = Cooperative::query()->where('status', 'active')->count();
        $members = CooperativeMember::query()->where('status', 'active')->count();
        $totalSavings = (int) Cooperative::query()->sum('savings_pool_ngn');
        $loansIssued = (int) CooperativeLoan::query()
            ->whereIn('status', ['disbursed', 'repaid'])
            ->sum('amount_ngn');

        $activities = CooperativeActivity::query()
            ->where('cooperative_id', $coop->id)
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (CooperativeActivity $activity) => [
                'title' => $activity->title,
                'value' => $activity->value,
                'icon' => $activity->icon,
            ])
            ->all();

        if ($activities === []) {
            $activities = [
                [
                    'title' => 'Welcome to '.$coop->name,
                    'value' => 'Get started',
                    'icon' => 'contribution',
                ],
            ];
        }

        $vote = CooperativeVote::query()
            ->where('cooperative_id', $coop->id)
            ->where('status', 'open')
            ->where('closes_at', '>', now())
            ->latest('id')
            ->first();

        $userBallot = $vote
            ? CooperativeBallot::query()
                ->where('cooperative_vote_id', $vote->id)
                ->where('user_id', $user->id)
                ->first()
            : null;

        $loans = CooperativeLoan::query()
            ->where('cooperative_id', $coop->id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (CooperativeLoan $loan) => $this->presentLoan($loan, $user, $membership))
            ->all();

        $memberRows = CooperativeMember::query()
            ->where('cooperative_id', $coop->id)
            ->where('status', 'active')
            ->with('user')
            ->orderByDesc('savings_balance_ngn')
            ->limit(12)
            ->get()
            ->map(fn (CooperativeMember $member) => [
                'name' => $member->user?->name ?? 'Member #'.$member->user_id,
                'role' => ucfirst($member->role),
                'savings' => '₦'.number_format($member->savings_balance_ngn),
                'joined' => $member->joined_at?->format('d M, Y') ?? '',
            ])
            ->all();

        return [
            'cooperative' => [
                'id' => $coop->id,
                'name' => $coop->name,
                'location' => $coop->location ?? 'Nigeria',
                'pool' => '₦'.number_format($coop->savings_pool_ngn),
                'my_savings' => '₦'.number_format($membership->savings_balance_ngn),
                'is_admin' => $membership->isAdmin(),
                'member_count' => CooperativeMember::query()
                    ->where('cooperative_id', $coop->id)
                    ->where('status', 'active')
                    ->count(),
            ],
            'kpis' => [
                ['label' => 'Total Members', 'value' => number_format($members)],
                ['label' => 'Active Groups', 'value' => number_format($groups)],
                ['label' => 'Total Savings', 'value' => $this->compactNaira($totalSavings)],
                ['label' => 'Loans Issued', 'value' => $this->compactNaira($loansIssued)],
            ],
            'activities' => $activities,
            'vote' => $vote ? [
                'id' => $vote->id,
                'title' => 'Upcoming Vote',
                'description' => $vote->title,
                'detail' => $vote->description,
                'date' => $vote->closes_at->format('d M, Y'),
                'yes' => $vote->yes_count,
                'no' => $vote->no_count,
                'has_voted' => $userBallot !== null,
                'my_choice' => $userBallot?->choice,
                'can_vote' => $userBallot === null && $vote->isOpen(),
            ] : [
                'id' => null,
                'title' => 'Upcoming Vote',
                'description' => 'No open vote yet. Admins can propose a group decision.',
                'detail' => '',
                'date' => '—',
                'yes' => 0,
                'no' => 0,
                'has_voted' => false,
                'my_choice' => null,
                'can_vote' => false,
            ],
            'members' => $memberRows,
            'loans' => $loans,
            'wallet_balance' => '₦'.number_format($this->walletService->getBalance($user)),
            'actions' => [
                ['label' => 'Members', 'href' => '#members', 'icon' => 'members'],
                ['label' => 'Loans', 'href' => '#loans', 'icon' => 'loans'],
                ['label' => 'Savings', 'href' => '#savings', 'icon' => 'savings'],
                ['label' => 'Equipment', 'href' => route('equipment.marketplace'), 'icon' => 'equipment'],
                ['label' => 'Reports', 'href' => route('reporting.analytics'), 'icon' => 'reports'],
                'contribute_url' => route('cooperative.contribute'),
                'loan_url' => route('cooperative.loans.store'),
                'vote_create_url' => route('cooperative.votes.store'),
                'vote_cast_url' => $vote ? route('cooperative.votes.cast', $vote) : null,
            ],
            'notifications_count' => max(2, count($activities) + ($vote && ! $userBallot ? 1 : 0)),
        ];
    }

    /**
     * Contribute savings from the member wallet into the cooperative pool.
     */
    public function contribute(User $user, int $amount): CooperativeActivity
    {
        if ($amount < 1000) {
            throw new BusinessLogicException('Minimum contribution is ₦1,000.');
        }

        $membership = $this->ensureMembership($user);
        $coop = $membership->cooperative;

        return DB::transaction(function () use ($user, $amount, $membership, $coop): CooperativeActivity {
            $coop = Cooperative::query()->whereKey($coop->id)->lockForUpdate()->firstOrFail();
            $membership = CooperativeMember::query()->whereKey($membership->id)->lockForUpdate()->firstOrFail();

            $this->walletService->ensureWallet($user);
            $this->walletService->debitCoopContribution(
                $user,
                $amount,
                $coop,
                'Contribution to '.$coop->name
            );

            $coop->forceFill([
                'savings_pool_ngn' => $coop->savings_pool_ngn + $amount,
            ])->save();

            $membership->forceFill([
                'savings_balance_ngn' => $membership->savings_balance_ngn + $amount,
            ])->save();

            return $this->recordActivity(
                $coop,
                $user,
                'contribution',
                'Member Contribution',
                '₦'.number_format($amount),
                'contribution',
                $membership
            );
        });
    }

    /**
     * Request a group loan from cooperative savings.
     */
    public function requestLoan(User $user, int $amount, string $purpose): CooperativeLoan
    {
        if ($amount < 5000) {
            throw new BusinessLogicException('Minimum loan request is ₦5,000.');
        }

        $membership = $this->ensureMembership($user);
        $coop = $membership->cooperative;

        if ($amount > $coop->savings_pool_ngn) {
            throw new BusinessLogicException('Requested amount exceeds the cooperative savings pool.');
        }

        $openLoan = CooperativeLoan::query()
            ->where('cooperative_id', $coop->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved', 'disbursed'])
            ->exists();

        if ($openLoan) {
            throw new BusinessLogicException('You already have an open cooperative loan.');
        }

        $loan = CooperativeLoan::query()->create([
            'cooperative_id' => $coop->id,
            'user_id' => $user->id,
            'reference' => 'CL-'.strtoupper(Str::random(8)),
            'amount_ngn' => $amount,
            'purpose' => $purpose,
            'status' => 'pending',
        ]);

        $this->recordActivity(
            $coop,
            $user,
            'loan',
            'Loan Requested',
            '₦'.number_format($amount),
            'loan',
            $loan
        );

        return $loan;
    }

    /**
     * Approve and disburse a pending loan (admin), or reject it.
     */
    public function reviewLoan(User $user, CooperativeLoan $loan, string $decision): CooperativeLoan
    {
        $membership = $this->ensureMembership($user);

        if ($loan->cooperative_id !== $membership->cooperative_id) {
            throw new BusinessLogicException('This loan belongs to another cooperative.', statusCode: 403);
        }

        if (! $membership->isAdmin()) {
            throw new BusinessLogicException('Only cooperative admins can review loans.', statusCode: 403);
        }

        if ($loan->status !== 'pending') {
            throw new BusinessLogicException('Only pending loans can be reviewed.');
        }

        if ($decision === 'reject') {
            $loan->forceFill([
                'status' => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
            ])->save();

            $this->recordActivity(
                $membership->cooperative,
                $user,
                'loan',
                'Loan Rejected',
                '₦'.number_format($loan->amount_ngn),
                'loan',
                $loan
            );

            return $loan->fresh();
        }

        return DB::transaction(function () use ($user, $loan, $membership): CooperativeLoan {
            $coop = Cooperative::query()->whereKey($loan->cooperative_id)->lockForUpdate()->firstOrFail();
            $loan = CooperativeLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->status !== 'pending') {
                throw new BusinessLogicException('Only pending loans can be reviewed.');
            }

            if ($loan->amount_ngn > $coop->savings_pool_ngn) {
                throw new BusinessLogicException('Insufficient cooperative savings to disburse this loan.');
            }

            $borrower = User::query()->findOrFail($loan->user_id);

            $coop->forceFill([
                'savings_pool_ngn' => $coop->savings_pool_ngn - $loan->amount_ngn,
            ])->save();

            $this->walletService->ensureWallet($borrower);
            $this->walletService->creditCoopLoan(
                $borrower,
                $loan->amount_ngn,
                $loan,
                'Loan from '.$coop->name.' ('.$loan->reference.')'
            );

            $loan->forceFill([
                'status' => 'disbursed',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'disbursed_at' => now(),
            ])->save();

            $this->recordActivity(
                $coop,
                $borrower,
                'loan',
                'Group Loan Disbursed',
                '₦'.number_format($loan->amount_ngn),
                'loan',
                $loan
            );

            return $loan->fresh();
        });
    }

    /**
     * Repay a disbursed cooperative loan from the borrower's wallet.
     */
    public function repayLoan(User $user, CooperativeLoan $loan): CooperativeLoan
    {
        if ($loan->user_id !== $user->id) {
            throw new BusinessLogicException('You can only repay your own cooperative loan.', statusCode: 403);
        }

        if ($loan->status !== 'disbursed') {
            throw new BusinessLogicException('Only disbursed loans can be repaid.');
        }

        $membership = $this->ensureMembership($user);

        if ($loan->cooperative_id !== $membership->cooperative_id) {
            throw new BusinessLogicException('This loan belongs to another cooperative.', statusCode: 403);
        }

        return DB::transaction(function () use ($user, $loan): CooperativeLoan {
            $coop = Cooperative::query()->whereKey($loan->cooperative_id)->lockForUpdate()->firstOrFail();
            $loan = CooperativeLoan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($loan->status !== 'disbursed') {
                throw new BusinessLogicException('Only disbursed loans can be repaid.');
            }

            $this->walletService->ensureWallet($user);
            $this->walletService->debitCoopLoanRepayment(
                $user,
                $loan->amount_ngn,
                $loan,
                'Repayment to '.$coop->name.' ('.$loan->reference.')'
            );

            $coop->forceFill([
                'savings_pool_ngn' => $coop->savings_pool_ngn + $loan->amount_ngn,
            ])->save();

            $loan->forceFill([
                'status' => 'repaid',
                'repaid_at' => now(),
            ])->save();

            $this->recordActivity(
                $coop,
                $user,
                'loan',
                'Loan Repaid',
                '₦'.number_format($loan->amount_ngn),
                'profit',
                $loan
            );

            return $loan->fresh();
        });
    }

    /**
     * Create an open vote for cooperative members.
     *
     * @param  array{title: string, description: string, closes_in_days?: int}  $data
     */
    public function createVote(User $user, array $data): CooperativeVote
    {
        $membership = $this->ensureMembership($user);

        if (! $membership->isAdmin()) {
            throw new BusinessLogicException('Only cooperative admins can create votes.', statusCode: 403);
        }

        $openExists = CooperativeVote::query()
            ->where('cooperative_id', $membership->cooperative_id)
            ->where('status', 'open')
            ->where('closes_at', '>', now())
            ->exists();

        if ($openExists) {
            throw new BusinessLogicException('There is already an open vote. Close it or wait for it to expire.');
        }

        $days = max(1, min(30, (int) ($data['closes_in_days'] ?? 7)));

        $vote = CooperativeVote::query()->create([
            'cooperative_id' => $membership->cooperative_id,
            'created_by' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'open',
            'yes_count' => 0,
            'no_count' => 0,
            'closes_at' => now()->addDays($days),
        ]);

        $this->recordActivity(
            $membership->cooperative,
            $user,
            'vote',
            'Vote Opened',
            $vote->title,
            'profit',
            $vote
        );

        return $vote;
    }

    /**
     * Cast a yes/no ballot on an open cooperative vote.
     */
    public function castVote(User $user, CooperativeVote $vote, string $choice): CooperativeBallot
    {
        $choice = strtolower($choice);
        if (! in_array($choice, ['yes', 'no'], true)) {
            throw new BusinessLogicException('Vote choice must be yes or no.');
        }

        $membership = $this->ensureMembership($user);

        if ($vote->cooperative_id !== $membership->cooperative_id) {
            throw new BusinessLogicException('This vote belongs to another cooperative.', statusCode: 403);
        }

        $this->closeExpiredVotes($membership->cooperative);
        $vote->refresh();

        if (! $vote->isOpen()) {
            throw new BusinessLogicException('This vote is no longer open.');
        }

        if (CooperativeBallot::query()
            ->where('cooperative_vote_id', $vote->id)
            ->where('user_id', $user->id)
            ->exists()) {
            throw new BusinessLogicException('You have already voted.');
        }

        return DB::transaction(function () use ($user, $vote, $choice): CooperativeBallot {
            $vote = CooperativeVote::query()->whereKey($vote->id)->lockForUpdate()->firstOrFail();

            $ballot = CooperativeBallot::query()->create([
                'cooperative_vote_id' => $vote->id,
                'user_id' => $user->id,
                'choice' => $choice,
            ]);

            $vote->forceFill([
                'yes_count' => $vote->yes_count + ($choice === 'yes' ? 1 : 0),
                'no_count' => $vote->no_count + ($choice === 'no' ? 1 : 0),
            ])->save();

            return $ballot;
        });
    }

    /**
     * Ensure the user belongs to a cooperative (creates a default one if needed).
     */
    public function ensureMembership(User $user): CooperativeMember
    {
        $existing = CooperativeMember::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with('cooperative')
            ->latest('id')
            ->first();

        if ($existing && $existing->cooperative) {
            return $existing;
        }

        return DB::transaction(function () use ($user): CooperativeMember {
            $name = trim(($user->name ?: 'Farmer').' Cooperative');
            $slugBase = Str::slug($name.'-'.$user->id) ?: 'coop-'.$user->id;

            $coop = Cooperative::query()->create([
                'name' => $name,
                'slug' => $slugBase,
                'location' => 'Nigeria',
                'description' => 'Smart cooperative for shared savings, loans, and group decisions.',
                'created_by' => $user->id,
                'savings_pool_ngn' => 0,
                'status' => 'active',
            ]);

            $member = CooperativeMember::query()->create([
                'cooperative_id' => $coop->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'savings_balance_ngn' => 0,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $this->recordActivity(
                $coop,
                $user,
                'member',
                'Cooperative Founded',
                $coop->name,
                'contribution',
                $member
            );

            CooperativeVote::query()->create([
                'cooperative_id' => $coop->id,
                'created_by' => $user->id,
                'title' => 'Approve Purchase of New Thresher Machine',
                'description' => 'Members vote to approve buying a shared thresher from cooperative savings.',
                'status' => 'open',
                'yes_count' => 0,
                'no_count' => 0,
                'closes_at' => now()->addDays(14),
            ]);

            return $member->load('cooperative');
        });
    }

    protected function closeExpiredVotes(Cooperative $coop): void
    {
        $expired = CooperativeVote::query()
            ->where('cooperative_id', $coop->id)
            ->where('status', 'open')
            ->where('closes_at', '<=', now())
            ->get();

        foreach ($expired as $vote) {
            $status = $vote->yes_count > $vote->no_count
                ? 'passed'
                : ($vote->no_count > $vote->yes_count ? 'rejected' : 'closed');

            $vote->forceFill([
                'status' => $status,
                'closed_at' => now(),
            ])->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentLoan(CooperativeLoan $loan, User $user, CooperativeMember $membership): array
    {
        return [
            'id' => $loan->id,
            'reference' => $loan->reference,
            'purpose' => $loan->purpose,
            'amount' => '₦'.number_format($loan->amount_ngn),
            'status' => $loan->status,
            'can_review' => $membership->isAdmin() && $loan->status === 'pending',
            'can_repay' => $loan->user_id === $user->id && $loan->status === 'disbursed',
            'approve_url' => route('cooperative.loans.review', $loan),
            'repay_url' => route('cooperative.loans.repay', $loan),
        ];
    }

    protected function recordActivity(
        Cooperative $coop,
        ?User $user,
        string $type,
        string $title,
        string $value,
        string $icon,
        ?Model $reference = null
    ): CooperativeActivity {
        return CooperativeActivity::query()->create([
            'cooperative_id' => $coop->id,
            'user_id' => $user?->id,
            'type' => $type,
            'title' => $title,
            'value' => $value,
            'icon' => $icon,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
        ]);
    }

    protected function compactNaira(int $amount): string
    {
        if ($amount >= 1_000_000) {
            $millions = $amount / 1_000_000;

            return '₦'.rtrim(rtrim(number_format($millions, 1, '.', ''), '0'), '.').'M';
        }

        if ($amount >= 1_000) {
            $thousands = $amount / 1_000;

            return '₦'.rtrim(rtrim(number_format($thousands, 1, '.', ''), '0'), '.').'K';
        }

        return '₦'.number_format($amount);
    }
}
