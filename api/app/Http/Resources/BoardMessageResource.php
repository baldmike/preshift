<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming BoardMessage model data.
 *
 * Serializes a BoardMessage instance for JSON API responses, including
 * the post's body, visibility, pinned status, author info, and conditionally
 * loaded replies with their own author info.
 */
class BoardMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'user_id' => $this->user_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'visibility' => $this->visibility,
            'pinned' => $this->pinned,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'role' => $this->user->role,
            ]),
            'replies' => BoardMessageResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->when($this->replies_count !== null, $this->replies_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
