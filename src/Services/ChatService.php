<?php

namespace OmarElnaghy\LaravelChatSoul\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OmarElnaghy\LaravelChatSoul\Models\Conversation;
use OmarElnaghy\LaravelChatSoul\Models\Message;

class ChatService
{
    /**
     * Create a new conversation.
     */
    public function createConversation(int $userId, array $data): Conversation
    {
        $conversation = Conversation::create([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'is_private' => $data['is_private'] ?? true,
            'created_by' => $userId,
            'settings' => $data['settings'] ?? [],
        ]);

        // Add creator as participant
        $conversation->addParticipant($userId, 'admin');

        // Add other participants
        if (!empty($data['participant_ids'])) {
            foreach ($data['participant_ids'] as $participantId) {
                $conversation->addParticipant($participantId, 'member');
            }
        }

        return $conversation;
    }

    /**
     * Send a message.
     */
    public function sendMessage(array $data, ?UploadedFile $attachment = null): Message
    {
        // Handle file upload if present
        if ($attachment && config('chat-soul.features.file_uploads')) {
            $attachmentData = $this->handleFileUpload($attachment);
            $data = array_merge($data, $attachmentData);
        }

        // Create message
        $message = Message::create([
            'conversation_id' => $data['conversation_id'],
            'user_id' => $data['user_id'],
            'content' => $data['content'] ?? null,
            'type' => $data['type'] ?? Message::TYPE_TEXT,
            'reply_to_id' => $data['reply_to_id'] ?? null,
            'attachment_path' => $data['attachment_path'] ?? null,
            'attachment_name' => $data['attachment_name'] ?? null,
            'attachment_type' => $data['attachment_type'] ?? null,
            'attachment_size' => $data['attachment_size'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);

        // Update conversation timestamp
        $conversation = Conversation::find($data['conversation_id']);
        $conversation->touch();

        return $message;
    }

    /**
     * Handle file upload for message attachment.
     */
    protected function handleFileUpload(UploadedFile $file): array
    {
        $disk = config('chat-soul.uploads.disk');
        $path = config('chat-soul.uploads.path');
        
        // Generate unique filename
        $filename = time() . '_' . $file->hashName();
        $filePath = $path . '/' . $filename;
        
        // Store file
        $storedPath = $file->storeAs($path, $filename, $disk);
        
        return [
            'attachment_path' => $storedPath,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_type' => $file->getMimeType(),
            'attachment_size' => $file->getSize(),
        ];
    }

    /**
     * Delete message attachment.
     */
    public function deleteAttachment(Message $message): bool
    {
        if (!$message->hasAttachment()) {
            return true;
        }

        $disk = config('chat-soul.uploads.disk');
        
        return Storage::disk($disk)->delete($message->attachment_path);
    }

    /**
     * Get conversation statistics.
     */
    public function getConversationStats(int $conversationId): array
    {
        $conversation = Conversation::with(['messages', 'participants'])->find($conversationId);
        
        if (!$conversation) {
            return [];
        }

        return [
            'total_messages' => $conversation->messages()->count(),
            'total_participants' => $conversation->participants()->count(),
            'messages_today' => $conversation->messages()
                ->whereDate('created_at', today())
                ->count(),
            'most_active_user' => $this->getMostActiveUser($conversationId),
            'file_count' => $conversation->messages()
                ->whereNotNull('attachment_path')
                ->count(),
        ];
    }

    /**
     * Get most active user in conversation.
     */
    protected function getMostActiveUser(int $conversationId): ?array
    {
        $userModel = config('chat-soul.auth.user_model');
        
        $user = Message::inConversation($conversationId)
            ->select('user_id')
            ->selectRaw('COUNT(*) as message_count')
            ->groupBy('user_id')
            ->orderBy('message_count', 'desc')
            ->first();

        if (!$user) {
            return null;
        }

        $userDetails = $userModel::find($user->user_id);
        
        return [
            'user' => $userDetails ? $userDetails->only(['id', 'name']) : null,
            'message_count' => $user->message_count,
        ];
    }

    /**
     * Search messages across conversations for a user.
     */
    public function searchMessages(int $userId, string $query, int $limit = 50): array
    {
        if (!config('chat-soul.features.message_search')) {
            return [];
        }

        $messages = Message::whereHas('conversation', function ($q) use ($userId) {
            $q->forUser($userId);
        })
        ->search($query)
        ->with(['user', 'conversation'])
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();

        return $messages->groupBy('conversation_id')->map(function ($conversationMessages) {
            return [
                'conversation' => $conversationMessages->first()->conversation,
                'messages' => $conversationMessages,
                'match_count' => $conversationMessages->count(),
            ];
        })->values()->toArray();
    }
}