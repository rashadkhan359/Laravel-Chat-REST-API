<?php

namespace App\Http\Requests\Api\v1;

use App\Enums\ConversationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConversationStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check(); // Only authenticated users can create conversations
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules =  [
            'type' => 'required|in:' . implode(',', ConversationType::getValues()),
            'name' => 'nullable|string|max:255', // Optional name for group conversations
            'user_ids' => ['required', 'array', 'min:1', 'distinct', // Ensure unique IDs
                function ($attribute, $value, $fail) {
                    // Ensure at least one user ID belongs to the authenticated user
                    if (!in_array(auth()->id(), $value)) {
                        $fail('The conversation must include the authenticated user.');
                    }
                },
                // Ensure participants exist in the database
                function ($attribute, $value, $fail) {
                    $existingUserIds = DB::table('users')->pluck('id')->toArray();
                    foreach ($value as $userId) {
                        if (!in_array($userId, $existingUserIds)) {
                            $fail('Invalid participant ID. User does not exist.');
                        }
                    }
                },
            ],
            'message' => 'nullable|string|max:2000', // Optional initial message
            'icon' => 'nullable|mimes:png,jpg,webp,gif,jpeg|max:15000',
            'file' => 'nullable|mimes:png,jpg,webp,gif,jpeg|max:15000',
        ];

        // Add conditional validation for the 'name' field based on the 'type' field
        if ($this->input('type') === ConversationType::GROUP) {
            // If the type is GROUP, make the 'name' field required
            $rules['name'] = 'required|string|max:255';
        }

        return $rules;
    }

    /**
     * Get the custom validation messages for the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'type.required' => 'Conversation type is required.',
            'type.in' => 'Invalid conversation type. Must be "direct" or "group".',
            'name.max' => 'Conversation name cannot be longer than 255 characters.',
            'user_ids.required' => 'At least one participant is required.',
            'user_ids.array' => 'Participants must be provided in an array.',
            'user_ids.min' => 'You must specify at least one participant.',
            'user_ids.distinct' => 'Participant IDs must be unique.',
            'user_ids.*.exists' => 'Invalid participant ID. User does not exist.',
            'message.max' => 'Message content cannot exceed 2000 characters.',
        ];
    }
}
