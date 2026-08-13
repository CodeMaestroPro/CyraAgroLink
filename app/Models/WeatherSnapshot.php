<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Stored weather observation / forecast snapshot for a location.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $farm_id
 * @property string $location_key
 * @property string $location_label
 * @property int $temperature_c
 * @property string $condition
 * @property string $recommendation
 */
class WeatherSnapshot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'farm_id',
        'location_key',
        'location_label',
        'latitude',
        'longitude',
        'temperature_c',
        'condition',
        'icon',
        'humidity_pct',
        'rainfall_mm',
        'wind_kmh',
        'forecast',
        'rainfall_zones',
        'recommendation',
        'source',
        'observed_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'temperature_c' => 'integer',
            'humidity_pct' => 'integer',
            'rainfall_mm' => 'decimal:1',
            'wind_kmh' => 'integer',
            'forecast' => 'array',
            'rainfall_zones' => 'array',
            'observed_at' => 'datetime',
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
     * @return BelongsTo<Farm, $this>
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * @return HasMany<WeatherAlert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(WeatherAlert::class);
    }
}
