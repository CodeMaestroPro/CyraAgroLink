<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Smart city food delivery job.
 *
 * @property int $id
 * @property int $user_id
 * @property int $origin_hub_id
 * @property int $destination_hub_id
 * @property int|null $fleet_unit_id
 * @property string $reference
 * @property string $cargo_name
 * @property int $quantity
 * @property string $status
 * @property int|null $route_order
 * @property \Illuminate\Support\Carbon $delivery_date
 * @property bool|null $on_time
 */
class SmartCityDelivery extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = [
        'scheduled',
        'dispatched',
        'in_transit',
        'delivered',
        'cancelled',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'origin_hub_id',
        'destination_hub_id',
        'fleet_unit_id',
        'reference',
        'cargo_name',
        'quantity',
        'status',
        'route_order',
        'delivery_date',
        'on_time',
        'dispatched_at',
        'delivered_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'route_order' => 'integer',
            'delivery_date' => 'date',
            'on_time' => 'boolean',
            'dispatched_at' => 'datetime',
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
     * @return BelongsTo<SmartCityHub, $this>
     */
    public function originHub(): BelongsTo
    {
        return $this->belongsTo(SmartCityHub::class, 'origin_hub_id');
    }

    /**
     * @return BelongsTo<SmartCityHub, $this>
     */
    public function destinationHub(): BelongsTo
    {
        return $this->belongsTo(SmartCityHub::class, 'destination_hub_id');
    }

    /**
     * @return BelongsTo<SmartCityFleetUnit, $this>
     */
    public function fleetUnit(): BelongsTo
    {
        return $this->belongsTo(SmartCityFleetUnit::class, 'fleet_unit_id');
    }

    public function referenceLabel(): string
    {
        return 'Delivery #'.$this->reference;
    }

    public function displayStatus(): string
    {
        return match ($this->status) {
            'scheduled' => 'Scheduled',
            'dispatched' => 'Dispatched',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['scheduled', 'dispatched', 'in_transit'], true);
    }
}
