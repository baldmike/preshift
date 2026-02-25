<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for uploading a profile photo.
 *
 * Validates that the uploaded file is a supported image type (JPG, PNG, WebP)
 * and does not exceed 5 MB. Used by AuthController::uploadProfilePhoto().
 */
class UploadProfilePhotoRequest extends FormRequest
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
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
