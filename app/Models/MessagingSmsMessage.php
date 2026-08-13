<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Outbound/inbound SMS logged in the messaging hub.
 *
 * @property int $id
 * @property int $user_id
 * @property string $direction
 * @property string $to_phone
 * @property string $body
 * @property string $status
 */
class MessagingSmsMessage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'direction',
        'to_phone',
        'from_phone',
        'body',
        'status',
        'provider',
        'sent_at',
        'failure_reason',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
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

    public function statusLabel(): string
    {
        return match ($this->status) {
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'queued' => 'Queued',
            'draft' => 'Draft',
            default => 'Sent',
        };
    }
}
