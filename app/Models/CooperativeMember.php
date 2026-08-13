<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Membership record linking a user to a cooperative.
 *
 * @property int $id
 * @property int $cooperative_id
 * @property int $user_id
 * @property string $role
 * @property int $savings_balance_ngn
 * @property string $status
 */
class CooperativeMember extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'cooperative_id',
        'user_id',
        'role',
        'savings_balance_ngn',
        'status',
        'joined_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'savings_balance_ngn' => 'integer',
            'joined_at' => 'datetime',
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' && $this->status === 'active';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
