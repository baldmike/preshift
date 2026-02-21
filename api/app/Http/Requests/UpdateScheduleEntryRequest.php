<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating an existing schedule entry.
 *
 * Used by ScheduleEntryController::update(). Validates required references
 * to user_id and shift_template_id (both must exist), a date, the assigned
 * role (server or bartender), and optional notes.
 */
class UpdateScheduleEntryRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date' => 'required|date',
            'role' => 'required|in:server,bartender',
            'notes' => 'nullable|string|max:255',
        ];
    }
}
