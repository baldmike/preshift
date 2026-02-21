<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for recording a polymorphic acknowledgment.
 *
 * Used by AcknowledgmentController::store(). Validates the acknowledgable
 * type (eighty_sixed, special, push_item, or announcement) and its
 * corresponding integer ID.
 */
class StoreAcknowledgmentRequest extends FormRequest
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
            'type' => 'required|in:eighty_sixed,special,push_item,announcement',
            'id' => 'required|integer',
        ];
    }
}
