<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating a new board message (top-level post or reply).
 *
 * Validates the message body, optional parent_id for replies, and optional
 * visibility setting. Authorization is handled by middleware and policies,
 * not by this form request.
 */
class StoreBoardMessageRequest extends FormRequest
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
            'parent_id' => 'nullable|exists:board_messages,id',
            'visibility' => 'sometimes|in:all,managers',
        ];
    }
}
