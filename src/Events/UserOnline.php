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

class UserOnline implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public $user
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $presenceChannel = config('chat-soul.broadcasting.presence_channel');
        
        return [
            new PresenceChannel($presenceChannel),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        $eventPrefix = config('chat-soul.broadcasting.event_prefix');
        return "{$eventPrefix}.UserOnline";
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user' => new UserResource($this->user),
            'timestamp' => now()->toISOString(),
        ];
    }
}