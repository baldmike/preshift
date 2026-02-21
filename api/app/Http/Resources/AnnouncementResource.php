<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming Announcement model data.
 *
 * Serializes an Announcement instance for JSON API responses, including
 * the title, body, priority, target roles, expiration date, and
 * conditionally loaded poster and acknowledgment relationships.
 */
class AnnouncementResource extends JsonResource
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
