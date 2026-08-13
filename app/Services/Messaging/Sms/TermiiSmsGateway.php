<?php

declare(strict_types=1);

namespace App\Services\Messaging\Sms;

use Illuminate\Support\Facades\Http;

/**
 * Termii SMS API transport (https://developers.termii.com/).
 */
class TermiiSmsGateway implements SmsGateway
{
    public function name(): string
    {
        return 'termii';
    }

    public function send(string $toPhone, string $body, ?string $fromPhone = null): SmsDispatchResult
    {
        $apiKey = (string) config('messaging.termii.api_key');
        if ($apiKey === '') {
            return new SmsDispatchResult(
                ok: false,
                provider: $this->name(),
                failureReason: 'TERMII_API_KEY is not configured.',
            );
        }

        $base = rtrim((string) config('messaging.termii.base_url'), '/');
        $sender = trim((string) ($fromPhone ?: config('messaging.termii.sender_id'))) ?: 'CyraAgro';

        $response = Http::acceptJson()
            ->timeout(30)
            ->post($base.'/api/sms/send', [
                'api_key' => $apiKey,
                'to' => $this->normalizePhone($toPhone),
                'from' => $sender,
                'sms' => $body,
                'type' => 'plain',
                'channel' => (string) config('messaging.termii.channel', 'generic'),
            ]);

        /** @var array<string, mixed> $json */
        $json = $response->json() ?? [];
        $messageId = isset($json['message_id']) ? (string) $json['message_id'] : null;
        $code = isset($json['code']) ? (string) $json['code'] : null;
        $ok = $response->successful() && ($code === 'ok' || $messageId !== null);

        if (! $ok) {
            $reason = (string) ($json['message'] ?? $response->body() ?: 'Termii SMS send failed.');

            return new SmsDispatchResult(
                ok: false,
                provider: $this->name(),
                failureReason: mb_substr($reason, 0, 240),
                meta: ['http_status' => $response->status(), 'response' => $json],
            );
        }

        return new SmsDispatchResult(
            ok: true,
            provider: $this->name(),
            providerMessageId: $messageId,
            meta: ['response' => $json],
        );
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '234'.substr($digits, 1);
        }

        return $digits !== '' ? $digits : $phone;
    }
}
