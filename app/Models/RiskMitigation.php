<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Planned mitigation action for a risk alert.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $alert_id
 * @property string $title
 * @property string $action_type
 * @property string $status
 */
class RiskMitigation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'alert_id',
        'title',
        'action_type',
        'status',
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

    /**
     * @return BelongsTo<RiskAlert, $this>
     */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(RiskAlert::class, 'alert_id');
    }
}
