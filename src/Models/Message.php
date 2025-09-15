<?php

namespace OmarElnaghy\LaravelChatSoul\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'attachment_path',
        'attachment_name',
        'attachment_type',
        'attachment_size',
        'metadata',
        'reply_to_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'attachment_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    const TYPE_TEXT = 'text';
    const TYPE_FILE = 'file';
    const TYPE_IMAGE = 'image';
    const TYPE_SYSTEM = 'system';

    /**
     * Get the table name with prefix.
     */
    public function getTable(): string
    {
        return config('chat-soul.database.prefix') . 'messages';
    }

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user(): BelongsTo
    {
        $userModel = config('chat-soul.auth.user_model');
        
        return $this->belongsTo($userModel);
    }

    /**
     * Get message read receipts.
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    /**
     * Get the message this is replying to.
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    /**
     * Get replies to this message.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'reply_to_id');
    }

    /**
     * Scope: Filter by conversation.
     */
    public function scopeInConversation(Builder $query, int $conversationId): Builder
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * Scope: Filter by message type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeFromUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Search messages.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('content', 'like', "%{$search}%");
    }

    /**
     * Scope: Unread by user.
     */
    public function scopeUnreadBy(Builder $query, int $userId): Builder
    {
        return $query->whereDoesntHave('reads', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->where('user_id', '!=', $userId);
    }

    /**
     * Mark message as read by user.
     */
    public function markAsReadBy(int $userId): MessageRead
    {
        return $this->reads()->firstOrCreate([
            'user_id' => $userId,
        ], [
            'read_at' => now(),
        ]);
    }

    /**
     * Check if message is read by user.
     */
    public function isReadBy(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }

    /**
     * Get attachment URL if exists.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }

        return \Storage::disk(config('chat-soul.uploads.disk'))->url($this->attachment_path);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSizeAttribute(): ?string
    {
        if (!$this->attachment_size) {
            return null;
        }

        $bytes = $this->attachment_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get read count for this message.
     */
    public function getReadCountAttribute(): int
    {
        return $this->reads()->count();
    }

    /**
     * Get users who have read this message.
     */
    public function getReadByUsersAttribute()
    {
        $userModel = config('chat-soul.auth.user_model');
        
        return $userModel::whereIn('id', $this->reads()->pluck('user_id'))->get();
    }

    /**
     * Check if message has attachments.
     */
    public function hasAttachment(): bool
    {
        return !empty($this->attachment_path);
    }

    /**
     * Check if message is a reply.
     */
    public function isReply(): bool
    {
        return !empty($this->reply_to_id);
    }

    /**
     * Check if message is system message.
     */
    public function isSystemMessage(): bool
    {
        return $this->type === self::TYPE_SYSTEM;
    }
}