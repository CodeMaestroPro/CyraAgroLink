<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * City last-mile delivery fleet unit.
 *
 * @property int $id
 * @property string $name
 * @property string $status
 * @property int|null $hub_id
 */
class SmartCityFleetUnit extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
        'hub_id',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SmartCityHub, $this>
     */
    public function hub(): BelongsTo
    {
        return $this->belongsTo(SmartCityHub::class, 'hub_id');
    }

    /**
     * @return HasMany<SmartCityDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(SmartCityDelivery::class, 'fleet_unit_id');
    }

    public function displayStatus(): string
    {
        return match ($this->status) {
            'in_transit' => 'In Transit',
            'maintenance' => 'Maintenance',
            default => 'Available',
        };
    }
}
