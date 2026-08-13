<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item in a consumer shopping cart.
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property int $quantity
 */
class ConsumerCartItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
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
     * @return BelongsTo<ConsumerProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ConsumerProduct::class, 'product_id');
    }

    public function lineTotal(): int
    {
        return (int) $this->quantity * (int) ($this->product?->price_per_unit ?? 0);
    }
}
