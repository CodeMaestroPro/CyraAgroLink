<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\FinancialInstitution;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Services\FinancialInstitution\FinancialInstitutionDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Agricultural lending portfolio overview for financial institutions.
 */
class FinancialInstitutionDashboardController extends Controller
{
    public function __construct(
        protected FinancialInstitutionDashboardService $financialInstitutionDashboardService
    ) {
    }

    /**
     * Display the financial institution dashboard.
     */
    public function show(Request $request): View
    {
        $data = $this->financialInstitutionDashboardService->getDashboardData(
            $request->user(),
            $request->string('tab')->toString() ?: 'overview',
            $request->string('sector')->toString() ?: null
        );

        return view('financial-institution.dashboard', [
            'tab' => $data['tab'],
            'sector' => $data['sector'],
            'sectors' => $data['sectors'],
            'kpis' => $data['kpis'],
            'portfolio' => $data['portfolio'],
            'repayment' => $data['repayment'],
            'applications' => $data['applications'],
            'farmers' => $data['farmers'],
            'risk' => $data['risk'],
            'recentRepayments' => $data['recent_repayments'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Submit a new loan application.
     */
    public function storeApplication(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'borrower' => ['required', 'string', 'max:150'],
            'sector' => ['required', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:100000', 'max:100000000'],
            'purpose' => ['nullable', 'string', 'max:200'],
        ]);

        try {
            $application = $this->financialInstitutionDashboardService->applyForLoan($request->user(), $data);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('financial.dashboard', ['tab' => 'loan-applications'])
                ->with('error', $e->getMessage())
                ->withFragment('apply-loan');
        }

        return redirect()
            ->route('financial.dashboard', ['tab' => 'loan-applications'])
            ->with('status', 'Loan application submitted for '.$application->borrower.'.')
            ->withFragment('recent-applications-heading');
    }

    /**
     * Approve and disburse a pending loan application.
     */
    public function approveApplication(Request $request, LoanApplication $application): RedirectResponse
    {
        try {
            $this->financialInstitutionDashboardService->approveApplication($application, $request->user());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('financial.dashboard', ['tab' => 'loan-applications'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('financial.dashboard', ['tab' => 'loan-applications'])
            ->with('status', 'Loan approved'.($application->user_id ? ' and disbursed to borrower wallet.' : '.'));
    }

    /**
     * Reject a pending loan application.
     */
    public function rejectApplication(Request $request, LoanApplication $application): RedirectResponse
    {
        try {
            $this->financialInstitutionDashboardService->rejectApplication($application, $request->user());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('financial.dashboard', ['tab' => 'loan-applications'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('financial.dashboard', ['tab' => 'loan-applications'])
            ->with('status', 'Loan application rejected.');
    }

    /**
     * Record a loan repayment.
     */
    public function repayApplication(Request $request, LoanApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->financialInstitutionDashboardService->repayLoan(
                $request->user(),
                $application,
                (int) $data['amount'],
                $data['note'] ?? null
            );
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('financial.dashboard', ['tab' => 'repayments'])
                ->with('error', $e->getMessage())
                ->withFragment('recent-applications-heading');
        }

        return redirect()
            ->route('financial.dashboard', ['tab' => 'repayments'])
            ->with('status', 'Repayment recorded: ₦'.number_format((int) $data['amount']))
            ->withFragment('recent-applications-heading');
    }

    /**
     * Export portfolio CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->financialInstitutionDashboardService->exportPortfolioCsv(
            $request->string('sector')->toString() ?: null
        );
    }
}
