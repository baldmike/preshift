<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,manager,server,bartender',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:20',
            'availability' => 'nullable|array',
            'availability.*' => 'array',
            'availability.*.*' => 'string|in:10:30,16:30,open',
        ];
    }
}
