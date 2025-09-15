<?php

use Illuminate\Support\Facades\Broadcast;
use OmarElnaghy\LaravelChatSoul\Models\Conversation;

/*
|--------------------------------------------------------------------------
| Chat Soul Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| chat application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Conversation channels - Private channels for conversation participants
Broadcast::channel('chat-soul-conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is a participant in the conversation
    $conversation = Conversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    return $conversation->hasParticipant($user->id);
});

// User private channel - For direct user notifications
Broadcast::channel('chat-soul-user.{userId}', function ($user, $userId) {
    // Users can only listen to their own private channel
    return (int) $user->id === (int) $userId;
});

// Presence channel - For online user tracking
Broadcast::channel('chat-presence', function ($user) {
    if (!config('chat-soul.features.presence')) {
        return false;
    }
    
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar ?? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=identicon&s=150',
        'email' => $user->email, // Optional: remove if you don't want to expose emails
        'status' => 'online',
        'joined_at' => now()->toISOString(),
    ];
});

// Typing channel - For typing indicators (alternative to conversation channel)
Broadcast::channel('chat-soul-typing.{conversationId}', function ($user, $conversationId) {
    if (!config('chat-soul.features.typing_indicators')) {
        return false;
    }
    
    $conversation = Conversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    return $conversation->hasParticipant($user->id);
});

// Admin channel - For administrative notifications (optional)
Broadcast::channel('chat-soul-admin', function ($user) {
    // Define your admin check logic here
    // Example: return $user->hasRole('admin');
    return false; // Disabled by default
});

// Public announcements channel (optional)
Broadcast::channel('chat-soul-announcements', function ($user) {
    // All authenticated users can listen to announcements
    return true;
});