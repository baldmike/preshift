<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A reusable shift definition for a location (e.g. "Lunch 10:30–3:00").
 *
 * Managers create shift templates once, then reference them when building
 * weekly schedules. Each template stores a name and the start/end times
 * that define the shift window.
 *
 * @property int                        $id
 * @property int                        $location_id  FK to the owning location
 * @property string                     $name         Short label (e.g. "Dinner")
 * @property string                     $start_time   HH:MM:SS when the shift begins
 * @property string                     $end_time     HH:MM:SS when the shift ends
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ShiftTemplate extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id', // FK — scopes the template to one venue
        'name',        // Human-readable shift label
        'start_time',  // Shift start (time only, no date component)
        'end_time',    // Shift end (time only)
    ];

    // ── Relationships ──

    /**
     * The venue this shift template belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, ShiftTemplate>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * All schedule entries that use this template.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<ScheduleEntry>
     */
    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }
}
