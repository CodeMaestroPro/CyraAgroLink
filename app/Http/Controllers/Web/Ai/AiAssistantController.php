<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Ai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\SendAiMessageRequest;
use App\Services\Ai\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CyraAI farming assistant — session chats that clear on page refresh.
 */
class AiAssistantController extends Controller
{
    public function __construct(
        protected AiAssistantService $aiAssistantService
    ) {
    }

    /**
     * Display the AI Assistant chat screen.
     *
     * Without a continue flash (normal visit / browser refresh), chats reset.
     */
    public function index(Request $request): View
    {
        if (! $this->aiAssistantService->shouldContinueSession()) {
            $this->aiAssistantService->clearSessionChats();
        }

        $conversationId = $request->string('conversation')->toString() ?: null;
        $data = $this->aiAssistantService->getAssistantData($request->user(), $conversationId);

        return view('ai.assistant', [
            'greetingName' => $data['greeting_name'],
            'conversations' => $data['conversations'],
            'activeConversation' => $data['active_conversation'],
            'notificationsCount' => $data['notifications_count'],
        ]);
    }

    /**
     * Start a new CyraAI conversation in this browser session.
     */
    public function store(Request $request): RedirectResponse
    {
        $conversation = $this->aiAssistantService->createConversation($request->user());
        $this->aiAssistantService->flashContinue();

        return redirect()
            ->route('ai.assistant', ['conversation' => $conversation['id']])
            ->with('status', 'New chat started.');
    }

    /**
     * Switch to an existing session conversation (keeps chats alive).
     */
    public function open(Request $request, string $conversation): RedirectResponse
    {
        $this->aiAssistantService->openConversation($conversation);
        $this->aiAssistantService->flashContinue();

        return redirect()->route('ai.assistant', ['conversation' => $conversation]);
    }

    /**
     * Send a farmer prompt and receive an advisory reply.
     */
    public function message(SendAiMessageRequest $request, string $conversation): JsonResponse|RedirectResponse
    {
        $result = $this->aiAssistantService->sendMessage(
            $request->user(),
            $conversation,
            $request->validated('prompt')
        );

        if ($request->expectsJson()) {
            // No continue flash — a browser refresh should still clear chats.
            return response()->json([
                'conversation_id' => $result['conversation']['id'],
                'title' => $result['conversation']['title'],
                'subtitle' => $result['conversation']['subtitle'],
                'reply' => $result['reply'],
            ]);
        }

        $this->aiAssistantService->flashContinue();

        return redirect()
            ->route('ai.assistant', ['conversation' => $conversation])
            ->withFragment('chat-end');
    }
}
