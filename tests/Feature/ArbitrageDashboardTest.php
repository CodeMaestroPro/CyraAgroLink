<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ArbitrageOpportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for AI Arbitrage Dashboard.
 */
class ArbitrageDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_arbitrage_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('arbitrage.show'));

        $response->assertOk();
        $response->assertSee('AI Arbitrage Dashboard');
        $response->assertDontSee('8. AI Arbitrage Dashboard');
        $response->assertSee('Best Arbitrage Opportunity');
        $response->assertSee('Kano → Lagos');
        $response->assertSee('Maize');
        $response->assertSee('₦45,600 / Ton');
        $response->assertSee('18.7% ROI');
        $response->assertSee('Buy Market');
        $response->assertSee('Sell Market');
        $response->assertSee('Cost Breakdown');
        $response->assertSee('AI Recommendation');
        $response->assertSee('View Full Analysis');
    }

    public function test_user_can_view_full_analysis(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('arbitrage.show'));

        $opportunity = ArbitrageOpportunity::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('arbitrage.analysis', $opportunity));

        $response->assertOk();
        $response->assertSee('Full Analysis');
        $response->assertSee('Net Profit / Ton');
        $response->assertSee('₦9,600');
    }

    public function test_guest_cannot_view_arbitrage_dashboard(): void
    {
        $this->get(route('arbitrage.show'))->assertRedirect(route('login'));
    }
}
