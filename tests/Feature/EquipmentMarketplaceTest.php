<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EquipmentCartItem;
use App\Models\EquipmentFavorite;
use App\Models\EquipmentListing;
use App\Models\EquipmentOrder;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Agricultural Equipment Marketplace.
 */
class EquipmentMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_equipment_marketplace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('equipment.marketplace'));

        $response->assertOk();
        $response->assertSee('Agricultural Equipment Marketplace');
        $response->assertDontSee('32. AGRICULTURAL EQUIPMENT MARKETPLACE');
        $response->assertDontSee('32. Agricultural Equipment Marketplace');
        $response->assertSee('Search equipment, brands...', false);
        $response->assertSee('Categories', false);
        $response->assertSee('Tractors', false);
        $response->assertSee('Harvesters', false);
        $response->assertSee('Irrigation', false);
        $response->assertSee('Implements', false);
        $response->assertSee('Sprayers', false);
        $response->assertSeeText('Parts & Tools');
        $response->assertSee('For Sale', false);
        $response->assertSee('For Rent', false);
        $response->assertSee('Spare Parts', false);
        $response->assertSee('John Deere 5075E', false);
        $response->assertSee('$35,000', false);
        $response->assertSee('Lagos, NG', false);
        $response->assertSee('New Holland TX66', false);
        $response->assertSee('Case IH Axial-Flow AFX8010', false);
        $response->assertSee('$155,000', false);
        $response->assertSee('Irrigation Pump Set', false);
        $response->assertSee('Loko, NG', false);
        $response->assertSee('Buy', false);
        $response->assertSee('Adds to cart — you pay at checkout', false);
        $response->assertSee('Cart (0)', false);
        $response->assertSee('Others', false);
    }

    public function test_every_category_has_listings_on_each_tab(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('equipment.marketplace'))->assertOk();

        $categories = [
            'Tractors',
            'Harvesters',
            'Irrigation',
            'Implements',
            'Sprayers',
            'Processing',
            'Parts & Tools',
            'Others',
        ];

        foreach (['sale', 'rent', 'parts'] as $tab) {
            foreach ($categories as $category) {
                $this->assertTrue(
                    EquipmentListing::query()
                        ->where('listing_type', $tab)
                        ->where('category', $category)
                        ->where('is_active', true)
                        ->where('stock', '>', 0)
                        ->exists(),
                    "Expected {$tab} listings for category {$category}."
                );

                $this->actingAs($user)
                    ->get(route('equipment.marketplace', ['tab' => $tab, 'category' => $category]))
                    ->assertOk()
                    ->assertDontSee('No equipment matches this filter', false);
            }
        }
    }

    public function test_each_listing_uses_a_product_matching_image_file(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('equipment.marketplace'))->assertOk();

        $expected = [
            'John Deere 5075E' => 'images/equipment/john-deere-5075e.jpg',
            'New Holland TX66' => 'images/equipment/new-holland-tx66.jpg',
            'Case IH Axial-Flow AFX8010' => 'images/equipment/case-ih-axial.jpg',
            'Irrigation Pump Set' => 'images/equipment/irrigation-pump.jpg',
            'Offset Disc Plough' => 'images/equipment/offset-disc-plough.jpg',
            'Knapsack Sprayer 20L' => 'images/equipment/knapsack-sprayer.jpg',
            'Mobile Grain Mill' => 'images/equipment/mobile-grain-mill.jpg',
            'Mechanic Tool Chest' => 'images/equipment/mechanic-tool-chest.jpg',
            'Solar Cold-Room Kit' => 'images/equipment/solar-cold-room.jpg',
            'Massey Ferguson 375' => 'images/equipment/massey-ferguson-375.jpg',
            'Boom Sprayer 600L' => 'images/equipment/boom-sprayer.jpg',
            'Disc Harrow Set' => 'images/equipment/disc-harrow.jpg',
            'Tractor Filter Kit' => 'images/equipment/tractor-filter-kit.jpg',
            'Harvester Belt Pack' => 'images/equipment/harvester-belt-pack.jpg',
            'Grain Mill Spare Stones' => 'images/equipment/grain-mill-stones.jpg',
        ];

        foreach ($expected as $name => $path) {
            $listing = EquipmentListing::query()->where('name', $name)->firstOrFail();
            $this->assertSame($path, $listing->image_path, "Image mismatch for {$name}");
            $this->assertFileExists(public_path($path), "Missing image file for {$name}");
        }

        // No two different product names should share the same image (except intentional combine hire reuse of TX66).
        $paths = EquipmentListing::query()
            ->whereNotIn('name', ['Combine Hire TX Unit', 'New Holland TX66'])
            ->pluck('image_path', 'name');

        $this->assertSame(
            $paths->count(),
            $paths->unique()->count(),
            'Distinct products should use distinct product photos.'
        );
    }

    public function test_guest_cannot_view_equipment_marketplace(): void
    {
        $this->get(route('equipment.marketplace'))->assertRedirect(route('login'));
    }

    public function test_user_can_filter_favorite_and_buy_goes_to_cart_not_instant_charge(): void
    {
        $user = User::factory()->create();

        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 100_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('equipment.marketplace'))->assertOk();

        $listing = EquipmentListing::query()->where('name', 'Irrigation Pump Set')->firstOrFail();
        $stockBefore = $listing->stock;
        $balanceBefore = 100_000_000;

        $this->actingAs($user)
            ->get(route('equipment.marketplace', ['tab' => 'rent']))
            ->assertOk()
            ->assertSee('Massey Ferguson 375', false)
            ->assertSee('Rent', false)
            ->assertSee('Adds to cart — you pay at checkout', false);

        $this->actingAs($user)
            ->get(route('equipment.marketplace', ['q' => 'John Deere']))
            ->assertOk()
            ->assertSee('John Deere 5075E', false)
            ->assertDontSee('Irrigation Pump Set', false);

        $this->actingAs($user)
            ->post(route('equipment.favorite', $listing))
            ->assertRedirect();

        $this->assertDatabaseHas('equipment_favorites', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        $this->actingAs($user)
            ->post(route('equipment.cart.add', $listing), ['quantity' => 1])
            ->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));

        // Buy only stages the cart — no order or wallet debit yet.
        $this->assertSame(0, EquipmentOrder::query()->where('user_id', $user->id)->count());
        $this->assertSame($stockBefore, $listing->fresh()->stock);
        $this->assertSame($balanceBefore, (int) Wallet::query()->where('user_id', $user->id)->value('balance'));
        $this->assertDatabaseHas('equipment_cart_items', [
            'user_id' => $user->id,
            'listing_id' => $listing->id,
            'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('equipment.checkout'))
            ->assertRedirect(route('equipment.marketplace', ['view' => 'orders']));

        $order = EquipmentOrder::query()
            ->where('user_id', $user->id)
            ->where('listing_id', $listing->id)
            ->firstOrFail();

        $this->assertSame('paid', $order->status);
        $this->assertSame($stockBefore - 1, $listing->fresh()->stock);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'equipment',
            'reference_id' => $order->id,
            'amount' => $order->amount_ngn,
        ]);

        $this->actingAs($user)
            ->get(route('equipment.marketplace', ['view' => 'orders']))
            ->assertOk()
            ->assertSee('My Equipment Orders', false)
            ->assertSee($order->reference, false)
            ->assertSee('Irrigation Pump Set', false);
    }

    public function test_user_can_add_update_remove_and_checkout_cart(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 100_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('equipment.marketplace'))->assertOk();

        $sale = EquipmentListing::query()->where('name', 'Irrigation Pump Set')->firstOrFail();
        $rent = EquipmentListing::query()->where('name', 'Massey Ferguson 375')->firstOrFail();
        $saleStock = $sale->stock;
        $rentStock = $rent->stock;

        $this->actingAs($user)
            ->post(route('equipment.cart.add', $sale), ['quantity' => 2])
            ->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));

        $this->actingAs($user)
            ->post(route('equipment.cart.add', $rent), ['quantity' => 1, 'rental_days' => 3])
            ->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));

        $this->assertSame(2, EquipmentCartItem::query()->where('user_id', $user->id)->count());

        $saleCart = EquipmentCartItem::query()
            ->where('user_id', $user->id)
            ->where('listing_id', $sale->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->patch(route('equipment.cart.update', $saleCart), ['quantity' => 1])
            ->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));

        $this->assertSame(1, $saleCart->fresh()->quantity);

        $rentCart = EquipmentCartItem::query()
            ->where('user_id', $user->id)
            ->where('listing_id', $rent->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->patch(route('equipment.cart.update', $rentCart), [
                'quantity' => 1,
                'rental_days' => 5,
            ])
            ->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));

        $this->assertSame(5, $rentCart->fresh()->rental_days);

        $this->actingAs($user)
            ->get(route('equipment.marketplace', ['view' => 'cart']))
            ->assertOk()
            ->assertSee('Your equipment cart', false)
            ->assertSee('Irrigation Pump Set', false)
            ->assertSee('Massey Ferguson 375', false)
            ->assertSeeText('Confirm & pay')
            ->assertSeeText('Review & pay');

        $this->actingAs($user)
            ->post(route('equipment.checkout'))
            ->assertRedirect(route('equipment.marketplace', ['view' => 'orders']));

        $this->assertSame(0, EquipmentCartItem::query()->where('user_id', $user->id)->count());
        $this->assertSame(2, EquipmentOrder::query()->where('user_id', $user->id)->count());
        $this->assertSame($saleStock - 1, $sale->fresh()->stock);
        $this->assertSame($rentStock - 1, $rent->fresh()->stock);

        $rentOrder = EquipmentOrder::query()
            ->where('user_id', $user->id)
            ->where('listing_id', $rent->id)
            ->firstOrFail();

        $this->assertSame('rent', $rentOrder->order_type);
        $this->assertSame(5, $rentOrder->meta['rental_days']);
        $this->assertSame(85 * 5 * 1550, $rentOrder->amount_ngn);
    }

    public function test_user_can_remove_cart_item(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('equipment.marketplace'))->assertOk();

        $listing = EquipmentListing::query()->where('name', 'Knapsack Sprayer 20L')->firstOrFail();

        $this->actingAs($user)
            ->post(route('equipment.cart.add', $listing))
            ->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));

        $item = EquipmentCartItem::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('equipment.cart.remove', $item))
            ->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));

        $this->assertSame(0, EquipmentCartItem::query()->where('user_id', $user->id)->count());
    }

    public function test_rent_checkout_charges_daily_rate_for_selected_days(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 5_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('equipment.marketplace', ['tab' => 'rent']))->assertOk();
        $listing = EquipmentListing::query()->where('name', 'Massey Ferguson 375')->firstOrFail();

        $this->actingAs($user)
            ->post(route('equipment.cart.add', $listing), [
                'quantity' => 1,
                'rental_days' => 1,
            ])
            ->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));

        $this->assertSame(0, EquipmentOrder::query()->where('user_id', $user->id)->count());

        $this->actingAs($user)
            ->post(route('equipment.checkout'))
            ->assertRedirect(route('equipment.marketplace', ['view' => 'orders']));

        $order = EquipmentOrder::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(85 * 1550, $order->amount_ngn);
        $this->assertSame('rent', $order->order_type);
    }

    public function test_user_cannot_checkout_without_wallet_funds(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 100,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('equipment.marketplace'))->assertOk();
        $listing = EquipmentListing::query()->where('name', 'John Deere 5075E')->firstOrFail();

        $this->actingAs($user)
            ->post(route('equipment.cart.add', $listing))
            ->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));

        $response = $this->actingAs($user)->post(route('equipment.checkout'));
        $response->assertRedirect(route('equipment.marketplace', ['view' => 'cart']));
        $this->followRedirects($response)->assertSee('Insufficient', false);

        $this->assertSame(0, EquipmentOrder::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, EquipmentCartItem::query()->where('user_id', $user->id)->count());
        $this->assertSame(0, EquipmentFavorite::query()->where('user_id', $user->id)->count());
    }
}
