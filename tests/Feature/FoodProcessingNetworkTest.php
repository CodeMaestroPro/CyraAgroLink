<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Models\Farm;
use App\Models\LogisticsShipment;
use App\Models\ProcessingFactory;
use App\Models\ProcessingRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Food Processing Network.
 */
class FoodProcessingNetworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_food_processing_network(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('processing.network'));

        $response->assertOk();
        $response->assertSee('Food Processing Network');
        $response->assertDontSee('31. FOOD PROCESSING NETWORK');
        $response->assertDontSee('31. Food Processing Network');
        $response->assertSee('Processing Overview', false);
        $response->assertSee('Total Factories', false);
        $response->assertSee('42', false);
        $response->assertSee('Active Requests', false);
        $response->assertSee('28', false);
        $response->assertSee('Processing Capacity', false);
        $response->assertSee('78%', false);
        $response->assertSee('Jobs Completed', false);
        $response->assertSee('1,245', false);
        $response->assertSee('Popular Services', false);
        $response->assertSee('Milling', false);
        $response->assertSee('Packaging', false);
        $response->assertSee('Drying', false);
        $response->assertSee('Cold Storage', false);
        $response->assertSee('Juicing', false);
        $response->assertSee('Others', false);
        $response->assertSee('Recent Requests', false);
        $response->assertSee('Maize Milling', false);
        $response->assertSee('Cassava Processing', false);
        $response->assertSee('Palm Oil Processing', false);
        $response->assertSee('View All Equipments', false);
        $response->assertSee('Submit Processing Request', false);
        $response->assertSee('Green Valley Farm', false);
        $response->assertSee('Track in logistics', false);
        $response->assertSee('Waiting for factory delivery', false);
    }

    public function test_guest_cannot_view_food_processing_network(): void
    {
        $this->get(route('processing.network'))->assertRedirect(route('login'));
    }

    public function test_user_can_submit_deliver_then_process_request(): void
    {
        $user = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Process Ridge Farm',
            'state' => 'Ogun',
            'local_government' => 'Abeokuta North',
            'size_hectares' => '4.00',
            'crops' => ['Maize', 'Cassava'],
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 5_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('processing.network'))->assertOk();

        $factory = ProcessingFactory::query()->firstOrFail();

        $this->actingAs($user)
            ->post(route('processing.requests.store'), [
                'service' => 'milling',
                'product' => 'Maize',
                'quantity_tons' => 2,
                'factory_id' => $factory->id,
                'farm_id' => $farm->id,
            ])
            ->assertRedirect(route('processing.network').'#requests');

        $job = ProcessingRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'queued')
            ->where('product', 'Maize')
            ->latest('id')
            ->firstOrFail();

        $this->assertNotNull($job->logistics_shipment_id);
        $this->assertGreaterThan(0, $job->fee_ngn);

        $shipment = LogisticsShipment::query()->findOrFail($job->logistics_shipment_id);
        $this->assertSame('booked', $shipment->status);
        $this->assertStringContainsString('Process Ridge Farm', $shipment->origin);
        $this->assertStringContainsString($factory->name, $shipment->destination);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'processing',
            'reference_id' => $job->id,
            'amount' => $job->fee_ngn,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'purchase',
            'reference_id' => $shipment->id,
        ]);

        // Cannot start processing before delivery.
        $blocked = $this->actingAs($user)->post(route('processing.requests.advance', $job));
        $blocked->assertRedirect(route('processing.network').'#requests');
        $this->followRedirects($blocked)
            ->assertSee('Deliver produce to the factory via logistics', false);

        $job->refresh();
        $this->assertSame('queued', $job->status);

        // Advance logistics through to factory delivery.
        foreach (['picked_up', 'in_transit', 'in_warehouse', 'delivered'] as $expected) {
            $this->actingAs($user)
                ->post(route('processing.requests.deliver', $job))
                ->assertRedirect(route('processing.network').'#requests');

            $shipment->refresh();
            $this->assertSame($expected, $shipment->status);
        }

        $this->actingAs($user)
            ->post(route('processing.requests.advance', $job))
            ->assertRedirect(route('processing.network').'#requests');

        $job->refresh();
        $this->assertSame('in_progress', $job->status);

        $this->actingAs($user)
            ->post(route('processing.requests.advance', $job))
            ->assertRedirect(route('processing.network').'#requests');

        $job->refresh();
        $this->assertSame('completed', $job->status);
        $this->assertNotNull($job->completed_at);
    }

    public function test_user_cannot_advance_another_users_processing_request(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $job = ProcessingRequest::query()->create([
            'user_id' => $owner->id,
            'reference' => 'PRC-0099',
            'service' => 'milling',
            'product' => 'Rice',
            'quantity_tons' => 3,
            'status' => 'queued',
            'fee_ngn' => 20000,
        ]);

        $this->actingAs($intruder)
            ->post(route('processing.requests.advance', $job))
            ->assertForbidden();
    }
}
