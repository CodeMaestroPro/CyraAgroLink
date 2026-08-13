<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Retail product listed on the consumer marketplace.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $category
 * @property string $unit
 * @property int $price_per_unit
 * @property int $stock_qty
 * @property string|null $image_path
 * @property bool $is_active
 * @property bool $is_featured
 */
class ConsumerProduct extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'category',
        'unit',
        'price_per_unit',
        'stock_qty',
        'image_path',
        'description',
        'is_active',
        'is_featured',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_per_unit' => 'integer',
            'stock_qty' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ConsumerCartItem, $this>
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(ConsumerCartItem::class, 'product_id');
    }

    public function formattedPrice(): string
    {
        return '₦'.number_format($this->price_per_unit).'/'.$this->unit;
    }

    public function imageUrl(): string
    {
        return asset($this->image_path ?: 'images/consumer/placeholder.jpg');
    }

    public function inStock(): bool
    {
        return $this->is_active && $this->stock_qty > 0;
    }
}
