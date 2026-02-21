<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'required|in:normal,important,urgent',
            'target_roles' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ];
    }
}
