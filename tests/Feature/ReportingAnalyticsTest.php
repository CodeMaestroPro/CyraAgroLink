<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\FarmStatus;
use App\Models\AnalyticsSnapshot;
use App\Models\CustomReportRequest;
use App\Models\Farm;
use App\Models\User;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Reporting & Analytics.
 */
class ReportingAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_reporting_analytics(): void
    {
        $user = User::factory()->create();
        Farm::query()->create([
            'user_id' => $user->id,
            'name' => 'Oyo Demo Farm',
            'state' => 'Oyo',
            'status' => FarmStatus::Active,
            'registration_step' => 4,
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reporting.analytics'));

        $response->assertOk();
        $response->assertSee('Reporting & Analytics');
        $response->assertDontSee('20. REPORTING & ANALYTICS');
        $response->assertDontSee('20. Reporting & Analytics');
        $response->assertSee('Business Overview', false);
        $response->assertSee('Key performance indicators', false);
        $response->assertSee('Total Revenue', false);
        $response->assertSee('Total Users', false);
        $response->assertSee('Total Transactions', false);
        $response->assertSee('Total Farms', false);
        $response->assertSee('Revenue Trend', false);
        $response->assertSee('Transactions', false);
        $response->assertSee('Revenue by Segment', false);
        $response->assertSee('Marketplace', false);
        $response->assertSee('Investments', false);
        $response->assertSee('Logistics', false);
        $response->assertSee('Warehouse', false);
        $response->assertSee('Others', false);
        $response->assertSee('Top Performing Regions', false);
        $response->assertSee('Export Report', false);
        $response->assertSee('Financial Reports', false);
        $response->assertSee('Custom Reports', false);
        $response->assertSee('Data Export', false);
        $response->assertSee('Refresh', false);
        $response->assertSee('Oyo', false);

        $this->assertSame(1, AnalyticsSnapshot::query()->count());
    }

    public function test_guest_cannot_view_reporting_analytics(): void
    {
        $this->get(route('reporting.analytics'))->assertRedirect(route('login'));
    }

    public function test_user_can_refresh_create_custom_report_and_export(): void
    {
        $user = User::factory()->create();
        app(DigitalWalletService::class)->deposit($user, 500_000, 'Seed for analytics');

        $this->actingAs($user)->get(route('reporting.analytics'))->assertOk();
        $before = AnalyticsSnapshot::query()->count();

        $this->actingAs($user)
            ->post(route('reporting.refresh'), ['period' => '6m'])
            ->assertRedirect(route('reporting.analytics', ['period' => '6m']).'#overview');

        $this->assertSame($before + 1, AnalyticsSnapshot::query()->count());

        $this->actingAs($user)
            ->post(route('reporting.custom.store'), [
                'title' => 'Marketplace deep dive',
                'report_type' => 'segment',
                'period' => '3m',
                'segment' => 'Marketplace',
                'notes' => 'Board pack',
            ])
            ->assertRedirect(route('reporting.analytics', ['period' => '3m']).'#custom');

        $report = CustomReportRequest::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('ready', $report->status);

        $download = $this->actingAs($user)->get(route('reporting.custom.download', $report));
        $download->assertOk();
        $this->assertStringContainsString('text/csv', (string) $download->headers->get('content-type'));
        $this->assertStringContainsString('Marketplace deep dive', $download->streamedContent());
        $this->assertSame('downloaded', $report->fresh()->status);

        $export = $this->actingAs($user)->get(route('reporting.export', ['period' => '6m']));
        $export->assertOk();
        $csv = $export->streamedContent();
        $this->assertStringContainsString('CyraAgroLink Reporting & Analytics', $csv);
        $this->assertStringContainsString('Total Revenue', $csv);
    }

    public function test_period_filter_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('reporting.analytics', ['period' => '3m']))
            ->assertOk()
            ->assertSee('3 months', false);

        $this->assertSame('3m', AnalyticsSnapshot::query()->latest('id')->value('period'));
    }
}
