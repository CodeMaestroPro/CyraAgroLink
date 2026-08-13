<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User favorite for an equipment listing.
 */
class EquipmentFavorite extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'listing_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<EquipmentListing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(EquipmentListing::class, 'listing_id');
    }
}
