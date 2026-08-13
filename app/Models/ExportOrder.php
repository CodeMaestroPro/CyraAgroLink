<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * International export order tracked through trade process stages.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $farm_id
 * @property string $reference
 * @property string $product
 * @property string $quantity_tons
 * @property string $destination_country
 * @property string $destination_code
 * @property int $value_usd
 * @property string $status
 * @property array<string, mixed>|null $meta
 */
class ExportOrder extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'farm_id',
        'reference',
        'product',
        'quantity_tons',
        'destination_country',
        'destination_code',
        'value_usd',
        'status',
        'shipped_at',
        'delivered_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_tons' => 'decimal:2',
            'value_usd' => 'integer',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
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
     * @return BelongsTo<Farm, $this>
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function isOpen(): bool
    {
        return $this->status !== 'delivered' && $this->status !== 'cancelled';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }
}
