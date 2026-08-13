<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FuturesContract;
use App\Models\FuturesOrder;
use App\Models\FuturesPosition;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Commodity Futures Exchange.
 */
class CommodityFuturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_commodity_futures_exchange(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('futures.exchange'));

        $response->assertOk();
        $response->assertSee('Commodity Futures Exchange');
        $response->assertDontSee('35. COMMODITY FUTURES EXCHANGE');
        $response->assertDontSee('35. Commodity Futures Exchange');
        $response->assertSee('Maize Futures', false);
        $response->assertSee('Open Interest', false);
        $response->assertSee('Volume', false);
        $response->assertSee('High', false);
        $response->assertSee('Low', false);
        $response->assertSee('1D', false);
        $response->assertSee('1W', false);
        $response->assertSee('1M', false);
        $response->assertSee('All Futures', false);
        $response->assertSee('Market Depth', false);
        $response->assertSee('Buy Orders', false);
        $response->assertSee('Sell Orders', false);
        $response->assertSee('Buy', false);
        $response->assertSee('Sell', false);
        $response->assertSee('Open Positions', false);
        $response->assertSee('My Orders', false);

        $this->assertGreaterThanOrEqual(1, FuturesContract::query()->count());
    }

    public function test_guest_cannot_view_commodity_futures_exchange(): void
    {
        $this->get(route('futures.exchange'))->assertRedirect(route('login'));
    }

    public function test_user_can_buy_open_position_and_close(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 50_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('futures.exchange'))->assertOk();

        $contract = FuturesContract::query()->where('name', 'like', 'Maize%')->firstOrFail();
        $price = $contract->last_price;
        $qty = 2;
        $expectedMargin = (int) round($qty * $price * 0.10);

        $this->actingAs($user)
            ->post(route('futures.orders.store'), [
                'contract_id' => $contract->id,
                'side' => 'buy',
                'quantity' => $qty,
                'price' => $price,
            ])
            ->assertRedirect(route('futures.exchange', ['contract' => $contract->id]).'#orders');

        $order = FuturesOrder::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('buy', $order->side);
        $this->assertSame('filled', $order->status);
        $this->assertSame($expectedMargin, $order->margin_ngn);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'futures_margin',
            'reference_id' => $order->id,
            'amount' => $expectedMargin,
        ]);

        $position = FuturesPosition::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->firstOrFail();

        $this->assertSame('long', $position->side);
        $this->assertSame($qty, $position->quantity);

        $balanceAfterOpen = (int) Wallet::query()->where('user_id', $user->id)->value('balance');
        $this->assertSame(50_000_000 - $expectedMargin, $balanceAfterOpen);

        $this->actingAs($user)
            ->get(route('futures.exchange', ['contract' => $contract->id]))
            ->assertOk()
            ->assertSee($order->reference, false)
            ->assertSee($position->reference, false);

        $this->actingAs($user)
            ->post(route('futures.positions.close', $position))
            ->assertRedirect(route('futures.exchange', ['contract' => $contract->id]).'#positions');

        $position->refresh();
        $this->assertSame('closed', $position->status);
        $this->assertNotNull($position->realized_pnl_ngn);

        $this->assertSame(
            0,
            FuturesPosition::query()->where('user_id', $user->id)->where('status', 'open')->count()
        );
    }

    public function test_user_can_cancel_open_order_and_get_margin_refund(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 50_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('futures.exchange'))->assertOk();
        $contract = FuturesContract::query()->firstOrFail();

        // Far below market so it stays open (does not cross).
        $limit = max(1000, (int) round($contract->last_price * 0.5));

        $this->actingAs($user)->post(route('futures.orders.store'), [
            'contract_id' => $contract->id,
            'side' => 'buy',
            'quantity' => 1,
            'price' => $limit,
        ]);

        $order = FuturesOrder::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('open', $order->status);

        $afterLock = (int) Wallet::query()->where('user_id', $user->id)->value('balance');

        $this->actingAs($user)
            ->post(route('futures.orders.cancel', $order))
            ->assertRedirect(route('futures.exchange', ['contract' => $contract->id]).'#orders');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(
            $afterLock + $order->margin_ngn,
            (int) Wallet::query()->where('user_id', $user->id)->value('balance')
        );
    }

    public function test_user_cannot_place_order_without_wallet_funds(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 100,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('futures.exchange'))->assertOk();
        $contract = FuturesContract::query()->firstOrFail();

        $response = $this->actingAs($user)->post(route('futures.orders.store'), [
            'contract_id' => $contract->id,
            'side' => 'buy',
            'quantity' => 1,
            'price' => $contract->last_price,
        ]);

        $response->assertRedirect(route('futures.exchange', ['contract' => $contract->id]).'#depth');
        $this->followRedirects($response)->assertSee('Insufficient', false);

        $this->assertSame(0, FuturesOrder::query()->where('user_id', $user->id)->count());
    }
}
