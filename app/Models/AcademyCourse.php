<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agricultural learning course in the academy catalog.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $level
 * @property string $level_tone
 * @property string $rating
 * @property int $duration_minutes
 * @property string $image_path
 * @property int $modules_count
 * @property bool $is_featured
 * @property bool $is_active
 */
class AcademyCourse extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'level',
        'level_tone',
        'rating',
        'duration_minutes',
        'image_path',
        'summary',
        'enterprise_tags',
        'modules_count',
        'sort_order',
        'is_featured',
        'is_active',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'duration_minutes' => 'integer',
            'modules_count' => 'integer',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'enterprise_tags' => 'array',
            'meta' => 'array',
        ];
    }

    /**
     * @return HasMany<AcademyEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(AcademyEnrollment::class);
    }

    public function formattedDuration(): string
    {
        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return $hours.'h '.$minutes.'m';
        }

        if ($hours > 0) {
            return $hours.'h';
        }

        return $minutes.'m';
    }
}
