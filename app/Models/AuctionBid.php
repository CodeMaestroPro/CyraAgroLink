<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bid placed on a commodity auction.
 *
 * @property int $id
 * @property int $auction_id
 * @property int $user_id
 * @property int $amount_ngn
 * @property string $status
 */
class AuctionBid extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'auction_id',
        'user_id',
        'reference',
        'amount_ngn',
        'bidder_label',
        'status',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_ngn' => 'integer',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CommodityAuction, $this>
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(CommodityAuction::class, 'auction_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
