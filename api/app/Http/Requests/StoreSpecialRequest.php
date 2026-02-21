<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
