<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Reporting;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\CustomReportRequest;
use App\Services\Reporting\ReportingAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Business reporting and analytics overview.
 */
class ReportingAnalyticsController extends Controller
{
    public function __construct(
        protected ReportingAnalyticsService $reportingAnalyticsService
    ) {
    }

    /**
     * Display the reporting and analytics overview.
     */
    public function index(Request $request): View
    {
        $period = $request->string('period')->toString() ?: '6m';
        $data = $this->reportingAnalyticsService->getOverviewData($request->user(), $period);

        return view('reporting.analytics', [
            'period' => $data['period'],
            'periodOptions' => $data['period_options'],
            'kpis' => $data['kpis'],
            'revenueTrend' => $data['revenue_trend'],
            'transactions' => $data['transactions'],
            'segments' => $data['segments'],
            'regions' => $data['regions'],
            'operations' => $data['operations'],
            'financial' => $data['financial'],
            'customReports' => $data['custom_reports'],
            'snapshotAt' => $data['snapshot_at'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Refresh analytics from live platform data.
     */
    public function refresh(Request $request): RedirectResponse
    {
        $period = $request->string('period')->toString() ?: '6m';
        $snapshot = $this->reportingAnalyticsService->refresh($request->user(), $period);

        return redirect()
            ->route('reporting.analytics', ['period' => $period])
            ->with('status', 'Analytics refreshed: ₦'.number_format($snapshot->revenue_ngn).' revenue across '.$snapshot->transactions_count.' transactions.')
            ->withFragment('overview');
    }

    /**
     * Queue / generate a custom report.
     */
    public function storeCustom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'report_type' => ['required', 'string', 'in:financial,operations,segment,regional,custom'],
            'period' => ['nullable', 'string', 'in:3m,6m,12m,ytd'],
            'segment' => ['nullable', 'string', 'in:Marketplace,Investments,Logistics,Warehouse,Others'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $report = $this->reportingAnalyticsService->createCustomReport($request->user(), $data);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('reporting.analytics', ['period' => $request->input('period', '6m')])
                ->with('error', $e->getMessage())
                ->withFragment('custom');
        }

        return redirect()
            ->route('reporting.analytics', ['period' => $report->period])
            ->with('status', 'Custom report ready: '.$report->title)
            ->withFragment('custom');
    }

    /**
     * Download a custom report CSV.
     */
    public function downloadCustom(Request $request, CustomReportRequest $report): StreamedResponse|RedirectResponse
    {
        try {
            return $this->reportingAnalyticsService->downloadCustomReport($request->user(), $report);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('reporting.analytics')
                ->with('error', $e->getMessage())
                ->withFragment('custom');
        }
    }

    /**
     * Export the main analytics CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->reportingAnalyticsService->exportReport(
            $request->user(),
            $request->string('period')->toString() ?: '6m'
        );
    }
}
