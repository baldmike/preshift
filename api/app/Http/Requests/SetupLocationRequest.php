<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for the initial establishment setup flow.
 *
 * Used by SetupController::store(). Validates that a new admin provides
 * the required fields to create their first establishment: name, city,
 * and state.
 */
class SetupLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'name'  => 'required|string|max:255',
            'city'  => 'required|string|max:255',
            'state' => 'required|string|max:255',
        ];
    }
}
