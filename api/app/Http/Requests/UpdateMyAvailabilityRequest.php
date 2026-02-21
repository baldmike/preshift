<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating the authenticated user's own availability.
 *
 * Used by UserController::updateMyAvailability(). Validates the required
 * availability array, where each day contains an array of time-slot
 * strings (10:30, 16:30, or open).
 */
class UpdateMyAvailabilityRequest extends FormRequest
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
            'availability' => 'required|array',
            'availability.*' => 'array',
            'availability.*.*' => 'string|in:10:30,16:30,open',
        ];
    }
}
