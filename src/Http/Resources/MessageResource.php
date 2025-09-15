<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'content' => $this->content,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // User data
            'user' => new UserResource($this->whenLoaded('user')),
            
            // Attachment data
            'attachment' => $this->when($this->hasAttachment(), [
                'name' => $this->attachment_name,
                'path' => $this->attachment_path,
                'url' => $this->attachment_url,
                'type' => $this->attachment_type,
                'size' => $this->attachment_size,
                'formatted_size' => $this->formatted_size,
            ]),
            
            // Reply data
            'reply_to' => new self($this->whenLoaded('replyTo')),
            'is_reply' => $this->isReply(),
            
            // Read receipts
            'read_count' => $this->when(config('chat-soul.features.read_receipts'), $this->read_count),
            'read_by' => $this->when(
                config('chat-soul.features.read_receipts') && $this->relationLoaded('reads'),
                UserResource::collection($this->read_by_users)
            ),
            
            // Status flags
            'is_edited' => !empty($this->metadata['edited_at']),
            'edited_at' => $this->metadata['edited_at'] ?? null,
            'is_system_message' => $this->isSystemMessage(),
            'can_edit' => $this->canEdit($request->user()),
            'can_delete' => $this->canDelete($request->user()),
        ];
    }

    /**
     * Check if user can edit this message.
     */
    protected function canEdit($user): bool
    {
        if (!$user || $this->user_id !== $user->id) {
            return false;
        }
        
        if ($this->type !== \OmarElnaghy\LaravelChatSoul\Models\Message::TYPE_TEXT) {
            return false;
        }
        
        // Allow editing within 5 minutes
        return $this->created_at->diffInMinutes(now()) <= 5;
    }

    /**
     * Check if user can delete this message.
     */
    protected function canDelete($user): bool
    {
        if (!$user) {
            return false;
        }
        
        // Users can delete their own messages
        if ($this->user_id === $user->id) {
            return true;
        }
        
        // Conversation admins/creators can delete any message
        if ($this->relationLoaded('conversation')) {
            $conversation = $this->conversation;
            if ($conversation->created_by === $user->id) {
                return true;
            }
            
            $userPivot = $conversation->participants()->where('user_id', $user->id)->first();
            if ($userPivot?->pivot?->role === 'admin') {
                return true;
            }
        }
        
        return false;
    }
}