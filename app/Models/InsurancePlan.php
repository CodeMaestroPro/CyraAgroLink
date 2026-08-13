<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalog plan offered on the farm insurance platform.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $category
 * @property int $premium_ngn
 * @property int $coverage_ngn
 * @property int $duration_days
 * @property bool $is_active
 */
class InsurancePlan extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'category',
        'premium_ngn',
        'coverage_ngn',
        'duration_days',
        'description',
        'enterprise_tags',
        'is_active',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'premium_ngn' => 'integer',
            'coverage_ngn' => 'integer',
            'duration_days' => 'integer',
            'enterprise_tags' => 'array',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<InsurancePolicy, $this>
     */
    public function policies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'plan_id');
    }
}
