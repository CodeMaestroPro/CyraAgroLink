<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Calculated national food security snapshot.
 *
 * @property int $id
 * @property int $index_score
 * @property string $index_status
 * @property int $production_tons
 * @property int $import_dependency_pct
 * @property int $reserves_tons
 */
class FoodSecuritySnapshot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'index_score',
        'index_status',
        'production_tons',
        'import_dependency_pct',
        'reserves_tons',
        'commodities',
        'hunger_zones',
        'factors',
        'calculated_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'index_score' => 'integer',
            'production_tons' => 'integer',
            'import_dependency_pct' => 'integer',
            'reserves_tons' => 'integer',
            'commodities' => 'array',
            'hunger_zones' => 'array',
            'factors' => 'array',
            'calculated_at' => 'datetime',
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
