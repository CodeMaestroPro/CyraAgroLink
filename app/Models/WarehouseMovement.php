<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock in/out movement ledger entry.
 *
 * @property int $id
 * @property int $warehouse_id
 * @property int|null $stock_id
 * @property int $user_id
 * @property string $type
 * @property string $commodity_name
 * @property int $quantity_tons
 * @property string|null $note
 */
class WarehouseMovement extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_id',
        'stock_id',
        'user_id',
        'type',
        'commodity_name',
        'quantity_tons',
        'note',
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
     * @return BelongsTo<WarehouseStock, $this>
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(WarehouseStock::class, 'stock_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
