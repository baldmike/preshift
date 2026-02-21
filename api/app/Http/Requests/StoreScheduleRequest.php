<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating or updating a weekly schedule.
 *
 * Used by ScheduleController::store() and ScheduleController::update().
 * Validates the required week_start date that defines the beginning of
 * the schedule week.
 */
class StoreScheduleRequest extends FormRequest
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
            'week_start' => 'required|date',
        ];
    }
}
