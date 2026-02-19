<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a menu category (e.g. "Appetizers", "Entrees", "Desserts", "Draft Beer").
 *
 * Categories group MenuItems within a Location so that the front-end can render
 * the menu in a structured, sortable order. Each category belongs to exactly one
 * location, allowing different venues to maintain independent menu structures.
 *
 * @property int    $id
 * @property int    $location_id  FK to the owning location
 * @property string $name         Display name shown as a section header on the menu
 * @property int    $sort_order   Determines the display sequence — lower values appear first
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Category extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id', // FK — scopes this category to a single venue
        'name',        // Category heading (e.g. "Salads", "Cocktails")
        'sort_order',  // Integer for manual ordering — allows managers to reorder sections
    ];

    // ── Relationships ──

    /**
     * The location that owns this category.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, Category>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * All menu items filed under this category.
     * Used to render the items within each section of the menu.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<MenuItem>
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}
