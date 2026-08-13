<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Exceptions\BusinessLogicException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Thin Paystack Initialize / Verify client for wallet funding.
 */
class PaystackGateway
{
    public function enabled(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    /**
     * Initialize a card/checkout payment. Amount is in Naira (converted to kobo).
     *
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initialize(string $email, int $amountNaira, string $reference, string $callbackUrl, array $metadata = []): array
    {
        if (! $this->enabled()) {
            throw new BusinessLogicException('Paystack is not configured.', 'PAYSTACK_DISABLED', 503);
        }

        if ($amountNaira < 100) {
            throw new BusinessLogicException('Minimum Paystack deposit is ₦100.');
        }

        $response = Http::withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->timeout(30)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $email,
                'amount' => $amountNaira * 100,
                'currency' => 'NGN',
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ]);

        if (! $response->successful() || ! ($response->json('status') === true)) {
            $message = (string) ($response->json('message') ?: 'Unable to start Paystack checkout.');
            throw new BusinessLogicException($message, 'PAYSTACK_INIT_FAILED', 502);
        }

        $data = $response->json('data') ?? [];

        return [
            'authorization_url' => (string) ($data['authorization_url'] ?? ''),
            'access_code' => (string) ($data['access_code'] ?? ''),
            'reference' => (string) ($data['reference'] ?? $reference),
        ];
    }

    /**
     * Verify a Paystack transaction by reference.
     *
     * @return array{status: string, amount_naira: int, reference: string, paid: bool, raw: array<string, mixed>}
     */
    public function verify(string $reference): array
    {
        if (! $this->enabled()) {
            throw new BusinessLogicException('Paystack is not configured.', 'PAYSTACK_DISABLED', 503);
        }

        $response = Http::withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->timeout(30)
            ->get('https://api.paystack.co/transaction/verify/'.urlencode($reference));

        if (! $response->successful() || ! ($response->json('status') === true)) {
            $message = (string) ($response->json('message') ?: 'Unable to verify Paystack payment.');
            throw new BusinessLogicException($message, 'PAYSTACK_VERIFY_FAILED', 502);
        }

        /** @var array<string, mixed> $data */
        $data = $response->json('data') ?? [];
        $status = (string) ($data['status'] ?? '');
        $amountKobo = (int) ($data['amount'] ?? 0);

        return [
            'status' => $status,
            'amount_naira' => (int) round($amountKobo / 100),
            'reference' => (string) ($data['reference'] ?? $reference),
            'paid' => $status === 'success',
            'raw' => $data,
        ];
    }

    /**
     * Validate Paystack webhook HMAC signature.
     */
    public function signatureValid(string $payload, ?string $signature): bool
    {
        $secret = (string) config('services.paystack.secret_key');

        if ($secret === '' || $signature === null || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha512', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    public function makeReference(string $prefix = 'CYRA'): string
    {
        return strtoupper($prefix).'_'.now()->format('YmdHis').'_'.Str::upper(Str::random(10));
    }
}
