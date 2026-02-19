<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Represents a promotional special at a location (e.g. happy-hour pricing, seasonal feature).
 *
 * Specials are date-bounded and optionally tied to a specific MenuItem. They are
 * surfaced to staff during pre-shift meetings so servers and bartenders can
 * communicate current promotions to guests. Staff acknowledge specials to confirm
 * they have been briefed.
 *
 * @property int                             $id
 * @property int                             $location_id   FK to the venue running the special
 * @property int|null                        $menu_item_id  FK to the featured menu item (nullable for general specials)
 * @property string                          $title         Short headline (e.g. "$5 Margaritas")
 * @property string|null                     $description   Extended detail (pricing, restrictions, talking points)
 * @property string                          $type          Classification (e.g. "happy_hour", "seasonal", "event")
 * @property \Illuminate\Support\Carbon      $starts_at     The first day the special is valid (date only, no time)
 * @property \Illuminate\Support\Carbon|null $ends_at       Last valid day; null means the special runs indefinitely
 * @property bool                            $is_active     Master toggle — managers can deactivate without deleting
 * @property int                             $created_by    FK to the User who created the special
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class Special extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',  // FK — scopes the special to one venue
        'menu_item_id', // FK — optional link to a specific dish/drink being promoted
        'title',        // Headline displayed in the staff briefing
        'description',  // Detailed info for the staff to relay to guests
        'type',         // Categorization of the special (happy_hour, seasonal, etc.)
        'starts_at',    // Inclusive start date
        'ends_at',      // Inclusive end date; null = open-ended
        'is_active',    // Allows managers to pause a special without deleting it
        'created_by',   // FK to users table — audit trail for who created the special
    ];

    /**
     * Attribute casts.
     *
     * - starts_at / ends_at: cast to Carbon date (time component is irrelevant for day-level specials)
     * - is_active: tinyint -> boolean for clean conditional checks
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    /**
     * The venue running this special.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, Special>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The specific menu item being promoted, if any.
     * Nullable — some specials are location-wide (e.g. "Free dessert with entree").
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<MenuItem, Special>
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * The manager or admin who created this special.
     * Uses a custom FK column because the column is named created_by, not user_id.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, Special>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Polymorphic acknowledgments — staff confirmations that they have reviewed
     * this special and are prepared to communicate it to guests.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Acknowledgment>
     */
    public function acknowledgments(): MorphMany
    {
        return $this->morphMany(Acknowledgment::class, 'acknowledgable');
    }

    // ── Scopes ──

    /**
     * Scope: only specials that are currently in effect.
     *
     * A special is "current" when all three conditions are met:
     *   1. is_active is true (manager hasn't paused it)
     *   2. starts_at is today or in the past (the special has begun)
     *   3. ends_at is null (open-ended) OR ends_at is today or in the future (hasn't expired)
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now()->toDateString())
            ->where(function ($q) {
                // Open-ended specials (no end date) always pass; otherwise check expiry
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now()->toDateString());
            });
    }
}
