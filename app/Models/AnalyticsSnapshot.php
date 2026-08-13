<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Point-in-time business analytics snapshot.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $period
 * @property int $revenue_ngn
 * @property int $users_count
 * @property int $transactions_count
 * @property int $farms_count
 */
class AnalyticsSnapshot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'period',
        'revenue_ngn',
        'users_count',
        'transactions_count',
        'farms_count',
        'revenue_trend',
        'transactions_trend',
        'segments',
        'regions',
        'operations',
        'meta',
        'calculated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revenue_ngn' => 'integer',
            'users_count' => 'integer',
            'transactions_count' => 'integer',
            'farms_count' => 'integer',
            'revenue_trend' => 'array',
            'transactions_trend' => 'array',
            'segments' => 'array',
            'regions' => 'array',
            'operations' => 'array',
            'meta' => 'array',
            'calculated_at' => 'datetime',
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
