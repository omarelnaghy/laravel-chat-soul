<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        
        return [
            'id' => $this->id,
            'name' => $this->getDisplayNameFor($user->id),
            'description' => $this->description,
            'type' => $this->type,
            'is_private' => $this->is_private,
            'created_by' => $this->created_by,
            'settings' => $this->settings,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Counts
            'messages_count' => $this->when($this->relationLoaded('messages'), $this->messages_count),
            'participants_count' => $this->when($this->relationLoaded('participants'), $this->participants_count),
            'unread_count' => $this->getUnreadCountFor($user->id),
            
            // Related data
            'participants' => UserResource::collection($this->whenLoaded('participants')),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            
            // User-specific data
            'user_role' => $this->getUserRole($user->id),
            'joined_at' => $this->getUserJoinedAt($user->id),
            'is_creator' => $this->created_by === $user->id,
        ];
    }

    /**
     * Get user's role in conversation.
     */
    protected function getUserRole(int $userId): ?string
    {
        if ($this->relationLoaded('participants')) {
            $participant = $this->participants->where('id', $userId)->first();
            return $participant?->pivot?->role;
        }
        
        return null;
    }

    /**
     * Get when user joined conversation.
     */
    protected function getUserJoinedAt(int $userId): ?string
    {
        if ($this->relationLoaded('participants')) {
            $participant = $this->participants->where('id', $userId)->first();
            return $participant?->pivot?->joined_at?->toISOString();
        }
        
        return null;
    }
}