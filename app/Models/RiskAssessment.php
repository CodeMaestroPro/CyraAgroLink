<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Snapshot of a user's calculated agricultural risk profile.
 *
 * @property int $id
 * @property int $user_id
 * @property int $overall_score
 * @property string $status
 */
class RiskAssessment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'overall_score',
        'status',
        'categories',
        'factors',
        'calculated_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'overall_score' => 'integer',
            'categories' => 'array',
            'factors' => 'array',
            'calculated_at' => 'datetime',
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
     * @return HasMany<RiskAlert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(RiskAlert::class, 'assessment_id');
    }
}
