<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a daily event at a location (e.g. "Wine tasting at 7pm").
 *
 * Events are simple, date-scoped items posted by managers so staff know
 * what's happening on a given day. They appear on the pre-shift dashboard.
 *
 * @property int                        $id
 * @property int                        $location_id   FK to the venue this event is for
 * @property string                     $title         Short headline for the event
 * @property string|null                $description   Optional details about the event
 * @property \Illuminate\Support\Carbon $event_date    The day the event applies to
 * @property string|null                $event_time    Optional "HH:MM" display time
 * @property int                        $created_by    FK to the User who created the event
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Event extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',  // FK — scopes the event to one venue
        'title',        // Short headline for the event
        'description',  // Optional additional details
        'event_date',   // The day the event applies to
        'event_time',   // Optional "HH:MM" display time
        'created_by',   // FK to users table — the author of this event
    ];

    /**
     * Attribute casts.
     *
     * - event_date: cast to Carbon date for date comparison in scopes
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    // ── Relationships ──

    /**
     * The venue this event belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, Event>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The manager or admin who created this event.
     * Uses a custom FK column (created_by instead of user_id).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Event>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ──

    /**
     * Scope: filter events to a specific date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string|\Illuminate\Support\Carbon     $date  The date to filter by
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('event_date', $date);
    }
}
