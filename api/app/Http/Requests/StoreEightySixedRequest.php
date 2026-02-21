<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for marking an item as 86'd (unavailable).
 *
 * Used by EightySixedController::store(). Validates the required item_name,
 * an optional menu_item_id reference to an existing menu item, and an
 * optional reason for the 86.
 */
class StoreEightySixedRequest extends FormRequest
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
            'item_name' => 'required|string|max:255',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'reason' => 'nullable|string|max:255',
        ];
    }
}
