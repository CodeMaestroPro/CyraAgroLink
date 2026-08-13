<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User watchlist entry for market intelligence alerts.
 *
 * @property int $id
 * @property int $user_id
 * @property int $commodity_id
 */
class MarketWatchlist extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'commodity_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MarketplaceCommodity, $this>
     */
    public function commodity(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCommodity::class, 'commodity_id');
    }
}
