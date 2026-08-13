<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Open or completed carbon credit sale listing.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $farm_id
 * @property string $credits_tco2e
 * @property string $unit_price_usd
 * @property string $status
 * @property string|null $buyer_name
 */
class CarbonListing extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'farm_id',
        'credits_tco2e',
        'unit_price_usd',
        'status',
        'listed_at',
        'sold_at',
        'buyer_name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits_tco2e' => 'decimal:2',
            'unit_price_usd' => 'decimal:2',
            'listed_at' => 'datetime',
            'sold_at' => 'datetime',
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
     * @return HasMany<CarbonTransaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CarbonTransaction::class, 'listing_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
