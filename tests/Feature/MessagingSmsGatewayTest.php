<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MessagingSmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * SMS gateway drivers used by the messaging hub.
 */
class MessagingSmsGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_driver_marks_sms_sent_and_writes_log(): void
    {
        config(['messaging.sms_driver' => 'log']);
        Log::spy();

        $user = User::factory()->create(['phone' => '+2348012345678']);

        $response = $this->actingAs($user)->post(route('messaging.sms.send'), [
            'to_phone' => '08031234567',
            'body' => 'Harvest pickup confirmed for tomorrow.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('messaging_sms_messages', [
            'user_id' => $user->id,
            'to_phone' => '08031234567',
            'status' => 'sent',
            'provider' => 'log',
        ]);

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $message): bool => $message === 'messaging.sms.dispatch')
            ->atLeast()
            ->once();
    }

    public function test_termii_driver_persists_provider_success(): void
    {
        config([
            'messaging.sms_driver' => 'termii',
            'messaging.termii.api_key' => 'termii-test-key',
            'messaging.termii.sender_id' => 'CyraAgro',
            'messaging.termii.base_url' => 'https://api.ng.termii.com',
        ]);

        Http::fake([
            'https://api.ng.termii.com/api/sms/send' => Http::response([
                'code' => 'ok',
                'message_id' => 'termii-msg-1',
                'message' => 'Successfully Sent',
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('messaging.sms.send'), [
            'to_phone' => '08035551234',
            'body' => 'Wallet funded successfully.',
        ])->assertRedirect();

        $this->assertDatabaseHas('messaging_sms_messages', [
            'user_id' => $user->id,
            'status' => 'sent',
            'provider' => 'termii',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ng.termii.com/api/sms/send'
                && ($request['api_key'] ?? null) === 'termii-test-key'
                && ($request['to'] ?? null) === '2348035551234';
        });
    }

    public function test_termii_driver_marks_failed_when_api_rejects(): void
    {
        config([
            'messaging.sms_driver' => 'termii',
            'messaging.termii.api_key' => 'termii-test-key',
        ]);

        Http::fake([
            'https://api.ng.termii.com/api/sms/send' => Http::response([
                'code' => 'error',
                'message' => 'Insufficient balance',
            ], 400),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('messaging.sms.send'), [
            'to_phone' => '08035551234',
            'body' => 'This should fail.',
        ])->assertRedirect();

        $sms = MessagingSmsMessage::query()->where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($sms);
        $this->assertSame('failed', $sms->status);
        $this->assertSame('termii', $sms->provider);
        $this->assertStringContainsString('Insufficient balance', (string) $sms->failure_reason);
    }
}
