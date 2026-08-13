<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pending or completed wallet top-up via a payment provider.
 *
 * @property int $id
 * @property int $user_id
 * @property string $reference
 * @property int $amount
 * @property string $currency
 * @property string $status
 * @property string $provider
 * @property string|null $provider_reference
 * @property string|null $authorization_url
 * @property string|null $note
 * @property int|null $wallet_transaction_id
 */
class WalletFundingIntent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'reference',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_reference',
        'authorization_url',
        'note',
        'wallet_transaction_id',
        'paid_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<WalletTransaction, $this>
     */
    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
