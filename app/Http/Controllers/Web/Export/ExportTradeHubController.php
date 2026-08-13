<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Export;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\ExportOrder;
use App\Services\Export\ExportTradeHubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Export and international trade hub overview and order actions.
 */
class ExportTradeHubController extends Controller
{
    public function __construct(
        protected ExportTradeHubService $exportTradeHubService
    ) {
    }

    /**
     * Display the export & international trade hub.
     */
    public function index(Request $request): View
    {
        $orderId = $request->integer('order') ?: null;
        $data = $this->exportTradeHubService->getHubData($request->user(), $orderId);

        return view('export.hub', [
            'kpis' => $data['kpis'],
            'destinations' => $data['destinations'],
            'process' => $data['process'],
            'orders' => $data['orders'],
            'focusOrderId' => $data['focus_order_id'],
            'farms' => $data['farms'],
            'destinationOptions' => $data['destination_options'],
            'productOptions' => $data['product_options'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Create a new export order.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product' => ['required', 'string', 'max:120'],
            'quantity_tons' => ['required', 'numeric', 'min:0.5', 'max:100000'],
            'destination_code' => ['required', 'string', 'size:2'],
            'farm_id' => ['nullable', 'integer'],
            'value_usd' => ['nullable', 'integer', 'min:100', 'max:50000000'],
        ]);

        try {
            $order = $this->exportTradeHubService->createOrder($request->user(), $data);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('export.hub')
                ->with('error', $e->getMessage())
                ->withFragment('orders');
        }

        return redirect()
            ->route('export.hub', ['order' => $order->id])
            ->with('status', "Export order {$order->reference} created.")
            ->withFragment('process');
    }

    /**
     * Advance an export order to the next process stage.
     */
    public function advance(Request $request, ExportOrder $order): RedirectResponse
    {
        try {
            $updated = $this->exportTradeHubService->advanceOrder($request->user(), $order);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('export.hub', ['order' => $order->id])
                ->with('error', $e->getMessage())
                ->withFragment('orders');
        }

        $message = "{$updated->reference} moved to {$this->stageLabel($updated->status)}.";
        if ($updated->status === 'delivered') {
            $message .= ' Export proceeds credited to your wallet.';
        }

        return redirect()
            ->route('export.hub', ['order' => $updated->id])
            ->with('status', $message)
            ->withFragment('process');
    }

    protected function stageLabel(string $status): string
    {
        return match ($status) {
            'request_received' => 'Request Received',
            'quality_inspection' => 'Quality Inspection',
            'documentation' => 'Documentation',
            'customs_clearance' => 'Customs Clearance',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            default => $status,
        };
    }
}
