<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Logistics;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Logistics\BookVehicleRequest;
use App\Models\LogisticsShipment;
use App\Models\LogisticsVehicle;
use App\Services\Logistics\LogisticsNetworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Logistics network booking and shipment tracking.
 */
class LogisticsNetworkController extends Controller
{
    public function __construct(
        protected LogisticsNetworkService $logisticsNetworkService
    ) {
    }

    /**
     * Display the logistics network screen.
     */
    public function index(Request $request): View
    {
        $data = $this->logisticsNetworkService->getNetworkData(
            $request->user(),
            $request->string('tab', 'trucks')->toString(),
            $request->integer('shipment') ?: null
        );

        return view('logistics.index', [
            ...$data,
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Book a vehicle and pay from wallet.
     */
    public function book(BookVehicleRequest $request, LogisticsVehicle $vehicle): RedirectResponse
    {
        try {
            $shipment = $this->logisticsNetworkService->bookVehicle(
                $request->user(),
                $vehicle,
                $request->validated()
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('logistics.index', ['tab' => 'trucks'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('logistics.index', [
                'tab' => 'shipments',
                'shipment' => $shipment->id,
            ])
            ->with('status', "{$shipment->referenceLabel()} booked and paid from your wallet.");
    }

    /**
     * Advance shipment tracking status.
     */
    public function advance(Request $request, LogisticsShipment $shipment): RedirectResponse
    {
        try {
            $updated = $this->logisticsNetworkService->advanceShipment($request->user(), $shipment);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('logistics.index', ['tab' => 'shipments', 'shipment' => $shipment->id])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('logistics.index', [
                'tab' => 'shipments',
                'shipment' => $updated->id,
            ])
            ->with('status', "{$updated->referenceLabel()} updated to {$updated->displayStatus()}.");
    }

    /**
     * Cancel a booked shipment and refund wallet.
     */
    public function cancel(Request $request, LogisticsShipment $shipment): RedirectResponse
    {
        try {
            $updated = $this->logisticsNetworkService->cancelShipment($request->user(), $shipment);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('logistics.index', ['tab' => 'shipments', 'shipment' => $shipment->id])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('logistics.index', ['tab' => 'shipments', 'shipment' => $updated->id])
            ->with('status', "{$updated->referenceLabel()} cancelled. Wallet refunded.");
    }
}
