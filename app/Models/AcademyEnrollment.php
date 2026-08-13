<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User progress on an academy course.
 *
 * @property int $id
 * @property int $user_id
 * @property int $academy_course_id
 * @property int $progress_pct
 * @property string $status
 * @property string|null $certificate_code
 */
class AcademyEnrollment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'academy_course_id',
        'progress_pct',
        'status',
        'started_at',
        'last_activity_at',
        'completed_at',
        'certificate_code',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'progress_pct' => 'integer',
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
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
     * @return BelongsTo<AcademyCourse, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(AcademyCourse::class, 'academy_course_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
