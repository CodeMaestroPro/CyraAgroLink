<?php

declare(strict_types=1);

namespace App\Services\Messaging\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dev/staging SMS transport that logs instead of billing a gateway.
 */
class LogSmsGateway implements SmsGateway
{
    public function name(): string
    {
        return 'log';
    }

    public function send(string $toPhone, string $body, ?string $fromPhone = null): SmsDispatchResult
    {
        $id = 'log_'.Str::lower(Str::random(12));

        Log::info('messaging.sms.dispatch', [
            'provider' => $this->name(),
            'to' => $toPhone,
            'from' => $fromPhone,
            'body' => $body,
            'message_id' => $id,
        ]);

        return new SmsDispatchResult(
            ok: true,
            provider: $this->name(),
            providerMessageId: $id,
            meta: ['channel' => 'log'],
        );
    }
}
