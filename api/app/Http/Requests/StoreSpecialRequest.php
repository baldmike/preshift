<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating or updating a menu special.
 *
 * Used by SpecialController::store() and SpecialController::update().
 * Validates required fields like title, type (daily, weekly, monthly,
 * limited_time), and starts_at date, plus optional description, ends_at,
 * menu_item_id, is_active flag, and quantity.
 */
class StoreSpecialRequest extends FormRequest
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
            'type' => 'required|in:daily,weekly,monthly,limited_time',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
            'quantity' => 'nullable|integer|min:0',
        ];
    }
}
