<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Executed spot trade between a buy and sell order.
 *
 * @property int $id
 * @property int $commodity_id
 * @property int $buy_order_id
 * @property int $sell_order_id
 * @property int $buyer_id
 * @property int $seller_id
 * @property int $quantity_tons
 * @property int $price_per_ton
 * @property int $notional_amount
 * @property \Illuminate\Support\Carbon|null $traded_at
 */
class ExchangeTrade extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'commodity_id',
        'buy_order_id',
        'sell_order_id',
        'buyer_id',
        'seller_id',
        'quantity_tons',
        'price_per_ton',
        'notional_amount',
        'traded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_tons' => 'integer',
            'price_per_ton' => 'integer',
            'notional_amount' => 'integer',
            'traded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<MarketplaceCommodity, $this>
     */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCommodity::class, 'commodity_id');
    }

    /**
     * @return BelongsTo<ExchangeOrder, $this>
     */
    public function buyOrder(): BelongsTo
    {
        return $this->belongsTo(ExchangeOrder::class, 'buy_order_id');
    }

    /**
     * @return BelongsTo<ExchangeOrder, $this>
     */
    public function sellOrder(): BelongsTo
    {
        return $this->belongsTo(ExchangeOrder::class, 'sell_order_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
