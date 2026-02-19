<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a single dish, drink, or other sellable item on a location's menu.
 *
 * MenuItems sit at the heart of the domain — they are referenced by EightySixed
 * records (unavailability), Specials (promotional pricing/features), and PushItems
 * (upsell directives). Each item belongs to one Category and one Location.
 *
 * @property int         $id
 * @property int         $location_id  FK to the venue that serves this item
 * @property int         $category_id  FK to the menu category (e.g. "Entrees")
 * @property string      $name         Display name (e.g. "Grilled Salmon")
 * @property string|null $description  Optional prose description for the menu
 * @property string      $price        Stored as DECIMAL(8,2); cast to a two-decimal string
 * @property string      $type         Classifies the item (e.g. "food", "drink") for filtering
 * @property bool        $is_new       Flagged when the item was recently added so the UI can badge it
 * @property bool        $is_active    Soft toggle — inactive items are hidden from the current menu
 * @property array|null  $allergens    JSON array of allergen identifiers (e.g. ["gluten","dairy"])
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class MenuItem extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id',  // FK — venue scope
        'category_id',  // FK — determines which section of the menu this item appears under
        'name',         // Human-readable item title
        'description',  // Optional detail text (tasting notes, ingredients, etc.)
        'price',        // Unit price in the location's local currency
        'type',         // Broad classification (food | drink) — useful for filtering
        'is_new',       // Boolean flag to highlight recently added items
        'is_active',    // When false the item is effectively soft-deleted from the active menu
        'allergens',    // JSON array of allergen tags for guest safety
    ];

    /**
     * Attribute casts.
     *
     * - is_new / is_active: stored as tinyint, cast to native PHP booleans
     * - allergens: stored as JSON text, cast to/from a PHP array automatically
     * - price: cast to a fixed two-decimal string to prevent floating-point display issues
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_new' => 'boolean',
            'is_active' => 'boolean',
            'allergens' => 'array',
            'price' => 'decimal:2',
        ];
    }

    // ── Relationships ──

    /**
     * The venue that serves this menu item.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, MenuItem>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * The category this item is grouped under (e.g. "Appetizers").
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Category, MenuItem>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Historical and current 86 records for this item.
     * An item may be 86'd multiple times over its lifetime (e.g. ingredient shortages).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EightySixed>
     */
    public function eightySixed(): HasMany
    {
        return $this->hasMany(EightySixed::class);
    }

    /**
     * Specials that feature this menu item (e.g. half-price happy-hour deals).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Special>
     */
    public function specials(): HasMany
    {
        return $this->hasMany(Special::class);
    }

    /**
     * Push-item directives tied to this menu item.
     * Staff are prompted to upsell these items during service.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<PushItem>
     */
    public function pushItems(): HasMany
    {
        return $this->hasMany(PushItem::class);
    }
}
