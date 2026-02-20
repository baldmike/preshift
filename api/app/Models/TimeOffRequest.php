<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A staff member's request for time off on a date range.
 *
 * The request starts as "pending" and a manager approves or denies it.
 * Approved time-off is surfaced in the schedule builder so managers can
 * avoid scheduling conflicts.
 *
 * @property int                             $id
 * @property int                             $user_id      FK to the requesting staff member
 * @property int                             $location_id  FK for scoping/visibility
 * @property string                          $start_date   First day of time off
 * @property string                          $end_date     Last day of time off
 * @property string|null                     $reason       Optional explanation
 * @property string                          $status       pending|approved|denied
 * @property int|null                        $resolved_by  FK to the deciding manager
 * @property \Illuminate\Support\Carbon|null $resolved_at  When the decision was made
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class TimeOffRequest extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',     // FK — the staff member requesting time off
        'location_id', // FK — for location-scoped queries
        'start_date',  // First day of the time-off period
        'end_date',    // Last day of the time-off period
        'reason',      // Optional explanation (e.g. "doctor appointment")
        'status',      // pending, approved, or denied
        'resolved_by', // FK — manager who made the decision
        'resolved_at', // Timestamp of the decision
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'resolved_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    /**
     * The staff member who requested time off.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, TimeOffRequest>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The location this request is scoped to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, TimeOffRequest>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The manager who approved or denied the request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, TimeOffRequest>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ── Scopes ──

    /**
     * Scope: only approved time-off requests.
     * Used in the schedule builder to show time-off conflicts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
