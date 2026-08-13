<?php

declare(strict_types=1);

namespace App\Services\Consumer;

use App\Exceptions\BusinessLogicException;
use App\Models\ConsumerOrder;
use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\URL;

/**
 * Build printable consumer order receipts with verification QR codes.
 */
class ConsumerOrderReceiptService
{
    /**
     * @return array{
     *     order: ConsumerOrder,
     *     reference: string,
     *     issued_at: string,
     *     buyer_name: string,
     *     buyer_email: string,
     *     verification_url: string,
     *     qr_svg: string,
     *     brand: string,
     *     company: string
     * }
     */
    public function getReceiptData(User $user, ConsumerOrder $order): array
    {
        $this->assertOwned($user, $order);

        $order->loadMissing(['items', 'user']);

        $verificationUrl = $this->verificationUrl($order);

        return [
            'order' => $order,
            'reference' => $order->receiptReference(),
            'issued_at' => now()->timezone(config('app.timezone'))->format('d M Y, H:i'),
            'buyer_name' => $order->user?->name ?? $user->name,
            'buyer_email' => $order->user?->email ?? $user->email,
            'verification_url' => $verificationUrl,
            'qr_svg' => $this->buildQrSvg($verificationUrl),
            'brand' => (string) config('cyra.brand', 'CyraAgroLink'),
            'company' => (string) config('cyra.company', 'CYRA-TECH LTD'),
        ];
    }

    /**
     * Public verification payload for a scanned receipt QR.
     *
     * @return array{
     *     order: ConsumerOrder,
     *     reference: string,
     *     valid: bool,
     *     brand: string,
     *     company: string
     * }
     */
    public function getVerificationData(ConsumerOrder $order, string $signature): array
    {
        $valid = hash_equals($order->receiptSignature(), $signature);

        if (! $valid) {
            throw new BusinessLogicException('This receipt QR code is not valid.');
        }

        $order->loadMissing('items');

        return [
            'order' => $order,
            'reference' => $order->receiptReference(),
            'valid' => true,
            'brand' => (string) config('cyra.brand', 'CyraAgroLink'),
            'company' => (string) config('cyra.company', 'CYRA-TECH LTD'),
        ];
    }

    public function verificationUrl(ConsumerOrder $order): string
    {
        return URL::route('consumer.orders.verify', [
            'order' => $order,
            'sig' => $order->receiptSignature(),
        ]);
    }

    protected function buildQrSvg(string $payload): string
    {
        $builder = new Builder(
            writer: new SvgWriter(),
            writerOptions: [],
            validateResult: false,
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 180,
            margin: 4,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        return $builder->build()->getString();
    }

    protected function assertOwned(User $user, ConsumerOrder $order): void
    {
        if ((int) $order->user_id !== (int) $user->id) {
            throw new BusinessLogicException('You can only print receipts for your own orders.');
        }
    }
}
