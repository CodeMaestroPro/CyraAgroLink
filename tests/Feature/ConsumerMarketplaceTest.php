<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConsumerCartItem;
use App\Models\ConsumerOrder;
use App\Models\ConsumerProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Consumer Marketplace.
 */
class ConsumerMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_consumer_marketplace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('consumer.marketplace'));

        $response->assertOk();
        $response->assertSee('Consumer Marketplace');
        $response->assertDontSee('46. CONSUMER MARKETPLACE');
        $response->assertDontSee('46. Consumer Marketplace');
        $response->assertSee('Shop Fresh. Eat Healthy.', false);
        $response->assertSee('Search for products...', false);
        $response->assertSee('Grains', false);
        $response->assertSee('Fruits', false);
        $response->assertSee('Vegetables', false);
        $response->assertSee('Oils', false);
        $response->assertSee('Organic', false);
        $response->assertSee('Others', false);
        $response->assertSee('Best Sellers', false);
        $response->assertSee('Rice (Ofada)', false);
        $response->assertSee('Honey (Raw)', false);
        $response->assertSee('Palm Oil', false);
        $response->assertSee('Yam Flour', false);
        $response->assertSee('₦1,200/kg', false);
        $response->assertSee('₦3,500/kg', false);
        $response->assertSee('₦2,000/L', false);
        $response->assertSee('₦2,500/kg', false);
        $response->assertSee('Add to Cart', false);
        $response->assertSee('Cart (0)', false);
    }

    public function test_user_can_search_consumer_products(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('consumer.marketplace'));

        $response = $this->actingAs($user)->get(route('consumer.marketplace', ['q' => 'Honey']));

        $response->assertOk();
        $response->assertSee('Honey (Raw)', false);
        $response->assertDontSee('Rice (Ofada)');
    }

    public function test_user_can_filter_by_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('consumer.marketplace'));

        $response = $this->actingAs($user)->get(route('consumer.marketplace', [
            'view' => 'shop',
            'category' => 'oils',
        ]));

        $response->assertOk();
        $response->assertSee('Palm Oil', false);
        $response->assertSee('Groundnut Oil', false);
        $response->assertDontSee('Honey (Raw)');
    }

    public function test_user_can_add_update_and_remove_cart_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('consumer.marketplace'));

        $product = ConsumerProduct::query()->where('name', 'Rice (Ofada)')->firstOrFail();

        $this->actingAs($user)
            ->post(route('consumer.cart.add', $product), ['quantity' => 2])
            ->assertRedirect(route('consumer.marketplace', ['view' => 'cart']));

        $this->assertDatabaseHas('consumer_cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $item = ConsumerCartItem::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('consumer.cart.update', $item), ['quantity' => 3])
            ->assertRedirect(route('consumer.marketplace', ['view' => 'cart']));

        $this->assertDatabaseHas('consumer_cart_items', [
            'id' => $item->id,
            'quantity' => 3,
        ]);

        $cartView = $this->actingAs($user)->get(route('consumer.marketplace', ['view' => 'cart']));
        $cartView->assertOk();
        $cartView->assertSee('Rice (Ofada)', false);
        $cartView->assertSee('Cart (3)', false);

        $this->actingAs($user)
            ->delete(route('consumer.cart.remove', $item))
            ->assertRedirect(route('consumer.marketplace', ['view' => 'cart']));

        $this->assertDatabaseMissing('consumer_cart_items', ['id' => $item->id]);
    }

    public function test_user_must_fund_wallet_before_paying_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('consumer.marketplace'));

        $product = ConsumerProduct::query()->where('name', 'Honey (Raw)')->firstOrFail();

        $this->actingAs($user)->post(route('consumer.cart.add', $product), ['quantity' => 2]);
        $this->actingAs($user)->post(route('consumer.checkout'), [
            'delivery_note' => 'Call on arrival',
        ]);

        $order = ConsumerOrder::query()->where('user_id', $user->id)->firstOrFail();

        $response = $this->actingAs($user)->post(route('consumer.orders.confirm', $order));

        $response->assertRedirect(route('consumer.marketplace', ['view' => 'orders']));
        $response->assertSessionHas('error');
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseMissing('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'purchase',
        ]);
    }

    public function test_user_can_pay_order_from_funded_wallet(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('consumer.marketplace'));

        $product = ConsumerProduct::query()->where('name', 'Honey (Raw)')->firstOrFail();
        $startingStock = $product->stock_qty;

        $this->actingAs($user)->post(route('wallet.deposit'), ['amount' => 10000]);
        $this->actingAs($user)->post(route('consumer.cart.add', $product), ['quantity' => 2]);

        $response = $this->actingAs($user)->post(route('consumer.checkout'), [
            'delivery_note' => 'Call on arrival',
        ]);

        $response->assertRedirect(route('consumer.marketplace', ['view' => 'orders']));

        $order = ConsumerOrder::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame('pending', $order->status);
        $this->assertSame(7000, $order->total_amount);
        $this->assertSame('Call on arrival', $order->delivery_note);
        $this->assertDatabaseCount('consumer_cart_items', 0);
        $this->assertSame($startingStock - 2, $product->fresh()->stock_qty);

        $ordersView = $this->actingAs($user)->get(route('consumer.marketplace', ['view' => 'orders']));
        $ordersView->assertOk();
        $ordersView->assertSee('Order #'.$order->id, false);
        $ordersView->assertSee('Honey (Raw)', false);
        $ordersView->assertSee('Pay with wallet', false);

        $pay = $this->actingAs($user)->post(route('consumer.orders.confirm', $order));
        $pay->assertRedirect(route('consumer.marketplace', ['view' => 'orders']));
        $pay->assertSessionHas('status');

        $this->assertSame('paid', $order->fresh()->status);
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 3000,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'debit',
            'category' => 'purchase',
            'amount' => 7000,
        ]);
    }

    public function test_user_can_cancel_pending_order_and_restock(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('consumer.marketplace'));

        $product = ConsumerProduct::query()->where('name', 'Palm Oil')->firstOrFail();
        $startingStock = $product->stock_qty;

        $this->actingAs($user)->post(route('consumer.cart.add', $product), ['quantity' => 1]);
        $this->actingAs($user)->post(route('consumer.checkout'));

        $order = ConsumerOrder::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('consumer.orders.cancel', $order))
            ->assertRedirect(route('consumer.marketplace', ['view' => 'orders']));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame($startingStock, $product->fresh()->stock_qty);
    }

    public function test_guest_cannot_view_consumer_marketplace(): void
    {
        $this->get(route('consumer.marketplace'))->assertRedirect(route('login'));
    }

    public function test_user_can_print_order_receipt_with_qr_code(): void
    {
        $user = User::factory()->create(['name' => 'Ada Buyer']);

        $this->actingAs($user)->get(route('consumer.marketplace'));

        $product = ConsumerProduct::query()->where('name', 'Honey (Raw)')->firstOrFail();

        $this->actingAs($user)->post(route('consumer.cart.add', $product), ['quantity' => 1]);
        $this->actingAs($user)->post(route('consumer.checkout'));

        $order = ConsumerOrder::query()->where('user_id', $user->id)->firstOrFail();

        $ordersView = $this->actingAs($user)->get(route('consumer.marketplace', ['view' => 'orders']));
        $ordersView->assertOk();
        $ordersView->assertSee('Print receipt', false);
        $ordersView->assertSee(route('consumer.orders.receipt', $order), false);

        $receipt = $this->actingAs($user)->get(route('consumer.orders.receipt', $order));
        $receipt->assertOk();
        $receipt->assertSee($order->receiptReference(), false);
        $receipt->assertSee('Ada Buyer', false);
        $receipt->assertSee('Honey (Raw)', false);
        $receipt->assertSee('Scan to verify this receipt', false);
        $receipt->assertSee('<svg', false);
        $receipt->assertSee(route('consumer.orders.verify', [
            'order' => $order,
            'sig' => $order->receiptSignature(),
        ]), false);

        $verify = $this->get(route('consumer.orders.verify', [
            'order' => $order,
            'sig' => $order->receiptSignature(),
        ]));
        $verify->assertOk();
        $verify->assertSee('Verified receipt', false);
        $verify->assertSee($order->receiptReference(), false);
        $verify->assertSee('Honey (Raw)', false);

        $other = User::factory()->create();
        $this->actingAs($other)
            ->get(route('consumer.orders.receipt', $order))
            ->assertForbidden();

        $this->get(route('consumer.orders.verify', [
            'order' => $order,
            'sig' => 'invalid-signature',
        ]))->assertNotFound();
    }
}
