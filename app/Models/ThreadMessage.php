<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single message inside a conversation thread.
 *
 * @property int $id
 * @property int $message_thread_id
 * @property int|null $user_id
 * @property string $role
 * @property string $body
 */
class ThreadMessage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'message_thread_id',
        'user_id',
        'role',
        'body',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<MessageThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'message_thread_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
