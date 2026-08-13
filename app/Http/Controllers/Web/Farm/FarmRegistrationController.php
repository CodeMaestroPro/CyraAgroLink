<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Farm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Farm\StoreFarmCropsRequest;
use App\Http\Requests\Farm\StoreFarmDetailsRequest;
use App\Http\Requests\Farm\StoreFarmDocumentsRequest;
use App\Http\Requests\Farm\StoreFarmLocationRequest;
use App\Models\Farm;
use App\Services\Farm\FarmRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Multi-step farm registration wizard.
 */
class FarmRegistrationController extends Controller
{
    public function __construct(
        protected FarmRegistrationService $registrationService
    ) {
    }

    /**
     * Display the registration wizard at the requested step.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $forceNew = $request->boolean('new');

        $farm = $this->registrationService->startOrResume(
            $user,
            $forceNew ? null : ($request->integer('farm') ?: null),
            $forceNew
        );

        $step = max(1, min(
            FarmRegistrationService::TOTAL_STEPS,
            $request->integer('step') ?: (int) $farm->registration_step
        ));

        // Submitted farms are read-only — bounce to review summary.
        if ($farm->status->value !== 'draft') {
            $step = FarmRegistrationService::TOTAL_STEPS;
        }

        return view('farmer.farms.register', [
            'farm' => $farm,
            'step' => $step,
            'steps' => $this->steps(),
            'farms' => $this->registrationService->listForUser($user),
            'states' => config('cyra.nigeria_states', []),
            'cropOptions' => config('cyra.enterprise_options', config('cyra.crop_options', [])),
            'notificationsCount' => 3,
            'readOnly' => $farm->status->value !== 'draft',
        ]);
    }

    /**
     * Persist location step and advance.
     */
    public function storeLocation(StoreFarmLocationRequest $request, Farm $farm): RedirectResponse
    {
        $this->registrationService->saveLocation($request->user(), $farm, $request->validated());

        return redirect()
            ->route('farms.register', ['farm' => $farm->id, 'step' => 2])
            ->with('status', 'Farm location saved.');
    }

    /**
     * Persist details step and advance.
     */
    public function storeDetails(StoreFarmDetailsRequest $request, Farm $farm): RedirectResponse
    {
        $this->registrationService->saveDetails($request->user(), $farm, $request->validated());

        return redirect()
            ->route('farms.register', ['farm' => $farm->id, 'step' => 3])
            ->with('status', 'Farm details saved.');
    }

    /**
     * Persist crops step and advance.
     */
    public function storeCrops(StoreFarmCropsRequest $request, Farm $farm): RedirectResponse
    {
        $this->registrationService->saveCrops($request->user(), $farm, $request->validated());

        return redirect()
            ->route('farms.register', ['farm' => $farm->id, 'step' => 4])
            ->with('status', 'Crop selection saved.');
    }

    /**
     * Persist documents step and advance.
     */
    public function storeDocuments(StoreFarmDocumentsRequest $request, Farm $farm): RedirectResponse
    {
        $this->registrationService->saveDocuments($request->user(), $farm, [
            'land_title' => $request->file('land_title'),
            'farm_certificate' => $request->file('farm_certificate'),
            'identity_document' => $request->file('identity_document'),
        ]);

        return redirect()
            ->route('farms.register', ['farm' => $farm->id, 'step' => 5])
            ->with('status', 'Documents step completed.');
    }

    /**
     * Submit the completed registration.
     */
    public function submit(Request $request, Farm $farm): RedirectResponse
    {
        $this->registrationService->submit($request->user(), $farm);

        return redirect()
            ->route('farms.register', ['farm' => $farm->id, 'step' => 5])
            ->with('status', 'Farm registration submitted for review. An admin will verify your farm.');
    }

    /**
     * Wizard step metadata.
     *
     * @return list<array{number: int, key: string, label: string}>
     */
    protected function steps(): array
    {
        return [
            ['number' => 1, 'key' => 'location', 'label' => 'Location'],
            ['number' => 2, 'key' => 'details', 'label' => 'Details'],
            ['number' => 3, 'key' => 'crops', 'label' => 'Enterprises'],
            ['number' => 4, 'key' => 'documents', 'label' => 'Documents'],
            ['number' => 5, 'key' => 'review', 'label' => 'Review'],
        ];
    }
}
