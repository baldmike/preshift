<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A request by a staff member to swap (give up) one of their scheduled shifts.
 *
 * Lifecycle:
 *   1. Staff creates a swap request (status = "pending"). They can optionally
 *      target a specific person or leave it open for anyone.
 *   2. Another eligible staff member offers to pick it up (status → "offered",
 *      picked_up_by is set).
 *   3. A manager approves or denies the swap.
 *      - Approved: the schedule entry's user_id is updated to the new person.
 *      - Denied: the swap request is closed, schedule unchanged.
 *   4. The requester can cancel at any point before resolution.
 *
 * @property int                             $id
 * @property int                             $schedule_entry_id  FK to the shift being swapped
 * @property int                             $requested_by       FK to the requesting staff member
 * @property int|null                        $target_user_id     FK to a specific person (or null for open)
 * @property int|null                        $picked_up_by       FK to the person who offered to take it
 * @property string                          $status             pending|offered|approved|denied|cancelled
 * @property string|null                     $reason             Why the staff member needs the swap
 * @property int|null                        $resolved_by        FK to the manager who approved/denied
 * @property \Illuminate\Support\Carbon|null $resolved_at        When the decision was made
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class SwapRequest extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'schedule_entry_id', // FK — the shift being swapped
        'requested_by',      // FK — staff member initiating the swap
        'target_user_id',    // FK — optional specific target person
        'picked_up_by',      // FK — the person who offered to cover
        'status',            // Current lifecycle state
        'reason',            // Optional reason for the swap
        'resolved_by',       // FK — manager who approved/denied
        'resolved_at',       // When the decision was made
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    /**
     * The schedule entry (shift) that is being swapped.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ScheduleEntry, SwapRequest>
     */
    public function scheduleEntry(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntry::class);
    }

    /**
     * The staff member who initiated the swap request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, SwapRequest>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * The specific person the requester wants to swap with (nullable).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, SwapRequest>
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * The person who offered to pick up the shift.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, SwapRequest>
     */
    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_up_by');
    }

    /**
     * The manager who approved or denied the swap.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, SwapRequest>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
