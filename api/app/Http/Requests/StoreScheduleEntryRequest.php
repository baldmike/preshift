<?php

namespace App\Http\Requests;

use App\Models\TimeOffRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('schedule_entries')->where(fn ($q) =>
                    $q->whereDate('date', $this->date)
                ),
                function ($attribute, $value, $fail) {
                    if (!$this->date || !$value) {
                        return;
                    }

                    $hasTimeOff = TimeOffRequest::where('user_id', $value)
                        ->where('status', 'approved')
                        ->whereDate('start_date', '<=', $this->date)
                        ->whereDate('end_date', '>=', $this->date)
                        ->exists();

                    if ($hasTimeOff) {
                        $fail('This user has approved time off on this date.');
                    }
                },
            ],
            'shift_template_id' => 'required|exists:shift_templates,id',
            'date' => 'required|date',
            'role' => 'required|in:server,bartender',
            'notes' => 'nullable|string|max:255',
        ];
    }

    /**
     * Custom validation messages.
     *
     * Provides a human-readable error when a manager tries to schedule the
     * same user for a second shift on the same day.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'This user is already scheduled on this date.',
        ];
    }
}
