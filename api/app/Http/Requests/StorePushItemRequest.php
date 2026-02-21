<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating or updating a push item.
 *
 * Used by PushItemController::store() and PushItemController::update().
 * Validates required fields like title and priority (low, medium, high),
 * plus optional description, reason, menu_item_id, and is_active flag.
 */
class StorePushItemRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'reason' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
        ];
    }
}
