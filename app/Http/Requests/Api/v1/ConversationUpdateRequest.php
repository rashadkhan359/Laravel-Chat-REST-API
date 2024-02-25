<?php

namespace App\Http\Requests\Api\v1;

use App\Enums\ConversationActionType;
use App\Enums\ConversationRoleType;
use App\Models\Conversation;
use App\Models\ConversationUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use BenSampo\Enum\Rules\IsEnum;

class ConversationUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'action' => ['required', 'string', 'in:' . implode(',', ConversationActionType::getValues())],
        ];

        // Add specific validation rules based on the requested action
        switch ($this->input('action')) {
            case ConversationActionType::UPDATE_NAME:
                $rules['name'] = 'required|string|max:255';
                break;
            case ConversationActionType::UPDATE_ICON:
                $rules['icon'] = 'required|image|mimes:jpeg,png,jpg,gif|max:15000';
                break;
            case ConversationActionType::ADD_USER:
                $rules['user_id'] = 'required|exists:users,id';
                // Ensure user is not already in the conversation
                $rules['user_id'][] = 'unique:conversation_users,user_id,conversation_id,' . Conversation::findOrFail($this->route('conversation'))->id;
                break;
            case ConversationActionType::REMOVE_USER:
                $rules['user_id'] = 'required|exists:users,id';
                // // Ensure user is not the owner or current user
                // $rules['user_id'][] = 'not_in:' . ConversationUser::where('conversation_id', $this->route('conversation'))->where('role', ConversationRoleType::ADMIN)->id . ',' . Auth::user()->id;
                break;
        }

        return $rules;
    }
}
