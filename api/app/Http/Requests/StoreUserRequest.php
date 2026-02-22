<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating a new user.
 *
 * Used by UserController::store(). Validates required fields like name,
 * unique email, password (min 8 chars), and role (admin, manager, server,
 * or bartender), plus optional location_id, phone, roles (JSON array of
 * additional roles for multi-role staff), and nested availability array
 * with time-slot values.
 */
class StoreUserRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,manager,server,bartender',
            'location_id' => 'nullable|exists:locations,id',
            'phone' => 'nullable|string|max:20',
            'roles' => 'nullable|array',
            'roles.*' => 'string|in:admin,manager,server,bartender',
            'availability' => 'nullable|array',
            'availability.*' => 'array',
            'availability.*.*' => 'string|in:10:30,16:30,open',
        ];
    }
}
