<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Weather risk alert tied to a snapshot / location.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $weather_snapshot_id
 * @property string $alert_key
 * @property string $title
 * @property string $detail
 * @property string $icon
 * @property string $status
 */
class WeatherAlert extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'weather_snapshot_id',
        'alert_key',
        'title',
        'detail',
        'icon',
        'severity',
        'status',
        'starts_at',
        'ends_at',
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
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
     * @return BelongsTo<WeatherSnapshot, $this>
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(WeatherSnapshot::class, 'weather_snapshot_id');
    }
}
