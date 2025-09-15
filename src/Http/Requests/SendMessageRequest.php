<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OmarElnaghy\LaravelChatSoul\Models\Message;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxLength = config('chat-soul.messages.max_length');
        $allowEmpty = config('chat-soul.messages.allow_empty');
        $uploadsEnabled = config('chat-soul.features.file_uploads');
        $maxFileSize = config('chat-soul.uploads.max_file_size');
        $allowedTypes = implode(',', config('chat-soul.uploads.allowed_types'));

        $rules = [
            'type' => 'sometimes|in:' . implode(',', [Message::TYPE_TEXT, Message::TYPE_FILE, Message::TYPE_IMAGE, Message::TYPE_SYSTEM]),
            'reply_to_id' => 'sometimes|integer|exists:' . (config('chat-soul.database.prefix') . 'messages') . ',id',
            'metadata' => 'sometimes|array',
        ];

        // Content validation
        if ($allowEmpty) {
            $rules['content'] = "nullable|string|max:{$maxLength}";
        } else {
            $rules['content'] = "required_without:attachment|string|max:{$maxLength}";
        }

        // Attachment validation
        if ($uploadsEnabled) {
            $rules['attachment'] = "nullable|file|max:{$maxFileSize}|mimes:{$allowedTypes}";
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required_without' => 'Message content is required when no attachment is provided.',
            'attachment.max' => 'File size cannot exceed ' . (config('chat-soul.uploads.max_file_size') / 1024) . 'MB.',
            'attachment.mimes' => 'File type not allowed. Allowed types: ' . implode(', ', config('chat-soul.uploads.allowed_types')),
            'reply_to_id.exists' => 'The message you are replying to does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure content or attachment is provided
            if (!$this->input('content') && !$this->hasFile('attachment')) {
                if (!config('chat-soul.messages.allow_empty')) {
                    $validator->errors()->add('content', 'Either message content or attachment is required.');
                }
            }

            // Validate reply_to_id belongs to the same conversation
            if ($this->input('reply_to_id')) {
                $conversationId = $this->route('conversationId');
                $replyMessage = Message::find($this->input('reply_to_id'));
                
                if ($replyMessage && $replyMessage->conversation_id != $conversationId) {
                    $validator->errors()->add('reply_to_id', 'You can only reply to messages in the same conversation.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default message type based on content
        if (!$this->has('type')) {
            if ($this->hasFile('attachment')) {
                $file = $this->file('attachment');
                $mimeType = $file->getMimeType();
                
                if (str_starts_with($mimeType, 'image/')) {
                    $this->merge(['type' => Message::TYPE_IMAGE]);
                } else {
                    $this->merge(['type' => Message::TYPE_FILE]);
                }
            } else {
                $this->merge(['type' => Message::TYPE_TEXT]);
            }
        }
    }
}