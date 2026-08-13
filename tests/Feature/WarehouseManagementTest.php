<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Warehouse Management.
 */
class WarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_warehouse_management(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('warehouse.index'));

        $response->assertOk();
        $response->assertSee('Warehouse Management');
        $response->assertDontSee('13. WAREHOUSE MANAGEMENT');
        $response->assertDontSee('13. Warehouse Management');
        $response->assertSee('My Warehouses', false);
        $response->assertSee('Ibadan Central Warehouse', false);
        $response->assertSee('Ibadan, Oyo State', false);
        $response->assertSee('Occupancy', false);
        $response->assertSee('75%', false);
        $response->assertSee('Inventory Summary', false);
        $response->assertSee('Maize', false);
        $response->assertSee('350 Tons', false);
        $response->assertSee('Rice', false);
        $response->assertSee('200 Tons', false);
        $response->assertSee('Cassava', false);
        $response->assertSee('150 Tons', false);
        $response->assertSee('Others', false);
        $response->assertSee('50 Tons', false);
        $response->assertSee('View Details', false);
        $response->assertSee('Stock In', false);
        $response->assertSee('Register warehouse', false);
    }

    public function test_user_can_register_warehouse_and_manage_stock(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('warehouse.index'));

        $response = $this->actingAs($user)->post(route('warehouse.store'), [
            'name' => 'Lagos Cold Store',
            'city' => 'Lagos',
            'state' => 'Lagos State',
            'capacity_tons' => 400,
        ]);

        $warehouse = Warehouse::query()
            ->where('user_id', $user->id)
            ->where('name', 'Lagos Cold Store')
            ->firstOrFail();

        $response->assertRedirect(route('warehouse.index', [
            'tab' => 'details',
            'warehouse' => $warehouse->id,
        ]));

        $this->actingAs($user)->post(route('warehouse.stock.receive', $warehouse), [
            'commodity_name' => '__custom__',
            'custom_commodity_name' => 'Tomato',
            'quantity_tons' => 40,
            'source' => 'Green Valley Farm',
            'note' => 'Morning intake',
        ])->assertRedirect(route('warehouse.index', [
            'tab' => 'details',
            'warehouse' => $warehouse->id,
        ]));

        $stock = WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('commodity_name', 'Tomato')
            ->firstOrFail();

        $this->assertSame(40, $stock->quantity_tons);
        $this->assertSame(10, $warehouse->fresh()->occupancyPercent());

        $details = $this->actingAs($user)->get(route('warehouse.index', [
            'tab' => 'details',
            'warehouse' => $warehouse->id,
        ]));
        $details->assertOk();
        $details->assertSee('Tomato', false);
        $details->assertSee('40 Tons', false);
        $details->assertSee('Stock In', false);
        $details->assertSee('Confirm stock in', false);
        $details->assertSee('Source: Green Valley Farm', false);
        $details->assertSee('Morning intake', false);

        $this->actingAs($user)->post(route('warehouse.stock.release', $stock), [
            'quantity_tons' => 15,
        ])->assertRedirect();

        $this->assertSame(25, $stock->fresh()->quantity_tons);
    }

    public function test_user_cannot_receive_beyond_capacity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('warehouse.index'));

        $warehouse = Warehouse::query()->where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('warehouse.stock.receive', $warehouse), [
            'commodity_name' => 'Maize',
            'quantity_tons' => 500,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(350, (int) WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('commodity_name', 'Maize')
            ->value('quantity_tons'));
    }

    public function test_stock_in_merges_case_insensitive_commodity_names(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('warehouse.index'));

        $warehouse = Warehouse::query()->where('user_id', $user->id)->firstOrFail();
        $before = (int) WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('commodity_name', 'Maize')
            ->value('quantity_tons');

        $this->actingAs($user)->post(route('warehouse.stock.receive', $warehouse), [
            'commodity_name' => 'maize',
            'quantity_tons' => 25,
            'source' => 'Co-op depot',
        ])->assertRedirect();

        $this->assertSame(1, WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereRaw('LOWER(commodity_name) = ?', ['maize'])
            ->count());

        $this->assertSame($before + 25, (int) WarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('commodity_name', 'Maize')
            ->value('quantity_tons'));

        $movements = $this->actingAs($user)->get(route('warehouse.index', [
            'tab' => 'details',
            'warehouse' => $warehouse->id,
        ]));
        $movements->assertSee('Source: Co-op depot', false);
        $movements->assertSee('+25', false);
    }

    public function test_user_cannot_stock_in_to_another_users_warehouse(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($owner)->get(route('warehouse.index'));
        $warehouse = Warehouse::query()->where('user_id', $owner->id)->firstOrFail();

        $this->actingAs($intruder)
            ->post(route('warehouse.stock.receive', $warehouse), [
                'commodity_name' => 'Rice',
                'quantity_tons' => 10,
            ])
            ->assertForbidden();
    }

    public function test_guest_cannot_view_warehouse_management(): void
    {
        $this->get(route('warehouse.index'))->assertRedirect(route('login'));
    }
}
