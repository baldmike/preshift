<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Records that a specific user has acknowledged (read/confirmed) a polymorphic entity.
 *
 * This is the "read receipt" model for the pre-shift meeting system. When staff
 * view an 86'd item, special, push item, or announcement, an Acknowledgment row
 * is created to prove they were informed. Management can then verify that the
 * entire team has been briefed before service begins.
 *
 * The polymorphic design (acknowledgable_type + acknowledgable_id) allows a single
 * table to store acknowledgments for any model that requires staff confirmation,
 * without needing a separate pivot table per entity type.
 *
 * Note: timestamps are disabled because we only care about the explicit
 * acknowledged_at timestamp, not created_at/updated_at.
 *
 * @property int                        $id
 * @property int                        $user_id             FK to the staff member who acknowledged
 * @property string                     $acknowledgable_type Fully-qualified class name of the acknowledged model
 * @property int                        $acknowledgable_id   Primary key of the acknowledged model instance
 * @property \Illuminate\Support\Carbon $acknowledged_at     Exact moment the user confirmed they read the item
 */
class Acknowledgment extends Model
{
    use HasFactory;

    /**
     * Disable automatic created_at / updated_at columns.
     * This model only uses the explicit acknowledged_at timestamp.
     */
    public $timestamps = false;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',              // FK — the staff member submitting the acknowledgment
        'acknowledgable_type',  // Morph type — class name of the parent (EightySixed, Special, PushItem, Announcement)
        'acknowledgable_id',    // Morph ID — primary key of the specific record being acknowledged
        'acknowledged_at',      // When the user confirmed they read/understood the item
    ];

    /**
     * Attribute casts.
     *
     * - acknowledged_at: cast to Carbon for consistent date handling and formatting
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    /**
     * The staff member who submitted this acknowledgment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Acknowledgment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The polymorphic parent entity that was acknowledged.
     *
     * Resolves to one of: EightySixed, Special, PushItem, or Announcement
     * depending on the acknowledgable_type column value.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<Model, Acknowledgment>
     */
    public function acknowledgable(): MorphTo
    {
        return $this->morphTo();
    }
}
