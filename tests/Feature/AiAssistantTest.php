<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\AiAssistantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for session-based AI Assistant (clears on refresh).
 */
class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_fresh_chat_not_seeded_history(): void
    {
        $user = User::factory()->create([
            'name' => 'Adewale Okonkwo',
        ]);

        $response = $this->actingAs($user)->get(route('ai.assistant'));

        $response->assertOk();
        $response->assertSee('AI Assistant');
        $response->assertSee('New Chat', false);
        $response->assertSee('CyraAI Assistant', false);
        $response->assertSee('Ask any farming question', false);
        $response->assertSee('Hello Adewale', false);
        $response->assertSee('Ask me any farming question', false);
        $response->assertSee('Chats stay for this visit only', false);
        $response->assertDontSee('Maize leaf disease');
        $response->assertDontSee('16. AI ASSISTANT');
    }

    public function test_guest_cannot_view_ai_assistant(): void
    {
        $this->get(route('ai.assistant'))->assertRedirect(route('login'));
    }

    public function test_user_can_send_message_and_see_specific_reply(): void
    {
        $user = User::factory()->create(['name' => 'Chioma Eze']);

        $page = $this->actingAs($user)->get(route('ai.assistant'));
        $page->assertOk();

        $conversationId = session(AiAssistantService::SESSION_KEY)['active_id'] ?? null;
        $this->assertNotNull($conversationId);

        $this->actingAs($user)->post(
            route('ai.assistant.message', $conversationId),
            ['prompt' => 'What is the best time to plant maize?']
        )->assertRedirect(route('ai.assistant', ['conversation' => $conversationId]).'#chat-end');

        $this->actingAs($user)
            ->get(route('ai.assistant', ['conversation' => $conversationId]))
            ->assertOk()
            ->assertSee('What is the best time to plant maize?', false)
            ->assertSee('reliable showers', false)
            ->assertDontSee('Practical next step:', false);
    }

    public function test_json_message_returns_reply_for_live_loader_ui(): void
    {
        $user = User::factory()->create(['name' => 'Grace Okoro']);

        $this->actingAs($user)->get(route('ai.assistant'))->assertOk();
        $conversationId = session(AiAssistantService::SESSION_KEY)['active_id'];

        $response = $this->actingAs($user)->postJson(
            route('ai.assistant.message', $conversationId),
            ['prompt' => 'Which type of soil is best for maize cultivation?']
        );

        $response->assertOk();
        $response->assertJsonPath('reply.type', 'text');
        $response->assertJsonFragment(['title' => 'Which Type Of Soil Is Best For Maize Cultivation?']);
        $this->assertStringContainsString('loamy', (string) $response->json('reply.body'));
        $this->assertStringContainsString('CyraAI is thinking', $this->actingAs($user)->get(route('ai.assistant'))->getContent());
    }

    public function test_page_refresh_clears_chats(): void
    {
        $user = User::factory()->create(['name' => 'Dayo Musa']);

        $this->actingAs($user)->get(route('ai.assistant'))->assertOk();
        $conversationId = session(AiAssistantService::SESSION_KEY)['active_id'];

        $this->actingAs($user)->post(
            route('ai.assistant.message', $conversationId),
            ['prompt' => 'How should I feed my goats?']
        )->assertRedirect();

        // Redirect GET consumes continue flash and shows the chat.
        $this->actingAs($user)
            ->get(route('ai.assistant', ['conversation' => $conversationId]))
            ->assertOk()
            ->assertSee('How should I feed my goats?', false)
            ->assertSee('browse', false);

        // A second GET has no continue flash — same as a browser refresh.
        $this->actingAs($user)
            ->get(route('ai.assistant'))
            ->assertOk()
            ->assertDontSee('How should I feed my goats?')
            ->assertDontSee('mineral salt lick')
            ->assertSee('Hello Dayo', false);
    }

    public function test_user_can_start_new_chat_in_session(): void
    {
        $user = User::factory()->create(['name' => 'Bola Ade']);

        $this->actingAs($user)->get(route('ai.assistant'))->assertOk();
        $firstId = session(AiAssistantService::SESSION_KEY)['active_id'];

        $this->actingAs($user)->post(
            route('ai.assistant.message', $firstId),
            ['prompt' => 'What fertilizer should I use for maize?']
        )->assertRedirect();

        $this->actingAs($user)
            ->get(route('ai.assistant', ['conversation' => $firstId]))
            ->assertOk()
            ->assertSee('NPK 15-15-15', false);

        $response = $this->actingAs($user)->post(route('ai.assistant.store'));
        $secondId = session(AiAssistantService::SESSION_KEY)['active_id'];
        $this->assertNotSame($firstId, $secondId);

        $response->assertRedirect(route('ai.assistant', ['conversation' => $secondId]));

        $this->actingAs($user)
            ->get(route('ai.assistant', ['conversation' => $secondId]))
            ->assertOk()
            ->assertSee('New chat started.', false)
            ->assertSee('Hello Bola', false)
            ->assertDontSee('NPK 15-15-15');
    }

    public function test_maize_watering_frequency_question_is_specific(): void
    {
        $user = User::factory()->create(['name' => 'Tunde Bakare']);

        $this->actingAs($user)->get(route('ai.assistant'))->assertOk();
        $conversationId = session(AiAssistantService::SESSION_KEY)['active_id'];

        $response = $this->actingAs($user)->postJson(
            route('ai.assistant.message', $conversationId),
            ['prompt' => 'How often should maize be watered if there is little or no rainfall?']
        );

        $response->assertOk();
        $body = (string) $response->json('reply.body');
        $this->assertStringContainsString('every 3–7 days', $body);
        $this->assertStringContainsString('tasseling', $body);
        $this->assertStringNotContainsString('500–800 mm of well-distributed rainfall', $body);
    }

    public function test_maize_pests_and_diseases_question_is_specific(): void
    {
        $user = User::factory()->create(['name' => 'Hassan Ali']);

        $this->actingAs($user)->get(route('ai.assistant'))->assertOk();
        $conversationId = session(AiAssistantService::SESSION_KEY)['active_id'];

        $response = $this->actingAs($user)->postJson(
            route('ai.assistant.message', $conversationId),
            ['prompt' => 'What are the most common pests and diseases affecting maize, and how can they be controlled?']
        );

        $response->assertOk();
        $body = (string) $response->json('reply.body');
        $this->assertStringContainsString('fall armyworm', $body);
        $this->assertStringContainsString('Northern Corn Leaf Blight', $body);
        $this->assertStringContainsString('Scout weekly', $body);
        $this->assertStringNotContainsString('common spacing', $body);
        $this->assertStringNotContainsString('500–800 mm', $body);
    }

    public function test_maize_soil_and_rainfall_questions_get_specific_answers(): void
    {
        $user = User::factory()->create(['name' => 'Fatima Bello']);

        $this->actingAs($user)->get(route('ai.assistant'))->assertOk();
        $conversationId = session(AiAssistantService::SESSION_KEY)['active_id'];

        $this->actingAs($user)->post(
            route('ai.assistant.message', $conversationId),
            ['prompt' => 'Which type of soil is best for maize cultivation?']
        )->assertRedirect();

        $this->actingAs($user)
            ->get(route('ai.assistant', ['conversation' => $conversationId]))
            ->assertOk()
            ->assertSee('well-drained loamy', false)
            ->assertSee('waterlogged', false)
            ->assertDontSee('90–120 days after planting', false);

        $this->actingAs($user)->post(
            route('ai.assistant.message', $conversationId),
            ['prompt' => 'How much rainfall does maize need for optimal growth?']
        )->assertRedirect();

        $this->actingAs($user)
            ->get(route('ai.assistant', ['conversation' => $conversationId]))
            ->assertOk()
            ->assertSee('500', false)
            ->assertSee('tasseling', false)
            ->assertDontSee('On your question about maize: Many Nigerian hybrids mature', false);
    }

    public function test_user_can_switch_session_conversations(): void
    {
        $user = User::factory()->create(['name' => 'Emeka Obi']);

        $this->actingAs($user)->get(route('ai.assistant'))->assertOk();
        $firstId = session(AiAssistantService::SESSION_KEY)['active_id'];

        $this->actingAs($user)->post(
            route('ai.assistant.message', $firstId),
            ['prompt' => 'How do I store maize grain safely?']
        )->assertRedirect();
        $this->actingAs($user)->get(route('ai.assistant', ['conversation' => $firstId]))->assertOk();

        $this->actingAs($user)->post(route('ai.assistant.store'))->assertRedirect();
        $secondId = session(AiAssistantService::SESSION_KEY)['active_id'];
        $this->actingAs($user)->get(route('ai.assistant', ['conversation' => $secondId]))->assertOk();

        $this->actingAs($user)
            ->post(route('ai.assistant.open', $firstId))
            ->assertRedirect(route('ai.assistant', ['conversation' => $firstId]));

        $this->actingAs($user)
            ->get(route('ai.assistant', ['conversation' => $firstId]))
            ->assertOk()
            ->assertSee('How do I store maize grain safely?', false)
            ->assertSee('hermetic', false);
    }
}
