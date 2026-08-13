<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Intelligence;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\BiInsight;
use App\Services\Intelligence\BusinessIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Business Intelligence Command Center executive overview.
 */
class BusinessIntelligenceController extends Controller
{
    public function __construct(
        protected BusinessIntelligenceService $businessIntelligenceService
    ) {
    }

    /**
     * Display the BI command center.
     */
    public function index(Request $request): View
    {
        $period = $request->string('period')->toString() ?: '6m';
        $data = $this->businessIntelligenceService->getCommandCenterData($request->user(), $period);

        return view('intelligence.command-center', [
            'period' => $data['period'],
            'periodOptions' => $data['period_options'],
            'kpis' => $data['kpis'],
            'revenueTrend' => $data['revenue_trend'],
            'commodities' => $data['commodities'],
            'insights' => $data['insights'],
            'snapshotAt' => $data['snapshot_at'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Refresh BI KPIs and insights from live platform data.
     */
    public function refresh(Request $request): RedirectResponse
    {
        $period = $request->string('period')->toString() ?: '6m';
        $snapshot = $this->businessIntelligenceService->refresh($request->user(), $period);

        return redirect()
            ->route('intelligence.command', ['period' => $period])
            ->with('status', 'Command center refreshed: '.$snapshot->kpis[0]['value'].' revenue · '.$snapshot->farms_count.' active farms.')
            ->withFragment('summary');
    }

    /**
     * Add a manual executive insight.
     */
    public function storeInsight(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'detail' => ['required', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'in:revenue,farms,commodities,risk,ops'],
            'severity' => ['nullable', 'string', 'in:low,medium,high'],
            'period' => ['nullable', 'string', 'in:3m,6m,12m,ytd'],
        ]);

        try {
            $insight = $this->businessIntelligenceService->createInsight($request->user(), $data);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('intelligence.command', ['period' => $request->input('period', '6m')])
                ->with('error', $e->getMessage())
                ->withFragment('insights');
        }

        return redirect()
            ->route('intelligence.command', ['period' => $request->input('period', '6m')])
            ->with('status', 'Insight added: '.$insight->title)
            ->withFragment('insights');
    }

    /**
     * Acknowledge an open insight.
     */
    public function acknowledge(Request $request, BiInsight $insight): RedirectResponse
    {
        try {
            $this->businessIntelligenceService->acknowledgeInsight($request->user(), $insight);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('intelligence.command')
                ->with('error', $e->getMessage())
                ->withFragment('insights');
        }

        return redirect()
            ->route('intelligence.command', array_filter(['period' => $request->input('period')]))
            ->with('status', 'Insight acknowledged.')
            ->withFragment('insights');
    }

    /**
     * Pin an insight for the executive board.
     */
    public function pin(Request $request, BiInsight $insight): RedirectResponse
    {
        try {
            $this->businessIntelligenceService->pinInsight($request->user(), $insight);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('intelligence.command')
                ->with('error', $e->getMessage())
                ->withFragment('insights');
        }

        return redirect()
            ->route('intelligence.command', array_filter(['period' => $request->input('period')]))
            ->with('status', 'Insight pinned.')
            ->withFragment('insights');
    }

    /**
     * Dismiss an insight.
     */
    public function dismiss(Request $request, BiInsight $insight): RedirectResponse
    {
        try {
            $this->businessIntelligenceService->dismissInsight($request->user(), $insight);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('intelligence.command')
                ->with('error', $e->getMessage())
                ->withFragment('insights');
        }

        return redirect()
            ->route('intelligence.command', array_filter(['period' => $request->input('period')]))
            ->with('status', 'Insight dismissed.')
            ->withFragment('insights');
    }

    /**
     * Export BI command-center CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->businessIntelligenceService->exportReport(
            $request->user(),
            $request->string('period')->toString() ?: '6m'
        );
    }
}
