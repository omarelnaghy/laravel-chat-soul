<?php

namespace OmarElnaghy\LaravelChatSoul\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageRead extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $timestamps = true;

    /**
     * Get the table name with prefix.
     */
    public function getTable(): string
    {
        return config('chat-soul.database.prefix') . 'message_reads';
    }

    /**
     * Get the message that was read.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the user who read the message.
     */
    public function user(): BelongsTo
    {
        $userModel = config('chat-soul.auth.user_model');
        
        return $this->belongsTo($userModel);
    }
}