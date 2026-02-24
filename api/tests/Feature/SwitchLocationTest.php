<?php

/**
 * SwitchLocationTest
 *
 * Feature tests for the SwitchLocationController endpoint.
 *
 * Tests verify:
 *   1. A user with multiple location memberships can switch their active location.
 *   2. Switching updates the user's location_id and role to match the target membership.
 *   3. A user cannot switch to a location they have no membership at (403).
 *   4. Switching to a non-existent location returns a validation error (422).
 *   5. Unauthenticated requests are rejected (401).
 */

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SwitchLocationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed two locations and a user with memberships at both.
     *
     * @return array{location1: Location, location2: Location, user: User}
     */
    private function seedMultiLocationUser(): array
    {
        $location1 = Location::create([
            'name' => 'Downtown Bar',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $location2 = Location::create([
            'name' => 'Uptown Lounge',
            'address' => '456 Oak Ave',
            'timezone' => 'America/Chicago',
        ]);

        $user = User::create([
            'name' => 'Multi User',
            'email' => 'multi@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location1->id,
        ]);

        // Create pivot memberships
        $user->locations()->attach($location1->id, ['role' => 'manager']);
        $user->locations()->attach($location2->id, ['role' => 'server']);

        return compact('location1', 'location2', 'user');
    }

    // ══════════════════════════════════════════════
    //  SWITCH LOCATION — HAPPY PATH
    // ══════════════════════════════════════════════

    /**
     * Verifies that a user with memberships at two locations can switch
     * from one to the other, and the response contains the updated user
     * and locations list.
     */
    public function test_user_can_switch_to_another_location(): void
    {
        $seed = $this->seedMultiLocationUser();

        $response = $this->actingAs($seed['user'], 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $seed['location2']->id,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'locations'])
            ->assertJsonPath('user.location_id', $seed['location2']->id)
            ->assertJsonPath('user.role', 'server');

        // Verify the database was updated
        $seed['user']->refresh();
        $this->assertEquals($seed['location2']->id, $seed['user']->location_id);
        $this->assertEquals('server', $seed['user']->role);
    }

    // ══════════════════════════════════════════════
    //  SWITCH LOCATION — NO MEMBERSHIP (403)
    // ══════════════════════════════════════════════

    /**
     * Verifies that a user cannot switch to a location they have no
     * pivot membership at, even if the location exists.
     */
    public function test_user_cannot_switch_to_location_without_membership(): void
    {
        $seed = $this->seedMultiLocationUser();

        $location3 = Location::create([
            'name' => 'Secret Spot',
            'address' => '789 Elm St',
            'timezone' => 'America/Denver',
        ]);

        $response = $this->actingAs($seed['user'], 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $location3->id,
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  SWITCH LOCATION — INVALID LOCATION (422)
    // ══════════════════════════════════════════════

    /**
     * Verifies that switching to a non-existent location_id fails
     * validation with a 422 response.
     */
    public function test_switch_to_nonexistent_location_fails_validation(): void
    {
        $seed = $this->seedMultiLocationUser();

        $response = $this->actingAs($seed['user'], 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => 99999,
            ]);

        $response->assertStatus(422);
    }

    // ══════════════════════════════════════════════
    //  SWITCH LOCATION — UNAUTHENTICATED (401)
    // ══════════════════════════════════════════════

    /**
     * Verifies that unauthenticated requests to switch location are
     * rejected with a 401 Unauthorized response.
     */
    public function test_unauthenticated_cannot_switch_location(): void
    {
        $response = $this->postJson('/api/switch-location', [
            'location_id' => 1,
        ]);

        $response->assertStatus(401);
    }
}
