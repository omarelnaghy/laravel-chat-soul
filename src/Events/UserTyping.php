<?php

namespace OmarElnaghy\LaravelChatSoul\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use OmarElnaghy\LaravelChatSoul\Http\Resources\UserResource;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public $user,
        public int $conversationId,
        public bool $isTyping
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channelPrefix = config('chat-soul.broadcasting.channel_prefix');
        
        return [
            new PrivateChannel("{$channelPrefix}-conversation.{$this->conversationId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        $eventPrefix = config('chat-soul.broadcasting.event_prefix');
        return "{$eventPrefix}.UserTyping";
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user' => new UserResource($this->user),
            'conversation_id' => $this->conversationId,
            'is_typing' => $this->isTyping,
            'timestamp' => now()->toISOString(),
        ];
    }
}