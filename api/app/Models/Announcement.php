<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Represents an internal announcement posted by management for staff at a location.
 *
 * Announcements are used to communicate operational updates, policy changes, event
 * details, or any other information the team needs to know. They support role-based
 * targeting (e.g. only bartenders), optional expiry dates, and priority levels.
 * Staff acknowledge announcements to confirm they have read them.
 *
 * @property int                             $id
 * @property int                             $location_id   FK to the venue this announcement is for
 * @property string                          $title         Headline displayed in the briefing feed
 * @property string                          $body          Full announcement content (may contain rich text)
 * @property string                          $priority      Urgency indicator (e.g. "high", "normal", "low")
 * @property array|null                      $target_roles  JSON array of roles that should see this announcement;
 *                                                          null means it targets everyone at the location
 * @property int                             $posted_by     FK to the User who authored the announcement
 * @property \Illuminate\Support\Carbon|null $expires_at    When set, the announcement is hidden after this timestamp
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class Announcement extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',  // FK — scopes the announcement to one venue
        'title',        // Short headline for the announcement
        'body',         // Full message content
        'priority',     // Controls visual emphasis and sort order in the feed
        'target_roles', // JSON array of role strings to restrict visibility (null = all roles)
        'posted_by',    // FK to users table — the author of this announcement
        'expires_at',   // Optional expiry timestamp; null = never expires
    ];

    /**
     * Attribute casts.
     *
     * - target_roles: JSON column -> PHP array so role checks can use in_array / contains
     * - expires_at: cast to Carbon for straightforward date comparison in scopes
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    /**
     * The venue this announcement was posted for.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, Announcement>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The manager or admin who authored this announcement.
     * Uses a custom FK column (posted_by instead of user_id).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Announcement>
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * Polymorphic acknowledgments — staff read-receipts for this announcement.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Acknowledgment>
     */
    public function acknowledgments(): MorphMany
    {
        return $this->morphMany(Acknowledgment::class, 'acknowledgable');
    }

    // ── Scopes ──

    /**
     * Scope: only announcements that have not expired.
     *
     * An announcement is "active" when:
     *   - expires_at is null (it never expires), OR
     *   - expires_at is still in the future
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: filter announcements to those visible to a given role.
     *
     * Announcements with a null target_roles column are visible to everyone.
     * Otherwise, the JSON array is checked for the given role using
     * whereJsonContains so the query runs at the database level.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string                                $role  The role to filter for (e.g. "server", "bartender")
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRole($query, $role)
    {
        return $query->where(function ($q) use ($role) {
            // null target_roles = broadcast to all roles at this location
            $q->whereNull('target_roles')
                ->orWhereJsonContains('target_roles', $role);
        });
    }
}
