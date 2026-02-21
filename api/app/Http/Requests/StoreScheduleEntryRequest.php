<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating a new schedule entry.
 *
 * Used by ScheduleEntryController::store(). Validates required references
 * to schedule_id, user_id, and shift_template_id (all must exist), a date,
 * the assigned role (server or bartender), and optional notes.
 */
class StoreScheduleEntryRequest extends FormRequest
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
            'schedule_id' => 'required|exists:schedules,id',
            'user_id' => 'required|exists:users,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date' => 'required|date',
            'role' => 'required|in:server,bartender',
            'notes' => 'nullable|string|max:255',
        ];
    }
}
