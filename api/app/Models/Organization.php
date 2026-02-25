<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a business organization that owns one or more locations.
 *
 * Organizations are the top-level grouping entity. Each restaurant group
 * (e.g. "Almost Home", "TITZ") is an organization containing one or more
 * Location records. Admin/manager users belong to an organization and can
 * access all locations within it.
 *
 * @property int         $id
 * @property string      $name      The business/organization name
 * @property string|null $address   Street address (nullable)
 * @property string|null $city      City name (nullable)
 * @property string|null $state     State abbreviation (nullable)
 * @property string|null $timezone  IANA timezone identifier (nullable)
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Organization extends Model
{
    use HasFactory;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'timezone',
    ];

    // ── Relationships ──

    /**
     * All locations belonging to this organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Location>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * All users belonging to this organization.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
