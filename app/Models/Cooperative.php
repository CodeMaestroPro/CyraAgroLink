<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Farmer cooperative group with shared savings and decisions.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $created_by
 * @property int $savings_pool_ngn
 * @property string $status
 */
class Cooperative extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'location',
        'description',
        'created_by',
        'savings_pool_ngn',
        'status',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'savings_pool_ngn' => 'integer',
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

    /**
     * @return HasMany<CooperativeMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(CooperativeMember::class);
    }

    /**
     * @return HasMany<CooperativeLoan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(CooperativeLoan::class);
    }

    /**
     * @return HasMany<CooperativeVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(CooperativeVote::class);
    }

    /**
     * @return HasMany<CooperativeActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(CooperativeActivity::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
