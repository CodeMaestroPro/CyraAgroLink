<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agricultural equipment marketplace listing.
 *
 * @property int $id
 * @property string $name
 * @property string $category
 * @property string $listing_type
 * @property int $price_usd
 * @property string $location
 * @property string $rating
 * @property string|null $image_path
 * @property int $stock
 * @property bool $is_active
 */
class EquipmentListing extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'category',
        'listing_type',
        'price_usd',
        'location',
        'rating',
        'image_path',
        'stock',
        'is_active',
        'description',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_usd' => 'integer',
            'rating' => 'decimal:1',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<EquipmentFavorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(EquipmentFavorite::class, 'listing_id');
    }

    /**
     * @return HasMany<EquipmentOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(EquipmentOrder::class, 'listing_id');
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->stock > 0;
    }

    public function imageUrl(): string
    {
        return asset($this->image_path ?: 'images/equipment/placeholder.jpg');
    }
}
