<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Cooperative;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\CooperativeLoan;
use App\Models\CooperativeVote;
use App\Services\Cooperative\CooperativeManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Smart cooperative management for members, savings, loans, and votes.
 */
class CooperativeManagementController extends Controller
{
    public function __construct(
        protected CooperativeManagementService $cooperativeManagementService
    ) {
    }

    /**
     * Display the smart cooperative management dashboard.
     */
    public function index(Request $request): View
    {
        $data = $this->cooperativeManagementService->getDashboardData($request->user());

        return view('cooperative.management', [
            'cooperative' => $data['cooperative'],
            'kpis' => $data['kpis'],
            'activities' => $data['activities'],
            'vote' => $data['vote'],
            'members' => $data['members'],
            'loans' => $data['loans'],
            'walletBalance' => $data['wallet_balance'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Contribute savings from the wallet into the cooperative pool.
     */
    public function contribute(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1000', 'max:50000000'],
        ]);

        try {
            $activity = $this->cooperativeManagementService->contribute(
                $request->user(),
                (int) $data['amount']
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('cooperative.management')
                ->with('error', $e->getMessage())
                ->withFragment('savings');
        }

        return redirect()
            ->route('cooperative.management')
            ->with('status', 'Contribution recorded: '.$activity->value)
            ->withFragment('savings');
    }

    /**
     * Request a group loan from cooperative savings.
     */
    public function storeLoan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:5000', 'max:50000000'],
            'purpose' => ['required', 'string', 'max:200'],
        ]);

        try {
            $loan = $this->cooperativeManagementService->requestLoan(
                $request->user(),
                (int) $data['amount'],
                $data['purpose']
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('cooperative.management')
                ->with('error', $e->getMessage())
                ->withFragment('loans');
        }

        return redirect()
            ->route('cooperative.management')
            ->with('status', 'Loan request submitted ('.$loan->reference.').')
            ->withFragment('loans');
    }

    /**
     * Approve/disburse or reject a pending loan.
     */
    public function reviewLoan(Request $request, CooperativeLoan $loan): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approve,reject'],
        ]);

        try {
            $updated = $this->cooperativeManagementService->reviewLoan(
                $request->user(),
                $loan,
                $data['decision']
            );
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('cooperative.management')
                ->with('error', $e->getMessage())
                ->withFragment('loans');
        }

        $message = $updated->status === 'rejected'
            ? 'Loan rejected.'
            : 'Loan disbursed to member wallet.';

        return redirect()
            ->route('cooperative.management')
            ->with('status', $message)
            ->withFragment('loans');
    }

    /**
     * Repay a disbursed cooperative loan.
     */
    public function repayLoan(Request $request, CooperativeLoan $loan): RedirectResponse
    {
        try {
            $this->cooperativeManagementService->repayLoan($request->user(), $loan);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('cooperative.management')
                ->with('error', $e->getMessage())
                ->withFragment('loans');
        }

        return redirect()
            ->route('cooperative.management')
            ->with('status', 'Loan repaid to the cooperative pool.')
            ->withFragment('loans');
    }

    /**
     * Create a new cooperative vote.
     */
    public function storeVote(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:500'],
            'closes_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        try {
            $vote = $this->cooperativeManagementService->createVote($request->user(), $data);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('cooperative.management')
                ->with('error', $e->getMessage())
                ->withFragment('vote');
        }

        return redirect()
            ->route('cooperative.management')
            ->with('status', 'Vote opened: '.$vote->title)
            ->withFragment('vote');
    }

    /**
     * Cast a yes/no ballot.
     */
    public function castVote(Request $request, CooperativeVote $vote): RedirectResponse
    {
        $data = $request->validate([
            'choice' => ['required', 'string', 'in:yes,no'],
        ]);

        try {
            $this->cooperativeManagementService->castVote(
                $request->user(),
                $vote,
                $data['choice']
            );
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('cooperative.management')
                ->with('error', $e->getMessage())
                ->withFragment('vote');
        }

        return redirect()
            ->route('cooperative.management')
            ->with('status', 'Your vote has been recorded.')
            ->withFragment('vote');
    }
}
