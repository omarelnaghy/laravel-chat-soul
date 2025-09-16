<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OmarElnaghy\LaravelChatSoul\Http\Requests\TypingEventRequest;
use OmarElnaghy\LaravelChatSoul\Services\PresenceService;
use OmarElnaghy\LaravelChatSoul\Events\UserTyping;

class ChatController extends Controller
{
    public function __construct(
        protected PresenceService $presenceService
    ) {}

    /**
     * Handle typing indicator events.
     */
    public function typing(TypingEventRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if (config('chat-soul.features.typing_indicators')) {
            // Store typing status in cache
            $this->presenceService->setUserTyping(
                $user->id,
                $data['conversation_id'],
                $data['is_typing']
            );

            // Broadcast typing event
            broadcast(new UserTyping(
                $user,
                $data['conversation_id'],
                $data['is_typing']
            ))->toOthers();
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Get typing users for a conversation.
     */
    public function getTypingUsers(Request $request, int $conversationId): JsonResponse
    {
        if (!config('chat-soul.features.typing_indicators')) {
            return response()->json(['typing_users' => []]);
        }

        $user = $request->user();
        $typingUsers = $this->presenceService->getTypingUsers($conversationId, $user->id);

        return response()->json(['typing_users' => $typingUsers]);
    }

    /**
     * Get user's overall chat stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'total_conversations' => $user->conversations()->count(),
            'unread_messages' => $user->getUnreadMessageCount(),
            'total_messages_sent' => $user->messages()->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * Search across conversations and messages.
     */
    public function search(Request $request): JsonResponse
    {
        if (!config('chat-soul.features.message_search')) {
            return response()->json(['message' => 'Search feature is disabled'], 403);
        }
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'type' => 'sometimes|in:messages,conversations,all',
        ]);

        $user = $request->user();
        $query = $request->get('query');
        $type = $request->get('type', 'all');
        $results = [];

        if (in_array($type, ['messages', 'all'])) {
            $messages = \OmarElnaghy\LaravelChatSoul\Models\Message::whereHas('conversation', function ($q) use ($user) {
                $q->forUser($user->id);
            })
            ->search($query)
            ->with(['user', 'conversation'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

            $results['messages'] = \OmarElnaghy\LaravelChatSoul\Http\Resources\MessageResource::collection($messages);
        }

        if (in_array($type, ['conversations', 'all'])) {
            $conversations = $user->conversations()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%");
                })
                ->with(['participants', 'latestMessage'])
                ->limit(10)
                ->get();

            $results['conversations'] = \OmarElnaghy\LaravelChatSoul\Http\Resources\ConversationResource::collection($conversations);
        }

        return response()->json(['data' => $results]);
    }
}
