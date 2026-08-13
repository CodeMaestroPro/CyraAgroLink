<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Planned food-security intervention for a state / risk zone.
 *
 * @property int $id
 * @property int $user_id
 * @property string $state
 * @property string $title
 * @property string $action_type
 * @property string $status
 */
class FoodSecurityIntervention extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'state',
        'title',
        'action_type',
        'status',
        'notes',
        'due_at',
        'completed_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
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
}
