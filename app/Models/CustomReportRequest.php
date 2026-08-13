<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User-requested custom analytics report.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $report_type
 * @property string $period
 * @property string $status
 * @property string|null $file_name
 */
class CustomReportRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'report_type',
        'period',
        'segment',
        'notes',
        'status',
        'file_name',
        'ready_at',
        'downloaded_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ready_at' => 'datetime',
            'downloaded_at' => 'datetime',
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

    public function isReady(): bool
    {
        return in_array($this->status, ['ready', 'downloaded'], true);
    }
}
