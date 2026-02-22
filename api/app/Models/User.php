<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Represents an authenticated user (staff member or administrator) in the system.
 *
 * Users are scoped to a single Location and assigned one role that governs what
 * they can see and do. Roles in ascending privilege order:
 *   - server / bartender  (front-of-house staff)
 *   - manager             (can create specials, push items, announcements, 86 items)
 *   - admin               (full access across all locations)
 *
 * Authentication is handled via Laravel Sanctum (token-based API auth).
 *
 * @property int                             $id
 * @property int                             $location_id       FK to the location this user belongs to
 * @property string                          $name              Full display name
 * @property string                          $email             Unique login email
 * @property string                          $password           Hashed via the 'hashed' cast — never stored in plain text
 * @property string                          $role              One of: admin, manager, server, bartender
 * @property array|null                      $roles             JSON array of all roles this user can work (nullable — falls back to [$role])
 * @property \Illuminate\Support\Carbon|null $email_verified_at Timestamp of email verification (nullable)
 * @property string|null                     $remember_token    Laravel "remember me" session token
 * @property \Illuminate\Support\Carbon      $created_at
 * @property \Illuminate\Support\Carbon      $updated_at
 */
class User extends Authenticatable
{
    /** HasApiTokens: Sanctum token auth | HasFactory: model factory support | Notifiable: notification channels */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Fields that may be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'location_id', // FK — ties this user to a specific venue
        'name',        // Display name shown in the UI and acknowledgment records
        'email',       // Unique credential used for login
        'password',    // Stored as a bcrypt/argon hash via the 'hashed' cast
        'role',        // Determines authorization level (admin | manager | server | bartender)
        'roles',       // JSON array of additional roles for multi-role staff (nullable)
        'phone',       // Contact phone number (nullable)
        'availability', // JSON map of day-of-week availability (nullable)
        'is_superadmin', // SuperAdmin privilege flag
    ];

    /**
     * Attributes excluded from JSON/array serialization to prevent leaking secrets.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',       // Never expose the password hash in API responses
        'remember_token', // Internal session token — not relevant to API consumers
    ];

    /**
     * Attribute casts applied automatically by Eloquent.
     *
     * - email_verified_at -> Carbon instance for easy date comparison
     * - password -> 'hashed' ensures any plain-text value assigned is automatically hashed before storage
     * - roles -> 'array' decodes the JSON multi-role list into a PHP array (nullable)
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'availability' => 'array',
            'roles' => 'array',
            'is_superadmin' => 'boolean',
        ];
    }

    // ── Relationships ──

    /**
     * The venue this user is assigned to.
     * Every non-admin user works at exactly one location.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Location, User>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * All acknowledgment receipts this user has submitted.
     * Acknowledgments confirm the user has read an 86, special, push item, or announcement.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Acknowledgment>
     */
    public function acknowledgments(): HasMany
    {
        return $this->hasMany(Acknowledgment::class);
    }

    // ── Helpers ──

    /**
     * Check whether the user holds the top-level admin role.
     * Admins typically have cross-location access and system-wide privileges.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check whether the user is a location manager.
     * Managers can create/edit specials, push items, announcements, and 86 items
     * but are scoped to their own location.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check whether the user is front-of-house staff (server or bartender).
     * Staff members have read-only access to the daily briefing data and can
     * submit acknowledgments, but cannot create or modify operational records.
     */
    /**
     * Check whether the user has the SuperAdmin privilege.
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_superadmin === true;
    }

    public function isStaff(): bool
    {
        // Both "server" and "bartender" share the same permission tier
        return in_array($this->role, ['server', 'bartender']);
    }

    /**
     * Return all roles this user can work as.
     * Falls back to the primary role when no multi-role array is set.
     *
     * @return string[]
     */
    public function getEffectiveRoles(): array
    {
        return $this->roles ?? [$this->role];
    }

    /**
     * Check whether the user holds a specific role (primary or secondary).
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getEffectiveRoles());
    }

    // ── Scopes ──

    /**
     * Scope: filter users to those belonging to a specific location.
     * Useful for manager dashboards and location-scoped user lists.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int                                    $locationId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}
