<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Commodity stock line inside a warehouse.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property string $commodity_name
 * @property string $icon
 * @property int $quantity_tons
 */
class WarehouseStock extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'commodity_name',
        'icon',
        'quantity_tons',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_tons' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<WarehouseMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(WarehouseMovement::class, 'stock_id');
    }

    public function quantityLabel(): string
    {
        return number_format($this->quantity_tons).' Tons';
    }
}
