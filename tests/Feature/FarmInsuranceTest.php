<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InsuranceClaim;
use App\Models\InsurancePlan;
use App\Models\InsurancePolicy;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Farm Insurance Platform.
 */
class FarmInsuranceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_farm_insurance_platform(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('insurance.platform'));

        $response->assertOk();
        $response->assertSee('Farm Insurance Platform');
        $response->assertDontSee('33. FARM INSURANCE PLATFORM');
        $response->assertDontSee('33. Farm Insurance Platform');
        $response->assertSee('Insurance Overview', false);
        $response->assertSee('Active Policies', false);
        $response->assertSee('Total Coverage', false);
        $response->assertSee('Claims This Month', false);
        $response->assertSee('Paid Claims', false);
        $response->assertSee('My Policies', false);
        $response->assertSee('Recent Claims', false);
        $response->assertSee('Buy New Policy', false);
        $response->assertSee('Crop Multi-Peril Cover', false);
        $response->assertSee('Weather Index Policy', false);
        $response->assertSee('Poultry Flock Cover', false);
        $response->assertSee('Aquaculture Pond Cover', false);
        $response->assertSee('Farm Equipment Cover', false);
        $response->assertSee('File a Claim', false);
        $response->assertSee('Green Valley Farm', false);
    }

    public function test_guest_cannot_view_farm_insurance_platform(): void
    {
        $this->get(route('insurance.platform'))->assertRedirect(route('login'));
    }

    public function test_user_can_buy_policy_file_claim_and_receive_payout(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 5_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('insurance.platform'))->assertOk();

        $plan = InsurancePlan::query()->where('slug', 'crop-multi-peril')->firstOrFail();
        $farmId = $user->farms()->firstOrFail()->id;

        $this->actingAs($user)
            ->post(route('insurance.policies.store'), [
                'plan_id' => $plan->id,
                'farm_id' => $farmId,
            ])
            ->assertRedirect(route('insurance.platform').'#policies');

        $policy = InsurancePolicy::query()
            ->where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->firstOrFail();

        $this->assertSame('active', $policy->status);
        $this->assertSame($plan->premium_ngn, $policy->premium_ngn);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'insurance',
            'reference_id' => $policy->id,
            'amount' => $plan->premium_ngn,
        ]);

        $balanceAfterPremium = (int) Wallet::query()->where('user_id', $user->id)->value('balance');
        $this->assertSame(5_000_000 - $plan->premium_ngn, $balanceAfterPremium);

        $this->actingAs($user)
            ->get(route('insurance.platform'))
            ->assertOk()
            ->assertSee($policy->reference, false)
            ->assertSee('Crop Multi-Peril Cover', false);

        $this->actingAs($user)
            ->post(route('insurance.claims.store'), [
                'policy_id' => $policy->id,
                'title' => 'Flood damage to maize field',
                'description' => 'Heavy rainfall washed seedlings.',
                'amount_requested_ngn' => 400_000,
            ])
            ->assertRedirect(route('insurance.platform').'#claims');

        $claim = InsuranceClaim::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('submitted', $claim->status);
        $this->assertSame(400_000, $claim->amount_requested_ngn);

        $this->actingAs($user)
            ->post(route('insurance.claims.advance', $claim), ['action' => 'next'])
            ->assertRedirect(route('insurance.platform').'#claims');
        $this->assertSame('under_review', $claim->fresh()->status);

        $this->actingAs($user)
            ->post(route('insurance.claims.advance', $claim), ['action' => 'next'])
            ->assertRedirect(route('insurance.platform').'#claims');
        $this->assertSame('approved', $claim->fresh()->status);

        $this->actingAs($user)
            ->post(route('insurance.claims.advance', $claim), ['action' => 'next'])
            ->assertRedirect(route('insurance.platform').'#claims');

        $claim->refresh();
        $this->assertSame('paid', $claim->status);
        $this->assertSame(400_000, $claim->amount_paid_ngn);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'insurance_claim',
            'reference_id' => $claim->id,
            'amount' => 400_000,
        ]);

        $this->assertSame(
            $balanceAfterPremium + 400_000,
            (int) Wallet::query()->where('user_id', $user->id)->value('balance')
        );
    }

    public function test_user_cannot_buy_policy_without_wallet_funds(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 100,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('insurance.platform'))->assertOk();
        $plan = InsurancePlan::query()->where('slug', 'weather-index')->firstOrFail();
        $farmId = $user->farms()->firstOrFail()->id;

        $response = $this->actingAs($user)->post(route('insurance.policies.store'), [
            'plan_id' => $plan->id,
            'farm_id' => $farmId,
        ]);

        $response->assertRedirect(route('insurance.platform').'#buy');
        $this->followRedirects($response)->assertSee('Insufficient', false);

        $this->assertSame(0, InsurancePolicy::query()->where('user_id', $user->id)->count());
    }

    public function test_user_can_reject_claim_during_review(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 5_000_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('insurance.platform'))->assertOk();
        $plan = InsurancePlan::query()->where('slug', 'poultry-flock')->firstOrFail();
        $farmId = $user->farms()->firstOrFail()->id;

        $this->actingAs($user)->post(route('insurance.policies.store'), [
            'plan_id' => $plan->id,
            'farm_id' => $farmId,
        ]);

        $policy = InsurancePolicy::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->post(route('insurance.claims.store'), [
            'policy_id' => $policy->id,
            'title' => 'Layer mortality event',
            'amount_requested_ngn' => 250_000,
        ]);

        $claim = InsuranceClaim::query()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('insurance.claims.advance', $claim), ['action' => 'reject'])
            ->assertRedirect(route('insurance.platform').'#claims');

        $this->assertSame('rejected', $claim->fresh()->status);
        $this->assertSame(
            0,
            \App\Models\WalletTransaction::query()
                ->where('user_id', $user->id)
                ->where('category', 'insurance_claim')
                ->count()
        );
    }
}
