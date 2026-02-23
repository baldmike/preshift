<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents a private 1-on-1 direct message conversation between two staff members.
 *
 * Conversations are scoped to a single location and contain two participants via
 * the `conversation_user` pivot table. Each participant's `last_read_at` timestamp
 * on the pivot tracks unread message state. The `latestMessage` relationship
 * provides quick access to the most recent message for conversation list previews.
 *
 * @property int                        $id
 * @property int                        $location_id  FK to the venue this conversation belongs to
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Conversation extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id', // FK — scopes the conversation to one venue
    ];

    // ── Relationships ──

    /**
     * The venue this conversation belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, Conversation>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The two participants in this conversation.
     * The pivot includes `last_read_at` for unread tracking and timestamps.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * All direct messages in this conversation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<DirectMessage>
     */
    public function directMessages(): HasMany
    {
        return $this->hasMany(DirectMessage::class);
    }

    /**
     * The most recent message in this conversation.
     * Used for displaying a preview in the conversation list.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<DirectMessage>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(DirectMessage::class)->latestOfMany();
    }
}
