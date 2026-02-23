<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a daily operational log entry for a location.
 *
 * Managers create one log per day containing freeform notes (`body`).
 * At creation time the system auto-snapshots weather, events, and
 * scheduled staff into immutable JSON columns, preserving a historical
 * record of what the day looked like.
 *
 * @property int                             $id
 * @property int                             $location_id       FK to the owning location
 * @property int                             $created_by        FK to the manager who created the log
 * @property \Illuminate\Support\Carbon      $log_date          The date this log covers
 * @property string                          $body              Freeform manager notes
 * @property array|null                      $weather_snapshot   Weather data frozen at creation
 * @property array|null                      $events_snapshot    Events data frozen at creation
 * @property array|null                      $schedule_snapshot  Schedule data frozen at creation
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class ManagerLog extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',       // FK — scopes the log to one venue
        'created_by',        // FK — the manager who authored this entry
        'log_date',          // The calendar date this log covers
        'body',              // Freeform manager notes
        'weather_snapshot',  // JSON snapshot of weather at creation time
        'events_snapshot',   // JSON snapshot of events at creation time
        'schedule_snapshot', // JSON snapshot of scheduled staff at creation time
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'log_date'          => 'date',
            'weather_snapshot'  => 'array',
            'events_snapshot'   => 'array',
            'schedule_snapshot' => 'array',
        ];
    }

    // ── Relationships ──

    /**
     * The venue this log belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, ManagerLog>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The manager or admin who created this log entry.
     * Uses a custom FK column (created_by instead of user_id).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, ManagerLog>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
