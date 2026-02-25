<?php

/**
 * SetupTest
 *
 * Feature tests for the SetupController endpoint (POST /api/setup).
 *
 * Tests verify:
 *   1. A new admin with no location memberships can create their first establishment.
 *   2. The created location has the correct name, city, and state.
 *   3. A pivot membership is created linking the admin to the new location.
 *   4. The admin's active location_id and role are updated.
 *   5. Non-admin users are blocked from using the setup endpoint (403).
 *   6. Admins who already have a location membership are blocked (422).
 *   7. Validation errors are returned for missing required fields.
 *   8. Unauthenticated requests are rejected (401).
 */

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    // ══════════════════════════════════════════════
    //  NEW ADMIN SETUP — HAPPY PATH
    // ══════════════════════════════════════════════

    /**
     * Verifies that a new admin with no location memberships can create
     * their first establishment via POST /api/setup.
     */
    public function test_new_admin_can_create_first_establishment(): void
    {
        $admin = User::create([
            'name' => 'New Admin',
            'email' => 'newadmin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/setup', [
                'organization_name' => 'My First Org',
                'name' => 'My First Bar',
                'city' => 'Austin',
                'state' => 'TX',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'locations'])
            ->assertJsonPath('user.role', 'admin');

        // Verify location was created
        $this->assertDatabaseHas('locations', [
            'name' => 'My First Bar',
            'city' => 'Austin',
            'state' => 'TX',
        ]);

        // Verify pivot membership was created
        $admin->refresh();
        $this->assertNotNull($admin->location_id);
        $this->assertEquals(1, $admin->locations()->count());
    }

    // ══════════════════════════════════════════════
    //  NON-ADMIN BLOCKED
    // ══════════════════════════════════════════════

    /**
     * Verifies that a non-admin user (e.g. a manager) cannot use the
     * setup endpoint. Returns 403 Forbidden.
     */
    public function test_non_admin_cannot_use_setup(): void
    {
        $location = Location::create([
            'name' => 'Existing Bar',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/setup', [
                'organization_name' => 'Sneaky Org',
                'name' => 'Sneaky Bar',
                'city' => 'Chicago',
                'state' => 'IL',
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  ADMIN WITH EXISTING LOCATION BLOCKED
    // ══════════════════════════════════════════════

    /**
     * Verifies that an admin who already has a location membership
     * cannot use setup again. Returns 422 with an appropriate message.
     */
    public function test_admin_with_existing_location_cannot_setup_again(): void
    {
        $location = Location::create([
            'name' => 'Already Here',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $admin = User::create([
            'name' => 'Existing Admin',
            'email' => 'existing@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $location->id,
        ]);

        // Give them a pivot membership
        $admin->locations()->attach($location->id, ['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/setup', [
                'organization_name' => 'Another Org',
                'name' => 'Another Bar',
                'city' => 'Denver',
                'state' => 'CO',
            ]);

        $response->assertStatus(422);
    }

    // ══════════════════════════════════════════════
    //  VALIDATION ERRORS
    // ══════════════════════════════════════════════

    /**
     * Verifies that missing required fields (name, city, state) return
     * a 422 validation error.
     */
    public function test_setup_requires_name_city_state(): void
    {
        $admin = User::create([
            'name' => 'Blank Admin',
            'email' => 'blank@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/setup', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'city', 'state']);
    }

    // ══════════════════════════════════════════════
    //  UNAUTHENTICATED (401)
    // ══════════════════════════════════════════════

    /**
     * Verifies that unauthenticated requests to setup are rejected.
     */
    public function test_unauthenticated_cannot_setup(): void
    {
        $response = $this->postJson('/api/setup', [
            'name' => 'Ghost Bar',
            'city' => 'Nowhere',
            'state' => 'XX',
        ]);

        $response->assertStatus(401);
    }
}
