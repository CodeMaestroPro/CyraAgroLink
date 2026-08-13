<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Food processing factory / plant in the network catalog.
 *
 * @property int $id
 * @property string $name
 * @property string|null $state
 * @property array<int, string> $services
 * @property int $capacity_tons_per_day
 * @property int $utilization_percent
 * @property int $active_jobs
 * @property int $completed_jobs
 * @property bool $is_active
 */
class ProcessingFactory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'state',
        'services',
        'capacity_tons_per_day',
        'utilization_percent',
        'active_jobs',
        'completed_jobs',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'services' => 'array',
            'capacity_tons_per_day' => 'integer',
            'utilization_percent' => 'integer',
            'active_jobs' => 'integer',
            'completed_jobs' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProcessingRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(ProcessingRequest::class, 'factory_id');
    }
}
