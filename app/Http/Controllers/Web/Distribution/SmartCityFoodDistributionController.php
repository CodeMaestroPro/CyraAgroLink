<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Distribution;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Distribution\StoreCityDeliveryRequest;
use App\Models\SmartCityDelivery;
use App\Models\SmartCityFleetUnit;
use App\Services\Distribution\SmartCityFoodDistributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Smart City Food Distribution operations overview.
 */
class SmartCityFoodDistributionController extends Controller
{
    public function __construct(
        protected SmartCityFoodDistributionService $smartCityFoodDistributionService
    ) {
    }

    /**
     * Display the smart city food distribution dashboard.
     */
    public function index(Request $request): View
    {
        $data = $this->smartCityFoodDistributionService->getDistributionData(
            $request->user(),
            $request->string('tab', 'overview')->toString()
        );

        return view('distribution.smart-city', [
            ...$data,
            'routePoints' => $data['route_points'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Schedule a city food delivery.
     */
    public function store(StoreCityDeliveryRequest $request): RedirectResponse
    {
        try {
            $delivery = $this->smartCityFoodDistributionService->createDelivery(
                $request->user(),
                $request->validated()
            );
        } catch (BusinessLogicException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('distribution.smart-city', ['tab' => 'deliveries'])
            ->with('status', "{$delivery->referenceLabel()} scheduled.");
    }

    /**
     * Optimize open delivery routes and assign fleet.
     */
    public function optimize(Request $request): RedirectResponse
    {
        try {
            $result = $this->smartCityFoodDistributionService->optimizeRoutes($request->user());
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('distribution.smart-city', ['tab' => 'overview'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('distribution.smart-city', ['tab' => 'overview'])
            ->with('status', "Optimized {$result['optimized']} deliveries and assigned {$result['assigned']} fleet units.");
    }

    /**
     * Advance a delivery milestone.
     */
    public function advance(Request $request, SmartCityDelivery $delivery): RedirectResponse
    {
        try {
            $updated = $this->smartCityFoodDistributionService->advanceDelivery($request->user(), $delivery);
        } catch (BusinessLogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('distribution.smart-city', ['tab' => 'deliveries'])
            ->with('status', "{$updated->referenceLabel()} is now {$updated->displayStatus()}.");
    }

    /**
     * Cancel a scheduled delivery.
     */
    public function cancel(Request $request, SmartCityDelivery $delivery): RedirectResponse
    {
        try {
            $updated = $this->smartCityFoodDistributionService->cancelDelivery($request->user(), $delivery);
        } catch (BusinessLogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('distribution.smart-city', ['tab' => 'deliveries'])
            ->with('status', "{$updated->referenceLabel()} cancelled.");
    }

    /**
     * Toggle fleet unit maintenance status.
     */
    public function toggleFleet(Request $request, SmartCityFleetUnit $unit): RedirectResponse
    {
        try {
            $updated = $this->smartCityFoodDistributionService->toggleFleetMaintenance($unit);
        } catch (BusinessLogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('distribution.smart-city', ['tab' => 'fleet'])
            ->with('status', "{$updated->name} is now {$updated->displayStatus()}.");
    }
}
