<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * User-owned agricultural storage warehouse.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $city
 * @property string $state
 * @property int $capacity_tons
 * @property string $status
 */
class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'city',
        'state',
        'capacity_tons',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity_tons' => 'integer',
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
     * @return HasMany<WarehouseStock, $this>
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    /**
     * @return HasMany<WarehouseMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(WarehouseMovement::class);
    }

    public function locationLabel(): string
    {
        return "{$this->city}, {$this->state}";
    }

    public function usedTons(): int
    {
        return (int) $this->stocks()->sum('quantity_tons');
    }

    public function occupancyPercent(): int
    {
        if ($this->capacity_tons <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->usedTons() / $this->capacity_tons) * 100));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
