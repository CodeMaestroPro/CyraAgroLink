<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SmartCityDelivery;
use App\Models\SmartCityFleetUnit;
use App\Models\SmartCityHub;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Smart City Food Distribution.
 */
class SmartCityFoodDistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_smart_city_food_distribution(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('distribution.smart-city'));

        $response->assertOk();
        $response->assertSee('Smart City Food Distribution');
        $response->assertDontSee('49. SMART CITY FOOD DISTRIBUTION');
        $response->assertDontSee('49. Smart City Food Distribution');
        $response->assertSee('Distribution Overview', false);
        $response->assertSee('Deliveries Today', false);
        $response->assertSee('In Transit', false);
        $response->assertSee('Delivered', false);
        $response->assertSee('On Time', false);
        $response->assertSee('Delivery Map', false);
        $response->assertSee('Fleet Status', false);
        $response->assertSee('Available', false);
        $response->assertSee('25', false);
        $response->assertSee('35', false);
        $response->assertSee('Maintenance', false);
        $response->assertSee('5', false);
        $response->assertSee('Optimize Routes', false);
        $response->assertSee('Warehouse Hub', false);
    }

    public function test_user_can_schedule_optimize_and_advance_delivery(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('distribution.smart-city'));

        $origin = SmartCityHub::query()->where('name', 'Warehouse Hub')->firstOrFail();
        $destination = SmartCityHub::query()->where('name', 'Distribution')->firstOrFail();

        $this->actingAs($user)->post(route('distribution.deliveries.store'), [
            'cargo_name' => 'Maize Crates',
            'quantity' => 40,
            'origin_hub_id' => $origin->id,
            'destination_hub_id' => $destination->id,
        ])->assertRedirect(route('distribution.smart-city', ['tab' => 'deliveries']));

        $delivery = SmartCityDelivery::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('scheduled', $delivery->status);

        $optimize = $this->actingAs($user)->post(route('distribution.optimize'));
        $optimize->assertRedirect(route('distribution.smart-city', ['tab' => 'overview']));
        $optimize->assertSessionHas('status');

        $delivery->refresh();
        $this->assertSame('dispatched', $delivery->status);
        $this->assertNotNull($delivery->fleet_unit_id);
        $this->assertSame(1, $delivery->route_order);

        $this->actingAs($user)
            ->post(route('distribution.deliveries.advance', $delivery))
            ->assertRedirect(route('distribution.smart-city', ['tab' => 'deliveries']));

        $this->assertSame('in_transit', $delivery->fresh()->status);

        $this->actingAs($user)->post(route('distribution.deliveries.advance', $delivery));
        $this->assertSame('delivered', $delivery->fresh()->status);

        $overview = $this->actingAs($user)->get(route('distribution.smart-city'));
        $overview->assertSee('Deliveries Today', false);
        $overview->assertSee('1', false);
    }

    public function test_user_can_cancel_scheduled_delivery(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('distribution.smart-city'));

        $hubs = SmartCityHub::query()->orderBy('sort_order')->get();

        $this->actingAs($user)->post(route('distribution.deliveries.store'), [
            'cargo_name' => 'Tomatoes',
            'quantity' => 12,
            'origin_hub_id' => $hubs[0]->id,
            'destination_hub_id' => $hubs[2]->id,
        ]);

        $delivery = SmartCityDelivery::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('distribution.deliveries.cancel', $delivery))
            ->assertRedirect(route('distribution.smart-city', ['tab' => 'deliveries']));

        $this->assertSame('cancelled', $delivery->fresh()->status);
    }

    public function test_user_can_toggle_fleet_maintenance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('distribution.smart-city'));

        $unit = SmartCityFleetUnit::query()->where('status', 'available')->firstOrFail();

        $this->actingAs($user)
            ->post(route('distribution.fleet.toggle', $unit))
            ->assertRedirect(route('distribution.smart-city', ['tab' => 'fleet']));

        $this->assertSame('maintenance', $unit->fresh()->status);

        $this->actingAs($user)->post(route('distribution.fleet.toggle', $unit));
        $this->assertSame('available', $unit->fresh()->status);
    }

    public function test_guest_cannot_view_smart_city_food_distribution(): void
    {
        $this->get(route('distribution.smart-city'))->assertRedirect(route('login'));
    }
}
