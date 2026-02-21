<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming ScheduleEntry model data.
 *
 * Serializes a ScheduleEntry instance for JSON API responses, including
 * the assigned date, role, notes, and conditionally loaded user, shift
 * template, schedule, and shift drop relationships.
 */
class ScheduleEntryResource extends JsonResource
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
