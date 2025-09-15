<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OmarElnaghy\LaravelChatSoul\Models\Conversation;
use OmarElnaghy\LaravelChatSoul\Models\Message;
use OmarElnaghy\LaravelChatSoul\Http\Requests\SendMessageRequest;
use OmarElnaghy\LaravelChatSoul\Http\Resources\MessageResource;
use OmarElnaghy\LaravelChatSoul\Services\ChatService;
use OmarElnaghy\LaravelChatSoul\Events\MessageSent;
use OmarElnaghy\LaravelChatSoul\Events\MessageRead;

class MessageController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    /**
     * Get messages for a conversation.
     */
    public function index(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        // Verify user has access to conversation
        $conversation = Conversation::forUser($user->id)->findOrFail($conversationId);
        
        $perPage = min($request->get('per_page', config('chat-soul.pagination.messages_per_page')), 100);
        $search = $request->get('search');

        $query = Message::inConversation($conversationId)
            ->with(['user', 'reads.user', 'replyTo.user'])
            ->orderBy('created_at', 'desc');

        if ($search && config('chat-soul.features.message_search')) {
            $query->search($search);
        }

        $messages = $query->paginate($perPage);

        return response()->json([
            'data' => MessageResource::collection($messages->items()),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        ]);
    }

    /**
     * Send a new message.
     */
    public function store(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        // Verify user has access to conversation
        $conversation = Conversation::forUser($user->id)->findOrFail($conversationId);
        
        $data = $request->validated();
        $data['conversation_id'] = $conversationId;
        $data['user_id'] = $user->id;

        $message = $this->chatService->sendMessage($data, $request->file('attachment'));

        // Broadcast message sent event
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'data' => new MessageResource($message->load(['user', 'reads.user', 'replyTo.user']))
        ], 201);
    }

    /**
     * Show a specific message.
     */
    public function show(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $user = $request->user();
        
        // Verify user has access to conversation
        Conversation::forUser($user->id)->findOrFail($conversationId);
        
        $message = Message::inConversation($conversationId)
            ->with(['user', 'reads.user', 'replyTo.user'])
            ->findOrFail($messageId);

        return response()->json([
            'data' => new MessageResource($message)
        ]);
    }

    /**
     * Update message (edit).
     */
    public function update(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'content' => 'required|string|max:' . config('chat-soul.messages.max_length'),
        ]);

        // Verify user has access to conversation
        Conversation::forUser($user->id)->findOrFail($conversationId);
        
        $message = Message::inConversation($conversationId)
            ->fromUser($user->id)
            ->findOrFail($messageId);

        // Only allow editing within 5 minutes and text messages
        if ($message->created_at->diffInMinutes(now()) > 5 || $message->type !== Message::TYPE_TEXT) {
            return response()->json(['message' => 'Message cannot be edited'], 422);
        }

        $message->update([
            'content' => $request->content,
            'metadata' => array_merge($message->metadata ?? [], ['edited_at' => now()]),
        ]);

        return response()->json([
            'data' => new MessageResource($message->load(['user', 'reads.user', 'replyTo.user']))
        ]);
    }

    /**
     * Delete message (soft delete).
     */
    public function destroy(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $user = $request->user();
        
        // Verify user has access to conversation
        Conversation::forUser($user->id)->findOrFail($conversationId);
        
        $message = Message::inConversation($conversationId)
            ->fromUser($user->id)
            ->findOrFail($messageId);

        if (config('chat-soul.messages.soft_delete')) {
            $message->delete();
        } else {
            $message->forceDelete();
        }

        return response()->json(['message' => 'Message deleted successfully']);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(Request $request, int $conversationId, int $messageId): JsonResponse
    {
        $user = $request->user();
        
        // Verify user has access to conversation
        Conversation::forUser($user->id)->findOrFail($conversationId);
        
        $message = Message::inConversation($conversationId)->findOrFail($messageId);
        
        // Don't mark own messages as read
        if ($message->user_id === $user->id) {
            return response()->json(['message' => 'Cannot mark own message as read'], 422);
        }

        $messageRead = $message->markAsReadBy($user->id);

        // Broadcast message read event
        if (config('chat-soul.features.read_receipts')) {
            broadcast(new MessageRead($message, $user))->toOthers();
        }

        return response()->json([
            'data' => [
                'message_id' => $message->id,
                'read_at' => $messageRead->read_at,
                'user' => $user->only(['id', 'name']),
            ]
        ]);
    }

    /**
     * Mark all messages as read in conversation.
     */
    public function markAllAsRead(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        // Verify user has access to conversation
        Conversation::forUser($user->id)->findOrFail($conversationId);
        
        $user->markAllAsReadInConversation($conversationId);

        return response()->json(['message' => 'All messages marked as read']);
    }

    /**
     * Get unread message count for conversation.
     */
    public function unreadCount(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        // Verify user has access to conversation
        $conversation = Conversation::forUser($user->id)->findOrFail($conversationId);
        
        $count = $conversation->getUnreadCountFor($user->id);

        return response()->json(['unread_count' => $count]);
    }
}