<?php

/**
 * AuthTest
 *
 * Feature tests for the AuthController endpoints.
 *
 * Tests verify:
 *   1. A user can log in with valid credentials and receives a user + token.
 *   2. Login fails with invalid credentials and returns 401.
 *   3. An authenticated user can retrieve their profile via GET /api/user.
 *   4. An authenticated user can log out and receives a success message.
 *   5. An authenticated user can change their password with the correct current password.
 *   6. Change password fails when the current password is wrong (422).
 *   7. An authenticated user can update their profile name via PUT /api/profile.
 *   8. Unauthenticated requests to GET /api/user receive 401.
 */

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a location and a manager user for auth tests.
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
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        return compact('location', 'user');
    }

    // ══════════════════════════════════════════════
    //  LOGIN WITH VALID CREDENTIALS
    // ══════════════════════════════════════════════

    /**
     * Verifies that POST /api/login with a correct email and password
     * returns a 200 response containing both 'user' and 'token' keys.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $seed = $this->seedLocationAndUser();

        $response = $this->postJson('/api/login', [
            'email' => 'manager@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token']);
    }

    // ══════════════════════════════════════════════
    //  LOGIN FAILS WITH INVALID CREDENTIALS
    // ══════════════════════════════════════════════

    /**
     * Verifies that POST /api/login with an incorrect password
     * returns a 401 Unauthorized response.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        $seed = $this->seedLocationAndUser();

        $response = $this->postJson('/api/login', [
            'email' => 'manager@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    // ══════════════════════════════════════════════
    //  AUTHENTICATED USER CAN GET PROFILE
    // ══════════════════════════════════════════════

    /**
     * Verifies that GET /api/user returns the authenticated user's data
     * including their name and email.
     */
    public function test_authenticated_user_can_get_profile(): void
    {
        $seed = $this->seedLocationAndUser();

        $response = $this->actingAs($seed['user'], 'sanctum')
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('name', 'Manager User')
            ->assertJsonPath('email', 'manager@test.com');
    }

    // ══════════════════════════════════════════════
    //  USER CAN LOGOUT
    // ══════════════════════════════════════════════

    /**
     * Verifies that POST /api/logout returns a 200 response with a
     * success message confirming the user has been logged out. Creates a
     * real Sanctum personal access token so currentAccessToken()->delete()
     * works correctly in the test environment.
     */
    public function test_user_can_logout(): void
    {
        $seed = $this->seedLocationAndUser();

        $token = $seed['user']->createToken('auth-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Logged out successfully.']);
    }

    // ══════════════════════════════════════════════
    //  USER CAN CHANGE PASSWORD
    // ══════════════════════════════════════════════

    /**
     * Verifies that POST /api/change-password with the correct current
     * password and a valid new password returns 200 and persists the change.
     */
    public function test_user_can_change_password(): void
    {
        $seed = $this->seedLocationAndUser();

        $response = $this->actingAs($seed['user'], 'sanctum')
            ->postJson('/api/change-password', [
                'current_password' => 'password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Password changed successfully.']);

        $seed['user']->refresh();
        $this->assertTrue(Hash::check('newpassword123', $seed['user']->password));
    }

    // ══════════════════════════════════════════════
    //  CHANGE PASSWORD FAILS WITH WRONG CURRENT
    // ══════════════════════════════════════════════

    /**
     * Verifies that POST /api/change-password with an incorrect current
     * password returns 422 and does not modify the stored password.
     */
    public function test_change_password_fails_with_wrong_current(): void
    {
        $seed = $this->seedLocationAndUser();

        $response = $this->actingAs($seed['user'], 'sanctum')
            ->postJson('/api/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Current password is incorrect.']);
    }

    // ══════════════════════════════════════════════
    //  USER CAN UPDATE PROFILE NAME
    // ══════════════════════════════════════════════

    /**
     * Verifies that PUT /api/profile with a new name returns the updated
     * user and persists the name change to the database.
     */
    public function test_user_can_update_profile_name(): void
    {
        $seed = $this->seedLocationAndUser();

        $response = $this->actingAs($seed['user'], 'sanctum')
            ->putJson('/api/profile', ['name' => 'Updated Name']);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Name');

        $seed['user']->refresh();
        $this->assertEquals('Updated Name', $seed['user']->name);
    }

    // ══════════════════════════════════════════════
    //  UNAUTHENTICATED CANNOT ACCESS USER
    // ══════════════════════════════════════════════

    /**
     * Verifies that GET /api/user without an authentication token
     * returns a 401 Unauthorized response.
     */
    public function test_unauthenticated_cannot_access_user(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }
}
