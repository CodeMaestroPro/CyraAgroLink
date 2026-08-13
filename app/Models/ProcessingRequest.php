<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Farmer processing job request against a network factory.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $farm_id
 * @property int|null $factory_id
 * @property int|null $logistics_shipment_id
 * @property string $reference
 * @property string $service
 * @property string $product
 * @property string $quantity_tons
 * @property string $status
 * @property int $fee_ngn
 * @property array<string, mixed>|null $meta
 */
class ProcessingRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'farm_id',
        'factory_id',
        'logistics_shipment_id',
        'reference',
        'service',
        'product',
        'quantity_tons',
        'status',
        'fee_ngn',
        'started_at',
        'completed_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_tons' => 'decimal:2',
            'fee_ngn' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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

    /**
     * @return BelongsTo<ProcessingFactory, $this>
     */
    public function factory(): BelongsTo
    {
        return $this->belongsTo(ProcessingFactory::class, 'factory_id');
    }

    /**
     * Logistics shipment delivering produce from the farm to the factory.
     *
     * @return BelongsTo<LogisticsShipment, $this>
     */
    public function logisticsShipment(): BelongsTo
    {
        return $this->belongsTo(LogisticsShipment::class, 'logistics_shipment_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['queued', 'in_progress'], true);
    }

    public function produceDelivered(): bool
    {
        $shipment = $this->relationLoaded('logisticsShipment')
            ? $this->logisticsShipment
            : $this->logisticsShipment()->first();

        return $shipment !== null && $shipment->status === 'delivered';
    }
}
