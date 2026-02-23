<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating an existing board message.
 *
 * Validates the updated body text and optional visibility and pinned fields.
 * Authorization is handled by the BoardMessagePolicy, not by this form request.
 */
class UpdateBoardMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => 'required|string|max:2000',
            'visibility' => 'sometimes|in:all,managers',
            'pinned' => 'sometimes|boolean',
        ];
    }
}
