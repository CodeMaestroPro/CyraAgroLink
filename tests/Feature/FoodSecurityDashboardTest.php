<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FoodSecurityIntervention;
use App\Models\FoodSecuritySnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for National Food Security Dashboard.
 */
class FoodSecurityDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_food_security_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('food.security'));

        $response->assertOk();
        $response->assertSee('National Food Security Dashboard');
        $response->assertDontSee('37. NATIONAL FOOD SECURITY DASHBOARD');
        $response->assertDontSee('37. National Food Security Dashboard');
        $response->assertSee('Food Security Overview', false);
        $response->assertSee('Food Security Index', false);
        $response->assertSee('National Production', false);
        $response->assertSee('Import Dependency', false);
        $response->assertSee('Food Reserves', false);
        $response->assertSee('Top Commodities', false);
        $response->assertSee('Hunger Map (Risk Level)', false);
        $response->assertSee('Low', false);
        $response->assertSee('Medium', false);
        $response->assertSee('High', false);
        $response->assertSee('Severe', false);
        $response->assertSee('Refresh index', false);
        $response->assertSee('Plan Intervention', false);
        $response->assertSee('Borno', false);

        $this->assertSame(1, FoodSecuritySnapshot::query()->count());
        $snapshot = FoodSecuritySnapshot::query()->firstOrFail();
        $this->assertGreaterThanOrEqual(35, $snapshot->index_score);
        $this->assertLessThanOrEqual(96, $snapshot->index_score);
    }

    public function test_guest_cannot_view_food_security_dashboard(): void
    {
        $this->get(route('food.security'))->assertRedirect(route('login'));
    }

    public function test_user_can_refresh_filter_and_manage_interventions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('food.security'))->assertOk();
        $before = FoodSecuritySnapshot::query()->count();

        $this->actingAs($user)
            ->post(route('food.refresh'))
            ->assertRedirect(route('food.security').'#overview');

        $this->assertSame($before + 1, FoodSecuritySnapshot::query()->count());

        $this->actingAs($user)
            ->get(route('food.security', ['state' => 'Borno']))
            ->assertOk()
            ->assertSee('Borno', false)
            ->assertSee('severe', false);

        $this->actingAs($user)
            ->post(route('food.interventions.store'), [
                'state' => 'Yobe',
                'title' => 'Release maize buffer to Yobe corridors',
                'action_type' => 'reserve_release',
                'notes' => 'Priority logistics week',
            ])
            ->assertRedirect(route('food.security', ['state' => 'Yobe']).'#interventions');

        $intervention = FoodSecurityIntervention::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('planned', $intervention->status);
        $this->assertSame('Yobe', $intervention->state);

        $this->actingAs($user)
            ->get(route('food.security'))
            ->assertOk()
            ->assertSee('Release maize buffer to Yobe corridors', false);

        $this->actingAs($user)
            ->post(route('food.interventions.complete', $intervention))
            ->assertRedirect(route('food.security').'#interventions');

        $this->assertSame('done', $intervention->fresh()->status);
    }

    public function test_user_can_export_food_security_report(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('food.security'))->assertOk();

        $response = $this->actingAs($user)->get(route('food.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('CyraAgroLink Food Security Report', $csv);
        $this->assertStringContainsString('Food Security Index', $csv);
        $this->assertStringContainsString('Borno', $csv);
    }
}
