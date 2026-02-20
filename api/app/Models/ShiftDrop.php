<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftDrop extends Model
{
    protected $fillable = [
        'schedule_entry_id',
        'requested_by',
        'reason',
        'status',
        'filled_by',
        'filled_at',
    ];

    protected function casts(): array
    {
        return [
            'filled_at' => 'datetime',
        ];
    }

    public function scheduleEntry(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntry::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function filler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by');
    }

    public function volunteers(): HasMany
    {
        return $this->hasMany(ShiftDropVolunteer::class);
    }
}
