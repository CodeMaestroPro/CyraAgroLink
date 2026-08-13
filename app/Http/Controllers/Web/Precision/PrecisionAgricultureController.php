<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Precision;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Services\Precision\PrecisionAgricultureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Precision agriculture overview for soil, NDVI, and agronomy actions.
 */
class PrecisionAgricultureController extends Controller
{
    public function __construct(
        protected PrecisionAgricultureService $precisionAgricultureService
    ) {
    }

    /**
     * Display the precision agriculture overview.
     */
    public function index(Request $request): View
    {
        $farmId = $request->integer('farm') ?: null;
        $data = $this->precisionAgricultureService->getOverviewData($request->user(), $farmId);

        return view('precision.agriculture', [
            'farm' => $data['farm'],
            'farms' => $data['farms'],
            'soil' => $data['soil'],
            'irrigation' => $data['irrigation'],
            'fertilizer' => $data['fertilizer'],
            'ndviZones' => $data['ndvi_zones'],
            'map' => $data['map'],
            'recommendationDetail' => $data['recommendation_detail'],
            'actions' => $data['actions'],
            'lastScanAt' => $data['last_scan_at'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Refresh NDVI / soil snapshot for the selected farm.
     */
    public function scan(Request $request, Farm $farm): RedirectResponse
    {
        abort_unless($farm->user_id === $request->user()->id, 403);
        $this->precisionAgricultureService->runNdviScan($request->user(), $farm);

        return redirect()
            ->route('precision.agriculture', ['farm' => $farm->id])
            ->with('status', 'NDVI scan refreshed for this field.');
    }

    /**
     * Schedule the next irrigation window.
     */
    public function irrigate(Request $request, Farm $farm): RedirectResponse
    {
        abort_unless($farm->user_id === $request->user()->id, 403);
        $this->precisionAgricultureService->scheduleIrrigation($request->user(), $farm);

        return redirect()
            ->route('precision.agriculture', ['farm' => $farm->id])
            ->with('status', 'Irrigation window scheduled.');
    }

    /**
     * Apply the fertilizer recommendation and log it on the farm crop cycle.
     */
    public function fertilizer(Request $request, Farm $farm): RedirectResponse
    {
        abort_unless($farm->user_id === $request->user()->id, 403);
        $overlay = $this->precisionAgricultureService->applyFertilizerPlan($request->user(), $farm);
        $cropName = $overlay['fertilizer_crop_name'] ?? 'crop';

        return redirect()
            ->route('precision.agriculture', ['farm' => $farm->id])
            ->with('status', "Fertilizer plan applied and logged on {$cropName}.")
            ->withFragment('fertilizer');
    }
}
