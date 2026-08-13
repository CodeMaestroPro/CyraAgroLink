<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Live or completed commodity auction lot.
 *
 * @property int $id
 * @property string $name
 * @property string $commodity
 * @property int $current_bid_ngn
 * @property string $status
 */
class CommodityAuction extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'commodity',
        'image_path',
        'quantity_tons',
        'starting_bid_ngn',
        'current_bid_ngn',
        'min_increment_ngn',
        'highest_bidder_id',
        'highest_bidder_name',
        'status',
        'starts_at',
        'ends_at',
        'winner_id',
        'winning_bid_ngn',
        'settled_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_tons' => 'integer',
            'starting_bid_ngn' => 'integer',
            'current_bid_ngn' => 'integer',
            'min_increment_ngn' => 'integer',
            'winning_bid_ngn' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'settled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function highestBidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'highest_bidder_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /**
     * @return HasMany<AuctionBid, $this>
     */
    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class, 'auction_id');
    }

    public function isLive(): bool
    {
        return $this->status === 'live' && $this->ends_at !== null && $this->ends_at->isFuture();
    }

    public function nextMinBid(): int
    {
        if ($this->highest_bidder_id || $this->current_bid_ngn > $this->starting_bid_ngn) {
            return $this->current_bid_ngn + $this->min_increment_ngn;
        }

        return max($this->starting_bid_ngn, $this->current_bid_ngn);
    }
}
