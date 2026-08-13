<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\WeatherAlert;
use App\Models\WeatherSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests for Weather Intelligence.
 */
class WeatherIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep tests offline-deterministic (climate model path).
        Http::fake([
            'api.open-meteo.com/*' => Http::response([], 503),
        ]);
    }

    public function test_authenticated_user_can_view_weather_intelligence(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('weather.intelligence'));

        $response->assertOk();
        $response->assertSee('Weather Intelligence');
        $response->assertDontSee('18. WEATHER INTELLIGENCE');
        $response->assertDontSee('18. Weather Intelligence');
        $response->assertSee('Ibadan, Oyo State', false);
        $response->assertSee('Update:', false);
        $response->assertSee('Today,', false);
        $response->assertSee('°C', false);
        $response->assertSee('Humidity', false);
        $response->assertSee('Rainfall', false);
        $response->assertSee('Wind', false);
        $response->assertSee('km/h', false);
        $response->assertSee('5-Day Forecast', false);
        $response->assertSee('Rainfall Map', false);
        $response->assertSee('Weather Alerts', false);
        $response->assertSee('AI Recommendation', false);
        $response->assertSee('View Full Forecast', false);
        $response->assertSee('Overview', false);
        $response->assertSee('Forecast', false);
        $response->assertSee('Satellite', false);
        $response->assertSee('Historical Data', false);
        $response->assertSee('Refresh', false);
        $response->assertSee('Export CSV', false);

        $this->assertSame(1, WeatherSnapshot::query()->where('user_id', $user->id)->count());
        $this->assertGreaterThanOrEqual(1, WeatherAlert::query()->where('user_id', $user->id)->count());
    }

    public function test_guest_cannot_view_weather_intelligence(): void
    {
        $this->get(route('weather.intelligence'))->assertRedirect(route('login'));
    }

    public function test_user_can_refresh_switch_location_and_manage_alerts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('weather.intelligence'))->assertOk();
        $before = WeatherSnapshot::query()->where('user_id', $user->id)->count();

        $this->actingAs($user)
            ->post(route('weather.refresh'), ['location' => 'ibadan'])
            ->assertRedirect(route('weather.intelligence', ['location' => 'ibadan']).'#overview');

        $this->assertSame($before + 1, WeatherSnapshot::query()->where('user_id', $user->id)->count());

        $this->actingAs($user)
            ->get(route('weather.intelligence', ['location' => 'lagos']))
            ->assertOk()
            ->assertSee('Lagos, Lagos State', false);

        $alert = WeatherAlert::query()->where('user_id', $user->id)->where('status', 'open')->firstOrFail();

        $this->actingAs($user)
            ->post(route('weather.alerts.acknowledge', $alert), ['location' => 'ibadan'])
            ->assertRedirect(route('weather.intelligence', ['location' => 'ibadan']).'#alerts');

        $this->assertSame('acknowledged', $alert->fresh()->status);

        $this->actingAs($user)
            ->post(route('weather.alerts.dismiss', $alert), ['location' => 'ibadan'])
            ->assertRedirect(route('weather.intelligence', ['location' => 'ibadan']).'#alerts');

        $this->assertSame('dismissed', $alert->fresh()->status);
    }

    public function test_user_can_export_weather_report(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('weather.intelligence'))->assertOk();

        $response = $this->actingAs($user)->get(route('weather.export', ['location' => 'ibadan']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('CyraAgroLink Weather Intelligence Report', $csv);
        $this->assertStringContainsString('Ibadan, Oyo State', $csv);
        $this->assertStringContainsString('Forecast Day', $csv);
    }
}
