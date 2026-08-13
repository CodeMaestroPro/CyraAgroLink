<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Exceptions\BusinessLogicException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletFundingIntent;
use App\Models\WalletTransaction;
use App\Services\Payments\PaystackGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Digital wallet funding, payments, and ledger history.
 */
class DigitalWalletService
{
    public function __construct(
        protected PaystackGateway $paystackGateway
    ) {
    }

    /**
     * Build the wallet screen payload.
     *
     * @return array<string, mixed>
     */
    /**
     * @param  string  $filter  all|credit|debit
     * @return array<string, mixed>
     */
    public function getWalletData(User $user, string $filter = 'all'): array
    {
        $wallet = $this->ensureWallet($user);
        $filter = in_array($filter, ['all', 'credit', 'debit'], true) ? $filter : 'all';

        $query = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id');

        if ($filter !== 'all') {
            $query->where('type', $filter);
        }

        $transactions = $query
            ->limit(40)
            ->get()
            ->map(fn (WalletTransaction $tx) => [
                'id' => $tx->id,
                'title' => $tx->title,
                'detail' => $tx->detail ?? '',
                'amount' => $tx->formattedAmount(),
                'balance_after' => '₦'.number_format($tx->balance_after),
                'time' => $tx->created_at?->diffForHumans() ?? '',
                'tone' => $tx->isCredit() ? 'credit' : 'debit',
                'icon' => $tx->iconKey(),
                'category' => $tx->category,
            ])
            ->all();

        $credits = (int) WalletTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'credit')
            ->sum('amount');
        $debits = (int) WalletTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'debit')
            ->sum('amount');

        return [
            'wallet' => $wallet,
            'balance' => [
                'amount' => $wallet->formattedBalance(),
                'raw' => $wallet->balance,
                'currency_label' => 'Naira Wallet',
            ],
            'stats' => [
                'in' => '₦'.number_format($credits),
                'out' => '₦'.number_format($debits),
                'count' => WalletTransaction::query()->where('user_id', $user->id)->count(),
            ],
            'filter' => $filter,
            'transactions' => $transactions,
            'notifications_count' => max(2, count($transactions) > 0 ? 3 : 2),
        ];
    }

    /**
     * Current wallet balance in naira.
     */
    public function getBalance(User $user): int
    {
        return $this->ensureWallet($user)->balance;
    }

    /**
     * Fund the user's wallet.
     *
     * When Paystack is configured, creates a funding intent and returns a checkout URL.
     * Otherwise uses simulated local credit (blocked in production).
     *
     * @return array{mode: 'redirect'|'credited', redirect_url?: string, transaction?: WalletTransaction, amount: int}
     */
    public function deposit(User $user, int $amount, ?string $note = null): array
    {
        if ($amount < 100) {
            throw new BusinessLogicException('Minimum deposit is ₦100.');
        }

        if ($amount > 50_000_000) {
            throw new BusinessLogicException('Deposit amount is too large.');
        }

        if ($this->paystackGateway->enabled()) {
            return $this->beginPaystackDeposit($user, $amount, $note);
        }

        if (app()->environment('production')) {
            throw new BusinessLogicException(
                'Card funding is not configured. Set PAYSTACK_SECRET_KEY before accepting deposits.',
                'PAYSTACK_REQUIRED',
                503
            );
        }

        $transaction = $this->credit(
            $user,
            $amount,
            'deposit',
            'Wallet Deposit',
            $note ?: 'Funds added to wallet (local)',
            null
        );

        return [
            'mode' => 'credited',
            'transaction' => $transaction,
            'amount' => $amount,
        ];
    }

    /**
     * Start Paystack checkout for a wallet top-up.
     *
     * @return array{mode: 'redirect', redirect_url: string, amount: int}
     */
    protected function beginPaystackDeposit(User $user, int $amount, ?string $note = null): array
    {
        $email = trim((string) $user->email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessLogicException('Add a valid email on your profile before funding with Paystack.');
        }

        $reference = $this->paystackGateway->makeReference('WAL');
        $callbackUrl = route('wallet.paystack.callback');

        $checkout = $this->paystackGateway->initialize(
            $email,
            $amount,
            $reference,
            $callbackUrl,
            [
                'user_id' => $user->id,
                'purpose' => 'wallet_deposit',
                'custom_fields' => [
                    [
                        'display_name' => 'Wallet funding',
                        'variable_name' => 'wallet_funding',
                        'value' => (string) $user->id,
                    ],
                ],
            ]
        );

        if ($checkout['authorization_url'] === '') {
            throw new BusinessLogicException('Paystack did not return a checkout URL.', 'PAYSTACK_INIT_FAILED', 502);
        }

        WalletFundingIntent::query()->create([
            'user_id' => $user->id,
            'reference' => $checkout['reference'] ?: $reference,
            'amount' => $amount,
            'currency' => 'NGN',
            'status' => 'pending',
            'provider' => 'paystack',
            'authorization_url' => $checkout['authorization_url'],
            'note' => $note,
            'meta' => [
                'access_code' => $checkout['access_code'],
            ],
        ]);

        return [
            'mode' => 'redirect',
            'redirect_url' => $checkout['authorization_url'],
            'amount' => $amount,
        ];
    }

    /**
     * Verify Paystack payment and credit the wallet once (idempotent).
     */
    public function completePaystackDeposit(string $reference): WalletTransaction
    {
        $intent = WalletFundingIntent::query()
            ->where('reference', $reference)
            ->first();

        if (! $intent) {
            throw new BusinessLogicException('Unknown payment reference.', 'FUNDING_NOT_FOUND', 404);
        }

        if ($intent->isPaid() && $intent->wallet_transaction_id) {
            /** @var WalletTransaction $existing */
            $existing = WalletTransaction::query()->findOrFail($intent->wallet_transaction_id);

            return $existing;
        }

        $verified = $this->paystackGateway->verify($reference);

        if (! $verified['paid']) {
            $intent->forceFill([
                'status' => 'failed',
                'meta' => array_merge($intent->meta ?? [], ['verify' => $verified['raw']]),
            ])->save();

            throw new BusinessLogicException('Payment was not successful. Status: '.$verified['status']);
        }

        if ((int) $verified['amount_naira'] !== (int) $intent->amount) {
            throw new BusinessLogicException('Paid amount does not match the funding request.');
        }

        return DB::transaction(function () use ($intent, $verified): WalletTransaction {
            /** @var WalletFundingIntent $locked */
            $locked = WalletFundingIntent::query()->whereKey($intent->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid() && $locked->wallet_transaction_id) {
                return WalletTransaction::query()->findOrFail($locked->wallet_transaction_id);
            }

            $user = User::query()->findOrFail($locked->user_id);

            $transaction = $this->credit(
                $user,
                (int) $locked->amount,
                'deposit',
                'Wallet Deposit',
                $locked->note ?: 'Paystack funding '.$locked->reference,
                $locked
            );

            $locked->forceFill([
                'status' => 'paid',
                'provider_reference' => $verified['reference'],
                'wallet_transaction_id' => $transaction->id,
                'paid_at' => now(),
                'meta' => array_merge($locked->meta ?? [], ['verify' => $verified['raw']]),
            ])->save();

            return $transaction;
        });
    }

    /**
     * Withdraw funds from the wallet.
     */
    public function withdraw(User $user, int $amount, ?string $note = null): WalletTransaction
    {
        if ($amount < 100) {
            throw new BusinessLogicException('Minimum withdrawal is ₦100.');
        }

        return $this->debit(
            $user,
            $amount,
            'withdrawal',
            'Withdrawal',
            $note ?: 'To bank account',
            null
        );
    }

    /**
     * Debit wallet for a consumer marketplace purchase.
     */
    public function payForPurchase(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid payment amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'purchase',
            'Marketplace Purchase',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet for a farm investment commitment.
     */
    public function payForInvestment(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid investment amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'investment',
            'Farm Investment',
            $detail,
            $reference
        );
    }

    /**
     * Refund a previous purchase back to the wallet.
     */
    public function refundPurchase(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid refund amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'refund',
            'Purchase Refund',
            $detail,
            $reference
        );
    }

    /**
     * Credit investment earnings into the wallet.
     */
    public function creditEarnings(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid earnings amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'earnings',
            'Investment Earnings',
            $detail,
            $reference
        );
    }

    /**
     * Credit proceeds from a carbon credit marketplace sale.
     */
    public function creditCarbonSale(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid carbon sale amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'carbon',
            'Carbon Credit Sale',
            $detail,
            $reference
        );
    }

    /**
     * Credit proceeds when an export order is delivered.
     */
    public function creditExportProceeds(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid export proceeds amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'export',
            'Export Proceeds',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet for a food processing network service fee.
     */
    public function payForProcessing(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid processing fee amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'processing',
            'Processing Fee',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet for an equipment marketplace purchase or rental.
     */
    public function payForEquipment(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid equipment payment amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'equipment',
            'Equipment Order',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet for a farm insurance premium.
     */
    public function payForInsurancePremium(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid insurance premium amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'insurance',
            'Insurance Premium',
            $detail,
            $reference
        );
    }

    /**
     * Credit wallet for an approved insurance claim payout.
     */
    public function creditInsuranceClaim(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid insurance claim payout amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'insurance_claim',
            'Insurance Claim Payout',
            $detail,
            $reference
        );
    }

    /**
     * Credit wallet when a government subsidy is approved and disbursed.
     */
    public function creditSubsidyDisbursement(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid subsidy disbursement amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'subsidy',
            'Government Subsidy',
            $detail,
            $reference
        );
    }

    /**
     * Credit wallet when an agricultural loan is approved and disbursed.
     */
    public function creditLoanDisbursement(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid loan disbursement amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'loan_disbursement',
            'Agricultural Loan Disbursed',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet for an agricultural loan repayment.
     */
    public function debitLoanRepayment(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid loan repayment amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'loan_repayment',
            'Agricultural Loan Repayment',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet for a cooperative savings contribution.
     */
    public function debitCoopContribution(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid cooperative contribution amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'coop_contribution',
            'Cooperative Contribution',
            $detail,
            $reference
        );
    }

    /**
     * Credit wallet when a cooperative loan is disbursed.
     */
    public function creditCoopLoan(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid cooperative loan amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'coop_loan',
            'Cooperative Loan Disbursed',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet when a member repays a cooperative loan.
     */
    public function debitCoopLoanRepayment(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid cooperative loan repayment amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'coop_repayment',
            'Cooperative Loan Repayment',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet to lock futures trading margin.
     */
    public function lockFuturesMargin(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid futures margin amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'futures_margin',
            'Futures Margin',
            $detail,
            $reference
        );
    }

    /**
     * Credit wallet when releasing futures margin / settling profit.
     */
    public function creditFuturesSettlement(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid futures settlement amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'futures_settlement',
            'Futures Settlement',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet for futures settlement loss beyond released margin.
     */
    public function debitFuturesSettlement(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid futures settlement debit.');
        }

        return $this->debit(
            $user,
            $amount,
            'futures_settlement',
            'Futures Settlement',
            $detail,
            $reference
        );
    }

    /**
     * Reserve wallet funds for an open exchange buy order.
     */
    public function lockExchangeBuy(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid exchange buy reservation.');
        }

        return $this->debit(
            $user,
            $amount,
            'exchange_hold',
            'Exchange Buy Hold',
            $detail,
            $reference
        );
    }

    /**
     * Release unused exchange buy reservation (cancel or price improvement).
     */
    public function releaseExchangeHold(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid exchange hold release.');
        }

        return $this->credit(
            $user,
            $amount,
            'exchange_refund',
            'Exchange Hold Released',
            $detail,
            $reference
        );
    }

    /**
     * Credit a seller for a matched spot exchange trade.
     */
    public function creditExchangeSale(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid exchange sale amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'exchange_sale',
            'Exchange Sale Proceeds',
            $detail,
            $reference
        );
    }

    /**
     * Debit wallet to hold funds for an auction bid.
     */
    public function lockAuctionBid(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid auction bid amount.');
        }

        return $this->debit(
            $user,
            $amount,
            'auction_hold',
            'Auction Bid Hold',
            $detail,
            $reference
        );
    }

    /**
     * Credit wallet when an auction bid is outbid or cancelled.
     */
    public function releaseAuctionBid(User $user, int $amount, Model $reference, string $detail): WalletTransaction
    {
        if ($amount < 1) {
            throw new BusinessLogicException('Invalid auction refund amount.');
        }

        return $this->credit(
            $user,
            $amount,
            'auction_refund',
            'Auction Bid Refund',
            $detail,
            $reference
        );
    }

    public function ensureWallet(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'currency' => 'NGN']
        );
    }

    /**
     * @param  Model|null  $reference
     */
    protected function credit(
        User $user,
        int $amount,
        string $category,
        string $title,
        string $detail,
        ?Model $reference
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $category, $title, $detail, $reference): WalletTransaction {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = Wallet::query()->create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'currency' => 'NGN',
                ]);
                $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $wallet->forceFill([
                'balance' => $wallet->balance + $amount,
            ])->save();

            return WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'credit',
                'category' => $category,
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'title' => $title,
                'detail' => $detail,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }

    /**
     * @param  Model|null  $reference
     */
    protected function debit(
        User $user,
        int $amount,
        string $category,
        string $title,
        string $detail,
        ?Model $reference
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $amount, $category, $title, $detail, $reference): WalletTransaction {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = Wallet::query()->create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'currency' => 'NGN',
                ]);
                $wallet = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            if ($wallet->balance < $amount) {
                throw new BusinessLogicException(
                    'Insufficient wallet balance. Fund your wallet to complete this payment.'
                );
            }

            $wallet->forceFill([
                'balance' => $wallet->balance - $amount,
            ])->save();

            return WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'debit',
                'category' => $category,
                'amount' => $amount,
                'balance_after' => $wallet->balance,
                'title' => $title,
                'detail' => $detail,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }
}
