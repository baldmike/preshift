<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for submitting a time-off request.
 *
 * Used by TimeOffRequestController::store(). Validates required start_date
 * and end_date (both must be today or later, and end_date must be on or
 * after start_date), plus an optional reason. Enforces a configurable
 * minimum advance notice period via the time_off_advance_days setting.
 */
class StoreTimeOffRequestRequest extends FormRequest
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
        $advanceDays = (int) Setting::get('time_off_advance_days', 14);
        $minDate = now()->addDays($advanceDays)->toDateString();

        return [
            'start_date' => "required|date|after_or_equal:{$minDate}",
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $advanceDays = (int) Setting::get('time_off_advance_days', 14);

        return [
            'start_date.after_or_equal' => "Time off must be requested at least {$advanceDays} days in advance.",
        ];
    }
}
