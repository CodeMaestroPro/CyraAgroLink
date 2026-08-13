<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Actionable executive insight on the BI command center.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $bi_snapshot_id
 * @property string $insight_key
 * @property string $category
 * @property string $severity
 * @property string $title
 * @property string $detail
 * @property string $status
 */
class BiInsight extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'bi_snapshot_id',
        'insight_key',
        'category',
        'severity',
        'title',
        'detail',
        'status',
        'acknowledged_at',
        'pinned_at',
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
            'pinned_at' => 'datetime',
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
     * @return BelongsTo<BiSnapshot, $this>
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(BiSnapshot::class, 'bi_snapshot_id');
    }
}
