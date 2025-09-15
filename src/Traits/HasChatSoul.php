<?php

namespace OmarElnaghy\LaravelChatSoul\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OmarElnaghy\LaravelChatSoul\Models\Conversation;
use OmarElnaghy\LaravelChatSoul\Models\Message;

trait HasChatSoul
{
    /**
     * Get user's conversations.
     */
    public function conversations(): BelongsToMany
    {
        $pivotTable = config('chat-soul.database.prefix') . 'conversation_user';
        
        return $this->belongsToMany(Conversation::class, $pivotTable)
            ->withPivot(['joined_at', 'left_at', 'role'])
            ->withTimestamps()
            ->wherePivotNull('left_at');
    }

    /**
     * Get user's messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get direct conversation with another user.
     */
    public function getDirectConversationWith(int $userId): ?Conversation
    {
        return $this->conversations()
            ->ofType(Conversation::TYPE_DIRECT)
            ->whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->first();
    }

    /**
     * Create or get direct conversation with another user.
     */
    public function getOrCreateDirectConversationWith(int $userId): Conversation
    {
        $conversation = $this->getDirectConversationWith($userId);
        
        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => Conversation::TYPE_DIRECT,
                'is_private' => true,
                'created_by' => $this->id,
            ]);
            
            $conversation->addParticipant($this->id, 'member');
            $conversation->addParticipant($userId, 'member');
        }
        
        return $conversation;
    }

    /**
     * Join a conversation.
     */
    public function joinConversation(int $conversationId, string $role = 'member'): bool
    {
        $conversation = Conversation::find($conversationId);
        
        if (!$conversation) {
            return false;
        }
        
        $conversation->addParticipant($this->id, $role);
        
        return true;
    }

    /**
     * Leave a conversation.
     */
    public function leaveConversation(int $conversationId): bool
    {
        $conversation = Conversation::find($conversationId);
        
        if (!$conversation) {
            return false;
        }
        
        $conversation->removeParticipant($this->id);
        
        return true;
    }

    /**
     * Get unread message count.
     */
    public function getUnreadMessageCount(): int
    {
        return Message::whereHas('conversation', function ($query) {
            $query->forUser($this->id);
        })
        ->unreadBy($this->id)
        ->count();
    }

    /**
     * Get unread messages.
     */
    public function getUnreadMessages()
    {
        return Message::whereHas('conversation', function ($query) {
            $query->forUser($this->id);
        })
        ->unreadBy($this->id)
        ->with(['user', 'conversation'])
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Mark all messages as read in a conversation.
     */
    public function markAllAsReadInConversation(int $conversationId): void
    {
        $messages = Message::inConversation($conversationId)
            ->unreadBy($this->id)
            ->get();
            
        foreach ($messages as $message) {
            $message->markAsReadBy($this->id);
        }
    }
}