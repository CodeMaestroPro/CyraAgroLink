<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Transport vehicle listed on the logistics network.
 *
 * @property int $id
 * @property string $name
 * @property int $capacity_tons
 * @property string $origin
 * @property string $destination
 * @property int $price
 * @property string|null $image_path
 * @property string $status
 * @property bool $is_active
 */
class LogisticsVehicle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'capacity_tons',
        'origin',
        'destination',
        'price',
        'image_path',
        'status',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity_tons' => 'integer',
            'price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<LogisticsShipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(LogisticsShipment::class, 'vehicle_id');
    }

    public function routeLabel(): string
    {
        return "{$this->origin} → {$this->destination}";
    }

    public function formattedPrice(): string
    {
        return '₦'.number_format($this->price);
    }

    public function imageUrl(): string
    {
        return asset($this->image_path ?: 'images/logistics/truck-10t.jpg');
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->status === 'available';
    }
}
