<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Government;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\GovernmentPolicy;
use App\Models\SubsidyApplication;
use App\Services\Government\GovernmentDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * National agricultural overview for government stakeholders.
 */
class GovernmentDashboardController extends Controller
{
    public function __construct(
        protected GovernmentDashboardService $governmentDashboardService
    ) {
    }

    /**
     * Display the government dashboard.
     */
    public function show(Request $request): View
    {
        $data = $this->governmentDashboardService->getDashboardData(
            $request->user(),
            $request->string('tab')->toString() ?: 'overview',
            $request->string('state')->toString() ?: null
        );

        return view('government.dashboard', [
            'tab' => $data['tab'],
            'state' => $data['state'],
            'states' => $data['states'],
            'programs' => $data['programs'],
            'kpis' => $data['kpis'],
            'production' => $data['production'],
            'mapZones' => $data['map_zones'],
            'subsidies' => $data['subsidies'],
            'subsidyApplications' => $data['subsidy_applications'],
            'farmers' => $data['farmers'],
            'policies' => $data['policies'],
            'foodSecurity' => $data['food_security'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Submit a new subsidy application.
     */
    public function applySubsidy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'program' => ['required', 'string', 'max:120'],
            'beneficiary_name' => ['required', 'string', 'max:150'],
            'state' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:50000', 'max:50000000'],
        ]);

        try {
            $application = $this->governmentDashboardService->applySubsidy($request->user(), $data);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('government.dashboard', ['tab' => 'subsidies', 'state' => $request->input('state')])
                ->with('error', $e->getMessage())
                ->withFragment('apply-subsidy');
        }

        return redirect()
            ->route('government.dashboard', array_filter([
                'tab' => 'subsidies',
                'state' => $data['state'] ?? null,
            ]))
            ->with('status', 'Subsidy application submitted for '.$application->program.'.')
            ->withFragment('subsidy-programs-heading');
    }

    /**
     * Approve a pending subsidy application.
     */
    public function approveSubsidy(Request $request, SubsidyApplication $subsidy): RedirectResponse
    {
        try {
            $this->governmentDashboardService->approveSubsidy($subsidy, $request->user());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('government.dashboard', ['tab' => 'subsidies'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('government.dashboard', ['tab' => 'subsidies'])
            ->with('status', 'Subsidy application approved'.($subsidy->user_id ? ' and disbursed to wallet.' : '.'));
    }

    /**
     * Reject a pending subsidy application.
     */
    public function rejectSubsidy(Request $request, SubsidyApplication $subsidy): RedirectResponse
    {
        try {
            $this->governmentDashboardService->rejectSubsidy($subsidy, $request->user());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('government.dashboard', ['tab' => 'subsidies'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('government.dashboard', ['tab' => 'subsidies'])
            ->with('status', 'Subsidy application rejected.');
    }

    /**
     * Create a new agricultural policy.
     */
    public function storePolicy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['required', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:draft,active,under_review'],
        ]);

        try {
            $policy = $this->governmentDashboardService->createPolicy($request->user(), $data);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('government.dashboard', ['tab' => 'policies'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('government.dashboard', ['tab' => 'policies'])
            ->with('status', 'Policy created: '.$policy->title);
    }

    /**
     * Update policy lifecycle status.
     */
    public function updatePolicyStatus(Request $request, GovernmentPolicy $policy): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:draft,active,under_review,archived'],
        ]);

        try {
            $updated = $this->governmentDashboardService->updatePolicyStatus(
                $request->user(),
                $policy,
                $data['status']
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('government.dashboard', ['tab' => 'policies'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('government.dashboard', ['tab' => 'policies'])
            ->with('status', 'Policy marked '.$updated->statusLabel().'.');
    }

    /**
     * Export the national overview as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->governmentDashboardService->exportOverviewCsv(
            $request->string('state')->toString() ?: null
        );
    }
}
