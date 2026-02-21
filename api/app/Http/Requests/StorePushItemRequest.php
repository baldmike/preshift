<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePushItemRequest extends FormRequest
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
            'reason' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'is_active' => 'boolean',
        ];
    }
}
