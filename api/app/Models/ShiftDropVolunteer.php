<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a user's volunteer sign-up for a specific shift drop.
 *
 * When a shift drop is open, eligible staff can volunteer to cover it. Each
 * volunteer record links the user to the drop and tracks whether a manager
 * has selected them as the replacement. Only one volunteer per drop will
 * have `selected = true` once the drop is filled.
 *
 * @property int                        $id
 * @property int                        $shift_drop_id  FK to the shift drop being volunteered for
 * @property int                        $user_id        FK to the user who volunteered
 * @property bool                       $selected       Whether this volunteer was chosen by the manager
 * @property \Illuminate\Support\Carbon $created_at
 */
class ShiftDropVolunteer extends Model
{
    use HasFactory;

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

    /**
     * The shift drop this volunteer sign-up belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<ShiftDrop, ShiftDropVolunteer>
     */
    public function shiftDrop(): BelongsTo
    {
        return $this->belongsTo(ShiftDrop::class);
    }

    /**
     * The user who volunteered to pick up the shift.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, ShiftDropVolunteer>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
