<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use OmarElnaghy\LaravelChatSoul\Services\PresenceService;
use OmarElnaghy\LaravelChatSoul\Events\UserOnline;
use OmarElnaghy\LaravelChatSoul\Events\UserOffline;

class PresenceController extends Controller
{
    public function __construct(
        protected PresenceService $presenceService
    ) {}

    /**
     * Mark user as online.
     */
    public function setOnline(Request $request): JsonResponse
    {
        if (!config('chat-soul.features.presence')) {
            return response()->json(['message' => 'Presence feature is disabled'], 403);
        }

        $user = $request->user();
        
        $this->presenceService->setUserOnline($user->id);
        
        // Broadcast user online event
        broadcast(new UserOnline($user))->toOthers();

        return response()->json(['status' => 'online']);
    }

    /**
     * Mark user as offline.
     */
    public function setOffline(Request $request): JsonResponse
    {
        if (!config('chat-soul.features.presence')) {
            return response()->json(['message' => 'Presence feature is disabled'], 403);
        }

        $user = $request->user();
        
        $this->presenceService->setUserOffline($user->id);
        
        // Broadcast user offline event
        broadcast(new UserOffline($user))->toOthers();

        return response()->json(['status' => 'offline']);
    }

    /**
     * Get online users.
     */
    public function getOnlineUsers(Request $request): JsonResponse
    {
        if (!config('chat-soul.features.presence')) {
            return response()->json(['online_users' => []]);
        }

        $onlineUsers = $this->presenceService->getOnlineUsers();
        $userModel = config('chat-soul.auth.user_model');
        
        $users = $userModel::whereIn('id', $onlineUsers)
            ->select(['id', 'name', 'email', 'avatar'])
            ->get();

        return response()->json([
            'online_users' => \OmarElnaghy\LaravelChatSoul\Http\Resources\UserResource::collection($users)
        ]);
    }

    /**
     * Check if users are online.
     */
    public function checkUsersOnline(Request $request): JsonResponse
    {
        if (!config('chat-soul.features.presence')) {
            return response()->json(['users_status' => []]);
        }

        $request->validate([
            'user_ids' => 'required|array|max:50',
            'user_ids.*' => 'required|integer',
        ]);

        $usersStatus = [];
        foreach ($request->user_ids as $userId) {
            $usersStatus[$userId] = $this->presenceService->isUserOnline($userId);
        }

        return response()->json(['users_status' => $usersStatus]);
    }

    /**
     * Update user's last seen.
     */
    public function updateLastSeen(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $this->presenceService->updateLastSeen($user->id);

        return response()->json(['status' => 'updated']);
    }

    /**
     * Get user's last seen time.
     */
    public function getLastSeen(Request $request, int $userId): JsonResponse
    {
        $lastSeen = $this->presenceService->getLastSeen($userId);

        return response()->json([
            'user_id' => $userId,
            'last_seen' => $lastSeen,
            'is_online' => $this->presenceService->isUserOnline($userId),
        ]);
    }
}