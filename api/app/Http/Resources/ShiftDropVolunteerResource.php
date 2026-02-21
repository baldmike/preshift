<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming ShiftDropVolunteer model data.
 *
 * Serializes a ShiftDropVolunteer instance for JSON API responses,
 * including the volunteer's selection status and conditionally loaded
 * user and shift drop relationships.
 */
class ShiftDropVolunteerResource extends JsonResource
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
