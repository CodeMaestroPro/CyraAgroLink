<?php

declare(strict_types=1);

namespace App\Services\Messaging\Sms;

/**
 * Outcome of an outbound SMS provider call.
 */
final class SmsDispatchResult
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $provider,
        public readonly ?string $providerMessageId = null,
        public readonly ?string $failureReason = null,
        public readonly array $meta = [],
    ) {
    }
}
