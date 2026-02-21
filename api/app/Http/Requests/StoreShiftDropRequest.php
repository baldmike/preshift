<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for requesting a shift drop.
 *
 * Used by ShiftDropController::store(). Validates the required
 * schedule_entry_id reference (must exist) and an optional reason
 * explaining why the staff member needs to drop the shift.
 */
class StoreShiftDropRequest extends FormRequest
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
            'schedule_entry_id' => 'required|exists:schedule_entries,id',
            'reason' => 'nullable|string|max:255',
        ];
    }
}
