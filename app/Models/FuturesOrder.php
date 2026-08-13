<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Open or filled futures order.
 *
 * @property int $id
 * @property int $user_id
 * @property int $contract_id
 * @property string $side
 * @property int $quantity
 * @property int $price
 * @property string $status
 */
class FuturesOrder extends Model
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
        'filled_quantity',
        'price',
        'margin_ngn',
        'status',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'filled_quantity' => 'integer',
            'price' => 'integer',
            'margin_ngn' => 'integer',
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

    public function remainingQuantity(): int
    {
        return max(0, $this->quantity - $this->filled_quantity);
    }
}
