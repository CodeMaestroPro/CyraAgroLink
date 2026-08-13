<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Wallet;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Services\Payments\PaystackGateway;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Paystack callback + webhook handlers for wallet funding.
 */
class PaystackWalletController extends Controller
{
    public function __construct(
        protected DigitalWalletService $digitalWalletService,
        protected PaystackGateway $paystackGateway
    ) {
    }

    /**
     * Browser return URL after Paystack checkout.
     */
    public function callback(Request $request): RedirectResponse
    {
        $reference = trim((string) $request->query('reference', $request->query('trxref', '')));

        if ($reference === '') {
            return redirect()
                ->route('wallet.index', ['panel' => 'deposit'])
                ->with('error', 'Missing payment reference.');
        }

        try {
            $tx = $this->digitalWalletService->completePaystackDeposit($reference);
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('wallet.index', ['panel' => 'deposit'])
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('wallet.index')
            ->with('status', 'Deposited ₦'.number_format($tx->amount).' into your wallet via Paystack.');
    }

    /**
     * Server-to-server Paystack webhook (charge.success).
     */
    public function webhook(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('x-paystack-signature');

        if (! $this->paystackGateway->signatureValid($payload, $signature)) {
            Log::warning('Paystack webhook rejected: invalid signature.');

            return response('Invalid signature', 400);
        }

        /** @var array<string, mixed> $body */
        $body = $request->json()->all();
        $event = (string) ($body['event'] ?? '');

        if ($event !== 'charge.success') {
            return response('Ignored', 200);
        }

        $data = is_array($body['data'] ?? null) ? $body['data'] : [];
        $reference = (string) ($data['reference'] ?? '');

        if ($reference === '') {
            return response('Missing reference', 400);
        }

        try {
            $this->digitalWalletService->completePaystackDeposit($reference);
        } catch (BusinessLogicException $e) {
            Log::warning('Paystack webhook credit skipped', [
                'reference' => $reference,
                'message' => $e->getMessage(),
            ]);
        }

        return response('OK', 200);
    }
}
