<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming Acknowledgment model data.
 *
 * Serializes an Acknowledgment instance for JSON API responses, including
 * the polymorphic reference (acknowledgable_type and acknowledgable_id),
 * the acknowledging user, and the acknowledged_at timestamp.
 */
class AcknowledgmentResource extends JsonResource
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
