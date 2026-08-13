<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * CyraAI chats stored in session (ChatGPT-style sidebar) and cleared on refresh.
 *
 * A flash flag keeps chats alive across POST → redirect → GET. A normal
 * browser refresh has no flash, so the session transcript is wiped.
 */
class AiAssistantService
{
    public const SESSION_KEY = 'cyra_ai_chats';

    public const CONTINUE_FLASH = 'cyra_ai_continue';

    public function __construct(
        protected FarmingAdvisoryEngine $advisoryEngine,
    ) {
    }

    /**
     * Wipe session chats (used on fresh page load / refresh).
     */
    public function clearSessionChats(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Keep chats alive for the next redirect GET (not for a manual refresh).
     */
    public function flashContinue(): void
    {
        session()->flash(self::CONTINUE_FLASH, true);
    }

    public function shouldContinueSession(): bool
    {
        return (bool) session()->pull(self::CONTINUE_FLASH, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAssistantData(User $user, ?string $conversationId = null): array
    {
        $state = $this->state();

        if ($state['conversations'] === []) {
            $conversation = $this->makeConversation($user);
            $state['conversations'][] = $conversation;
            $state['active_id'] = $conversation['id'];
            $this->putState($state);
        }

        $activeId = $conversationId ?? $state['active_id'];
        $active = $this->findConversation($state, $activeId) ?? $state['conversations'][0];
        $state['active_id'] = $active['id'];
        $this->putState($state);

        return [
            'greeting_name' => $this->firstName($user->name),
            'conversations' => collect($state['conversations'])
                ->sortByDesc(fn (array $c) => $c['updated_at'])
                ->values()
                ->map(fn (array $conversation) => $this->toSidebarItem($conversation, $active['id']))
                ->all(),
            'active_conversation' => [
                'id' => $active['id'],
                'title' => $active['title'],
                'messages' => $this->messagesForView($active['messages']),
            ],
            'notifications_count' => 2,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createConversation(User $user): array
    {
        $state = $this->state();
        $conversation = $this->makeConversation($user);
        $state['conversations'][] = $conversation;
        $state['active_id'] = $conversation['id'];
        $this->putState($state);

        return $conversation;
    }

    /**
     * @return array<string, mixed>
     */
    public function openConversation(string $conversationId): array
    {
        $state = $this->state();
        $conversation = $this->findConversation($state, $conversationId);
        abort_if($conversation === null, 404);

        $state['active_id'] = $conversationId;
        $this->putState($state);

        return $conversation;
    }

    /**
     * @return array{conversation: array<string, mixed>, reply: array<string, mixed>}
     */
    public function sendMessage(User $user, string $conversationId, string $prompt): array
    {
        $state = $this->state();
        $index = $this->findConversationIndex($state, $conversationId);
        abort_if($index === null, 404);

        $trimmed = trim($prompt);
        $conversation = $state['conversations'][$index];
        $now = now()->toIso8601String();

        $conversation['messages'][] = [
            'role' => 'user',
            'type' => 'text',
            'body' => $trimmed,
        ];

        $replyPayload = $this->advisoryEngine->answer($user, $trimmed);
        $replyMessage = $this->normalizeReplyMessage($replyPayload);
        $conversation['messages'][] = $replyMessage;

        if (($conversation['title'] ?? '') === 'New chat' || ($conversation['title'] ?? '') === '') {
            $conversation['title'] = Str::limit(Str::title($trimmed), 60, '…');
        }
        $conversation['subtitle'] = $this->resolveSubtitle($trimmed);
        $conversation['updated_at'] = $now;

        $state['conversations'][$index] = $conversation;
        $state['active_id'] = $conversationId;
        $this->putState($state);

        $viewMessages = $this->messagesForView($conversation['messages']);
        $viewReply = $viewMessages[array_key_last($viewMessages)] ?? $replyMessage;

        return [
            'conversation' => [
                ...$conversation,
                'messages' => $viewMessages,
            ],
            'reply' => $viewReply,
        ];
    }

    /**
     * @return array{conversations: list<array<string, mixed>>, active_id: string|null}
     */
    protected function state(): array
    {
        $state = session(self::SESSION_KEY, [
            'conversations' => [],
            'active_id' => null,
        ]);

        return [
            'conversations' => array_values($state['conversations'] ?? []),
            'active_id' => $state['active_id'] ?? null,
        ];
    }

    /**
     * @param  array{conversations: list<array<string, mixed>>, active_id: string|null}  $state
     */
    protected function putState(array $state): void
    {
        session([self::SESSION_KEY => $state]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function makeConversation(User $user): array
    {
        $name = $this->firstName($user->name);
        $now = now()->toIso8601String();

        return [
            'id' => (string) Str::uuid(),
            'title' => 'New chat',
            'subtitle' => 'Ask CyraAI',
            'updated_at' => $now,
            'messages' => [
                [
                    'role' => 'assistant',
                    'type' => 'text',
                    'body' => "Hello {$name} 👋 Ask me any farming question—crops, poultry, livestock, fish, soil, fertilizer, pests, irrigation, storage, or farm business—and I will answer.",
                ],
            ],
        ];
    }

    /**
     * @param  array{conversations: list<array<string, mixed>>, active_id: string|null}  $state
     * @return array<string, mixed>|null
     */
    protected function findConversation(array $state, ?string $id): ?array
    {
        if ($id === null || $id === '') {
            return null;
        }

        foreach ($state['conversations'] as $conversation) {
            if (($conversation['id'] ?? null) === $id) {
                return $conversation;
            }
        }

        return null;
    }

    /**
     * @param  array{conversations: list<array<string, mixed>>, active_id: string|null}  $state
     */
    protected function findConversationIndex(array $state, string $id): ?int
    {
        foreach ($state['conversations'] as $index => $conversation) {
            if (($conversation['id'] ?? null) === $id) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $conversation
     * @return array<string, mixed>
     */
    protected function toSidebarItem(array $conversation, string $activeId): array
    {
        $at = isset($conversation['updated_at'])
            ? Carbon::parse($conversation['updated_at'])
            : now();

        return [
            'id' => $conversation['id'],
            'title' => $conversation['title'] ?? 'New chat',
            'subtitle' => $conversation['subtitle'] ?? 'CyraAI',
            'time' => $this->formatSidebarTime($at),
            'active' => ($conversation['id'] ?? null) === $activeId,
            'group' => 'Today',
        ];
    }

    protected function formatSidebarTime(Carbon $at): string
    {
        if ($at->isToday()) {
            return $at->format('g:i A');
        }

        if ($at->isYesterday()) {
            return 'Yesterday';
        }

        return $at->diffForHumans(short: true);
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    protected function messagesForView(array $messages): array
    {
        return array_map(function (array $message): array {
            if (($message['type'] ?? 'text') === 'diagnosis') {
                $image = $message['image'] ?? null;
                if (is_string($image) && $image !== '' && ! str_starts_with($image, 'http')) {
                    $message['image'] = asset($image);
                }
            }

            return $message;
        }, $messages);
    }

    /**
     * @param  array{type: string, body?: string, payload?: array<string, mixed>}  $reply
     * @return array<string, mixed>
     */
    protected function normalizeReplyMessage(array $reply): array
    {
        if (($reply['type'] ?? 'text') === 'diagnosis') {
            return [
                'role' => 'assistant',
                'type' => 'diagnosis',
                ...($reply['payload'] ?? []),
            ];
        }

        return [
            'role' => 'assistant',
            'type' => 'text',
            'body' => $reply['body'] ?? 'I am here to help with your farm.',
        ];
    }

    protected function firstName(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $first = $parts[0] ?? '';

        return $first !== '' ? $first : 'Farmer';
    }

    protected function resolveSubtitle(string $prompt): string
    {
        $text = Str::lower($prompt);

        return match (true) {
            Str::contains($text, ['layer', 'egg']) => 'Layers',
            Str::contains($text, ['broiler', 'poultry', 'chicken']) => 'Poultry',
            Str::contains($text, ['fish', 'catfish', 'tilapia', 'pond']) => 'Aquaculture',
            Str::contains($text, ['goat', 'sheep']) => 'Livestock',
            Str::contains($text, ['maize', 'corn', 'blight']) => 'Maize',
            Str::contains($text, ['fertilizer', 'npk', 'urea']) => 'NPK guidance',
            Str::contains($text, ['soil']) => 'Soil health',
            Str::contains($text, ['water', 'irrigat']) => 'Irrigation',
            default => 'Farm advisory',
        };
    }
}
