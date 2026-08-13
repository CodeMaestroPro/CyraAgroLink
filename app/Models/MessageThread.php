<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Conversation between a user and a messaging contact.
 *
 * @property int $id
 * @property int $user_id
 * @property int $messaging_contact_id
 */
class MessageThread extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'messaging_contact_id',
        'last_message_at',
        'last_read_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'last_read_at' => 'datetime',
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
     * @return BelongsTo<MessagingContact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(MessagingContact::class, 'messaging_contact_id');
    }

    /**
     * @return HasMany<ThreadMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ThreadMessage::class);
    }
}
