<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMyAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'availability' => 'required|array',
            'availability.*' => 'array',
            'availability.*.*' => 'string|in:10:30,16:30,open',
        ];
    }
}
