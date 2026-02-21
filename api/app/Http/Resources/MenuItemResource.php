<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming MenuItem model data.
 *
 * Serializes a MenuItem instance for JSON API responses, including
 * the item name, description, price, type, allergens, active/new
 * flags, and conditionally loaded category relationship.
 */
class MenuItemResource extends JsonResource
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
