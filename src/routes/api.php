<?php

use Illuminate\Support\Facades\Route;
use OmarElnaghy\LaravelChatSoul\Http\Controllers\ConversationController;
use OmarElnaghy\LaravelChatSoul\Http\Controllers\MessageController;
use OmarElnaghy\LaravelChatSoul\Http\Controllers\ChatController;
use OmarElnaghy\LaravelChatSoul\Http\Controllers\PresenceController;

/*
|--------------------------------------------------------------------------
| Chat Soul API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with 'api/chat' and require authentication
|
*/

// Apply rate limiting middleware
Route::middleware(['throttle:' . config('chat-soul.rate_limits.send_message')])->group(function () {
    
    // Conversation routes
    Route::apiResource('conversations', ConversationController::class);
    Route::post('conversations/direct', [ConversationController::class, 'getOrCreateDirect']);
    Route::post('conversations/{conversationId}/participants', [ConversationController::class, 'addParticipants']);
    Route::delete('conversations/{conversationId}/participants/{userId}', [ConversationController::class, 'removeParticipant']);
    Route::post('conversations/{conversationId}/leave', [ConversationController::class, 'leave']);

    // Message routes
    Route::get('conversations/{conversationId}/messages', [MessageController::class, 'index']);
    Route::post('conversations/{conversationId}/messages', [MessageController::class, 'store']);
    Route::get('conversations/{conversationId}/messages/{messageId}', [MessageController::class, 'show']);
    Route::put('conversations/{conversationId}/messages/{messageId}', [MessageController::class, 'update']);
    Route::delete('conversations/{conversationId}/messages/{messageId}', [MessageController::class, 'destroy']);
    
    // Read receipts
    Route::post('conversations/{conversationId}/messages/{messageId}/read', [MessageController::class, 'markAsRead']);
    Route::post('conversations/{conversationId}/messages/read-all', [MessageController::class, 'markAllAsRead']);
    Route::get('conversations/{conversationId}/unread-count', [MessageController::class, 'unreadCount']);
});

// Typing indicators with separate rate limit
Route::middleware(['throttle:' . config('chat-soul.rate_limits.typing_events')])->group(function () {
    Route::post('typing', [ChatController::class, 'typing']);
    Route::get('conversations/{conversationId}/typing', [ChatController::class, 'getTypingUsers']);
});

// Presence routes
Route::prefix('presence')->group(function () {
    Route::post('online', [PresenceController::class, 'setOnline']);
    Route::post('offline', [PresenceController::class, 'setOffline']);
    Route::get('online-users', [PresenceController::class, 'getOnlineUsers']);
    Route::post('check-users', [PresenceController::class, 'checkUsersOnline']);
    Route::post('update-last-seen', [PresenceController::class, 'updateLastSeen']);
    Route::get('last-seen/{userId}', [PresenceController::class, 'getLastSeen']);
});

// General chat routes
Route::get('stats', [ChatController::class, 'stats']);
Route::get('search', [ChatController::class, 'search']);

// Health check route
Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'features' => [
            'typing_indicators' => config('chat-soul.features.typing_indicators'),
            'read_receipts' => config('chat-soul.features.read_receipts'),
            'presence' => config('chat-soul.features.presence'),
            'file_uploads' => config('chat-soul.features.file_uploads'),
            'message_search' => config('chat-soul.features.message_search'),
            'group_chats' => config('chat-soul.features.group_chats'),
        ]
    ]);
});