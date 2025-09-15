<?php

namespace OmarElnaghy\LaravelChatSoul\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'is_private',
        'created_by',
        'settings',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const TYPE_DIRECT = 'direct';
    const TYPE_GROUP = 'group';

    /**
     * Get the table name with prefix.
     */
    public function getTable(): string
    {
        return config('chat-soul.database.prefix') . 'conversations';
    }

    /**
     * Get conversation participants.
     */
    public function participants(): BelongsToMany
    {
        $userModel = config('chat-soul.auth.user_model');
        
        return $this->belongsToMany($userModel, $this->getPivotTableName())
            ->withPivot(['joined_at', 'left_at', 'role'])
            ->withTimestamps();
    }

    /**
     * Get active participants (not left).
     */
    public function activeParticipants(): BelongsToMany
    {
        return $this->participants()->wherePivotNull('left_at');
    }

    /**
     * Get all messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest message.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Get unread messages for a specific user.
     */
    public function unreadMessagesFor(int $userId): HasMany
    {
        return $this->messages()
            ->whereDoesntHave('reads', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('user_id', '!=', $userId);
    }

    /**
     * Get conversation creator.
     */
    public function creator(): BelongsTo
    {
        $userModel = config('chat-soul.auth.user_model');
        
        return $this->belongsTo($userModel, 'created_by');
    }

    /**
     * Scope: Filter by user participation.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId)->whereNull('left_at');
        });
    }

    /**
     * Scope: Filter by conversation type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter private conversations.
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_private', true);
    }

    /**
     * Scope: Filter public conversations.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_private', false);
    }

    /**
     * Get the pivot table name.
     */
    protected function getPivotTableName(): string
    {
        return config('chat-soul.database.prefix') . 'conversation_user';
    }

    /**
     * Check if user is participant.
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    /**
     * Add participant to conversation.
     */
    public function addParticipant(int $userId, string $role = 'member'): void
    {
        $this->participants()->syncWithoutDetaching([
            $userId => [
                'joined_at' => now(),
                'role' => $role,
            ]
        ]);
    }

    /**
     * Remove participant from conversation.
     */
    public function removeParticipant(int $userId): void
    {
        $this->participants()->updateExistingPivot($userId, [
            'left_at' => now(),
        ]);
    }

    /**
     * Get unread count for user.
     */
    public function getUnreadCountFor(int $userId): int
    {
        return $this->unreadMessagesFor($userId)->count();
    }

    /**
     * Get conversation name for display.
     */
    public function getDisplayNameFor(int $userId): string
    {
        if ($this->type === self::TYPE_GROUP) {
            return $this->name ?: 'Group Chat';
        }

        // For direct conversations, show the other participant's name
        $otherParticipant = $this->participants()
            ->where('user_id', '!=', $userId)
            ->first();

        return $otherParticipant ? $otherParticipant->name : 'Direct Chat';
    }
}