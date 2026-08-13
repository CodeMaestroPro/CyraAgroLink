<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Risk;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\RiskAlert;
use App\Models\RiskMitigation;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AI risk intelligence center for agricultural risk monitoring.
 */
class RiskIntelligenceController extends Controller
{
    public function __construct(
        protected RiskIntelligenceService $riskIntelligenceService
    ) {
    }

    /**
     * Display the AI risk intelligence center.
     */
    public function index(Request $request): View
    {
        $data = $this->riskIntelligenceService->getCenterData($request->user());

        return view('risk.intelligence', [
            'score' => $data['score'],
            'categories' => $data['categories'],
            'alerts' => $data['alerts'],
            'mitigations' => $data['mitigations'],
            'report' => $data['report'],
            'gauge' => $data['gauge'],
            'farmsCount' => $data['farms_count'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Recalculate risk from live farm and market signals.
     */
    public function refresh(Request $request): RedirectResponse
    {
        $assessment = $this->riskIntelligenceService->recalculate($request->user());

        return redirect()
            ->route('risk.intelligence')
            ->with('status', 'Risk score refreshed: '.$assessment->overall_score.' ('.ucfirst($assessment->status).').')
            ->withFragment('score');
    }

    /**
     * Acknowledge an open risk alert.
     */
    public function acknowledge(Request $request, RiskAlert $alert): RedirectResponse
    {
        try {
            $this->riskIntelligenceService->acknowledgeAlert($request->user(), $alert);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('risk.intelligence')
                ->with('error', $e->getMessage())
                ->withFragment('alerts');
        }

        return redirect()
            ->route('risk.intelligence')
            ->with('status', 'Alert acknowledged.')
            ->withFragment('alerts');
    }

    /**
     * Dismiss a risk alert.
     */
    public function dismiss(Request $request, RiskAlert $alert): RedirectResponse
    {
        try {
            $this->riskIntelligenceService->dismissAlert($request->user(), $alert);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('risk.intelligence')
                ->with('error', $e->getMessage())
                ->withFragment('alerts');
        }

        return redirect()
            ->route('risk.intelligence')
            ->with('status', 'Alert dismissed.')
            ->withFragment('alerts');
    }

    /**
     * Create a mitigation action.
     */
    public function storeMitigation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'action_type' => ['required', 'string', 'in:insure,logistics_review,market_hedge,crop_scouting,wallet_topup,other'],
            'alert_id' => ['nullable', 'integer', 'exists:risk_alerts,id'],
        ]);

        try {
            $mitigation = $this->riskIntelligenceService->createMitigation($request->user(), $data);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('risk.intelligence')
                ->with('error', $e->getMessage())
                ->withFragment('mitigations');
        }

        return redirect()
            ->route('risk.intelligence')
            ->with('status', 'Mitigation planned: '.$mitigation->title)
            ->withFragment('mitigations');
    }

    /**
     * Mark a mitigation as done.
     */
    public function completeMitigation(Request $request, RiskMitigation $mitigation): RedirectResponse
    {
        try {
            $this->riskIntelligenceService->completeMitigation($request->user(), $mitigation);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('risk.intelligence')
                ->with('error', $e->getMessage())
                ->withFragment('mitigations');
        }

        return redirect()
            ->route('risk.intelligence')
            ->with('status', 'Mitigation marked done.')
            ->withFragment('mitigations');
    }

    /**
     * Download a CSV risk report.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->riskIntelligenceService->exportReport($request->user());
    }
}
