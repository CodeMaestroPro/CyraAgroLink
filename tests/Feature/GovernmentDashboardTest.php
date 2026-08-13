<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\GovernmentPolicy;
use App\Models\SubsidyApplication;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Government Dashboard.
 */
class GovernmentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_government_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('government.dashboard'));

        $response->assertOk();
        $response->assertSee('Government Dashboard');
        $response->assertDontSee('21. GOVERNMENT DASHBOARD');
        $response->assertDontSee('21. Government Dashboard');
        $response->assertSee('National Agricultural Overview', false);
        $response->assertSee('Real-time agricultural data and insights', false);
        $response->assertSee('Registered Farmers', false);
        $response->assertSee('Total Production (Tons)', false);
        $response->assertSee('Food Security Index', false);
        $response->assertSee('Active Farms', false);
        $response->assertSee('Production by Commodity', false);
        $response->assertSee('Map Overview', false);
        $response->assertSee('Subsidy Programs', false);
        $response->assertSee('Total Disbursed', false);
        $response->assertSee('Beneficiaries', false);
        $response->assertSee('Pending Approval', false);
        $response->assertSee('Farmers', false);
        $response->assertSee('Food Security', false);
        $response->assertSee('Policies', false);
        $response->assertSee('Export overview', false);
        $response->assertSee('Apply for subsidy', false);

        $this->assertGreaterThanOrEqual(4, GovernmentPolicy::query()->count());
        $this->assertGreaterThanOrEqual(6, SubsidyApplication::query()->count());
    }

    public function test_user_can_apply_approve_reject_subsidy_and_manage_policies(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('government.dashboard', ['tab' => 'policies']))
            ->assertOk()
            ->assertSee('Agricultural Policies', false)
            ->assertSee('National Fertilizer Subsidy Framework', false);

        $this->actingAs($user)
            ->post(route('government.subsidies.apply'), [
                'program' => 'Fertilizer Support',
                'beneficiary_name' => $user->name,
                'state' => 'Oyo',
                'amount' => 1_500_000,
            ])
            ->assertRedirect(route('government.dashboard', ['tab' => 'subsidies', 'state' => 'Oyo']).'#subsidy-programs-heading');

        $application = SubsidyApplication::query()
            ->where('user_id', $user->id)
            ->where('program', 'Fertilizer Support')
            ->firstOrFail();

        $this->assertSame(ApplicationStatus::Pending, $application->status);

        $wallet = app(DigitalWalletService::class);
        $before = $wallet->getBalance($user);

        $this->actingAs($user)
            ->post(route('government.subsidies.approve', $application))
            ->assertRedirect(route('government.dashboard', ['tab' => 'subsidies']));

        $this->assertSame(ApplicationStatus::Approved, $application->fresh()->status);
        $this->assertSame($before + 1_500_000, $wallet->getBalance($user));

        $pending = SubsidyApplication::query()
            ->where('status', ApplicationStatus::Pending)
            ->whereNull('user_id')
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('government.subsidies.reject', $pending))
            ->assertRedirect(route('government.dashboard', ['tab' => 'subsidies']));

        $this->assertSame(ApplicationStatus::Rejected, $pending->fresh()->status);

        $this->actingAs($user)
            ->post(route('government.policies.store'), [
                'title' => 'National Irrigation Expansion Act',
                'summary' => 'Funds shared irrigation corridors for dry-season farming.',
                'status' => 'draft',
            ])
            ->assertRedirect(route('government.dashboard', ['tab' => 'policies']));

        $policy = GovernmentPolicy::query()->where('title', 'National Irrigation Expansion Act')->firstOrFail();
        $this->assertSame('draft', $policy->status);

        $this->actingAs($user)
            ->post(route('government.policies.status', $policy), ['status' => 'active'])
            ->assertRedirect(route('government.dashboard', ['tab' => 'policies']));

        $this->assertSame('active', $policy->fresh()->status);
    }

    public function test_admin_can_view_government_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('government.dashboard'));

        $response->assertOk();
        $response->assertSee('National Agricultural Overview', false);
        $response->assertDontSee('Farm Overview');
    }

    public function test_guest_cannot_view_government_dashboard(): void
    {
        $this->get(route('government.dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_can_export_government_overview(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('government.dashboard'))->assertOk();

        $response = $this->actingAs($user)->get(route('government.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('CyraAgroLink Government Dashboard', $csv);
        $this->assertStringContainsString('Registered Farmers', $csv);
        $this->assertStringContainsString('National Fertilizer Subsidy Framework', $csv);
    }
}
