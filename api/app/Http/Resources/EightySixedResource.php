<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming EightySixed model data.
 *
 * Serializes an EightySixed (86'd item) instance for JSON API responses,
 * including the item name, reason, restoration status, and conditionally
 * loaded menu item, reporting user, and acknowledgment relationships.
 */
class EightySixedResource extends JsonResource
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
