<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExchangeOrder;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for Smart Marketplace.
 */
class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_smart_marketplace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('marketplace.index'));

        $response->assertOk();
        $response->assertSee('Smart Marketplace');
        $response->assertDontSee('6. Smart Marketplace');
        $response->assertSee('Featured Commodities');
        $response->assertSee('Top Suppliers');
        $response->assertSee('Maize');
        $response->assertSee('Green Valley Farms');
        $response->assertSee('Tubers');
        $response->assertSee('My listings', false);
        $response->assertSee('Orders', false);
    }

    public function test_user_can_search_and_filter_by_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('marketplace.index'));

        $response = $this->actingAs($user)->get(route('marketplace.index', ['q' => 'Cocoa']));

        $response->assertOk();
        $response->assertSee('Cocoa');
        $response->assertSee('Search Results');

        $filtered = $this->actingAs($user)->get(route('marketplace.index', ['category' => 'tubers']));
        $filtered->assertOk();
        $filtered->assertSee('Yam');
        $filtered->assertSee('Cassava');

        $byCategoryName = $this->actingAs($user)->get(route('marketplace.index', ['q' => 'tubers']));
        $byCategoryName->assertOk();
        $byCategoryName->assertSee('Search Results');
        $byCategoryName->assertSee('Yam');
        $byCategoryName->assertSee('Cassava');
        $byCategoryName->assertDontSee('Cocoa');

        $combined = $this->actingAs($user)->get(route('marketplace.index', [
            'category' => 'tubers',
            'q' => 'tubers',
        ]));
        $combined->assertOk();
        $combined->assertSee('Yam');
        $combined->assertSee('Cassava');
    }

    public function test_user_can_quick_buy_and_manage_listing(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('marketplace.index'));

        $this->actingAs($user)->post(route('marketplace.store'), [
            'name' => 'Fresh Sorghum',
            'price_per_ton' => 275000,
            'city' => 'Kano',
            'state' => 'Kano',
            'image' => UploadedFile::fake()->image('sorghum.jpg'),
        ])->assertRedirect(route('marketplace.index', ['view' => 'listings']));

        $listing = MarketplaceCommodity::query()->where('name', 'Fresh Sorghum')->firstOrFail();
        $this->assertSame($user->id, $listing->user_id);

        $this->actingAs($user)->patch(route('marketplace.update', $listing), [
            'price_per_ton' => 280000,
            'city' => 'Kano',
            'state' => 'Kano',
        ])->assertRedirect(route('marketplace.index', ['view' => 'listings']));

        $this->assertSame(280000, $listing->fresh()->price_per_ton);

        $buyer = User::factory()->buyer()->create();
        Wallet::query()->create([
            'user_id' => $buyer->id,
            'balance' => 5_000_000,
            'currency' => 'NGN',
        ]);
        $commodity = MarketplaceCommodity::query()->where('name', 'Maize')->firstOrFail();

        $this->actingAs($buyer)->post(route('marketplace.buy', $commodity), [
            'quantity_tons' => 5,
        ])->assertRedirect(route('marketplace.index', ['view' => 'orders', 'order_status' => 'open']));

        $this->assertDatabaseHas('exchange_orders', [
            'user_id' => $buyer->id,
            'commodity_id' => $commodity->id,
            'side' => 'buy',
            'quantity_tons' => 5,
            'status' => 'open',
        ]);

        $order = ExchangeOrder::query()
            ->where('user_id', $buyer->id)
            ->where('commodity_id', $commodity->id)
            ->firstOrFail();

        $this->actingAs($buyer)
            ->patch(route('marketplace.orders.update', $order), ['quantity_tons' => 8])
            ->assertRedirect(route('marketplace.index', ['view' => 'orders', 'order_status' => 'open']))
            ->assertSessionHas('error');

        $this->assertSame(5, $order->fresh()->quantity_tons);

        $seller = User::factory()->create();
        $this->actingAs($seller)->post(route('exchange.order', $commodity), [
            'side' => 'sell',
            'quantity_tons' => 5,
            'price_per_ton' => $commodity->price_per_ton,
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame('filled', $order->status);
        $this->assertSame(5, $order->filled_quantity_tons);

        $this->actingAs($buyer)
            ->get(route('marketplace.index', ['view' => 'orders', 'order_status' => 'filled']))
            ->assertOk()
            ->assertSee('Your orders', false)
            ->assertSee('Maize', false)
            ->assertSee('Filled', false);
    }

    public function test_guest_cannot_view_marketplace(): void
    {
        $this->get(route('marketplace.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_publish_commodity_listing(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('marketplace.index'));

        $response = $this->actingAs($user)->post(route('marketplace.store'), [
            'name' => 'Fresh Sorghum',
            'price_per_ton' => 275000,
            'city' => 'Kano',
            'state' => 'Kano',
            'is_featured' => '1',
            'image' => UploadedFile::fake()->image('sorghum.jpg'),
        ]);

        $response->assertRedirect(route('marketplace.index', ['view' => 'listings']));
        $this->assertDatabaseHas('marketplace_commodities', [
            'name' => 'Fresh Sorghum',
            'price_per_ton' => 275000,
            'city' => 'Kano',
        ]);
    }
}
