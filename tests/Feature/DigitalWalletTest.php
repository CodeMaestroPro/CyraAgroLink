<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Digital Wallet.
 */
class DigitalWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_digital_wallet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('wallet.index'));

        $response->assertOk();
        $response->assertSee('Digital Wallet');
        $response->assertDontSee('11. DIGITAL WALLET');
        $response->assertDontSee('11. Digital Wallet');
        $response->assertSee('Wallet Balance', false);
        $response->assertSee('₦0', false);
        $response->assertSee('Naira Wallet', false);
        $response->assertSee('Deposit', false);
        $response->assertSee('Withdraw', false);
        $response->assertSee('Recent Transactions', false);
        $response->assertSee('No transactions yet.', false);
        $response->assertSee('Fund wallet', false);
        $response->assertSee('Money in', false);
    }

    public function test_user_can_fund_wallet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('wallet.deposit'), [
            'amount' => 10000,
            'note' => 'Card top-up',
        ]);

        $response->assertRedirect(route('wallet.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 10000,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'credit',
            'category' => 'deposit',
            'amount' => 10000,
            'detail' => 'Card top-up',
        ]);

        $view = $this->actingAs($user)->get(route('wallet.index'));
        $view->assertOk();
        $view->assertSee('₦10,000', false);
        $view->assertSee('Wallet Deposit', false);
        $view->assertSee('Card top-up', false);
    }

    public function test_user_can_withdraw_from_wallet(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('wallet.deposit'), ['amount' => 5000]);

        $response = $this->actingAs($user)->post(route('wallet.withdraw'), [
            'amount' => 2000,
            'note' => 'To bank',
        ]);

        $response->assertRedirect(route('wallet.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 3000,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'debit',
            'category' => 'withdrawal',
            'amount' => 2000,
        ]);

        $view = $this->actingAs($user)->get(route('wallet.index', ['filter' => 'debit']));
        $view->assertSee('Withdrawal', false);
        $view->assertDontSee('Wallet Deposit', false);
    }

    public function test_user_cannot_withdraw_more_than_balance(): void
    {
        $user = User::factory()->create();

        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 500,
            'currency' => 'NGN',
        ]);

        $response = $this->actingAs($user)->post(route('wallet.withdraw'), [
            'amount' => 1000,
        ]);

        $response->assertRedirect(route('wallet.index', ['panel' => 'withdraw']));
        $response->assertSessionHas('error');
        $this->assertSame(500, (int) Wallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_guest_cannot_view_digital_wallet(): void
    {
        $this->get(route('wallet.index'))->assertRedirect(route('login'));
    }
}
