<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cooperative decision poll for members.
 *
 * @property int $id
 * @property int $cooperative_id
 * @property int $created_by
 * @property string $title
 * @property string $description
 * @property string $status
 * @property int $yes_count
 * @property int $no_count
 */
class CooperativeVote extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cooperative_id',
        'created_by',
        'title',
        'description',
        'status',
        'yes_count',
        'no_count',
        'closes_at',
        'closed_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'yes_count' => 'integer',
            'no_count' => 'integer',
            'closes_at' => 'datetime',
            'closed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Cooperative, $this>
     */
    public function cooperative(): BelongsTo
    {
        return $this->belongsTo(Cooperative::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CooperativeBallot, $this>
     */
    public function ballots(): HasMany
    {
        return $this->hasMany(CooperativeBallot::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && $this->closes_at->isFuture();
    }
}
