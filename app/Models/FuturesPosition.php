<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Open or closed futures position.
 *
 * @property int $id
 * @property int $user_id
 * @property int $contract_id
 * @property string $side
 * @property int $quantity
 * @property int $entry_price
 * @property int $margin_ngn
 * @property string $status
 */
class FuturesPosition extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'contract_id',
        'reference',
        'side',
        'quantity',
        'entry_price',
        'margin_ngn',
        'realized_pnl_ngn',
        'status',
        'opened_at',
        'closed_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'entry_price' => 'integer',
            'margin_ngn' => 'integer',
            'realized_pnl_ngn' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
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
     * @return BelongsTo<FuturesContract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(FuturesContract::class, 'contract_id');
    }

    public function unrealizedPnl(int $markPrice): int
    {
        $diff = $markPrice - $this->entry_price;

        return $this->side === 'long'
            ? $diff * $this->quantity
            : -$diff * $this->quantity;
    }
}
