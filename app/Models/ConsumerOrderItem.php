<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot line on a consumer order.
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string $unit
 * @property int $unit_price
 * @property int $quantity
 * @property int $line_total
 */
class ConsumerOrderItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'unit',
        'unit_price',
        'quantity',
        'line_total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ConsumerOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(ConsumerOrder::class, 'order_id');
    }

    /**
     * @return BelongsTo<ConsumerProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ConsumerProduct::class, 'product_id');
    }
}