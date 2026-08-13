<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Models\CarbonAccount;
use App\Models\CarbonListing;
use App\Models\CarbonTransaction;
use App\Models\Farm;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Carbon Credit Marketplace.
 */
class CarbonCreditMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_carbon_credit_marketplace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('carbon.marketplace'));

        $response->assertOk();
        $response->assertSee('Carbon Credit Marketplace');
        $response->assertDontSee('29. CARBON CREDIT MARKETPLACE');
        $response->assertDontSee('29. Carbon Credit Marketplace');
        $response->assertSee('Carbon Impact Overview', false);
        $response->assertSee('Total Credits', false);
        $response->assertSee('tCO2e', false);
        $response->assertSee('Credits Earned', false);
        $response->assertSee('(This Month)', false);
        $response->assertSee('Potential Value', false);
        $response->assertSee('Sustainability Score', false);
        $response->assertSee('/100', false);
        $response->assertSee('Credits Trend', false);
        $response->assertSee('Recent Transactions', false);
        $response->assertSee('Sale to EcoMarket', false);
        $response->assertSee('Sale to GreenFuture', false);
        $response->assertSee('Purchase/Offset', false);
        $response->assertSee('View All Credits', false);
        $response->assertSee('List Credits for Sale', false);
        $response->assertSee('Claim field credits', false);
        $response->assertSee('Open Listings', false);
        $response->assertSee('Green Valley Farm', false);
    }

    public function test_guest_cannot_view_carbon_credit_marketplace(): void
    {
        $this->get(route('carbon.marketplace'))->assertRedirect(route('login'));
    }

    public function test_user_can_list_sell_and_offset_carbon_credits(): void
    {
        $user = User::factory()->create();
        Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Carbon Ridge Farm',
            'state' => 'Kaduna',
            'size_hectares' => '5.00',
            'soil_type' => 'Loamy',
            'crops' => ['Maize'],
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);

        $this->actingAs($user)->get(route('carbon.marketplace'))->assertOk();

        $account = CarbonAccount::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertGreaterThan(0, (float) $account->balance_tco2e);

        $this->actingAs($user)
            ->post(route('carbon.list'), [
                'credits' => 10,
                'unit_price_usd' => 14,
            ])
            ->assertRedirect(route('carbon.marketplace').'#listings');

        $listing = CarbonListing::query()->where('user_id', $user->id)->where('status', 'open')->firstOrFail();
        $this->assertEquals(10.0, (float) $listing->credits_tco2e);

        $this->actingAs($user)
            ->post(route('carbon.sell', $listing))
            ->assertRedirect(route('carbon.marketplace').'#transactions');

        $listing->refresh();
        $this->assertSame('sold', $listing->status);

        $sale = CarbonTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'sale')
            ->where('listing_id', $listing->id)
            ->firstOrFail();

        $this->assertGreaterThan(0, $sale->value_ngn);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'carbon',
            'reference_id' => $sale->id,
            'amount' => $sale->value_ngn,
        ]);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame($sale->value_ngn, (int) $wallet->balance);

        $this->actingAs($user)
            ->post(route('carbon.offset'), ['credits' => 5])
            ->assertRedirect(route('carbon.marketplace').'#transactions');

        $this->assertDatabaseHas('carbon_transactions', [
            'user_id' => $user->id,
            'type' => 'offset',
            'title' => 'Purchase/Offset',
        ]);
    }

    public function test_user_can_claim_monthly_field_credits_once(): void
    {
        $user = User::factory()->create();
        Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Claim Farm',
            'size_hectares' => '10.00',
            'soil_type' => 'Loamy',
            'registration_step' => 5,
            'status' => FarmStatus::Active,
            'registered_at' => now(),
        ]);

        $this->actingAs($user)->get(route('carbon.marketplace'))->assertOk();

        $before = (float) CarbonAccount::query()->where('user_id', $user->id)->value('balance_tco2e');

        $this->actingAs($user)
            ->post(route('carbon.generate'))
            ->assertRedirect(route('carbon.marketplace').'#credits');

        $after = (float) CarbonAccount::query()->where('user_id', $user->id)->value('balance_tco2e');
        $this->assertGreaterThan($before, $after);

        $blocked = $this->actingAs($user)->post(route('carbon.generate'));
        $blocked->assertRedirect(route('carbon.marketplace').'#credits');
        $this->followRedirects($blocked)->assertSee('Field credits for this month are already claimed', false);
    }

    public function test_user_cannot_sell_another_users_listing(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $listing = CarbonListing::query()->create([
            'user_id' => $owner->id,
            'credits_tco2e' => 5,
            'unit_price_usd' => 14,
            'status' => 'open',
            'listed_at' => now(),
        ]);

        $this->actingAs($intruder)
            ->post(route('carbon.sell', $listing))
            ->assertForbidden();
    }
}
