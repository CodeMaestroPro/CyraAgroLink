<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MarketWatchlist;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Market Intelligence.
 */
class MarketIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_market_intelligence(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('market.intelligence'));

        $response->assertOk();
        $response->assertSee('Market Intelligence');
        $response->assertDontSee('17. MARKET INTELLIGENCE');
        $response->assertDontSee('17. Market Intelligence');
        $response->assertSee('Market Overview', false);
        $response->assertSee('Overview', false);
        $response->assertSee('Commodities', false);
        $response->assertSee('Price Trends', false);
        $response->assertSee('Demand Forecast', false);
        $response->assertSee('Import / Export', false);
        $response->assertSee('View Full Report', false);
        $response->assertSee('Maize Price / Ton', false);
        $response->assertSee('₦320,000', false);
        $response->assertSee('Rice Price / Ton', false);
        $response->assertSee('₦780,000', false);
        $response->assertSee('Price Trend (Maize)', false);
        $response->assertSee('Demand Forecast (Maize)', false);
        $response->assertSee('Top Export Destinations', false);
        $response->assertSee('China', false);
        $response->assertSee('Netherlands', false);
        $response->assertSee('Spain', false);
        $response->assertSee('Market Alerts', false);
        $response->assertSee('Focus commodity', false);
    }

    public function test_user_can_switch_commodity_and_tab(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('market.intelligence'));

        $rice = MarketplaceCommodity::query()->where('name', 'Rice')->firstOrFail();

        $response = $this->actingAs($user)->get(route('market.intelligence', [
            'tab' => 'trends',
            'commodity' => $rice->id,
            'range' => '1M',
        ]));

        $response->assertOk();
        $response->assertSee('Price Trend (Rice)', false);
        $response->assertDontSee('Demand Forecast (Rice)');
    }

    public function test_user_can_watch_and_unwatch_commodity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('market.intelligence'));

        $maize = MarketplaceCommodity::query()->where('name', 'Maize')->firstOrFail();

        $this->actingAs($user)
            ->post(route('market.watch', $maize), ['tab' => 'commodities'])
            ->assertRedirect();

        $this->assertDatabaseHas('market_watchlists', [
            'user_id' => $user->id,
            'commodity_id' => $maize->id,
        ]);

        $alerts = $this->actingAs($user)->get(route('market.intelligence', ['tab' => 'alerts']));
        $alerts->assertOk();
        $alerts->assertSee('Watchlist: Maize', false);

        $this->actingAs($user)
            ->delete(route('market.unwatch', $maize), ['tab' => 'commodities'])
            ->assertRedirect();

        $this->assertDatabaseMissing('market_watchlists', [
            'user_id' => $user->id,
            'commodity_id' => $maize->id,
        ]);
    }

    public function test_user_can_export_market_report(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('market.intelligence'));

        $response = $this->actingAs($user)->get(route('market.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Maize', $csv);
        $this->assertStringContainsString('Rice', $csv);
        $this->assertStringContainsString('Commodity', $csv);
    }

    public function test_commodities_tab_lists_live_catalog(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('market.intelligence'));

        $response = $this->actingAs($user)->get(route('market.intelligence', ['tab' => 'commodities']));

        $response->assertOk();
        $response->assertSee('Watch', false);
        $response->assertSee('Trade', false);
        $response->assertSee('Cocoa', false);
        $this->assertGreaterThan(0, MarketWatchlist::query()->count() + MarketplaceCommodity::query()->count());
    }

    public function test_guest_cannot_view_market_intelligence(): void
    {
        $this->get(route('market.intelligence'))->assertRedirect(route('login'));
    }
}
