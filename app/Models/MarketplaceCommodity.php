<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Marketplace commodity listing.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $category_id
 * @property string $name
 * @property string|null $scientific_name
 * @property int $price_per_ton
 * @property int|null $previous_price_per_ton
 * @property int|null $day_high
 * @property int|null $day_low
 * @property int|null $volume_tons
 * @property int|null $open_interest_tons
 * @property string|null $city
 * @property string|null $state
 * @property string|null $image_path
 * @property bool $is_featured
 */
class MarketplaceCommodity extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'scientific_name',
        'price_per_ton',
        'previous_price_per_ton',
        'day_high',
        'day_low',
        'volume_tons',
        'open_interest_tons',
        'city',
        'state',
        'image_path',
        'is_featured',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_per_ton' => 'integer',
            'previous_price_per_ton' => 'integer',
            'day_high' => 'integer',
            'day_low' => 'integer',
            'volume_tons' => 'integer',
            'open_interest_tons' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<MarketplaceCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    /**
     * @return HasMany<ExchangeOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(ExchangeOrder::class, 'commodity_id');
    }

    /**
     * Formatted Naira price label.
     */
    public function formattedPrice(): string
    {
        return '₦'.number_format($this->price_per_ton).' / Ton';
    }

    /**
     * Title with scientific name when available.
     */
    public function exchangeTitle(): string
    {
        if (filled($this->scientific_name)) {
            return "{$this->name} ({$this->scientific_name})";
        }

        return $this->name;
    }

    /**
     * Percentage change vs previous price.
     */
    public function changePercent(): float
    {
        $previous = (int) ($this->previous_price_per_ton ?: 0);

        if ($previous <= 0) {
            return 0.0;
        }

        return round((($this->price_per_ton - $previous) / $previous) * 100, 2);
    }

    /**
     * City, State location label.
     */
    public function locationLabel(): string
    {
        return collect([$this->city, $this->state])->filter()->implode(', ');
    }

    /**
     * Public image URL with local placeholder fallback.
     */
    public function imageUrl(): string
    {
        if ($this->image_path) {
            if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
                return $this->image_path;
            }

            return asset($this->image_path);
        }

        return asset('images/marketplace/maize.jpg');
    }
}
