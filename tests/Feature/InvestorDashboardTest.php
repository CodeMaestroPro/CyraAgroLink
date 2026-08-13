<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\InvestmentOpportunity;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\Wallet;
use App\Services\Investment\InvestmentMarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Investor Dashboard.
 */
class InvestorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_investor_sees_empty_live_dashboard(): void
    {
        $user = User::factory()->investor()->create([
            'name' => 'Tunde Adebayo',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Investor Dashboard');
        $response->assertDontSee('10. INVESTOR DASHBOARD');
        $response->assertSee('Welcome, Tunde', false);
        $response->assertSee('Live portfolio value, earnings, and holdings.', false);
        $response->assertSee('Total Portfolio Value', false);
        $response->assertSee('₦0', false);
        $response->assertSee('Total Earnings', false);
        $response->assertSee('Portfolio Performance', false);
        $response->assertSee('Active Investments', false);
        $response->assertSee('Total ROI', false);
        $response->assertSee('Recent payouts', false);
        $response->assertSee('Active holdings', false);
        $response->assertSee('No active holdings yet', false);
        $response->assertSee('Fund wallet', false);
        $response->assertSee('Browse farms', false);
        $response->assertSee('Investments', false);
        $response->assertDontSee('>Reports<', false);

        $this->assertSame(0, UserInvestment::query()->where('user_id', $user->id)->count());
    }

    public function test_investor_dashboard_shows_live_holdings_and_accrued_earnings(): void
    {
        $user = User::factory()->investor()->create(['name' => 'Tunde Adebayo']);
        $opportunity = $this->seedOpportunity();

        UserInvestment::query()->create([
            'user_id' => $user->id,
            'investment_opportunity_id' => $opportunity->id,
            'amount' => 600000,
            'accrued_earnings' => 0,
            'status' => 'active',
            'is_seeded' => false,
            'invested_at' => now()->subMonths(3)->setTime(10, 0),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        // monthly = 600000 * 24% / 6 = 24000; 3 months = 72000
        $response->assertOk();
        $response->assertSee('Maize Expansion', false);
        $response->assertSee('Oyo State', false);
        $response->assertSee('₦600,000', false);
        $response->assertSee('₦72,000', false);
        $response->assertSee('Collect ₦72,000', false);
        $response->assertSee('Investments', false);
        $response->assertSee('Wallet', false);
    }

    public function test_investor_can_collect_earnings_to_wallet_once(): void
    {
        $user = User::factory()->investor()->create();
        $opportunity = $this->seedOpportunity();

        $investment = UserInvestment::query()->create([
            'user_id' => $user->id,
            'investment_opportunity_id' => $opportunity->id,
            'amount' => 600000,
            'accrued_earnings' => 0,
            'status' => 'active',
            'is_seeded' => false,
            'invested_at' => now()->subMonths(3)->setTime(10, 0),
        ]);

        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 10000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('dashboard'));
        $investment->refresh();
        $pending = $investment->accrued_earnings;
        $this->assertSame(72000, $pending);

        $response = $this->actingAs($user)->post(route('investor.collect', $investment));

        $response->assertRedirect(route('investor.dashboard'));
        $response->assertSessionHas('status');

        $investment->refresh();
        $this->assertSame(0, $investment->accrued_earnings);
        $this->assertSame(82000, (int) Wallet::query()->where('user_id', $user->id)->value('balance'));
        $this->assertDatabaseHas('investment_payouts', [
            'user_id' => $user->id,
            'user_investment_id' => $investment->id,
            'amount' => 72000,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'earnings',
            'amount' => 72000,
            'type' => 'credit',
        ]);
        $this->assertDatabaseHas('user_inbox_notifications', [
            'user_id' => $user->id,
            'title' => 'Earnings collected',
        ]);

        $again = $this->actingAs($user)->post(route('investor.collect', $investment));
        $again->assertRedirect(route('investor.dashboard'));
        $again->assertSessionHas('error');
        $this->assertSame(82000, (int) Wallet::query()->where('user_id', $user->id)->value('balance'));
    }

    public function test_investor_cannot_collect_another_users_holding(): void
    {
        $owner = User::factory()->investor()->create();
        $intruder = User::factory()->investor()->create();
        $opportunity = $this->seedOpportunity();

        $investment = UserInvestment::query()->create([
            'user_id' => $owner->id,
            'investment_opportunity_id' => $opportunity->id,
            'amount' => 600000,
            'accrued_earnings' => 12000,
            'status' => 'active',
            'is_seeded' => false,
            'invested_at' => now()->subMonths(2),
        ]);

        $this->actingAs($intruder)
            ->post(route('investor.collect', $investment))
            ->assertRedirect(route('investor.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_investor_can_search_holdings_on_portfolio(): void
    {
        $user = User::factory()->investor()->create();
        $maize = $this->seedOpportunity();
        app(InvestmentMarketplaceService::class)->ensureCatalog();
        $fish = InvestmentOpportunity::query()
            ->where('title', 'Fish Farming (Catfish)')
            ->firstOrFail();

        UserInvestment::query()->create([
            'user_id' => $user->id,
            'investment_opportunity_id' => $maize->id,
            'amount' => 100000,
            'accrued_earnings' => 0,
            'status' => 'active',
            'is_seeded' => false,
            'invested_at' => now()->subMonth(),
        ]);
        UserInvestment::query()->create([
            'user_id' => $user->id,
            'investment_opportunity_id' => $fish->id,
            'amount' => 150000,
            'accrued_earnings' => 0,
            'status' => 'active',
            'is_seeded' => false,
            'invested_at' => now()->subMonth(),
        ]);

        $response = $this->actingAs($user)->get(route('investor.dashboard', ['q' => 'Maize']));

        $response->assertOk();
        $response->assertSee('Holdings matching', false);
        $response->assertSee('Maize Expansion', false);
        $response->assertDontSee('Fish Farming (Catfish)', false);
    }

    public function test_farmer_can_view_and_search_portfolio_holdings(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Farmer,
            'name' => 'Ada Farmer',
        ]);
        $opportunity = $this->seedOpportunity();

        UserInvestment::query()->create([
            'user_id' => $user->id,
            'investment_opportunity_id' => $opportunity->id,
            'amount' => 200000,
            'accrued_earnings' => 0,
            'status' => 'active',
            'is_seeded' => false,
            'invested_at' => now()->subMonths(2),
        ]);

        $portfolio = $this->actingAs($user)->get(route('investor.dashboard'));
        $portfolio->assertOk();
        $portfolio->assertSee('My Portfolio', false);
        $portfolio->assertSee('Maize Expansion', false);
        $portfolio->assertSee('Search my holdings', false);

        $search = $this->actingAs($user)->get(route('investor.dashboard', ['q' => 'Maize']));
        $search->assertOk();
        $search->assertSee('Maize Expansion', false);
        $search->assertSee('Holdings matching', false);

        $miss = $this->actingAs($user)->get(route('investor.dashboard', ['q' => 'UnmatchedFarmXYZ']));
        $miss->assertOk();
        $miss->assertSee('No holdings matched', false);
        $miss->assertDontSee('Maize Expansion', false);
    }

    public function test_investor_role_sees_investor_dashboard_on_main_dashboard_route(): void
    {
        $user = User::factory()->investor()->create([
            'name' => 'Tunde Adebayo',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Welcome, Tunde');
        $response->assertSee('Total Portfolio Value');
        $response->assertDontSee('Farm Overview');
    }

    public function test_guest_cannot_view_investor_dashboard(): void
    {
        $this->get(route('investor.dashboard'))->assertRedirect(route('login'));
    }

    protected function seedOpportunity(): InvestmentOpportunity
    {
        app(InvestmentMarketplaceService::class)->ensureCatalog();

        return InvestmentOpportunity::query()
            ->where('title', 'Maize Expansion')
            ->firstOrFail();
    }
}
