<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item in an equipment marketplace cart.
 *
 * @property int $id
 * @property int $user_id
 * @property int $listing_id
 * @property int $quantity
 * @property int $rental_days
 */
class EquipmentCartItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'listing_id',
        'quantity',
        'rental_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'rental_days' => 'integer',
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
     * @return BelongsTo<EquipmentListing, $this>
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(EquipmentListing::class, 'listing_id');
    }

    /**
     * Line total in NGN (USD price × qty × rental days × FX).
     */
    public function lineTotalNgn(int $usdToNgn = 1550): int
    {
        $listing = $this->listing;
        if (! $listing) {
            return 0;
        }

        $units = max(1, (int) $this->quantity);
        $days = $listing->listing_type === 'rent'
            ? max(1, (int) $this->rental_days)
            : 1;

        return (int) max(1, round((int) $listing->price_usd * $units * $days * $usdToNgn));
    }
}
