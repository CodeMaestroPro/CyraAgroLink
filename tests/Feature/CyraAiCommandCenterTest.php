<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for CyraAI Command Center.
 */
class CyraAiCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_cyraai_command_center(): void
    {
        $user = User::factory()->create([
            'name' => 'Adewale Okonkwo',
        ]);

        $response = $this->actingAs($user)->get(route('ai.command'));

        $response->assertOk();
        $response->assertSee('CyraAI Command Center');
        $response->assertDontSee('50. CYRAAI COMMAND CENTER');
        $response->assertDontSee('50. CyraAI Command Center');
        $response->assertSee('CyraAI Assistant', false);
        $response->assertSee('Hello Adewale! How can I help you today?', false);
        $response->assertSee('Analyze my farm performance', false);
        $response->assertSee('Predict market prices for maize', false);
        $response->assertSee('Check weather for my location', false);
        $response->assertSee('Recommend crops to plant', false);
        $response->assertSee('Ask CyraAI anything...', false);
        $response->assertSee('AI Insights', false);
        $response->assertSee('Maize price likely to increase by 12% in next 2 weeks.', false);
        $response->assertSee('Heavy rainfall expected in Kaduna State.', false);
    }

    public function test_guest_cannot_view_cyraai_command_center(): void
    {
        $this->get(route('ai.command'))->assertRedirect(route('login'));
    }
}
