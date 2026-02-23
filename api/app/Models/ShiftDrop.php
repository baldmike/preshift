<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a request by a staff member to drop (release) an assigned shift.
 *
 * A ShiftDrop tracks the full lifecycle of a shift-release request: creation
 * (status "open"), volunteer sign-ups, manager selection of a volunteer
 * (status "filled"), or cancellation by the requester (status "cancelled").
 *
 * @property int                             $id
 * @property int                             $schedule_entry_id  FK to the schedule entry being dropped
 * @property int                             $requested_by       FK to the user who requested the drop
 * @property string|null                     $reason             Optional reason for dropping the shift
 * @property string                          $status             One of: open, filled, cancelled
 * @property int|null                        $filled_by          FK to the user who picked up the shift (null until filled)
 * @property \Illuminate\Support\Carbon|null $filled_at          Timestamp when the drop was filled (null until filled)
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class ShiftDrop extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'schedule_entry_id', // FK — the shift assignment being dropped
        'requested_by',      // FK — the user who wants to drop the shift
        'reason',            // Optional freeform explanation for the drop
        'status',            // Lifecycle state: open, filled, or cancelled
        'filled_by',         // FK — the user who picked up the shift (set when filled)
        'filled_at',         // Timestamp when a volunteer was selected (set when filled)
    ];

    protected function casts(): array
    {
        return [
            'filled_at' => 'datetime',
        ];
    }

    /**
     * The schedule entry (shift assignment) that is being dropped.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ScheduleEntry, ShiftDrop>
     */
    public function scheduleEntry(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntry::class);
    }

    /**
     * The user who requested to drop the shift.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, ShiftDrop>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * The user who was selected to fill (pick up) the dropped shift.
     * Null until a manager selects a volunteer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, ShiftDrop>
     */
    public function filler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by');
    }

    /**
     * All users who have volunteered to pick up this dropped shift.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ShiftDropVolunteer>
     */
    public function volunteers(): HasMany
    {
        return $this->hasMany(ShiftDropVolunteer::class);
    }
}
