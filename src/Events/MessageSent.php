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
use OmarElnaghy\LaravelChatSoul\Http\Resources\MessageResource;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Message $message
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
        return "{$eventPrefix}.MessageSent";
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => new MessageResource($this->message->load(['user', 'reads.user', 'replyTo.user'])),
            'conversation_id' => $this->message->conversation_id,
            'timestamp' => now()->toISOString(),
        ];
    }
}