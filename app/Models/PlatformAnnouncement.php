<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide announcement shown in the messaging hub.
 *
 * @property int $id
 * @property string $title
 * @property string $body
 * @property bool $is_active
 */
class PlatformAnnouncement extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'body',
        'audience',
        'is_active',
        'published_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
