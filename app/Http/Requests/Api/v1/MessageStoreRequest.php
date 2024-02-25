<?php

namespace App\Http\Requests\Api\v1;

use Illuminate\Foundation\Http\FormRequest;

class MessageStoreRequest extends FormRequest
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
        return [
            'message' => 'nullable|string|max:5000', // Adjust max length as needed
            'file' => 'nullable|file', // Adjust allowed mime types
        ];
    }

    public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $data = $this->validated();

        // Check if both fields are null, add error if true
        if (empty($data['message']) && empty($data['file'])) {
            $validator->errors()->add('message', 'At least one of content or file must be provided.');
            $validator->errors()->add('file', 'At least one of content or file must be provided.');
        }
    });
}
}
