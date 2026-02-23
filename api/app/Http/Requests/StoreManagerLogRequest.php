<?php

namespace App\Http\Requests;

use App\Models\ManagerLog;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating a new manager log entry.
 *
 * Validates the log_date (must be unique per location) and body text.
 * Snapshots are auto-populated by the controller and are not user-provided.
 */
class StoreManagerLogRequest extends FormRequest
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
            'log_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $exists = ManagerLog::where('location_id', $this->user()->location_id)
                        ->whereDate('log_date', $value)
                        ->exists();
                    if ($exists) {
                        $fail('A log entry already exists for this date.');
                    }
                },
            ],
            'body' => 'required|string',
        ];
    }
}
