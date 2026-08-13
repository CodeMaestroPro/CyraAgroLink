<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Processing;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\ProcessingRequest;
use App\Services\Processing\FoodProcessingNetworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Food processing network overview and request actions.
 */
class FoodProcessingNetworkController extends Controller
{
    public function __construct(
        protected FoodProcessingNetworkService $foodProcessingNetworkService
    ) {
    }

    /**
     * Display the food processing network.
     */
    public function index(Request $request): View
    {
        $data = $this->foodProcessingNetworkService->getNetworkData($request->user());

        return view('processing.network', [
            'kpis' => $data['kpis'],
            'services' => $data['services'],
            'requests' => $data['requests'],
            'factories' => $data['factories'],
            'farms' => $data['farms'],
            'factoryOptions' => $data['factory_options'],
            'serviceOptions' => $data['service_options'],
            'productOptions' => $data['product_options'],
            'actions' => $data['actions'],
            'walletBalance' => $data['wallet_balance'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Submit a new processing request and charge the wallet fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service' => ['required', 'string', 'max:40'],
            'product' => ['required', 'string', 'max:120'],
            'quantity_tons' => ['required', 'numeric', 'min:0.5', 'max:100000'],
            'factory_id' => ['nullable', 'integer'],
            'farm_id' => ['nullable', 'integer'],
        ]);

        try {
            $job = $this->foodProcessingNetworkService->createRequest($request->user(), $data);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('processing.network')
                ->with('error', $e->getMessage())
                ->withFragment('create-request');
        }

        $shipmentRef = $job->logisticsShipment?->referenceLabel() ?? 'logistics';

        return redirect()
            ->route('processing.network')
            ->with(
                'status',
                "{$job->reference} queued. Logistics {$shipmentRef} booked to deliver produce to the factory. "
                .'Processing fee ₦'.number_format($job->fee_ngn).' charged.'
            )
            ->withFragment('requests');
    }

    /**
     * Advance a processing request to the next status.
     */
    public function advance(Request $request, ProcessingRequest $processingRequest): RedirectResponse
    {
        try {
            $updated = $this->foodProcessingNetworkService->advanceRequest($request->user(), $processingRequest);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('processing.network')
                ->with('error', $e->getMessage())
                ->withFragment('requests');
        }

        $label = match ($updated->status) {
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            default => $updated->status,
        };

        return redirect()
            ->route('processing.network')
            ->with('status', "{$updated->reference} updated to {$label}.")
            ->withFragment('requests');
    }

    /**
     * Advance the logistics delivery that moves produce to the factory.
     */
    public function deliver(Request $request, ProcessingRequest $processingRequest): RedirectResponse
    {
        try {
            $shipment = $this->foodProcessingNetworkService->advanceDelivery($request->user(), $processingRequest);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('processing.network')
                ->with('error', $e->getMessage())
                ->withFragment('requests');
        }

        $message = $shipment->status === 'delivered'
            ? "{$processingRequest->reference}: produce delivered to the factory. You can start processing."
            : "{$processingRequest->reference}: delivery updated to {$shipment->displayStatus()}.";

        return redirect()
            ->route('processing.network')
            ->with('status', $message)
            ->withFragment('requests');
    }
}
