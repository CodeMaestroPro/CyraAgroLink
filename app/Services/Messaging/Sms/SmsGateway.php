<?php

declare(strict_types=1);

namespace App\Services\Messaging\Sms;

/**
 * Outbound SMS transport.
 */
interface SmsGateway
{
    public function name(): string;

    public function send(string $toPhone, string $body, ?string $fromPhone = null): SmsDispatchResult;
}
