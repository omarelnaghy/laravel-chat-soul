<?php

namespace OmarElnaghy\LaravelChatSoul\Broadcasting;

use OmarElnaghy\LaravelChatSoul\Models\Conversation;

/**
 * @deprecated This class is deprecated. Use the channels.php file instead.
 */
class ChatChannel
{
    /**
     * Authenticate the user's access to the channel.
     * 
     * @deprecated Use broadcasting channels defined in channels.php
     */
    public function join($user, $conversationId = null)
    {
        // For presence channel
        if (!$conversationId) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar ?? $this->getGravatarUrl($user->email),
            ];
        }

        // For conversation channels
        $conversation = Conversation::find($conversationId);
        
        if (!$conversation) {
            return false;
        }

        // Check if user is participant
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Get Gravatar URL.
     */
    protected function getGravatarUrl(string $email): string
    {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
    }
}