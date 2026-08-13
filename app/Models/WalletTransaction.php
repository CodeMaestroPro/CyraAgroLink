<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Wallet ledger entry.
 *
 * @property int $id
 * @property int $wallet_id
 * @property int $user_id
 * @property string $type
 * @property string $category
 * @property int $amount
 * @property int $balance_after
 * @property string $title
 * @property string|null $detail
 */
class WalletTransaction extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'wallet_id',
        'user_id',
        'type',
        'category',
        'amount',
        'balance_after',
        'title',
        'detail',
        'reference_type',
        'reference_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function formattedAmount(): string
    {
        $prefix = $this->isCredit() ? '+' : '-';

        return $prefix.'₦'.number_format($this->amount);
    }

    public function iconKey(): string
    {
        return match ($this->category) {
            'withdrawal' => 'withdrawal',
            'transfer_out', 'purchase' => 'sent',
            'earnings', 'investment' => 'investment',
            'deposit', 'refund', 'transfer_in' => 'payment',
            default => 'payment',
        };
    }
}
