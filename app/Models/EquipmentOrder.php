<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paid equipment purchase or rental order.
 *
 * @property int $id
 * @property int $user_id
 * @property int $listing_id
 * @property string $reference
 * @property string $order_type
 * @property int $amount_ngn
 * @property string $status
 */
class EquipmentOrder extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'listing_id',
        'reference',
        'order_type',
        'amount_ngn',
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
