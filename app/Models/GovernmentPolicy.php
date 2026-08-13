<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * National agricultural policy tracked by the government dashboard.
 *
 * @property int $id
 * @property int|null $created_by
 * @property string $title
 * @property string $slug
 * @property string $summary
 * @property string $status
 */
class GovernmentPolicy extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'created_by',
        'title',
        'slug',
        'summary',
        'status',
        'sort_order',
        'published_at',
        'archived_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'under_review' => 'Under review',
            'archived' => 'Archived',
            default => 'Draft',
        };
    }
}
