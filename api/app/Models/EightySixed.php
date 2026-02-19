<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Tracks an item that has been "86'd" (marked unavailable) at a location.
 *
 * In restaurant jargon, "86" means an item is out of stock or otherwise cannot
 * be served. This model records who flagged the item, why, and when (if ever) it
 * was restored. Staff members acknowledge 86 records so management can verify
 * the entire team is aware of the change.
 *
 * Uses a polymorphic acknowledgment relationship so that the same Acknowledgment
 * model tracks read-receipts for 86s, specials, push items, and announcements.
 *
 * @property int                             $id
 * @property int                             $location_id      FK to the venue
 * @property int|null                        $menu_item_id     FK to the menu item (nullable if the item was deleted)
 * @property string                          $item_name        Denormalized name — preserved even if the MenuItem is later removed
 * @property string|null                     $reason           Optional explanation (e.g. "ran out of salmon")
 * @property int                             $eighty_sixed_by  FK to the User who flagged the item as unavailable
 * @property \Illuminate\Support\Carbon|null $restored_at      Null while the item is still 86'd; set when availability is restored
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class EightySixed extends Model
{
    use HasFactory;

    /**
     * Override the default table name because Laravel's pluralization of
     * "EightySixed" would not produce the correct snake_case table name.
     */
    protected $table = 'eighty_sixed';

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',     // FK — scopes the 86 to a specific venue
        'menu_item_id',    // FK — the affected menu item (nullable for safety)
        'item_name',       // Snapshot of the item name at the time of 86 — avoids broken references
        'reason',          // Free-text explanation from the person who 86'd the item
        'eighty_sixed_by', // FK to users table — who performed the 86 action
        'restored_at',     // Null = still unavailable; timestamp = item has been brought back
    ];

    /**
     * Attribute casts.
     *
     * - restored_at: cast to Carbon so null-checks and date comparisons work cleanly
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'restored_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    /**
     * The venue where this 86 was recorded.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, EightySixed>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The menu item that is/was unavailable.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<MenuItem, EightySixed>
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * The staff member (usually a manager) who marked the item as 86'd.
     * Uses a custom FK column because the column name differs from the convention.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, EightySixed>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eighty_sixed_by');
    }

    /**
     * Polymorphic acknowledgments — staff "read receipts" confirming they are
     * aware this item has been 86'd. Uses the acknowledgable morph map.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Acknowledgment>
     */
    public function acknowledgments(): MorphMany
    {
        return $this->morphMany(Acknowledgment::class, 'acknowledgable');
    }

    // ── Scopes ──

    /**
     * Scope: only records that have NOT been restored (i.e. the item is still unavailable).
     * Filters out historical 86s by checking that restored_at is null.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->whereNull('restored_at');
    }
}
