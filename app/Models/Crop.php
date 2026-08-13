<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CropGrowthStage;
use App\Enums\CropHealthStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Crop cycle managed on a farm.
 *
 * @property int $id
 * @property int $farm_id
 * @property int $user_id
 * @property string $name
 * @property string|null $variety
 * @property CropGrowthStage $growth_stage
 * @property int $progress_percent
 * @property CropHealthStatus $health_status
 * @property string|null $health_notes
 * @property string|null $next_activity
 * @property Carbon|null $next_activity_at
 * @property Carbon|null $planted_at
 * @property Carbon|null $expected_harvest_at
 * @property string|null $ai_recommendation
 * @property string $status
 */
class Crop extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'farm_id',
        'user_id',
        'name',
        'variety',
        'growth_stage',
        'progress_percent',
        'health_status',
        'health_notes',
        'next_activity',
        'next_activity_at',
        'planted_at',
        'expected_harvest_at',
        'ai_recommendation',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'growth_stage' => CropGrowthStage::class,
            'health_status' => CropHealthStatus::class,
            'progress_percent' => 'integer',
            'next_activity_at' => 'datetime',
            'planted_at' => 'datetime',
            'expected_harvest_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Farm, $this>
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CropActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(CropActivity::class)->latest('occurred_at')->latest('id');
    }

    /**
     * Display title: "Maize - Green Valley Farm".
     */
    public function displayTitle(): string
    {
        $farmName = $this->farm?->name ?: 'Farm';

        return "{$this->name} - {$farmName}";
    }

    /**
     * Days remaining until expected harvest.
     */
    public function daysToHarvest(): ?int
    {
        if ($this->expected_harvest_at === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expected_harvest_at->startOfDay(), false);
    }

    /**
     * Human-readable next activity timing.
     */
    public function nextActivityLabel(): ?string
    {
        if ($this->next_activity_at === null) {
            return null;
        }

        $days = (int) now()->startOfDay()->diffInDays($this->next_activity_at->startOfDay(), false);

        if ($days === 0) {
            return 'Today';
        }

        if ($days === 1) {
            return 'In 1 day';
        }

        if ($days > 1) {
            return "In {$days} days";
        }

        return abs($days).' days ago';
    }
}
