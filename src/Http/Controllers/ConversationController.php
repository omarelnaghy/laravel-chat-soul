<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OmarElnaghy\LaravelChatSoul\Models\Conversation;
use OmarElnaghy\LaravelChatSoul\Http\Requests\CreateConversationRequest;
use OmarElnaghy\LaravelChatSoul\Http\Resources\ConversationResource;
use OmarElnaghy\LaravelChatSoul\Services\ChatService;

class ConversationController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    /**
     * Get user's conversations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min($request->get('per_page', config('chat-soul.pagination.conversations_per_page')), 50);
        
        $conversations = $user->conversations()
            ->with(['latestMessage.user', 'participants'])
            ->withCount(['messages', 'participants'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => ConversationResource::collection($conversations->items()),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ]
        ]);
    }

    /**
     * Show a specific conversation.
     */
    public function show(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        $conversation = Conversation::with(['participants', 'latestMessage.user'])
            ->withCount(['messages', 'participants'])
            ->forUser($user->id)
            ->findOrFail($conversationId);

        return response()->json([
            'data' => new ConversationResource($conversation)
        ]);
    }

    /**
     * Create a new conversation.
     */
    public function store(CreateConversationRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $conversation = $this->chatService->createConversation($user->id, $data);

        return response()->json([
            'data' => new ConversationResource($conversation->load(['participants', 'latestMessage.user']))
        ], 201);
    }

    /**
     * Update conversation details.
     */
    public function update(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'settings' => 'sometimes|array',
        ]);

        $conversation = Conversation::forUser($user->id)->findOrFail($conversationId);

        // Check if user has permission to update (creator or admin)
        $userPivot = $conversation->participants()->where('user_id', $user->id)->first();
        if ($conversation->created_by !== $user->id && $userPivot?->pivot?->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->update($request->only(['name', 'description', 'settings']));

        return response()->json([
            'data' => new ConversationResource($conversation->load(['participants', 'latestMessage.user']))
        ]);
    }

    /**
     * Add participants to conversation.
     */
    public function addParticipants(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'user_ids' => 'required|array|min:1|max:10',
            'user_ids.*' => 'required|integer|exists:users,id',
        ]);

        $conversation = Conversation::forUser($user->id)->findOrFail($conversationId);

        // Check if user has permission to add participants
        $userPivot = $conversation->participants()->where('user_id', $user->id)->first();
        if ($conversation->created_by !== $user->id && $userPivot?->pivot?->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        foreach ($request->user_ids as $userId) {
            if (!$conversation->hasParticipant($userId)) {
                $conversation->addParticipant($userId);
            }
        }

        return response()->json([
            'data' => new ConversationResource($conversation->load(['participants', 'latestMessage.user']))
        ]);
    }

    /**
     * Remove participant from conversation.
     */
    public function removeParticipant(Request $request, int $conversationId, int $userId): JsonResponse
    {
        $user = $request->user();
        
        $conversation = Conversation::forUser($user->id)->findOrFail($conversationId);

        // Users can remove themselves, or admins/creators can remove others
        $userPivot = $conversation->participants()->where('user_id', $user->id)->first();
        $canRemove = $userId === $user->id || 
                    $conversation->created_by === $user->id || 
                    $userPivot?->pivot?->role === 'admin';

        if (!$canRemove) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->removeParticipant($userId);

        return response()->json([
            'data' => new ConversationResource($conversation->load(['participants', 'latestMessage.user']))
        ]);
    }

    /**
     * Leave conversation.
     */
    public function leave(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        $conversation = Conversation::forUser($user->id)->findOrFail($conversationId);
        $conversation->removeParticipant($user->id);

        return response()->json(['message' => 'Left conversation successfully']);
    }

    /**
     * Delete conversation (soft delete).
     */
    public function destroy(Request $request, int $conversationId): JsonResponse
    {
        $user = $request->user();
        
        $conversation = Conversation::forUser($user->id)->findOrFail($conversationId);

        // Only creator can delete the conversation
        if ($conversation->created_by !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted successfully']);
    }

    /**
     * Get or create direct conversation with another user.
     */
    public function getOrCreateDirect(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $request->validate([
            'user_id' => 'required|integer|exists:users,id|not_in:' . $user->id,
        ]);

        $conversation = $user->getOrCreateDirectConversationWith($request->user_id);

        return response()->json([
            'data' => new ConversationResource($conversation->load(['participants', 'latestMessage.user']))
        ]);
    }
}