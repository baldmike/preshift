<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming ShiftDrop model data.
 *
 * Serializes a ShiftDrop instance for JSON API responses, including
 * the drop reason, status, filled_by/filled_at details, and conditionally
 * loaded schedule entry, requester, filler, and volunteer relationships.
 */
class ShiftDropResource extends JsonResource
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
