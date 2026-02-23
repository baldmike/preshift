<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a single direct message within a Conversation.
 *
 * Direct messages are private chat messages sent between two staff members.
 * Each message belongs to one conversation and has a single sender. Messages
 * are not editable once sent (standard chat behavior).
 *
 * @property int                        $id
 * @property int                        $conversation_id  FK to the parent conversation
 * @property int                        $sender_id        FK to the user who sent the message
 * @property string                     $body             The message content
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class DirectMessage extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'conversation_id', // FK — the thread this message belongs to
        'sender_id',       // FK — the user who authored the message
        'body',            // The message text content
    ];

    // ── Relationships ──

    /**
     * The conversation this message belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Conversation, DirectMessage>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * The user who sent this message.
     * Uses a custom FK column because the column is named sender_id, not user_id.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, DirectMessage>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
