<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Single message in a CyraAI conversation.
 *
 * @property int $id
 * @property int $ai_conversation_id
 * @property string $role
 * @property string $type
 * @property string|null $body
 * @property array<string, mixed>|null $payload
 */
class AiMessage extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ai_conversation_id',
        'role',
        'type',
        'body',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<AiConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewMessage(): array
    {
        if ($this->type === 'diagnosis') {
            $payload = $this->payload ?? [];
            $image = $payload['image'] ?? null;

            if (is_string($image) && $image !== '' && ! str_starts_with($image, 'http')) {
                $payload['image'] = asset($image);
            }

            return [
                'role' => $this->role,
                'type' => 'diagnosis',
                ...$payload,
            ];
        }

        return [
            'role' => $this->role,
            'type' => 'text',
            'body' => $this->body ?? '',
        ];
    }
}
