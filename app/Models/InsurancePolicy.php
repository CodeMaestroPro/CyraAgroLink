<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Purchased farm insurance policy.
 *
 * @property int $id
 * @property int $user_id
 * @property int $farm_id
 * @property int $plan_id
 * @property string $reference
 * @property string $status
 * @property int $premium_ngn
 * @property int $coverage_ngn
 */
class InsurancePolicy extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'farm_id',
        'plan_id',
        'reference',
        'status',
        'premium_ngn',
        'coverage_ngn',
        'starts_at',
        'expires_at',
        'covered_enterprises',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'premium_ngn' => 'integer',
            'coverage_ngn' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'covered_enterprises' => 'array',
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
     * @return BelongsTo<InsurancePlan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class, 'plan_id');
    }

    /**
     * @return HasMany<InsuranceClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class, 'policy_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}
