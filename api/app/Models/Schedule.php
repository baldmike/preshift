<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents one week of shifts at a location.
 *
 * A schedule is identified by its location and week_start (Monday). It begins
 * in "draft" status while the manager builds out the shift assignments, then
 * transitions to "published" when ready for staff to view. The manager can
 * unpublish to make further edits, then republish.
 *
 * @property int                             $id
 * @property int                             $location_id   FK to the owning location
 * @property string                          $week_start    Date string for the Monday of this week
 * @property string                          $status        "draft" or "published"
 * @property \Illuminate\Support\Carbon|null $published_at  When the schedule was last published
 * @property int|null                        $published_by  FK to the User who published
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class Schedule extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',  // FK — scopes the schedule to one venue
        'week_start',   // Monday of the target week (date string)
        'status',       // "draft" or "published"
        'published_at', // Timestamp of most recent publish action
        'published_by', // FK to user who published
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'week_start'    => 'date',
            'published_at'  => 'datetime',
        ];
    }

    // ── Relationships ──

    /**
     * The venue this schedule belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, Schedule>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The manager who last published this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Schedule>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * All shift entries (staff assignments) within this schedule.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ScheduleEntry>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }

    // ── Helpers ──

    /**
     * Whether this schedule has been published and is visible to staff.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
