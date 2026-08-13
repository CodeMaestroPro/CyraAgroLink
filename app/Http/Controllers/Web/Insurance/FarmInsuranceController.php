<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Insurance;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\InsuranceClaim;
use App\Services\Insurance\FarmInsuranceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Farm insurance platform: browse plans, buy policies, and manage claims.
 */
class FarmInsuranceController extends Controller
{
    public function __construct(
        protected FarmInsuranceService $farmInsuranceService
    ) {
    }

    /**
     * Display the farm insurance platform.
     */
    public function index(Request $request): View
    {
        $data = $this->farmInsuranceService->getPlatformData($request->user());

        return view('insurance.platform', [
            'kpis' => $data['kpis'],
            'plans' => $data['plans'],
            'policies' => $data['policies'],
            'claims' => $data['claims'],
            'farms' => $data['farms'],
            'claimablePolicies' => $data['claimable_policies'],
            'walletBalance' => $data['wallet_balance'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Purchase a policy for a selected farm.
     */
    public function purchase(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:insurance_plans,id'],
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
        ]);

        try {
            $policy = $this->farmInsuranceService->purchasePolicy(
                $request->user(),
                (int) $data['plan_id'],
                (int) $data['farm_id']
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('insurance.platform')
                ->with('error', $e->getMessage())
                ->withFragment('buy');
        }

        return redirect()
            ->route('insurance.platform')
            ->with(
                'status',
                "{$policy->reference}: {$policy->plan?->name} activated. Premium ₦".number_format($policy->premium_ngn).' charged to wallet.'
            )
            ->withFragment('policies');
    }

    /**
     * File a claim against an active policy.
     */
    public function fileClaim(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'policy_id' => ['required', 'integer', 'exists:insurance_policies,id'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'amount_requested_ngn' => ['required', 'integer', 'min:1', 'max:50000000'],
        ]);

        try {
            $claim = $this->farmInsuranceService->fileClaim($request->user(), $data);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('insurance.platform')
                ->with('error', $e->getMessage())
                ->withFragment('file-claim');
        }

        return redirect()
            ->route('insurance.platform')
            ->with('status', "{$claim->reference}: claim submitted for review.")
            ->withFragment('claims');
    }

    /**
     * Advance or reject a claim in the demo adjuster workflow.
     */
    public function advanceClaim(Request $request, InsuranceClaim $claim): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['nullable', 'in:next,reject'],
        ]);

        try {
            $updated = $this->farmInsuranceService->advanceClaim(
                $request->user(),
                $claim,
                $data['action'] ?? 'next'
            );
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('insurance.platform')
                ->with('error', $e->getMessage())
                ->withFragment('claims');
        }

        $message = match ($updated->status) {
            'under_review' => "{$updated->reference} moved to under review.",
            'approved' => "{$updated->reference} approved for ₦".number_format((int) $updated->amount_paid_ngn).'.',
            'paid' => "{$updated->reference} paid. ₦".number_format((int) $updated->amount_paid_ngn).' credited to wallet.',
            'rejected' => "{$updated->reference} rejected.",
            default => "{$updated->reference} updated.",
        };

        return redirect()
            ->route('insurance.platform')
            ->with('status', $message)
            ->withFragment('claims');
    }
}
