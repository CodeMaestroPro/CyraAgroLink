<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user acknowledgement / dismiss state for a platform announcement.
 *
 * @property int $id
 * @property int $user_id
 * @property int $platform_announcement_id
 */
class UserAnnouncementRead extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'platform_announcement_id',
        'acknowledged_at',
        'dismissed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'dismissed_at' => 'datetime',
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
     * @return BelongsTo<PlatformAnnouncement, $this>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(PlatformAnnouncement::class, 'platform_announcement_id');
    }
}
