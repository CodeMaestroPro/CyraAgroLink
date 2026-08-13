<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\SupplyChain;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\LogisticsShipment;
use App\Services\SupplyChain\SupplyChainTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * End-to-end supply chain shipment tracking.
 */
class SupplyChainTrackingController extends Controller
{
    public function __construct(
        protected SupplyChainTrackingService $supplyChainTrackingService
    ) {
    }

    /**
     * Display the supply chain tracking screen.
     */
    public function index(Request $request): View
    {
        $data = $this->supplyChainTrackingService->getTrackingData(
            $request->user(),
            $request->integer('shipment') ?: null
        );

        return view('supply-chain.index', [
            'shipment' => $data['shipment'],
            'shipments' => $data['shipments'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Advance the selected shipment to the next milestone.
     */
    public function advance(Request $request, LogisticsShipment $shipment): RedirectResponse
    {
        try {
            $updated = $this->supplyChainTrackingService->advanceShipment(
                $request->user(),
                $shipment
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('supply-chain.index', ['shipment' => $shipment->id])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('supply-chain.index', ['shipment' => $updated->id])
            ->with('status', "{$updated->referenceLabel()} advanced to {$updated->displayStatus()}.");
    }

    /**
     * Cancel a booked shipment (refunds wallet when a fare was paid).
     */
    public function cancel(Request $request, LogisticsShipment $shipment): RedirectResponse
    {
        try {
            $updated = $this->supplyChainTrackingService->cancelShipment(
                $request->user(),
                $shipment
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('supply-chain.index', ['shipment' => $shipment->id])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('supply-chain.index', ['shipment' => $updated->id])
            ->with('status', "{$updated->referenceLabel()} cancelled.");
    }
}
