<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\RiskAlert;
use App\Models\RiskAssessment;
use App\Models\RiskMitigation;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for AI Risk Intelligence Center.
 */
class RiskIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_risk_intelligence_center(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('risk.intelligence'));

        $response->assertOk();
        $response->assertSee('AI Risk Intelligence Center');
        $response->assertDontSee('34. AI RISK INTELLIGENCE CENTER');
        $response->assertDontSee('34. AI Risk Intelligence Center');
        $response->assertSee('Risk Overview', false);
        $response->assertSee('Overall Risk Score', false);
        $response->assertSee('Market Risk', false);
        $response->assertSee('Weather Risk', false);
        $response->assertSee('Disease Risk', false);
        $response->assertSee('Supply Chain Risk', false);
        $response->assertSee('Political Risk', false);
        $response->assertSee('Credit Risk', false);
        $response->assertSee('Fraud Risk', false);
        $response->assertSee('Risk Alerts', false);
        $response->assertSee('View Risk Report', false);
        $response->assertSee('Refresh score', false);
        $response->assertSee('Plan Mitigation', false);

        $this->assertSame(1, RiskAssessment::query()->where('user_id', $user->id)->count());
        $this->assertGreaterThan(0, RiskAlert::query()->where('user_id', $user->id)->count());
    }

    public function test_guest_cannot_view_risk_intelligence_center(): void
    {
        $this->get(route('risk.intelligence'))->assertRedirect(route('login'));
    }

    public function test_user_can_refresh_acknowledge_dismiss_and_mitigate(): void
    {
        $user = User::factory()->create();
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 10_000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($user)->get(route('risk.intelligence'))->assertOk();

        $before = RiskAssessment::query()->where('user_id', $user->id)->count();

        $this->actingAs($user)
            ->post(route('risk.refresh'))
            ->assertRedirect(route('risk.intelligence').'#score');

        $this->assertSame($before + 1, RiskAssessment::query()->where('user_id', $user->id)->count());

        $alert = RiskAlert::query()
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('risk.alerts.acknowledge', $alert))
            ->assertRedirect(route('risk.intelligence').'#alerts');

        $this->assertSame('acknowledged', $alert->fresh()->status);

        $this->actingAs($user)
            ->post(route('risk.mitigations.store'), [
                'title' => 'Fund wallet buffer',
                'action_type' => 'wallet_topup',
                'alert_id' => $alert->id,
            ])
            ->assertRedirect(route('risk.intelligence').'#mitigations');

        $mitigation = RiskMitigation::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('planned', $mitigation->status);
        $this->assertSame('wallet_topup', $mitigation->action_type);

        $this->actingAs($user)
            ->post(route('risk.mitigations.complete', $mitigation))
            ->assertRedirect(route('risk.intelligence').'#mitigations');

        $this->assertSame('done', $mitigation->fresh()->status);

        $openAlert = RiskAlert::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'acknowledged'])
            ->firstOrFail();

        $this->actingAs($user)
            ->post(route('risk.alerts.dismiss', $openAlert))
            ->assertRedirect(route('risk.intelligence').'#alerts');

        $this->assertSame('dismissed', $openAlert->fresh()->status);
    }

    public function test_user_can_export_risk_report_csv(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('risk.intelligence'))->assertOk();

        $response = $this->actingAs($user)->get(route('risk.export'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('CyraAgroLink Risk Intelligence Report', $content);
        $this->assertStringContainsString('Overall Score', $content);
        $this->assertStringContainsString('Market Risk', $content);
    }
}
