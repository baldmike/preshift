<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a physical restaurant/bar location.
 *
 * This is the top-level tenant model in the application. Nearly every other
 * model (users, menu items, specials, etc.) belongs to a Location, making it
 * the central scoping entity for multi-location operations.
 *
 * @property int    $id
 * @property string $name      The human-readable name of the location (e.g. "Downtown Taproom")
 * @property string $address   The full street address, used for display purposes
 * @property string $timezone  IANA timezone identifier (e.g. "America/New_York") so time-sensitive
 *                             features like specials and announcement expiry resolve correctly
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Location extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned when creating or updating a location.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',      // Display name for the venue
        'address',   // Physical street address
        'timezone',  // IANA timezone — drives all date/time logic scoped to this location
    ];

    // ── Relationships ──

    /**
     * All staff members (admins, managers, servers, bartenders) assigned to this location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Menu categories defined for this location (e.g. "Appetizers", "Entrees", "Cocktails").
     * Categories provide the organizational structure for menu items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Category>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Every individual menu item that belongs to this location.
     * This is a direct relationship — items also belong to a Category, but are
     * independently scoped to a location for quick queries.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<MenuItem>
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * Items that have been 86'd (marked unavailable) at this location.
     * Each record tracks which item is out, why, and when it was restored.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<EightySixed>
     */
    public function eightySixed(): HasMany
    {
        return $this->hasMany(EightySixed::class);
    }

    /**
     * Promotional specials (e.g. happy-hour deals, limited-time offers) running at this location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Special>
     */
    public function specials(): HasMany
    {
        return $this->hasMany(Special::class);
    }

    /**
     * "Push items" — menu items that management wants staff to actively recommend
     * to guests (e.g. high-margin dishes, items nearing expiry).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<PushItem>
     */
    public function pushItems(): HasMany
    {
        return $this->hasMany(PushItem::class);
    }

    /**
     * Internal announcements posted by management for the staff at this location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Announcement>
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }
}
