<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LogisticsShipment;
use App\Models\LogisticsVehicle;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Logistics Network.
 */
class LogisticsNetworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_logistics_network(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('logistics.index'));

        $response->assertOk();
        $response->assertSee('Logistics Network');
        $response->assertDontSee('12. LOGISTICS NETWORK');
        $response->assertDontSee('12. Logistics Network');
        $response->assertSee('Find & Book Transport');
        $response->assertSee('Reliable transport for your goods', false);
        $response->assertSee('Available Trucks', false);
        $response->assertSee('My Shipments', false);
        $response->assertSee('10 Ton Truck', false);
        $response->assertSee('Lagos → Ibadan', false);
        $response->assertSee('₦150,000', false);
        $response->assertSee('20 Ton Truck', false);
        $response->assertSee('Kano → Lagos', false);
        $response->assertSee('₦280,000', false);
        $response->assertSee('15 Ton Truck', false);
        $response->assertSee('Port Harcourt → Abuja', false);
        $response->assertSee('₦200,000', false);
        $response->assertSee('View All Vehicles', false);
        $response->assertSee('Shipment Tracking', false);
        $response->assertSee('Fund wallet', false);
        $response->assertSee('Picked Up', false);
        $response->assertSee('In Transit', false);
        $response->assertSee('In Warehouse', false);
        $response->assertSee('Delivered', false);
    }

    public function test_user_must_fund_wallet_before_booking(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('logistics.index'));

        $vehicle = LogisticsVehicle::query()->where('name', '10 Ton Truck')->firstOrFail();

        $response = $this->actingAs($user)->post(route('logistics.book', $vehicle), [
            'cargo_name' => 'Maize',
            'cargo_tons' => 10,
        ]);

        $response->assertRedirect(route('logistics.index', ['tab' => 'trucks']));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('logistics_shipments', 0);
    }

    public function test_user_can_book_advance_and_track_shipment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('logistics.index'));
        $this->actingAs($user)->post(route('wallet.deposit'), ['amount' => 500000]);

        $vehicle = LogisticsVehicle::query()->where('name', '10 Ton Truck')->firstOrFail();

        $response = $this->actingAs($user)->post(route('logistics.book', $vehicle), [
            'cargo_name' => 'Maize',
            'cargo_tons' => 10,
        ]);

        $shipment = LogisticsShipment::query()->where('user_id', $user->id)->firstOrFail();

        $response->assertRedirect(route('logistics.index', [
            'tab' => 'shipments',
            'shipment' => $shipment->id,
        ]));

        $this->assertSame('booked', $shipment->status);
        $this->assertSame(150000, $shipment->price);
        $this->assertSame('Maize, 10 Tons', $shipment->cargoLabel());
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 350000,
        ]);

        $track = $this->actingAs($user)->get(route('logistics.index', [
            'tab' => 'shipments',
            'shipment' => $shipment->id,
        ]));
        $track->assertOk();
        $track->assertSee($shipment->referenceLabel(), false);
        $track->assertSee('Maize, 10 Tons', false);
        $track->assertSee('Advance status', false);

        $this->actingAs($user)->post(route('logistics.advance', $shipment));
        $this->assertSame('picked_up', $shipment->fresh()->status);

        $this->actingAs($user)->post(route('logistics.advance', $shipment));
        $this->actingAs($user)->post(route('logistics.advance', $shipment));
        $this->actingAs($user)->post(route('logistics.advance', $shipment));

        $this->assertSame('delivered', $shipment->fresh()->status);

        $delivered = $this->actingAs($user)->get(route('logistics.index', [
            'tab' => 'shipments',
            'shipment' => $shipment->id,
        ]));
        $delivered->assertSee('Delivered', false);
        $delivered->assertSee('Picked Up', false);
    }

    public function test_user_can_cancel_booked_shipment_and_get_refund(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('logistics.index'));
        $this->actingAs($user)->post(route('wallet.deposit'), ['amount' => 200000]);

        $vehicle = LogisticsVehicle::query()->where('name', '10 Ton Truck')->firstOrFail();

        $this->actingAs($user)->post(route('logistics.book', $vehicle), [
            'cargo_name' => 'Cassava',
            'cargo_tons' => 8,
        ]);

        $shipment = LogisticsShipment::query()->where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('logistics.cancel', $shipment));

        $response->assertRedirect(route('logistics.index', [
            'tab' => 'shipments',
            'shipment' => $shipment->id,
        ]));

        $this->assertSame('cancelled', $shipment->fresh()->status);
        $this->assertSame(200000, (int) Wallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_guest_cannot_view_logistics_network(): void
    {
        $this->get(route('logistics.index'))->assertRedirect(route('login'));
    }
}
