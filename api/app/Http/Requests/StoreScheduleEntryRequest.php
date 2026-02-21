<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
