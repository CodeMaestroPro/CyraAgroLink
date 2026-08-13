<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Models\BiInsight;
use App\Models\BiSnapshot;
use App\Models\Farm;
use App\Models\MarketplaceCommodity;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Business Intelligence Command Center.
 */
class BusinessIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_business_intelligence_command_center(): void
    {
        $user = User::factory()->create();
        Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Green Acre',
            'state' => 'Oyo',
            'crops' => ['Maize', 'Cassava'],
            'status' => FarmStatus::Active,
            'registration_step' => 4,
            'registered_at' => now(),
        ]);
        MarketplaceCommodity::query()->create([
            'user_id' => $user->id,
            'name' => 'Maize',
            'price_per_ton' => 280000,
            'volume_tons' => 120,
            'is_featured' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('intelligence.command'));

        $response->assertOk();
        $response->assertSee('Business Intelligence Command Center');
        $response->assertDontSee('48. BUSINESS INTELLIGENCE COMMAND CENTER');
        $response->assertDontSee('48. Business Intelligence Command Center');
        $response->assertSee('Total Revenue', false);
        $response->assertSee('Total Users', false);
        $response->assertSee('Transactions', false);
        $response->assertSee('Active Farms', false);
        $response->assertSee('Revenue Trend', false);
        $response->assertSee('Top Performing Commodities', false);
        $response->assertSee('Maize', false);
        $response->assertSee('Executive Insights', false);
        $response->assertSee('View Full Analytics', false);
        $response->assertSee('Refresh', false);
        $response->assertSee('Export', false);

        $this->assertSame(1, BiSnapshot::query()->count());
        $this->assertGreaterThanOrEqual(1, BiInsight::query()->where('user_id', $user->id)->count());
    }

    public function test_guest_cannot_view_business_intelligence_command_center(): void
    {
        $this->get(route('intelligence.command'))->assertRedirect(route('login'));
    }

    public function test_user_can_refresh_manage_insights_and_export(): void
    {
        $user = User::factory()->create();
        app(DigitalWalletService::class)->deposit($user, 750_000, 'BI seed');

        $this->actingAs($user)->get(route('intelligence.command'))->assertOk();
        $before = BiSnapshot::query()->count();

        $this->actingAs($user)
            ->post(route('intelligence.refresh'), ['period' => '6m'])
            ->assertRedirect(route('intelligence.command', ['period' => '6m']).'#summary');

        $this->assertSame($before + 1, BiSnapshot::query()->count());

        $this->actingAs($user)
            ->post(route('intelligence.insights.store'), [
                'title' => 'Expand maize corridor capacity',
                'detail' => 'Prioritize Oyo and Kaduna offtake lanes.',
                'category' => 'commodities',
                'severity' => 'high',
                'period' => '6m',
            ])
            ->assertRedirect(route('intelligence.command', ['period' => '6m']).'#insights');

        $manual = BiInsight::query()
            ->where('user_id', $user->id)
            ->where('title', 'Expand maize corridor capacity')
            ->firstOrFail();

        $auto = BiInsight::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->where('id', '!=', $manual->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('intelligence.insights.acknowledge', $auto), ['period' => '6m'])
            ->assertRedirect(route('intelligence.command', ['period' => '6m']).'#insights');

        $this->assertSame('acknowledged', $auto->fresh()->status);

        $this->actingAs($user)
            ->post(route('intelligence.insights.pin', $manual), ['period' => '6m'])
            ->assertRedirect(route('intelligence.command', ['period' => '6m']).'#insights');

        $this->assertSame('pinned', $manual->fresh()->status);

        $this->actingAs($user)
            ->post(route('intelligence.insights.dismiss', $auto), ['period' => '6m'])
            ->assertRedirect(route('intelligence.command', ['period' => '6m']).'#insights');

        $this->assertSame('dismissed', $auto->fresh()->status);

        $export = $this->actingAs($user)->get(route('intelligence.export', ['period' => '6m']));
        $export->assertOk();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('CyraAgroLink Business Intelligence Command Center', $csv);
        $this->assertStringContainsString('Total Revenue', $csv);
        $this->assertStringContainsString('Expand maize corridor capacity', $csv);
    }
}
