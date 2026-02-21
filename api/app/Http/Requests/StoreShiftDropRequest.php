<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftDropRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_entry_id' => 'required|exists:schedule_entries,id',
            'reason' => 'nullable|string|max:255',
        ];
    }
}
