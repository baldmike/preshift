<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for transforming Conversation model data.
 *
 * Serializes a Conversation instance for JSON API responses, including
 * the participant list, latest message preview, and computed unread count
 * based on the authenticated user's last_read_at timestamp.
 */
class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $pivot = $this->participants->firstWhere('id', $user->id)?->pivot;
        $lastReadAt = $pivot?->last_read_at;

        // Count messages sent after the user's last_read_at
        $unreadCount = 0;
        if ($lastReadAt) {
            $unreadCount = $this->directMessages()
                ->where('created_at', '>', $lastReadAt)
                ->where('sender_id', '!=', $user->id)
                ->count();
        } else {
            // Never read = all messages from the other person are unread
            $unreadCount = $this->directMessages()
                ->where('sender_id', '!=', $user->id)
                ->count();
        }

        return [
            'id' => $this->id,
            'location_id' => $this->location_id,
            'participants' => $this->whenLoaded('participants', fn () => $this->participants->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'role' => $p->role,
            ])),
            'latest_message' => $this->whenLoaded('latestMessage', fn () => $this->latestMessage ? new DirectMessageResource($this->latestMessage) : null),
            'unread_count' => $unreadCount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
