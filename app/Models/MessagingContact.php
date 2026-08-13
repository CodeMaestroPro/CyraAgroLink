<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Directory contact available for messaging threads.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $image_path
 * @property bool $is_online
 */
class MessagingContact extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'name',
        'image_path',
        'is_online',
        'is_system',
        'linked_user_id',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_online' => 'boolean',
            'is_system' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    /**
     * @return HasMany<MessageThread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(MessageThread::class);
    }

    public function imageUrl(): string
    {
        return asset($this->image_path ?: 'images/marketplace/supplier-1.jpg');
    }
}
