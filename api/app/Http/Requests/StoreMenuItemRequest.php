<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating or updating a menu item.
 *
 * Used by MenuItemController::store() and MenuItemController::update().
 * Validates required fields like name and type (food, drink, or both),
 * plus optional description, price, category_id, is_new, is_active,
 * and allergens array.
 */
class StoreMenuItemRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'type' => 'required|in:food,drink,both',
            'category_id' => 'nullable|exists:categories,id',
            'is_new' => 'boolean',
            'is_active' => 'boolean',
            'allergens' => 'nullable|array',
        ];
    }
}
