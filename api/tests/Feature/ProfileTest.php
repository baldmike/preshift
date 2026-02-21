<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for PUT /api/profile.
 *
 * This endpoint lets any authenticated user update their own name and
 * availability. It must NOT allow changes to sensitive fields like role,
 * email, or location_id. Tests cover authentication, each updatable field,
 * the safety guard on restricted fields, and the response shape.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a location with one server user for profile tests.
     *
     * @return array{location: Location, staff: User}
     */
    private function seedLocationAndUsers(): array
    {
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $staff = User::create([
            'name' => 'Server User',
            'email' => 'server@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        return compact('location', 'staff');
    }

    /**
     * Verify that unauthenticated requests receive 401 Unauthorized.
     */
    public function test_unauthenticated_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/profile', ['name' => 'New Name']);

        $response->assertStatus(401);
    }

    /**
     * Verify that an authenticated user can update their display name.
     */
    public function test_update_name(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/profile', ['name' => 'Updated Name']);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Name');

        // Confirm the change persisted to the database.
        $seed['staff']->refresh();
        $this->assertEquals('Updated Name', $seed['staff']->name);
    }

    /**
     * Verify that an authenticated user can update their weekly availability.
     */
    public function test_update_availability(): void
    {
        $seed = $this->seedLocationAndUsers();

        $availability = [
            'monday' => ['10:30', '16:30'],
            'tuesday' => ['open'],
        ];

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/profile', ['availability' => $availability]);

        $response->assertOk()
            ->assertJsonPath('availability.monday', ['10:30', '16:30'])
            ->assertJsonPath('availability.tuesday', ['open']);
    }

    /**
     * Verify that sending role, email, or location_id in the request body
     * has no effect -- these fields are not in the validation whitelist
     * and are silently ignored.
     */
    public function test_ignores_role_email_and_location_id(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/profile', [
                'name' => 'New Name',
                'role' => 'admin',            // Should be ignored
                'email' => 'hacker@evil.com', // Should be ignored
                'location_id' => 999,         // Should be ignored
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('role', 'server')           // Unchanged
            ->assertJsonPath('email', 'server@test.com'); // Unchanged

        // Double-check the database wasn't modified.
        $seed['staff']->refresh();
        $this->assertEquals('server', $seed['staff']->role);
        $this->assertEquals('server@test.com', $seed['staff']->email);
        $this->assertEquals($seed['location']->id, $seed['staff']->location_id);
    }

    /**
     * Verify the response includes the user's location relationship
     * so the frontend can refresh its auth store in one call.
     */
    public function test_returns_location_relationship(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/profile', ['name' => 'Test']);

        $response->assertOk()
            ->assertJsonPath('location.name', 'Test Location');
    }
}
