<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftDropVolunteer extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'shift_drop_id',
        'user_id',
        'selected',
    ];

    protected function casts(): array
    {
        return [
            'selected' => 'boolean',
        ];
    }

    public function shiftDrop(): BelongsTo
    {
        return $this->belongsTo(ShiftDrop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
