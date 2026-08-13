<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Crop;

use App\Enums\CropActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crop\StoreCropActivityRequest;
use App\Http\Requests\Crop\StoreCropRequest;
use App\Models\Crop;
use App\Services\Crop\CropManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Crop Management screens for farmers.
 */
class CropManagementController extends Controller
{
    public function __construct(
        protected CropManagementService $cropManagementService
    ) {
    }

    /**
     * Display crop management overview and operational tabs.
     */
    public function show(Request $request): View
    {
        $tab = $request->string('tab', 'overview')->toString();
        $allowed = ['overview', 'activities', 'irrigation', 'fertilizer', 'health', 'harvest'];

        if (! in_array($tab, $allowed, true)) {
            $tab = 'overview';
        }

        $payload = $this->cropManagementService->getOverview(
            $request->user(),
            $request->integer('crop') ?: null,
            $tab
        );

        return view('farmer.crops.manage', [
            ...$payload,
            'activeTab' => $tab,
            'notificationsCount' => 3,
        ]);
    }

    /**
     * Create a new crop cycle.
     */
    public function store(StoreCropRequest $request): RedirectResponse
    {
        $crop = $this->cropManagementService->createCrop($request->user(), $request->validated());

        return redirect()
            ->route('crops.manage', ['crop' => $crop->id, 'tab' => 'overview'])
            ->with('status', 'Crop cycle created successfully.');
    }

    /**
     * Log a care event, health update, or harvest.
     */
    public function storeActivity(StoreCropActivityRequest $request, Crop $crop): RedirectResponse
    {
        $data = $request->validated();
        $type = CropActivityType::from((string) $data['type']);

        if ($type === CropActivityType::Health) {
            $this->cropManagementService->updateHealth($request->user(), $crop, $data);
            $tab = 'health';
            $message = 'Crop health updated.';
        } elseif ($type === CropActivityType::Harvest) {
            $this->cropManagementService->recordHarvest($request->user(), $crop, $data);
            $tab = 'harvest';
            $message = 'Harvest recorded. Crop cycle marked as harvested.';
        } else {
            $this->cropManagementService->recordCareEvent($request->user(), $crop, $type, $data);
            $tab = match ($type) {
                CropActivityType::Irrigation => 'irrigation',
                CropActivityType::Fertilizer => 'fertilizer',
                default => 'activities',
            };
            $message = $type->label().' activity logged.';
        }

        return redirect()
            ->route('crops.manage', ['crop' => $crop->id, 'tab' => $tab])
            ->with('status', $message);
    }

    /**
     * Advance the crop to the next growth stage.
     */
    public function advanceStage(Request $request, Crop $crop): RedirectResponse
    {
        $this->cropManagementService->advanceStage($request->user(), $crop);

        return redirect()
            ->route('crops.manage', ['crop' => $crop->id, 'tab' => 'overview'])
            ->with('status', 'Growth stage advanced.');
    }
}
