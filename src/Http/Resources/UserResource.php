<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($this->shouldShowEmail($request), $this->email),
            'avatar' => $this->avatar ?? $this->getGravatarUrl(),
            
            // Pivot data for conversation participants
            'role' => $this->when($this->pivot, $this->pivot?->role),
            'joined_at' => $this->when($this->pivot, $this->pivot?->joined_at),
            'left_at' => $this->when($this->pivot, $this->pivot?->left_at),
            
            // Presence data (if enabled)
            'is_online' => $this->when(
                config('chat-soul.features.presence'),
                $this->getPresenceStatus()
            ),
            'last_seen' => $this->when(
                config('chat-soul.features.presence'),
                $this->getLastSeenTime()
            ),
        ];
    }

    /**
     * Determine if email should be shown.
     */
    protected function shouldShowEmail(Request $request): bool
    {
        $user = $request->user();
        
        // Show email to self
        if ($user && $user->id === $this->id) {
            return true;
        }
        
        // Show email to users in same conversation (implement your logic)
        return false;
    }

    /**
     * Get Gravatar URL for user avatar.
     */
    protected function getGravatarUrl(): string
    {
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=150";
    }

    /**
     * Get user presence status.
     */
    protected function getPresenceStatus(): bool
    {
        if (!config('chat-soul.features.presence')) {
            return false;
        }
        
        return app(\OmarElnaghy\LaravelChatSoul\Services\PresenceService::class)
            ->isUserOnline($this->id);
    }

    /**
     * Get user last seen time.
     */
    protected function getLastSeenTime(): ?string
    {
        if (!config('chat-soul.features.presence')) {
            return null;
        }
        
        return app(\OmarElnaghy\LaravelChatSoul\Services\PresenceService::class)
            ->getLastSeen($this->id);
    }
}