<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * API resource for transforming User model data.
 *
 * Serializes a User instance for JSON API responses, including
 * the user's name, email, role, phone, availability, and conditionally
 * loaded location relationship. Adds a computed `profile_photo_url`
 * field that resolves the stored path to a full public URL.
 * Sensitive fields such as password and remember_token are excluded
 * by the model's $hidden array.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['profile_photo_url'] = $this->profile_photo_path
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null;

        return $data;
    }
}
