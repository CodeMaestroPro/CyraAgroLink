<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Insurance claim filed against a purchased policy.
 *
 * @property int $id
 * @property int $user_id
 * @property int $policy_id
 * @property int $farm_id
 * @property string $reference
 * @property string $title
 * @property string $status
 * @property int $amount_requested_ngn
 * @property int|null $amount_paid_ngn
 */
class InsuranceClaim extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'policy_id',
        'farm_id',
        'reference',
        'title',
        'description',
        'amount_requested_ngn',
        'amount_paid_ngn',
        'status',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_requested_ngn' => 'integer',
            'amount_paid_ngn' => 'integer',
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
     * @return BelongsTo<InsurancePolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'policy_id');
    }

    /**
     * @return BelongsTo<Farm, $this>
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }
}
