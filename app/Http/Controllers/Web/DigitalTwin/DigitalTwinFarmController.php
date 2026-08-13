<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\DigitalTwin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Services\DigitalTwin\DigitalTwinFarmService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI digital twin farm overview for precision agriculture.
 */
class DigitalTwinFarmController extends Controller
{
    public function __construct(
        protected DigitalTwinFarmService $digitalTwinFarmService
    ) {
    }

    /**
     * Display the AI digital twin farm screen.
     */
    public function index(Request $request): View
    {
        $farmId = $request->integer('farm') ?: null;
        $data = $this->digitalTwinFarmService->getFarmData($request->user(), $farmId);

        return view('digital-twin.farm', [
            'farm' => $data['farm'],
            'farms' => $data['farms'],
            'kpis' => $data['kpis'],
            'widgets' => $data['widgets'],
            'plots' => $data['plots'],
            'map' => $data['map'],
            'alerts' => $data['alerts'],
            'actions' => $data['actions'],
            'lastScanAt' => $data['last_scan_at'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Run a digital twin health scan for the selected farm.
     */
    public function scan(Request $request, Farm $farm): RedirectResponse
    {
        abort_unless($farm->user_id === $request->user()->id, 403);

        $this->digitalTwinFarmService->runScan($request->user(), $farm);

        return redirect()
            ->route('digital.twin', ['farm' => $farm->id])
            ->with('status', 'Twin scan complete — metrics and alerts updated.');
    }

    /**
     * Simulate irrigation on the twin and raise soil moisture.
     */
    public function irrigate(Request $request, Farm $farm): RedirectResponse
    {
        abort_unless($farm->user_id === $request->user()->id, 403);

        $this->digitalTwinFarmService->simulateIrrigation($request->user(), $farm);

        return redirect()
            ->route('digital.twin', ['farm' => $farm->id])
            ->with('status', 'Irrigation applied on the digital twin.');
    }
}
