<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\FoodSecurity;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Models\FoodSecurityIntervention;
use App\Services\FoodSecurity\FoodSecurityDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * National food security overview for government stakeholders.
 */
class FoodSecurityDashboardController extends Controller
{
    public function __construct(
        protected FoodSecurityDashboardService $foodSecurityDashboardService
    ) {
    }

    /**
     * Display the national food security dashboard.
     */
    public function index(Request $request): View
    {
        $data = $this->foodSecurityDashboardService->getDashboardData(
            $request->user(),
            $request->string('state')->toString() ?: null
        );

        return view('food-security.dashboard', [
            'kpis' => $data['kpis'],
            'commodities' => $data['commodities'],
            'hungerZones' => $data['hunger_zones'],
            'map' => $data['map'],
            'stateFilter' => $data['state_filter'],
            'stateOptions' => $data['state_options'],
            'interventions' => $data['interventions'],
            'snapshotAt' => $data['snapshot_at'],
            'factors' => $data['factors'],
            'actions' => $data['actions'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Recalculate food security metrics from live platform data.
     */
    public function refresh(Request $request): RedirectResponse
    {
        $snapshot = $this->foodSecurityDashboardService->recalculate($request->user());

        return redirect()
            ->route('food.security', array_filter(['state' => $request->input('state')]))
            ->with('status', 'Food security index refreshed: '.$snapshot->index_score.' ('.$snapshot->index_status.').')
            ->withFragment('overview');
    }

    /**
     * Plan an intervention for a hunger-risk state.
     */
    public function storeIntervention(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'state' => ['required', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'action_type' => ['required', 'string', 'in:reserve_release,subsidy_push,logistics_aid,market_support,scouting,other'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $intervention = $this->foodSecurityDashboardService->createIntervention($request->user(), $data);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('food.security')
                ->with('error', $e->getMessage())
                ->withFragment('interventions');
        }

        return redirect()
            ->route('food.security', array_filter(['state' => $data['state']]))
            ->with('status', 'Intervention planned for '.$intervention->state.': '.$intervention->title)
            ->withFragment('interventions');
    }

    /**
     * Mark an intervention as done.
     */
    public function completeIntervention(Request $request, FoodSecurityIntervention $intervention): RedirectResponse
    {
        try {
            $this->foodSecurityDashboardService->completeIntervention($request->user(), $intervention);
        } catch (BusinessLogicException $e) {
            if ($e->getStatusCode() === 403) {
                abort(403, $e->getMessage());
            }

            return redirect()
                ->route('food.security')
                ->with('error', $e->getMessage())
                ->withFragment('interventions');
        }

        return redirect()
            ->route('food.security')
            ->with('status', 'Intervention marked done.')
            ->withFragment('interventions');
    }

    /**
     * Download a CSV food security report.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->foodSecurityDashboardService->exportReport($request->user());
    }
}
