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
        $data = parent::toArray($request);

        $user = $request->user();

        if ($user && $user->isStaff()) {
            $hasVolunteered = collect($this->volunteers ?? [])
                ->contains('user_id', $user->id);

            $data['has_volunteered'] = $hasVolunteered;
            unset($data['volunteers']);
        }

        return $data;
    }
}
