<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Carbon ledger movement: earn, sale, or offset.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $farm_id
 * @property int|null $listing_id
 * @property string $type
 * @property string $title
 * @property string|null $counterparty
 * @property string $credits_tco2e
 * @property string|null $unit_price_usd
 * @property int $value_ngn
 * @property string $status
 * @property array<string, mixed>|null $meta
 */
class CarbonTransaction extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'farm_id',
        'listing_id',
        'type',
        'title',
        'counterparty',
        'credits_tco2e',
        'unit_price_usd',
        'value_ngn',
        'status',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits_tco2e' => 'decimal:2',
            'unit_price_usd' => 'decimal:2',
            'value_ngn' => 'integer',
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
     * @return BelongsTo<CarbonListing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(CarbonListing::class, 'listing_id');
    }

    public function isCredit(): bool
    {
        return in_array($this->type, ['earn', 'sale'], true);
    }
}
