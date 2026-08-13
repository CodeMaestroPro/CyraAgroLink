<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LogisticsShipment;
use App\Models\LogisticsVehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Supply Chain Tracking.
 */
class SupplyChainTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_live_supply_chain_tracking(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('supply-chain.index'));

        $response->assertOk();
        $response->assertSee('Supply Chain Tracking');
        $response->assertDontSee('14. SUPPLY CHAIN TRACKING');
        $response->assertDontSee('14. Supply Chain Tracking');
        $response->assertSee('Shipment #SH12345', false);
        $response->assertSee('Maize, 10 Tons', false);
        $response->assertSee('Harvested', false);
        $response->assertSee('Picked Up', false);
        $response->assertSee('In Transit', false);
        $response->assertSee('In Warehouse', false);
        $response->assertSee('Delivered', false);
        $response->assertSee('Kano', false);
        $response->assertSee('Kaduna', false);
        $response->assertSee('Ibadan', false);
        $response->assertSee('Advance status', false);
        $response->assertSee('My shipments', false);
        $response->assertSee('View in Logistics', false);

        $this->assertDatabaseHas('logistics_shipments', [
            'user_id' => $user->id,
            'reference' => 'SH12345',
            'cargo_name' => 'Maize',
            'status' => 'in_transit',
        ]);
    }

    public function test_user_can_advance_shipment_from_supply_chain(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('supply-chain.index'));

        $shipment = LogisticsShipment::query()
            ->where('user_id', $user->id)
            ->where('reference', 'SH12345')
            ->firstOrFail();

        $this->assertSame('in_transit', $shipment->status);

        $response = $this->actingAs($user)->post(route('supply-chain.advance', $shipment));

        $response->assertRedirect(route('supply-chain.index', ['shipment' => $shipment->id]));
        $response->assertSessionHas('status');
        $this->assertSame('in_warehouse', $shipment->fresh()->status);

        $this->actingAs($user)->post(route('supply-chain.advance', $shipment));
        $this->assertSame('delivered', $shipment->fresh()->status);

        $page = $this->actingAs($user)->get(route('supply-chain.index', ['shipment' => $shipment->id]));
        $page->assertOk();
        $page->assertSee('Delivered', false);
        $page->assertDontSee('Advance status', false);
    }

    public function test_user_can_cancel_booked_shipment_from_supply_chain(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('logistics.index'));
        $this->actingAs($user)->post(route('wallet.deposit'), ['amount' => 500000]);

        $vehicle = LogisticsVehicle::query()->where('name', '10 Ton Truck')->firstOrFail();

        $this->actingAs($user)->post(route('logistics.book', $vehicle), [
            'cargo_name' => 'Rice',
            'cargo_tons' => 8,
        ]);

        $shipment = LogisticsShipment::query()
            ->where('user_id', $user->id)
            ->where('cargo_name', 'Rice')
            ->firstOrFail();

        $response = $this->actingAs($user)->post(route('supply-chain.cancel', $shipment));

        $response->assertRedirect(route('supply-chain.index', ['shipment' => $shipment->id]));
        $this->assertSame('cancelled', $shipment->fresh()->status);

        $page = $this->actingAs($user)->get(route('supply-chain.index', ['shipment' => $shipment->id]));
        $page->assertSee('Cancelled', false);
    }

    public function test_user_can_switch_tracked_shipment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('supply-chain.index'));

        $demo = LogisticsShipment::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->get(route('logistics.index'));
        $this->actingAs($user)->post(route('wallet.deposit'), ['amount' => 500000]);
        $vehicle = LogisticsVehicle::query()->where('name', '15 Ton Truck')->firstOrFail();

        $this->actingAs($user)->post(route('logistics.book', $vehicle), [
            'cargo_name' => 'Cassava',
            'cargo_tons' => 12,
        ]);

        $second = LogisticsShipment::query()
            ->where('user_id', $user->id)
            ->where('cargo_name', 'Cassava')
            ->firstOrFail();

        $page = $this->actingAs($user)->get(route('supply-chain.index', ['shipment' => $second->id]));
        $page->assertOk();
        $page->assertSee($second->referenceLabel(), false);
        $page->assertSee('Cassava, 12 Tons', false);
        $page->assertSee($demo->referenceLabel(), false);
    }

    public function test_user_cannot_advance_another_users_shipment(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($owner)->get(route('supply-chain.index'));
        $shipment = LogisticsShipment::query()->where('user_id', $owner->id)->firstOrFail();

        $response = $this->actingAs($intruder)->post(route('supply-chain.advance', $shipment));

        $response->assertRedirect(route('supply-chain.index', ['shipment' => $shipment->id]));
        $response->assertSessionHas('error');
        $this->assertSame('in_transit', $shipment->fresh()->status);
    }

    public function test_guest_cannot_view_supply_chain_tracking(): void
    {
        $this->get(route('supply-chain.index'))->assertRedirect(route('login'));
    }
}
