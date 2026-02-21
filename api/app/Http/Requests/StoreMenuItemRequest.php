<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
