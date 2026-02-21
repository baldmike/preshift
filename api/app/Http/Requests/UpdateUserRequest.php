<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating an existing user.
 *
 * Used by UserController::update(). Validates required fields like name,
 * email (unique except for the current user), and role (admin, manager,
 * server, or bartender), plus optional password, location_id, phone,
 * and nested availability array with time-slot values.
 */
class UpdateUserRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email,' . $this->route('user')->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,manager,server,bartender',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:20',
            'availability' => 'nullable|array',
            'availability.*' => 'array',
            'availability.*.*' => 'string|in:10:30,16:30,open',
        ];
    }
}
