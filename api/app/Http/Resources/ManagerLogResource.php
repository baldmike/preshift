<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming ManagerLog model data.
 *
 * Serializes a ManagerLog instance for JSON API responses, including
 * the body, log_date, weather/events/schedule snapshots, and the
 * conditionally loaded creator relationship.
 */
class ManagerLogResource extends JsonResource
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
