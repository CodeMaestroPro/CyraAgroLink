<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Models\ExportOrder;
use App\Models\Farm;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Export & International Trade Hub.
 */
class ExportTradeHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_export_trade_hub(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('export.hub'));

        $response->assertOk();
        $response->assertSee('Export & International Trade Hub');
        $response->assertDontSee('30. EXPORT & INTERNATIONAL TRADE HUB');
        $response->assertDontSee('30. Export & International Trade Hub');
        $response->assertSee('Export Overview', false);
        $response->assertSee('Create Export Order', false);
        $response->assertSee('Active Exports', false);
        $response->assertSee('Total Value', false);
        $response->assertSee('Countries', false);
        $response->assertSee('Orders in Transit', false);
        $response->assertSee('Top Destinations', false);
        $response->assertSee('Netherlands', false);
        $response->assertSee('$850,000', false);
        $response->assertSee('United Arab Emirates', false);
        $response->assertSee('United Kingdom', false);
        $response->assertSee('Saudi Arabia', false);
        $response->assertSee('United States', false);
        $response->assertSee('Export Process', false);
        $response->assertSee('Request Received', false);
        $response->assertSee('Quality Inspection', false);
        $response->assertSee('Documentation', false);
        $response->assertSee('Customs Clearance', false);
        $response->assertSee('In Transit', false);
        $response->assertSee('Delivered', false);
        $response->assertSee('Active & Recent Orders', false);
        $response->assertSee('Advance to', false);
        $response->assertSee('Green Valley Farm', false);
    }

    public function test_guest_cannot_view_export_trade_hub(): void
    {
        $this->get(route('export.hub'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_and_advance_export_order_to_delivery(): void
    {
        $user = User::factory()->create();
        $farm = Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Export Ridge Farm',
            'state' => 'Ogun',
            'size_hectares' => '8.00',
            'soil_type' => 'Loamy',
            'crops' => ['Cocoa', 'Maize'],
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);

        $this->actingAs($user)->get(route('export.hub'))->assertOk();

        $this->actingAs($user)
            ->post(route('export.orders.store'), [
                'product' => 'Cocoa',
                'quantity_tons' => 5,
                'destination_code' => 'NL',
                'farm_id' => $farm->id,
            ])
            ->assertRedirect();

        $order = ExportOrder::query()
            ->where('user_id', $user->id)
            ->where('product', 'Cocoa')
            ->where('status', 'request_received')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('NL', $order->destination_code);
        $this->assertGreaterThan(0, $order->value_usd);

        // Advance through all remaining stages to delivery.
        $stages = [
            'quality_inspection',
            'documentation',
            'customs_clearance',
            'in_transit',
            'delivered',
        ];

        foreach ($stages as $expected) {
            $response = $this->actingAs($user)
                ->post(route('export.orders.advance', $order));

            $response->assertRedirect();
            $order->refresh();
            $this->assertSame($expected, $order->status);

            if ($expected === 'delivered') {
                $this->followRedirects($response)
                    ->assertSee('Export proceeds credited', false);
            }
        }

        $this->assertNotNull($order->delivered_at);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'export',
            'reference_id' => $order->id,
        ]);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertGreaterThan(0, (int) $wallet->balance);

        $this->actingAs($user)
            ->get(route('export.hub', ['order' => $order->id]))
            ->assertOk()
            ->assertSee($order->reference, false);
    }

    public function test_user_cannot_advance_another_users_export_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $order = ExportOrder::query()->create([
            'user_id' => $owner->id,
            'reference' => 'EXP-0099',
            'product' => 'Sesame',
            'quantity_tons' => 10,
            'destination_country' => 'Netherlands',
            'destination_code' => 'NL',
            'value_usd' => 50000,
            'status' => 'request_received',
        ]);

        $this->actingAs($intruder)
            ->post(route('export.orders.advance', $order))
            ->assertForbidden();
    }
}
