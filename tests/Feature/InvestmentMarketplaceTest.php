<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Http\Middleware\SetLocale;
use App\Models\InvestmentOpportunity;
use App\Models\InvestmentReview;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Investment Marketplace.
 */
class InvestmentMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_investment_marketplace(): void
    {
        $user = User::factory()->investor()->create();

        $response = $this->actingAs($user)->get(route('investments.index'));

        $response->assertOk();
        $response->assertSee('Investment Marketplace');
        $response->assertDontSee('9. Investment Marketplace');
        $response->assertSee('Invest in Quality Farms');
        $response->assertSee('High returns. Real impact.');
        $response->assertSee('Featured Investment Opportunities');
        $response->assertSee('Maize Expansion');
        $response->assertSee('Oyo State');
        $response->assertSee('Expand maize production with improved seed', false);
        $response->assertSee('Cassava Farm');
        $response->assertSee('Rice Production');
        $response->assertSee('₦2,500,000');
        $response->assertSee('View photos and reviews', false);
        $response->assertSee('View All Opportunities');
    }

    public function test_opportunity_card_writeups_follow_selected_locale(): void
    {
        $user = User::factory()->investor()->create();

        $this->actingAs($user)
            ->withSession([SetLocale::SESSION_KEY => 'fr'])
            ->get(route('investments.index'))
            ->assertOk()
            ->assertSee('Expansion du maïs', false)
            ->assertSee('État d’Oyo', false)
            ->assertSee('Développer la production de maïs', false)
            ->assertSee('Ferme de manioc', false)
            ->assertSee('Voir photos et avis', false)
            ->assertDontSee('Maize Expansion', false);
    }

    public function test_user_can_view_all_opportunities_with_type_specific_galleries(): void
    {
        $user = User::factory()->investor()->create();

        $response = $this->actingAs($user)->get(route('investments.index', ['all' => 1]));

        $response->assertOk();
        $response->assertSee('All Investment Opportunities');
        $response->assertSee('Layers Poultry Unit', false);
        $response->assertSee('Broiler Poultry Farm', false);
        $response->assertSee('Fish Farming (Catfish)', false);
        $response->assertSee('images/investments/fish-1.jpg', false);
        $response->assertSee('images/investments/layers-1.jpg', false);
        $response->assertSee('images/investments/broilers-1.jpg', false);
    }

    public function test_investor_can_view_three_farm_photos_and_post_review(): void
    {
        $user = User::factory()->investor()->create();

        $this->actingAs($user)->get(route('investments.index', ['all' => 1]));

        $opportunity = InvestmentOpportunity::query()
            ->where('title', 'Fish Farming (Catfish)')
            ->firstOrFail();

        $this->assertCount(3, $opportunity->galleryPaths());
        $this->assertSame('images/investments/fish-1.jpg', $opportunity->galleryPaths()[0]);

        $page = $this->actingAs($user)->get(route('investments.show', $opportunity));
        $page->assertOk();
        $page->assertSee('Fish Farming (Catfish)', false);
        $page->assertSee('3 farm photos', false);
        $page->assertSee('images/investments/fish-1.jpg', false);
        $page->assertSee('images/investments/fish-2.jpg', false);
        $page->assertSee('images/investments/fish-3.jpg', false);
        $page->assertSee('Investor reviews', false);
        $page->assertSee('Invest to leave a review', false);
        $page->assertDontSee('Write a review', false);

        $blocked = $this->actingAs($user)->post(route('investments.reviews.store', $opportunity), [
            'rating' => 5,
            'title' => 'Strong pond operations',
            'body' => 'Clear stocking plan and realistic harvest timelines for catfish.',
        ]);
        $blocked->assertRedirect(route('investments.show', $opportunity));
        $blocked->assertSessionHas('error');

        $this->actingAs($user)->post(route('wallet.deposit'), ['amount' => 100000]);
        $this->actingAs($user)->post(route('investments.invest', $opportunity), [
            'amount' => 50000,
            'detail' => 1,
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'investment',
            'title' => 'Farm Investment',
            'amount' => 50000,
            'type' => 'debit',
        ]);

        $response = $this->actingAs($user)->post(route('investments.reviews.store', $opportunity), [
            'rating' => 5,
            'title' => 'Strong pond operations',
            'body' => 'Clear stocking plan and realistic harvest timelines for catfish.',
        ]);

        $response->assertRedirect(route('investments.show', $opportunity));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('investment_reviews', [
            'user_id' => $user->id,
            'investment_opportunity_id' => $opportunity->id,
            'rating' => 5,
            'title' => 'Strong pond operations',
        ]);

        $updated = $this->actingAs($user)->get(route('investments.show', $opportunity));
        $updated->assertSee('Strong pond operations', false);
        $updated->assertSee('Update your review', false);
        $updated->assertSee('You already hold a stake in this farm.', false);
        $updated->assertSee('★ 5.0', false);
        $updated->assertSee('Open portfolio', false);
    }

    public function test_user_can_invest_from_wallet_on_marketplace(): void
    {
        $user = User::factory()->investor()->create();

        $this->actingAs($user)->get(route('investments.index', ['all' => 1]));
        $this->actingAs($user)->post(route('wallet.deposit'), ['amount' => 500000]);

        $opportunity = InvestmentOpportunity::query()
            ->where('title', 'Rice Production')
            ->firstOrFail();

        $response = $this->actingAs($user)->post(route('investments.invest', $opportunity), [
            'amount' => 100000,
            'detail' => 1,
        ]);

        $response->assertRedirect(route('investments.show', $opportunity));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('user_investments', [
            'user_id' => $user->id,
            'investment_opportunity_id' => $opportunity->id,
            'amount' => 100000,
            'status' => 'active',
            'is_seeded' => 0,
        ]);

        $this->assertSame(400000, (int) Wallet::query()->where('user_id', $user->id)->value('balance'));
        $this->assertSame(2, (int) $opportunity->fresh()->funded_percent);
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'category' => 'investment',
            'title' => 'Farm Investment',
            'amount' => 100000,
        ]);
    }

    public function test_empty_portfolio_can_still_invest_from_marketplace(): void
    {
        $user = User::factory()->investor()->create();

        $this->actingAs($user)->get(route('investor.dashboard'));
        $this->assertSame(0, UserInvestment::query()->where('user_id', $user->id)->count());

        $this->actingAs($user)->post(route('wallet.deposit'), ['amount' => 200000]);

        $opportunity = InvestmentOpportunity::query()
            ->where('title', 'Maize Expansion')
            ->firstOrFail();

        $this->assertGreaterThan(100000, $opportunity->remainingCapacity());

        $response = $this->actingAs($user)->post(route('investments.invest', $opportunity), [
            'amount' => 50000,
            'detail' => 1,
        ]);

        $response->assertRedirect(route('investments.show', $opportunity));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('user_investments', [
            'user_id' => $user->id,
            'investment_opportunity_id' => $opportunity->id,
            'amount' => 50000,
            'is_seeded' => 0,
        ]);
        $this->assertDatabaseHas('user_inbox_notifications', [
            'user_id' => $user->id,
            'title' => 'Investment confirmed',
        ]);
    }

    public function test_invest_requires_funded_wallet(): void
    {
        $user = User::factory()->investor()->create();

        $this->actingAs($user)->get(route('investments.index'));

        $opportunity = InvestmentOpportunity::query()
            ->where('title', 'Maize Expansion')
            ->firstOrFail();

        $response = $this->actingAs($user)->post(route('investments.invest', $opportunity), [
            'amount' => 50000,
            'detail' => 1,
        ]);

        $response->assertRedirect(route('investments.show', $opportunity));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('user_investments', [
            'user_id' => $user->id,
            'investment_opportunity_id' => $opportunity->id,
            'amount' => 50000,
        ]);
        $this->assertSame(0, InvestmentReview::query()->count());
    }

    public function test_investor_can_search_opportunities(): void
    {
        $user = User::factory()->investor()->create();

        $this->actingAs($user)->get(route('investments.index'));

        $response = $this->actingAs($user)->get(route('investments.index', [
            'all' => 1,
            'q' => 'Maize',
        ]));

        $response->assertOk();
        $response->assertSee('Search results for', false);
        $response->assertSee('Maize', false);
        $response->assertSee('Maize Expansion', false);
        $response->assertDontSee('Fish Farming (Catfish)', false);
    }

    public function test_farmer_can_search_investment_opportunities(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Farmer,
        ]);

        $this->actingAs($user)->get(route('investments.index'));

        $response = $this->actingAs($user)->get(route('investments.index', [
            'all' => 1,
            'q' => 'Maize',
        ]));

        $response->assertOk();
        $response->assertSee('Search results for', false);
        $response->assertSee('Maize Expansion', false);
        $response->assertDontSee('Fish Farming (Catfish)', false);
        $response->assertSee('My Portfolio', false);
    }

    public function test_guest_cannot_view_investments(): void
    {
        $this->get(route('investments.index'))->assertRedirect(route('login'));
    }
}
