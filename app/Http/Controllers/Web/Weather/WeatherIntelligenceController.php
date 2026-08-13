<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Weather;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\WeatherAlert;
use App\Services\Weather\WeatherIntelligenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Weather intelligence overview dashboard.
 */
class WeatherIntelligenceController extends Controller
{
    public function __construct(
        protected WeatherIntelligenceService $weatherIntelligenceService
    ) {
    }

    /**
     * Display the weather intelligence overview.
     */
    public function index(Request $request): View
    {
        $data = $this->weatherIntelligenceService->getOverviewData(
            $request->user(),
            $request->string('location')->toString() ?: null
        );

        return view('weather.intelligence', [
            'location' => $data['location'],
            'locationOptions' => $data['location_options'],
            'current' => $data['current'],
            'forecast' => $data['forecast'],
            'rainfallZones' => $data['rainfall_zones'],
            'alerts' => $data['alerts'],
            'aiRecommendation' => $data['ai_recommendation'],
            'history' => $data['history'],
            'source' => $data['source'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Refresh weather for the selected location.
     */
    public function refresh(Request $request): RedirectResponse
    {
        $location = $request->string('location')->toString() ?: null;
        $snapshot = $this->weatherIntelligenceService->refresh($request->user(), $location);

        return redirect()
            ->route('weather.intelligence', array_filter(['location' => $location]))
            ->with('status', 'Weather refreshed for '.$snapshot->location_label.' ('.$snapshot->temperature_c.'°C).')
            ->withFragment('overview');
    }

    /**
     * Acknowledge an open weather alert.
     */
    public function acknowledge(Request $request, WeatherAlert $alert): RedirectResponse
    {
        try {
            $this->weatherIntelligenceService->acknowledgeAlert($request->user(), $alert);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('weather.intelligence')
                ->with('error', $e->getMessage())
                ->withFragment('alerts');
        }

        return redirect()
            ->route('weather.intelligence', array_filter(['location' => $request->input('location')]))
            ->with('status', 'Alert acknowledged.')
            ->withFragment('alerts');
    }

    /**
     * Dismiss a weather alert.
     */
    public function dismiss(Request $request, WeatherAlert $alert): RedirectResponse
    {
        try {
            $this->weatherIntelligenceService->dismissAlert($request->user(), $alert);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('weather.intelligence')
                ->with('error', $e->getMessage())
                ->withFragment('alerts');
        }

        return redirect()
            ->route('weather.intelligence', array_filter(['location' => $request->input('location')]))
            ->with('status', 'Alert dismissed.')
            ->withFragment('alerts');
    }

    /**
     * Download a CSV weather report.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->weatherIntelligenceService->exportReport(
            $request->user(),
            $request->string('location')->toString() ?: null
        );
    }
}
