<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming PushItem model data.
 *
 * Serializes a PushItem instance for JSON API responses, including
 * the title, description, reason, priority, active status, and
 * conditionally loaded menu item, creator, and acknowledgment relationships.
 */
class PushItemResource extends JsonResource
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
