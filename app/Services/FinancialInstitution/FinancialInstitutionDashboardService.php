<?php

declare(strict_types=1);

namespace App\Services\FinancialInstitution;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessLogicException;
use App\Models\LoanApplication;
use App\Models\LoanRepayment;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Agricultural lending portfolio with live loan, disbursement, and repayment workflows.
 */
class FinancialInstitutionDashboardService
{
    /** @var list<string> */
    private const TABS = ['overview', 'loan-applications', 'loan-portfolio', 'repayments', 'risk-assessment', 'farmers'];

    /** @var list<string> */
    public const SECTORS = [
        'Crop Farming',
        'Livestock',
        'Poultry',
        'Aquaculture',
        'Agro-processing',
        'Storage',
        'Mechanization',
        'Others',
    ];

    public function __construct(
        protected DigitalWalletService $walletService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(User $user, string $tab = 'overview', ?string $sector = null): array
    {
        $this->ensureDemoApplications();

        $tab = in_array($tab, self::TABS, true) ? $tab : 'overview';
        $sectorFilter = $sector && in_array($sector, self::SECTORS, true) ? $sector : null;

        $applications = $this->applications($sectorFilter);

        return [
            'tab' => $tab,
            'sector' => $sectorFilter,
            'sectors' => self::SECTORS,
            'kpis' => $this->kpis($sectorFilter),
            'portfolio' => $this->portfolioBySector(),
            'repayment' => $this->repaymentTrend(),
            'applications' => $applications->map(fn (LoanApplication $loan) => $this->presentApplication($loan, $user))->all(),
            'farmers' => $this->farmers(),
            'risk' => $this->riskAssessment($sectorFilter),
            'recent_repayments' => $this->recentRepayments(),
            'actions' => [
                'apply_url' => route('financial.applications.store'),
                'export_url' => route('financial.export', array_filter(['sector' => $sectorFilter])),
                'filter_url' => route('financial.dashboard'),
            ],
            'notifications_count' => max(
                1,
                LoanApplication::query()
                    ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::UnderReview])
                    ->count()
            ),
        ];
    }

    /**
     * @param  array{borrower: string, sector: string, amount: int, purpose?: string|null}  $data
     */
    public function applyForLoan(User $user, array $data): LoanApplication
    {
        if (! in_array($data['sector'], self::SECTORS, true)) {
            throw new BusinessLogicException('Select a valid loan sector.');
        }

        $amount = (int) $data['amount'];
        if ($amount < 100_000) {
            throw new BusinessLogicException('Minimum loan request is ₦100,000.');
        }

        if ($amount > 100_000_000) {
            throw new BusinessLogicException('Loan amount is too large.');
        }

        $open = LoanApplication::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::UnderReview])
            ->exists();

        if ($open) {
            throw new BusinessLogicException('You already have a loan application under review.');
        }

        return LoanApplication::query()->create([
            'user_id' => $user->id,
            'borrower' => $data['borrower'],
            'sector' => $data['sector'],
            'purpose' => $data['purpose'] ?? null,
            'amount' => $amount,
            'amount_repaid' => 0,
            'status' => ApplicationStatus::Pending,
        ]);
    }

    public function approveApplication(LoanApplication $application, User $reviewer): LoanApplication
    {
        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::UnderReview], true)) {
            throw new BusinessLogicException('Only pending loan applications can be approved.');
        }

        return DB::transaction(function () use ($application, $reviewer): LoanApplication {
            $application = LoanApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();

            if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::UnderReview], true)) {
                throw new BusinessLogicException('Only pending loan applications can be approved.');
            }

            $application->forceFill([
                'status' => ApplicationStatus::Approved,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'disbursed_at' => now(),
            ])->save();

            if ($application->user_id) {
                $borrower = User::query()->find($application->user_id);
                if ($borrower) {
                    $this->walletService->ensureWallet($borrower);
                    $this->walletService->creditLoanDisbursement(
                        $borrower,
                        $application->amount,
                        $application,
                        'Loan disbursed to '.$application->borrower.' ('.$application->sector.')'
                    );
                }
            }

            return $application->fresh();
        });
    }

    public function rejectApplication(LoanApplication $application, User $reviewer): LoanApplication
    {
        if (! in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::UnderReview], true)) {
            throw new BusinessLogicException('Only pending loan applications can be rejected.');
        }

        $application->forceFill([
            'status' => ApplicationStatus::Rejected,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewer->id,
        ])->save();

        return $application->fresh();
    }

    /**
     * Record a repayment against an approved / disbursed loan.
     */
    public function repayLoan(User $user, LoanApplication $application, int $amount, ?string $note = null): LoanRepayment
    {
        if (! $application->isDisbursed()) {
            throw new BusinessLogicException('Only disbursed loans can receive repayments.');
        }

        if ($application->user_id && $application->user_id !== $user->id) {
            // FI staff can also post repayments on behalf of seeded loans without owners.
            // For owned loans, only the borrower can repay from wallet.
            throw new BusinessLogicException('Only the borrower can repay this loan from their wallet.', statusCode: 403);
        }

        $outstanding = $application->outstandingAmount();
        if ($amount < 1 || $amount > $outstanding) {
            throw new BusinessLogicException('Repayment must be between ₦1 and the outstanding balance (₦'.number_format($outstanding).').');
        }

        return DB::transaction(function () use ($user, $application, $amount, $note): LoanRepayment {
            $application = LoanApplication::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
            $outstanding = $application->outstandingAmount();

            if ($amount > $outstanding) {
                throw new BusinessLogicException('Repayment exceeds outstanding balance.');
            }

            if ($application->user_id) {
                $borrower = User::query()->findOrFail($application->user_id);
                $this->walletService->ensureWallet($borrower);
                $this->walletService->debitLoanRepayment(
                    $borrower,
                    $amount,
                    $application,
                    'Repayment on '.$application->borrower.' loan'
                );
            }

            $repayment = LoanRepayment::query()->create([
                'loan_application_id' => $application->id,
                'user_id' => $application->user_id ?: $user->id,
                'amount' => $amount,
                'note' => $note,
                'paid_at' => now(),
            ]);

            $repaid = $application->amount_repaid + $amount;
            $application->forceFill([
                'amount_repaid' => $repaid,
                'closed_at' => $repaid >= $application->amount ? now() : null,
            ])->save();

            return $repayment;
        });
    }

    public function exportPortfolioCsv(?string $sector = null): StreamedResponse
    {
        $data = $this->getDashboardData(new User, 'overview', $sector);

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CyraAgroLink Financial Institution Portfolio', now()->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['KPI', 'Value']);
            foreach ($data['kpis'] as $kpi) {
                fputcsv($out, [$kpi['label'], $kpi['value']]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Sector', 'Share %']);
            foreach ($data['portfolio']['labels'] as $i => $label) {
                fputcsv($out, [$label, $data['portfolio']['values'][$i] ?? 0]);
            }
            fputcsv($out, []);
            fputcsv($out, ['Borrower', 'Sector', 'Amount', 'Outstanding', 'Status']);
            foreach ($data['applications'] as $app) {
                fputcsv($out, [$app['borrower'], $app['sector'], $app['amount'], $app['outstanding'], $app['status']]);
            }
            fclose($out);
        }, 'fi-loan-portfolio-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    protected function kpis(?string $sector): array
    {
        $base = LoanApplication::query()->when($sector, fn ($q) => $q->where('sector', $sector));

        $approved = (int) (clone $base)->where('status', ApplicationStatus::Approved)->sum('amount');
        $pendingAmount = (int) (clone $base)
            ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::UnderReview])
            ->sum('amount');
        $repaid = (int) (clone $base)->where('status', ApplicationStatus::Approved)->sum('amount_repaid');
        $outstanding = max(0, $approved - $repaid);

        $borrowers = (int) (clone $base)->distinct()->count('borrower');
        $total = max($approved + $pendingAmount, 1);
        $nplBase = (int) (clone $base)
            ->where('status', ApplicationStatus::Approved)
            ->where('amount_repaid', 0)
            ->where('disbursed_at', '<=', now()->subDays(90))
            ->count();
        $approvedCount = max(1, (int) (clone $base)->where('status', ApplicationStatus::Approved)->count());
        $npl = round(($nplBase / $approvedCount) * 100, 1);

        return [
            ['label' => 'Total Loans', 'value' => '₦'.$this->formatCompact($approved + $pendingAmount)],
            ['label' => 'Active Loans', 'value' => '₦'.$this->formatCompact($outstanding > 0 ? $outstanding : $approved)],
            ['label' => 'Total Borrowers', 'value' => number_format(max($borrowers, 0))],
            ['label' => 'NPL Ratio', 'value' => min(24.9, max(0.5, $npl)).'%'],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    protected function portfolioBySector(): array
    {
        $rows = LoanApplication::query()
            ->where('status', ApplicationStatus::Approved)
            ->selectRaw('sector, SUM(amount) as total')
            ->groupBy('sector')
            ->orderByDesc('total')
            ->get();

        if ($rows->isEmpty()) {
            return [
                'labels' => ['Crop Farming', 'Livestock', 'Poultry', 'Aquaculture', 'Others'],
                'values' => [35, 20, 15, 15, 15],
                'colors' => ['#5C4033', '#2F8F4E', '#0A5C2E', '#E6A817', '#7BC47F'],
            ];
        }

        $sum = max((int) $rows->sum('total'), 1);
        $values = $rows->map(fn ($row) => (int) round(((int) $row->total / $sum) * 100))->all();
        $drift = 100 - array_sum($values);
        if ($values !== []) {
            $values[0] += $drift;
        }

        return [
            'labels' => $rows->pluck('sector')->all(),
            'values' => array_values($values),
            'colors' => ['#5C4033', '#2F8F4E', '#0A5C2E', '#E6A817', '#7BC47F', '#0EA5E9', '#C4782B', '#6B7280'],
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    protected function repaymentTrend(): array
    {
        $months = [];
        $values = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M');
            $values[] = (int) LoanRepayment::query()
                ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum('amount');
        }

        // Chart expects a readable index when no repayments yet.
        if (array_sum($values) === 0) {
            return [
                'labels' => $months,
                'values' => [72, 65, 78, 86, 82, 98],
            ];
        }

        $max = max(1, ...$values);

        return [
            'labels' => $months,
            'values' => array_map(fn (int $v) => (int) max(5, round(($v / $max) * 100)), $values),
        ];
    }

    /**
     * @return Collection<int, LoanApplication>
     */
    protected function applications(?string $sector): Collection
    {
        return LoanApplication::query()
            ->when($sector, fn ($q) => $q->where('sector', $sector))
            ->latest('id')
            ->limit(25)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    protected function farmers(): Collection
    {
        return User::query()
            ->where('role', UserRole::Farmer)
            ->latest('id')
            ->limit(20)
            ->get();
    }

    /**
     * @return list<array{label: string, value: string, tone: string}>
     */
    protected function riskAssessment(?string $sector): array
    {
        $base = LoanApplication::query()->when($sector, fn ($q) => $q->where('sector', $sector));
        $pending = (int) (clone $base)
            ->whereIn('status', [ApplicationStatus::Pending, ApplicationStatus::UnderReview])
            ->count();

        $cropShare = (int) LoanApplication::query()
            ->where('status', ApplicationStatus::Approved)
            ->where('sector', 'Crop Farming')
            ->sum('amount');
        $approvedTotal = max(1, (int) LoanApplication::query()
            ->where('status', ApplicationStatus::Approved)
            ->sum('amount'));
        $concentration = (int) round(($cropShare / $approvedTotal) * 100);

        $repaidLast90 = (int) LoanRepayment::query()
            ->where('paid_at', '>=', now()->subDays(90))
            ->sum('amount');

        return [
            [
                'label' => 'Applications awaiting review',
                'value' => (string) $pending,
                'tone' => $pending > 3 ? 'warning' : 'info',
            ],
            [
                'label' => 'Portfolio concentration (crops)',
                'value' => $concentration.'% · '.($concentration >= 55 ? 'Elevated' : 'Moderate'),
                'tone' => $concentration >= 55 ? 'warning' : 'info',
            ],
            [
                'label' => 'Repayment outlook (90 days)',
                'value' => $repaidLast90 > 0 ? '₦'.$this->formatCompact($repaidLast90).' collected' : 'Stable',
                'tone' => 'success',
            ],
        ];
    }

    /**
     * @return list<array{borrower: string, amount: string, paid_at: string, note: string}>
     */
    protected function recentRepayments(): array
    {
        return LoanRepayment::query()
            ->with('loan')
            ->latest('paid_at')
            ->limit(8)
            ->get()
            ->map(fn (LoanRepayment $row) => [
                'borrower' => $row->loan?->borrower ?? 'Borrower',
                'amount' => '₦'.number_format($row->amount),
                'paid_at' => $row->paid_at?->format('d M Y') ?? '',
                'note' => $row->note ?: 'Repayment',
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentApplication(LoanApplication $application, User $user): array
    {
        $canRepay = $application->isDisbursed()
            && $application->outstandingAmount() > 0
            && ($application->user_id === null || $application->user_id === $user->id);

        return [
            'id' => $application->id,
            'borrower' => $application->borrower,
            'sector' => $application->sector,
            'purpose' => $application->purpose,
            'amount' => $application->formattedAmount(),
            'outstanding' => '₦'.number_format($application->outstandingAmount()),
            'status' => $application->status->label(),
            'status_value' => $application->status->value,
            'date' => $application->created_at?->format('d M Y') ?? '',
            'can_review' => in_array($application->status, [ApplicationStatus::Pending, ApplicationStatus::UnderReview], true),
            'can_repay' => $canRepay,
            'approve_url' => route('financial.applications.approve', $application),
            'reject_url' => route('financial.applications.reject', $application),
            'repay_url' => route('financial.applications.repay', $application),
        ];
    }

    protected function ensureDemoApplications(): void
    {
        if (! \App\Support\DemoSeeding::allowed()) {
            return;
        }

        if (LoanApplication::query()->exists()) {
            return;
        }

        $seed = [
            ['borrower' => 'Green Valley Farms', 'sector' => 'Crop Farming', 'purpose' => 'Seasonal inputs', 'amount' => 2000000, 'status' => ApplicationStatus::UnderReview],
            ['borrower' => 'Sunrise Farms', 'sector' => 'Livestock', 'purpose' => 'Feed and housing', 'amount' => 1500000, 'status' => ApplicationStatus::Approved, 'disbursed' => true],
            ['borrower' => "Nature's Pride", 'sector' => 'Agro-processing', 'purpose' => 'Mill upgrade', 'amount' => 2200000, 'status' => ApplicationStatus::Pending],
            ['borrower' => 'Delta Rice Mills', 'sector' => 'Storage', 'purpose' => 'Warehouse expansion', 'amount' => 3500000, 'status' => ApplicationStatus::Approved, 'disbursed' => true],
            ['borrower' => 'Plateau Poultry', 'sector' => 'Poultry', 'purpose' => 'Broiler cycle finance', 'amount' => 1250000, 'status' => ApplicationStatus::Pending],
            ['borrower' => 'Rivers Fish Growers', 'sector' => 'Aquaculture', 'purpose' => 'Pond restocking', 'amount' => 1800000, 'status' => ApplicationStatus::Pending],
        ];

        foreach ($seed as $row) {
            $disbursed = (bool) ($row['disbursed'] ?? false);
            unset($row['disbursed']);

            LoanApplication::query()->create([
                ...$row,
                'amount_repaid' => 0,
                'reviewed_at' => $row['status'] === ApplicationStatus::Approved ? now()->subDays(20) : null,
                'disbursed_at' => $disbursed ? now()->subDays(18) : null,
            ]);
        }
    }

    protected function formatCompact(int $value): string
    {
        if ($value >= 1_000_000_000) {
            return round($value / 1_000_000_000, 1).'B';
        }

        if ($value >= 1_000_000) {
            return round($value / 1_000_000, 1).'M';
        }

        if ($value >= 1_000) {
            return round($value / 1_000, 1).'K';
        }

        return number_format($value);
    }
}
