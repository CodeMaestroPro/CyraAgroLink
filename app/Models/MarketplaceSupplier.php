<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Marketplace supplier / farm seller profile.
 *
 * @property int $id
 * @property string $name
 * @property string|null $state
 * @property string $rating
 * @property int $review_count
 * @property string|null $image_path
 * @property bool $is_top
 */
class MarketplaceSupplier extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'state',
        'rating',
        'review_count',
        'image_path',
        'is_top',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'review_count' => 'integer',
            'is_top' => 'boolean',
        ];
    }

    /**
     * State label for display.
     */
    public function locationLabel(): string
    {
        if (blank($this->state)) {
            return '';
        }

        return str_ends_with(strtolower((string) $this->state), 'state')
            ? (string) $this->state
            : $this->state.' State';
    }

    /**
     * Public image URL with local placeholder fallback.
     */
    public function imageUrl(): string
    {
        if ($this->image_path) {
            return asset($this->image_path);
        }

        return asset('images/marketplace/supplier-1.jpg');
    }
}
