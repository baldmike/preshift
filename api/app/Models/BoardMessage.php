<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a post on the location-scoped message board.
 *
 * Board messages are informal, body-only posts (no title) that any staff member
 * can create. Posts support one level of threading: top-level posts have a null
 * `parent_id`, and replies reference the parent post. Managers can restrict
 * visibility to managers-only and pin important posts to the top of the board.
 *
 * @property int                        $id
 * @property int                        $location_id   FK to the venue this post belongs to
 * @property int                        $user_id       FK to the author
 * @property int|null                   $parent_id     FK to the parent post (null = top-level)
 * @property string                     $body          The message content
 * @property string                     $visibility    'all' or 'managers'
 * @property bool                       $pinned        Whether this post is pinned to the top
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class BoardMessage extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id', // FK — scopes the post to one venue
        'user_id',     // FK — the staff member who authored the post
        'parent_id',   // FK — null for top-level posts, set for replies
        'body',        // The message text content
        'visibility',  // 'all' or 'managers' — controls who can see the post
        'pinned',      // Whether this post floats to the top of the board
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pinned' => 'boolean',
        ];
    }

    // ── Relationships ──

    /**
     * The venue this post belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, BoardMessage>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The staff member who authored this post.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, BoardMessage>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The parent post this message is a reply to (null for top-level posts).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<BoardMessage, BoardMessage>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(BoardMessage::class, 'parent_id');
    }

    /**
     * Replies to this top-level post, ordered chronologically.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<BoardMessage>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(BoardMessage::class, 'parent_id')->orderBy('created_at');
    }

    // ── Scopes ──

    /**
     * Scope: only top-level posts (not replies).
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope: filter posts by visibility based on the user's role.
     * Staff roles (server, bartender) can only see posts with visibility 'all'.
     * Managers and admins can see all posts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string                                $role  The authenticated user's role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForVisibility($query, string $role)
    {
        if (in_array($role, ['server', 'bartender'])) {
            return $query->where('visibility', 'all');
        }

        return $query;
    }
}
