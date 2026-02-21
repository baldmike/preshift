<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming Special model data.
 *
 * Serializes a Special instance for JSON API responses, including
 * the special's title, description, type, date range, quantity,
 * active status, and conditionally loaded menu item, creator, and
 * acknowledgment relationships.
 */
class SpecialResource extends JsonResource
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
