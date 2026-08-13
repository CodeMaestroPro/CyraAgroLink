<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletFundingIntent;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Paystack wallet funding initialize / verify / webhook.
 */
class PaystackWalletFundingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.paystack.secret_key' => 'sk_test_cyra',
            'services.paystack.public_key' => 'pk_test_cyra',
        ]);
    }

    public function test_deposit_redirects_to_paystack_when_configured(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test-session',
                    'access_code' => 'ACCESS123',
                    'reference' => 'WAL_TEST_REF_001',
                ],
            ], 200),
        ]);

        $user = User::factory()->create(['email' => 'farmer@example.com']);

        $response = $this->actingAs($user)->post(route('wallet.deposit'), [
            'amount' => 5000,
            'note' => 'Top up',
        ]);

        $response->assertRedirect('https://checkout.paystack.com/test-session');

        $this->assertDatabaseHas('wallet_funding_intents', [
            'user_id' => $user->id,
            'reference' => 'WAL_TEST_REF_001',
            'amount' => 5000,
            'status' => 'pending',
            'provider' => 'paystack',
        ]);

        $this->assertDatabaseMissing('wallets', [
            'user_id' => $user->id,
            'balance' => 5000,
        ]);
    }

    public function test_paystack_callback_credits_wallet_once(): void
    {
        $user = User::factory()->create(['email' => 'farmer@example.com']);

        WalletFundingIntent::query()->create([
            'user_id' => $user->id,
            'reference' => 'WAL_CB_001',
            'amount' => 7500,
            'currency' => 'NGN',
            'status' => 'pending',
            'provider' => 'paystack',
            'authorization_url' => 'https://checkout.paystack.com/x',
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 750_000,
                    'reference' => 'WAL_CB_001',
                ],
            ], 200),
        ]);

        $first = $this->actingAs($user)->get(route('wallet.paystack.callback', [
            'reference' => 'WAL_CB_001',
        ]));
        $first->assertRedirect(route('wallet.index'));
        $first->assertSessionHas('status');

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 7500,
        ]);
        $this->assertSame(1, WalletTransaction::query()->where('user_id', $user->id)->where('category', 'deposit')->count());

        $second = $this->actingAs($user)->get(route('wallet.paystack.callback', [
            'reference' => 'WAL_CB_001',
        ]));
        $second->assertRedirect(route('wallet.index'));

        $this->assertSame(1, WalletTransaction::query()->where('user_id', $user->id)->where('category', 'deposit')->count());
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 7500,
        ]);
    }

    public function test_paystack_webhook_credits_wallet_with_valid_signature(): void
    {
        $user = User::factory()->create();

        WalletFundingIntent::query()->create([
            'user_id' => $user->id,
            'reference' => 'WAL_WH_001',
            'amount' => 3000,
            'currency' => 'NGN',
            'status' => 'pending',
            'provider' => 'paystack',
        ]);

        Http::fake([
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 300_000,
                    'reference' => 'WAL_WH_001',
                ],
            ], 200),
        ]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'reference' => 'WAL_WH_001',
                'amount' => 300_000,
                'status' => 'success',
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha512', $payload, 'sk_test_cyra');

        $response = $this->call(
            'POST',
            route('webhooks.paystack'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertOk();

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 3000,
        ]);
        $this->assertDatabaseHas('wallet_funding_intents', [
            'reference' => 'WAL_WH_001',
            'status' => 'paid',
        ]);
    }

    public function test_simulated_deposit_still_works_without_paystack_outside_production(): void
    {
        config(['services.paystack.secret_key' => null]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('wallet.deposit'), [
            'amount' => 10000,
        ]);

        $response->assertRedirect(route('wallet.index'));
        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'balance' => 10000,
        ]);
    }
}
