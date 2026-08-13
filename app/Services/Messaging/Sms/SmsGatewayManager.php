<?php

declare(strict_types=1);

namespace App\Services\Messaging\Sms;

/**
 * Resolve the configured outbound SMS gateway.
 */
class SmsGatewayManager
{
    public function driver(?string $name = null): SmsGateway
    {
        $name = $name ?: (string) config('messaging.sms_driver', 'log');

        return match ($name) {
            'termii' => app(TermiiSmsGateway::class),
            default => app(LogSmsGateway::class),
        };
    }
}
