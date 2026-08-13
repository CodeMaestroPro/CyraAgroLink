<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Actionable risk alert generated from an assessment.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $assessment_id
 * @property string $alert_key
 * @property string $category
 * @property string $severity
 * @property string $title
 * @property string $status
 */
class RiskAlert extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'assessment_id',
        'alert_key',
        'category',
        'severity',
        'title',
        'detail',
        'status',
        'acknowledged_at',
        'dismissed_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'dismissed_at' => 'datetime',
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
     * @return BelongsTo<RiskAssessment, $this>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class, 'assessment_id');
    }

    /**
     * @return HasMany<RiskMitigation, $this>
     */
    public function mitigations(): HasMany
    {
        return $this->hasMany(RiskMitigation::class, 'alert_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
