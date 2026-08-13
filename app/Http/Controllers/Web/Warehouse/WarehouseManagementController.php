<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Warehouse;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\ReceiveStockRequest;
use App\Http\Requests\Warehouse\StockMovementRequest;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\Warehouse\WarehouseManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Warehouse occupancy and inventory management.
 */
class WarehouseManagementController extends Controller
{
    public function __construct(
        protected WarehouseManagementService $warehouseManagementService
    ) {
    }

    /**
     * Display the warehouse management screen.
     */
    public function index(Request $request): View
    {
        $data = $this->warehouseManagementService->getManagementData(
            $request->user(),
            $request->integer('warehouse') ?: null,
            $request->string('tab', 'list')->toString()
        );

        return view('warehouse.index', [
            ...$data,
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Register a new warehouse.
     */
    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $warehouse = $this->warehouseManagementService->createWarehouse(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('warehouse.index', ['tab' => 'details', 'warehouse' => $warehouse->id])
            ->with('status', "{$warehouse->name} registered.");
    }

    /**
     * Receive stock into a warehouse.
     */
    public function receive(ReceiveStockRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $payload = $request->validated();

        try {
            $stock = $this->warehouseManagementService->receiveStock(
                $request->user(),
                $warehouse,
                $payload
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('warehouse.index', ['tab' => 'details', 'warehouse' => $warehouse->id])
                ->with('error', $e->getMessage())
                ->withInput();
        }

        $received = number_format((int) $payload['quantity_tons']);

        return redirect()
            ->route('warehouse.index', ['tab' => 'details', 'warehouse' => $warehouse->id])
            ->with('status', "Stock in complete: +{$received} tons of {$stock->commodity_name}. Occupancy is now {$warehouse->fresh()?->occupancyPercent()}%.");
    }

    /**
     * Release stock from a warehouse line.
     */
    public function release(StockMovementRequest $request, WarehouseStock $stock): RedirectResponse
    {
        $warehouseId = $stock->warehouse_id;

        try {
            $this->warehouseManagementService->releaseStock(
                $request->user(),
                $stock,
                $request->validated()
            );
        } catch (BusinessLogicException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('warehouse.index', ['tab' => 'details', 'warehouse' => $warehouseId])
            ->with('status', 'Stock released successfully.');
    }
}
