<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming TimeOffRequest model data.
 *
 * Serializes a TimeOffRequest instance for JSON API responses, including
 * the requested date range, reason, approval status, and conditionally
 * loaded user and resolver relationships.
 */
class TimeOffRequestResource extends JsonResource
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
