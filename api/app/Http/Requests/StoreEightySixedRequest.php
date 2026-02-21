<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEightySixedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name' => 'required|string|max:255',
            'menu_item_id' => 'nullable|exists:menu_items,id',
            'reason' => 'nullable|string|max:255',
        ];
    }
}
