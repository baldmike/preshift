<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Represents a "push item" — a menu item that management wants staff to actively
 * recommend or upsell to guests during service.
 *
 * Common reasons for pushing an item include: high profit margin, ingredient
 * nearing its use-by date, a new addition management wants to gain traction, or
 * excess prep that needs to move. Each push item is acknowledged by staff to
 * confirm they received the directive.
 *
 * @property int         $id
 * @property int         $location_id   FK to the venue
 * @property int|null    $menu_item_id  FK to the menu item being pushed (nullable for non-menu items)
 * @property string      $title         Short directive headline (e.g. "Push the Lobster Risotto")
 * @property string|null $description   Talking points or context for the staff
 * @property string|null $reason        Internal explanation of why this item is being pushed
 * @property string      $priority      Urgency level (e.g. "high", "medium", "low") — controls sort/display emphasis
 * @property bool        $is_active     Master toggle; inactive push items no longer appear in briefings
 * @property int         $created_by    FK to the User (manager/admin) who created the push directive
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class PushItem extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',  // FK — scopes the push directive to one venue
        'menu_item_id', // FK — the item staff should recommend (nullable for general pushes)
        'title',        // Short action-oriented label displayed to staff
        'description',  // Detailed guidance on how to sell or describe the item
        'reason',       // Internal-only rationale (e.g. "surplus prep", "high margin")
        'priority',     // Controls visual prominence in the staff briefing UI
        'is_active',    // Lets managers deactivate a push without deleting the record
        'created_by',   // FK to users table — tracks who issued the push directive
    ];

    /**
     * Attribute casts.
     *
     * - is_active: tinyint -> boolean for cleaner conditional logic
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──

    /**
     * The venue this push directive applies to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, PushItem>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The specific menu item that staff should recommend.
     * Nullable — some push directives may be general (e.g. "push desserts tonight").
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<MenuItem, PushItem>
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * The manager or admin who created this push directive.
     * Uses a custom FK column (created_by instead of user_id).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, PushItem>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Polymorphic acknowledgments — staff confirmations that they received and
     * understood the push directive for this item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<Acknowledgment>
     */
    public function acknowledgments(): MorphMany
    {
        return $this->morphMany(Acknowledgment::class, 'acknowledgable');
    }

    // ── Scopes ──

    /**
     * Scope: only push items that are currently active.
     * Excludes deactivated records so the briefing only shows relevant directives.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
