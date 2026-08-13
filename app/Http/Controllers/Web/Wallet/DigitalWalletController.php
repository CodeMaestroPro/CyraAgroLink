<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Wallet;

use App\Exceptions\BusinessLogicException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\FundWalletRequest;
use App\Services\Wallet\DigitalWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Digital wallet balance, funding, withdrawals, and ledger.
 */
class DigitalWalletController extends Controller
{
    public function __construct(
        protected DigitalWalletService $digitalWalletService
    ) {
    }

    /**
     * Display the digital wallet screen.
     */
    public function index(Request $request): View
    {
        $data = $this->digitalWalletService->getWalletData(
            $request->user(),
            $request->string('filter', 'all')->toString()
        );

        $panel = $request->string('panel')->toString();
        if (! in_array($panel, ['deposit', 'withdraw'], true)) {
            $panel = $data['balance']['raw'] < 1 ? 'deposit' : '';
        }

        return view('wallet.index', [
            'balance' => $data['balance'],
            'stats' => $data['stats'],
            'filter' => $data['filter'],
            'transactions' => $data['transactions'],
            'panel' => $panel,
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Fund (deposit into) the authenticated user's wallet.
     */
    public function deposit(FundWalletRequest $request): RedirectResponse
    {
        $amount = (int) $request->validated('amount');

        try {
            $result = $this->digitalWalletService->deposit(
                $request->user(),
                $amount,
                $request->validated('note')
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('wallet.index', ['panel' => 'deposit'])
                ->with('error', $e->getMessage())
                ->withInput();
        }

        if (($result['mode'] ?? '') === 'redirect' && filled($result['redirect_url'] ?? null)) {
            return redirect()->away((string) $result['redirect_url']);
        }

        return redirect()
            ->route('wallet.index')
            ->with('status', 'Deposited ₦'.number_format($amount).' into your wallet.');
    }

    /**
     * Withdraw funds from the wallet.
     */
    public function withdraw(FundWalletRequest $request): RedirectResponse
    {
        $amount = (int) $request->validated('amount');

        try {
            $this->digitalWalletService->withdraw(
                $request->user(),
                $amount,
                $request->validated('note')
            );
        } catch (BusinessLogicException $e) {
            return redirect()
                ->route('wallet.index', ['panel' => 'withdraw'])
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return redirect()
            ->route('wallet.index')
            ->with('status', 'Withdrew ₦'.number_format($amount).' from your wallet.');
    }
}
