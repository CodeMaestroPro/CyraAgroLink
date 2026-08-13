<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ExchangeOrder;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for live Commodity Exchange.
 */
class CommodityExchangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_commodity_exchange(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('exchange.show'));

        $response->assertOk();
        $response->assertSee('Commodity Exchange');
        $response->assertDontSee('7. Commodity Exchange');
        $response->assertSee('Maize (Zea mays)');
        $response->assertSee('Live Market Price');
        $response->assertSee('Market Depth');
        $response->assertSee('Recent trades');
        $response->assertSee('Place Buy Order');
        $response->assertSee('Your orders');
        $response->assertSee('Fund wallet');
        $response->assertDontSee('Mark filled');
    }

    public function test_user_can_switch_commodity_via_query(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('exchange.show'));

        $rice = MarketplaceCommodity::query()->where('name', 'Rice')->firstOrFail();

        $response = $this->actingAs($user)->get(route('exchange.show', ['commodity' => $rice->id]));

        $response->assertOk();
        $response->assertSee('Rice (Oryza sativa)');
    }

    public function test_buy_order_requires_wallet_funds(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('exchange.show'));
        $commodity = MarketplaceCommodity::query()->where('name', 'Maize')->firstOrFail();

        $response = $this->actingAs($user)->post(route('exchange.order', $commodity), [
            'side' => 'buy',
            'quantity_tons' => 2,
            'price_per_ton' => 320000,
        ]);

        $response->assertRedirect(route('exchange.show', ['commodity' => $commodity->id]));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('exchange_orders', [
            'user_id' => $user->id,
            'commodity_id' => $commodity->id,
        ]);
    }

    public function test_user_can_place_buy_order_with_wallet_hold(): void
    {
        $user = User::factory()->create();
        $this->fundWallet($user, 1_000_000);

        $this->actingAs($user)->get(route('exchange.show'));
        $commodity = MarketplaceCommodity::query()->where('name', 'Maize')->firstOrFail();

        $response = $this->actingAs($user)->post(route('exchange.order', $commodity), [
            'side' => 'buy',
            'quantity_tons' => 2,
            'price_per_ton' => 320000,
        ]);

        $response->assertRedirect(route('exchange.show', ['commodity' => $commodity->id]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('exchange_orders', [
            'user_id' => $user->id,
            'commodity_id' => $commodity->id,
            'side' => 'buy',
            'quantity_tons' => 2,
            'original_quantity_tons' => 2,
            'price_per_ton' => 320000,
            'reserved_amount' => 640000,
            'status' => 'open',
        ]);
        $this->assertSame(360000, $this->walletBalance($user));
    }

    public function test_user_can_place_sell_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('exchange.show'));
        $commodity = MarketplaceCommodity::query()->where('name', 'Maize')->firstOrFail();

        $response = $this->actingAs($user)->post(route('exchange.order', $commodity), [
            'side' => 'sell',
            'quantity_tons' => 5,
            'price_per_ton' => 330000,
        ]);

        $response->assertRedirect(route('exchange.show', ['commodity' => $commodity->id]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('exchange_orders', [
            'user_id' => $user->id,
            'commodity_id' => $commodity->id,
            'side' => 'sell',
            'quantity_tons' => 5,
            'price_per_ton' => 330000,
            'status' => 'open',
        ]);
    }

    public function test_matching_buy_and_sell_settles_wallet_and_records_trade(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $this->fundWallet($buyer, 2_000_000);

        $this->actingAs($seller)->get(route('exchange.show'));
        $commodity = MarketplaceCommodity::query()->where('name', 'Maize')->firstOrFail();

        $this->actingAs($seller)->post(route('exchange.order', $commodity), [
            'side' => 'sell',
            'quantity_tons' => 3,
            'price_per_ton' => 315000,
        ])->assertRedirect();

        $response = $this->actingAs($buyer)->post(route('exchange.order', $commodity), [
            'side' => 'buy',
            'quantity_tons' => 3,
            'price_per_ton' => 320000,
        ]);

        $response->assertRedirect(route('exchange.show', ['commodity' => $commodity->id]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('exchange_orders', [
            'user_id' => $seller->id,
            'side' => 'sell',
            'status' => 'filled',
            'filled_quantity_tons' => 3,
            'quantity_tons' => 0,
        ]);
        $this->assertDatabaseHas('exchange_orders', [
            'user_id' => $buyer->id,
            'side' => 'buy',
            'status' => 'filled',
            'filled_quantity_tons' => 3,
            'quantity_tons' => 0,
            'reserved_amount' => 0,
        ]);

        $this->assertDatabaseHas('exchange_trades', [
            'commodity_id' => $commodity->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'quantity_tons' => 3,
            'price_per_ton' => 315000,
            'notional_amount' => 945000,
        ]);

        // Buyer locked 3*320000=960000, traded at 315000 => refund 15000, net spent 945000
        $this->assertSame(2_000_000 - 945000, $this->walletBalance($buyer));
        $this->assertSame(945000, $this->walletBalance($seller));

        $board = $this->actingAs($buyer)->get(route('exchange.show', ['commodity' => $commodity->id]));
        $board->assertOk();
        $board->assertSee('945,000');
        $board->assertSee('Recent trades');
    }

    public function test_cancel_buy_order_releases_wallet_hold(): void
    {
        $user = User::factory()->create();
        $this->fundWallet($user, 500000);

        $this->actingAs($user)->get(route('exchange.show'));
        $commodity = MarketplaceCommodity::query()->where('name', 'Maize')->firstOrFail();

        $this->actingAs($user)->post(route('exchange.order', $commodity), [
            'side' => 'buy',
            'quantity_tons' => 1,
            'price_per_ton' => 300000,
        ]);

        $this->assertSame(200000, $this->walletBalance($user));

        $order = ExchangeOrder::query()->where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('exchange.orders.cancel', $order));

        $response->assertRedirect(route('exchange.show', ['commodity' => $commodity->id]));
        $this->assertDatabaseHas('exchange_orders', [
            'id' => $order->id,
            'status' => 'cancelled',
            'reserved_amount' => 0,
        ]);
        $this->assertSame(500000, $this->walletBalance($user));
    }

    public function test_manual_fill_route_is_removed(): void
    {
        $user = User::factory()->create();
        $this->fundWallet($user, 500000);
        $this->actingAs($user)->get(route('exchange.show'));
        $commodity = MarketplaceCommodity::query()->where('name', 'Maize')->firstOrFail();

        $this->actingAs($user)->post(route('exchange.order', $commodity), [
            'side' => 'buy',
            'quantity_tons' => 1,
            'price_per_ton' => 300000,
        ]);

        $order = ExchangeOrder::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post('/exchange/orders/'.$order->id.'/fill')
            ->assertNotFound();
    }

    public function test_live_open_order_appears_in_market_depth_without_synthetic_rows(): void
    {
        $user = User::factory()->create();
        $this->fundWallet($user, 5_000_000);

        $this->actingAs($user)->get(route('exchange.show'));
        $commodity = MarketplaceCommodity::query()->where('name', 'Maize')->firstOrFail();

        $this->actingAs($user)->post(route('exchange.order', $commodity), [
            'side' => 'buy',
            'quantity_tons' => 12,
            'price_per_ton' => 311111,
        ]);

        $response = $this->actingAs($user)->get(route('exchange.show', ['commodity' => $commodity->id]));

        $response->assertOk();
        $response->assertSee('311,111');
        $response->assertSee('No live sell orders');
        $response->assertDontSee('Mark filled');
    }

    public function test_guest_cannot_view_exchange(): void
    {
        $this->get(route('exchange.show'))->assertRedirect(route('login'));
    }

    protected function fundWallet(User $user, int $balance): void
    {
        Wallet::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['balance' => $balance, 'currency' => 'NGN']
        );
    }

    protected function walletBalance(User $user): int
    {
        return (int) Wallet::query()->where('user_id', $user->id)->value('balance');
    }
}
