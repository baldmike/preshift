<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming ShiftTemplate model data.
 *
 * Serializes a ShiftTemplate instance for JSON API responses, including
 * the template name, start time, end time, and conditionally loaded
 * location relationship.
 */
class ShiftTemplateResource extends JsonResource
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
