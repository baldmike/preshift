<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One staff member assigned to one shift on a specific date.
 *
 * A schedule entry ties together a Schedule (the week), a User (who's working),
 * a ShiftTemplate (which shift type), a date (which day), and a role (what
 * capacity they're filling). Managers can also attach notes like "training"
 * or "cut first".
 *
 * @property int                        $id
 * @property int                        $schedule_id        FK to the parent Schedule
 * @property int                        $user_id            FK to the assigned staff member
 * @property int                        $shift_template_id  FK to the shift type
 * @property string                     $date               The specific calendar day (date string)
 * @property string                     $role               "server" or "bartender"
 * @property string|null                $notes              Optional manager notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ScheduleEntry extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'schedule_id',       // FK — which weekly schedule this belongs to
        'user_id',           // FK — the staff member assigned
        'shift_template_id', // FK — which shift type (Lunch, Dinner, etc.)
        'date',              // The calendar day for this shift
        'role',              // server or bartender
        'notes',             // Optional manager notes (e.g. "training")
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    // ── Relationships ──

    /**
     * The weekly schedule this entry belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Schedule, ScheduleEntry>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * The staff member assigned to this shift.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, ScheduleEntry>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The shift template defining the shift name and time window.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ShiftTemplate, ScheduleEntry>
     */
    public function shiftTemplate(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class);
    }

    /**
     * Swap requests filed against this schedule entry.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<SwapRequest>
     */
    public function swapRequests(): HasMany
    {
        return $this->hasMany(SwapRequest::class, 'schedule_entry_id');
    }
}
