<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tradable agricultural futures contract.
 *
 * @property int $id
 * @property string $name
 * @property string $symbol
 * @property int|null $commodity_id
 * @property int $last_price
 * @property bool $is_active
 */
class FuturesContract extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'symbol',
        'commodity_id',
        'expiry_label',
        'expires_on',
        'contract_size_tons',
        'last_price',
        'day_high',
        'day_low',
        'volume',
        'open_interest',
        'is_active',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_on' => 'date',
            'contract_size_tons' => 'integer',
            'last_price' => 'integer',
            'day_high' => 'integer',
            'day_low' => 'integer',
            'volume' => 'integer',
            'open_interest' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MarketplaceCommodity, $this>
     */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCommodity::class, 'commodity_id');
    }

    /**
     * @return HasMany<FuturesOrder, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(FuturesOrder::class, 'contract_id');
    }

    /**
     * @return HasMany<FuturesPosition, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(FuturesPosition::class, 'contract_id');
    }

    public function changePercent(): float
    {
        $prev = (int) ($this->meta['previous_price'] ?? 0);
        if ($prev < 1) {
            return 0.0;
        }

        return (($this->last_price - $prev) / $prev) * 100;
    }
}
