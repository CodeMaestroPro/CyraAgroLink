<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Buy/sell order on the commodity exchange.
 *
 * @property int $id
 * @property int $user_id
 * @property int $commodity_id
 * @property string $side
 * @property int $quantity_tons
 * @property int $original_quantity_tons
 * @property int $filled_quantity_tons
 * @property int $price_per_ton
 * @property int $reserved_amount
 * @property string $status
 */
class ExchangeOrder extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'commodity_id',
        'side',
        'quantity_tons',
        'original_quantity_tons',
        'filled_quantity_tons',
        'price_per_ton',
        'reserved_amount',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_tons' => 'integer',
            'original_quantity_tons' => 'integer',
            'filled_quantity_tons' => 'integer',
            'price_per_ton' => 'integer',
            'reserved_amount' => 'integer',
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
     * @return BelongsTo<MarketplaceCommodity, $this>
     */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCommodity::class, 'commodity_id');
    }

    public function isBuy(): bool
    {
        return $this->side === 'buy';
    }
}
