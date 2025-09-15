<?php

namespace OmarElnaghy\LaravelChatSoul\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use OmarElnaghy\LaravelChatSoul\Models\Message;
use OmarElnaghy\LaravelChatSoul\Http\Resources\UserResource;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Message $message,
        public $user
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channelPrefix = config('chat-soul.broadcasting.channel_prefix');
        
        return [
            new PrivateChannel("{$channelPrefix}-conversation.{$this->message->conversation_id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        $eventPrefix = config('chat-soul.broadcasting.event_prefix');
        return "{$eventPrefix}.MessageRead";
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'user' => new UserResource($this->user),
            'read_at' => now()->toISOString(),
        ];
    }
}