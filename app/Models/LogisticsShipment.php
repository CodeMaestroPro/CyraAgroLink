<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Booked logistics shipment for a user.
 *
 * @property int $id
 * @property int $user_id
 * @property int $vehicle_id
 * @property string $reference
 * @property string $cargo_name
 * @property int $cargo_tons
 * @property string $origin
 * @property string $destination
 * @property int $price
 * @property string $status
 */
class LogisticsShipment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'booked',
        'picked_up',
        'in_transit',
        'in_warehouse',
        'delivered',
        'cancelled',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'reference',
        'cargo_name',
        'cargo_tons',
        'origin',
        'destination',
        'price',
        'status',
        'booked_at',
        'delivered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cargo_tons' => 'integer',
            'price' => 'integer',
            'booked_at' => 'datetime',
            'delivered_at' => 'datetime',
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
     * @return BelongsTo<LogisticsVehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(LogisticsVehicle::class, 'vehicle_id');
    }

    public function routeLabel(): string
    {
        return "{$this->origin} → {$this->destination}";
    }

    public function cargoLabel(): string
    {
        return "{$this->cargo_name}, {$this->cargo_tons} Tons";
    }

    public function displayStatus(): string
    {
        return match ($this->status) {
            'booked' => 'Booked',
            'picked_up' => 'Picked Up',
            'in_transit' => 'In Transit',
            'in_warehouse' => 'In Warehouse',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['delivered', 'cancelled'], true);
    }

    public function referenceLabel(): string
    {
        return 'Shipment #'.$this->reference;
    }
}
