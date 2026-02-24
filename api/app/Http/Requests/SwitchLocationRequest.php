<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for switching the authenticated user's active location.
 *
 * Used by SwitchLocationController::switch(). Validates that the
 * requested location_id exists in the database. Authorization is
 * handled in the controller (checking the pivot table).
 */
class SwitchLocationRequest extends FormRequest
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
            'location_id' => 'required|integer|exists:locations,id',
        ];
    }
}
