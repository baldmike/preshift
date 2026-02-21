<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming User model data.
 *
 * Serializes a User instance for JSON API responses, including
 * the user's name, email, role, phone, availability, and conditionally
 * loaded location relationship. Sensitive fields such as password and
 * remember_token are excluded by the model's $hidden array.
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
        return parent::toArray($request);
    }
}
