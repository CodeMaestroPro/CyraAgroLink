<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * City food distribution hub / map node.
 *
 * @property int $id
 * @property string $name
 * @property float $lat
 * @property float $lng
 * @property string $kind
 * @property int $sort_order
 */
class SmartCityHub extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'lat',
        'lng',
        'kind',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SmartCityDelivery, $this>
     */
    public function originDeliveries(): HasMany
    {
        return $this->hasMany(SmartCityDelivery::class, 'origin_hub_id');
    }

    /**
     * @return array{lat: float, lng: float, label: string, kind: string}
     */
    public function toMapPoint(): array
    {
        return [
            'lat' => (float) $this->lat,
            'lng' => (float) $this->lng,
            'label' => $this->name,
            'kind' => $this->kind,
        ];
    }
}
