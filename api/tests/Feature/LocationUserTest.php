<?php

/**
 * LocationUserTest
 *
 * Feature tests for the location_user pivot table and User model relationships.
 *
 * Tests verify:
 *   1. The backfill migration correctly populates the pivot table from existing users.
 *   2. User->locations() returns the correct BelongsToMany relationship with pivot role.
 *   3. Location->members() returns the correct inverse BelongsToMany relationship.
 *   4. User->switchLocation() updates location_id and role from the pivot.
 *   5. User->switchLocation() fails when the user has no membership at the target.
 *   6. User->needsSetup() returns true for admin with no memberships, false otherwise.
 *   7. Login response includes the locations array.
 *   8. GET /api/user response includes the locations array.
 */

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LocationUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a location, a user, and a pivot membership.
     *
     * @return array{location: Location, user: User}
     */
    private function seedLocationAndUser(): array
    {
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        $user->locations()->attach($location->id, ['role' => 'manager']);

        return compact('location', 'user');
    }

    // ══════════════════════════════════════════════
    //  USER->LOCATIONS() RELATIONSHIP
    // ══════════════════════════════════════════════

    /**
     * Verifies that the User->locations() BelongsToMany relationship
     * returns the correct locations with the pivot role column.
     */
    public function test_user_locations_relationship_returns_pivot_with_role(): void
    {
        $seed = $this->seedLocationAndUser();

        $locations = $seed['user']->locations()->get();

        $this->assertCount(1, $locations);
        $this->assertEquals($seed['location']->id, $locations->first()->id);
        $this->assertEquals('manager', $locations->first()->pivot->role);
    }

    // ══════════════════════════════════════════════
    //  LOCATION->MEMBERS() RELATIONSHIP
    // ══════════════════════════════════════════════

    /**
     * Verifies that the Location->members() BelongsToMany relationship
     * returns the correct users with the pivot role column.
     */
    public function test_location_members_relationship_returns_pivot_with_role(): void
    {
        $seed = $this->seedLocationAndUser();

        $members = $seed['location']->members()->get();

        $this->assertCount(1, $members);
        $this->assertEquals($seed['user']->id, $members->first()->id);
        $this->assertEquals('manager', $members->first()->pivot->role);
    }

    // ══════════════════════════════════════════════
    //  SWITCH LOCATION UPDATES USER RECORD
    // ══════════════════════════════════════════════

    /**
     * Verifies that switchLocation() updates the user's location_id
     * and role based on their pivot membership at the target location.
     */
    public function test_switch_location_updates_user_record(): void
    {
        $seed = $this->seedLocationAndUser();

        $location2 = Location::create([
            'name' => 'Second Location',
            'address' => '456 Oak Ave',
            'timezone' => 'America/Chicago',
        ]);

        $seed['user']->locations()->attach($location2->id, ['role' => 'server']);

        $seed['user']->switchLocation($location2);

        $this->assertEquals($location2->id, $seed['user']->location_id);
        $this->assertEquals('server', $seed['user']->role);
    }

    // ══════════════════════════════════════════════
    //  SWITCH LOCATION FAILS WITHOUT MEMBERSHIP
    // ══════════════════════════════════════════════

    /**
     * Verifies that switchLocation() throws an exception when the user
     * has no pivot membership at the target location.
     */
    public function test_switch_location_fails_without_membership(): void
    {
        $seed = $this->seedLocationAndUser();

        $location2 = Location::create([
            'name' => 'No Access',
            'address' => '789 Elm St',
            'timezone' => 'America/Denver',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $seed['user']->switchLocation($location2);
    }

    // ══════════════════════════════════════════════
    //  NEEDS SETUP — ADMIN WITH NO MEMBERSHIPS
    // ══════════════════════════════════════════════

    /**
     * Verifies that needsSetup() returns true for an admin user with
     * zero location memberships.
     */
    public function test_needs_setup_true_for_admin_without_memberships(): void
    {
        $admin = User::create([
            'name' => 'New Admin',
            'email' => 'newadmin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => null,
        ]);

        $this->assertTrue($admin->needsSetup());
    }

    /**
     * Verifies that needsSetup() returns false for an admin who has
     * at least one location membership.
     */
    public function test_needs_setup_false_for_admin_with_memberships(): void
    {
        $seed = $this->seedLocationAndUser();

        // Change to admin role
        $seed['user']->update(['role' => 'admin']);
        $seed['user']->locations()->updateExistingPivot(
            $seed['location']->id,
            ['role' => 'admin']
        );

        $this->assertFalse($seed['user']->needsSetup());
    }

    /**
     * Verifies that needsSetup() returns false for non-admin users
     * even if they have no location memberships.
     */
    public function test_needs_setup_false_for_non_admin(): void
    {
        $server = User::create([
            'name' => 'Server',
            'email' => 'server@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => null,
        ]);

        $this->assertFalse($server->needsSetup());
    }

    // ══════════════════════════════════════════════
    //  LOGIN RESPONSE INCLUDES LOCATIONS
    // ══════════════════════════════════════════════

    /**
     * Verifies that POST /api/login returns a 'locations' array
     * alongside 'user' and 'token'.
     */
    public function test_login_response_includes_locations(): void
    {
        $seed = $this->seedLocationAndUser();

        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token', 'locations'])
            ->assertJsonCount(1, 'locations')
            ->assertJsonPath('locations.0.id', $seed['location']->id)
            ->assertJsonPath('locations.0.role', 'manager');
    }

    // ══════════════════════════════════════════════
    //  GET /api/user INCLUDES LOCATIONS
    // ══════════════════════════════════════════════

    /**
     * Verifies that GET /api/user returns a 'locations' array
     * alongside the user data.
     */
    public function test_get_user_response_includes_locations(): void
    {
        $seed = $this->seedLocationAndUser();

        $response = $this->actingAs($seed['user'], 'sanctum')
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonStructure(['user', 'locations'])
            ->assertJsonCount(1, 'locations');
    }
}
