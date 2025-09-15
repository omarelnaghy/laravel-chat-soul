<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TypingEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return config('chat-soul.features.typing_indicators', false);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'conversation_id' => 'required|integer|exists:' . (config('chat-soul.database.prefix') . 'conversations') . ',id',
            'is_typing' => 'required|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'conversation_id.required' => 'Conversation ID is required.',
            'conversation_id.exists' => 'The specified conversation does not exist.',
            'is_typing.required' => 'Typing status is required.',
            'is_typing.boolean' => 'Typing status must be true or false.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verify user is participant of the conversation
            $conversationId = $this->input('conversation_id');
            $user = $this->user();
            
            $conversation = \OmarElnaghy\LaravelChatSoul\Models\Conversation::find($conversationId);
            
            if ($conversation && !$conversation->hasParticipant($user->id)) {
                $validator->errors()->add('conversation_id', 'You are not a participant in this conversation.');
            }
        });
    }
}