<?php

namespace OmarElnaghy\LaravelChatSoul\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OmarElnaghy\LaravelChatSoul\Models\Conversation;

class CreateConversationRequest extends FormRequest
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
        $rules = [
            'type' => 'required|in:' . Conversation::TYPE_DIRECT . ',' . Conversation::TYPE_GROUP,
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_private' => 'sometimes|boolean',
            'settings' => 'sometimes|array',
        ];

        // For direct conversations, require exactly one participant
        if ($this->input('type') === Conversation::TYPE_DIRECT) {
            $rules['participant_ids'] = 'required|array|size:1';
            $rules['participant_ids.*'] = 'required|integer|exists:users,id|not_in:' . $this->user()->id;
        }

        // For group conversations
        if ($this->input('type') === Conversation::TYPE_GROUP) {
            if (!config('chat-soul.features.group_chats')) {
                abort(403, 'Group chats are disabled');
            }
            
            $rules['name'] = 'required|string|max:255';
            $rules['participant_ids'] = 'required|array|min:1|max:50';
            $rules['participant_ids.*'] = 'required|integer|exists:users,id|not_in:' . $this->user()->id;
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'participant_ids.required' => 'At least one participant is required.',
            'participant_ids.*.not_in' => 'You cannot add yourself as a participant.',
            'name.required_if' => 'Group conversations must have a name.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // For direct conversations, check if conversation already exists
            if ($this->input('type') === Conversation::TYPE_DIRECT) {
                $participantId = $this->input('participant_ids.0');
                $existingConversation = $this->user()->getDirectConversationWith($participantId);
                
                if ($existingConversation) {
                    $validator->errors()->add('participant_ids', 'Direct conversation with this user already exists.');
                }
            }
        });
    }
}