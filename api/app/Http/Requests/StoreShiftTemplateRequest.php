<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating or updating a reusable shift template.
 *
 * Used by ShiftTemplateController::store() and ShiftTemplateController::update().
 * Validates the required template name and start time in H:i format
 * (e.g., "10:30", "22:00").
 */
class StoreShiftTemplateRequest extends FormRequest
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
            'start_time' => 'required|date_format:H:i',
        ];
    }
}
