<?php

namespace Tests\Unit;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Unit tests for the CheckRole and EnsureLocationAccess middleware.
 *
 * These tests exercise the middleware by sending HTTP requests to actual routes
 * that use the middleware, and verifying that the expected status codes are
 * returned based on the user's role and location assignment.
 *
 * This approach (HTTP integration style) is simpler and more realistic than
 * manually instantiating middleware classes and crafting Request objects. It
 * catches real-world issues like middleware ordering, Sanctum guard behavior,
 * and JSON response formatting.
 *
 * RefreshDatabase is used so each test starts with a clean database.
 */
class MiddlewareTest extends TestCase
{
    /**
     * RefreshDatabase wraps each test in a transaction and rolls it back after,
     * giving every test method a pristine database to work with.
     */
    use RefreshDatabase;

    // =========================================================================
    //  SHARED HELPERS
    // =========================================================================

    /**
     * Create a Location record that user models can reference via location_id.
     * Nearly every test needs a location because the middleware checks
     * the user's location_id attribute.
     *
     * @return Location  The freshly created Location instance.
     */
    private function createLocation(): Location
    {
        return Location::create([
            'name'     => 'Test Location',
            'address'  => '123 Main St',
            'timezone' => 'America/New_York',
        ]);
    }

    /**
     * Create a User record with the given role and optional location.
     *
     * @param  string        $role       One of: admin, manager, server, bartender.
     * @param  Location|null $location   The location to assign; null for admins.
     * @param  array         $overrides  Additional attribute overrides.
     * @return User          The freshly created User instance.
     */
    private function createUser(string $role, ?Location $location = null, array $overrides = []): User
    {
        return User::create(array_merge([
            'name'        => ucfirst($role) . ' User',
            'email'       => $role . '_' . uniqid() . '@example.com',
            'password'    => 'password',     // auto-hashed by the 'hashed' cast
            'role'        => $role,
            'location_id' => $location?->id, // null for admins is valid (nullable FK)
        ], $overrides));
    }

    // =========================================================================
    //  CHECK ROLE MIDDLEWARE TESTS
    // =========================================================================
    //
    //  The CheckRole middleware is aliased as 'role' and used on routes like:
    //      Route::post('/eighty-sixed', ...)->middleware('role:admin,manager')
    //      Route::middleware('role:admin')->group(...)
    //
    //  It checks $request->user()->role against the allowed roles and returns
    //  403 if the user's role is not in the list (or if there is no user).
    //
    //  We test against real routes that use this middleware:
    //    - POST /api/eighty-sixed uses role:admin,manager
    //    - GET  /api/locations    uses role:admin
    //    - GET  /api/users        uses role:admin,manager
    // =========================================================================

    /**
     * Verify that a user whose role matches the middleware's allowed roles
     * is permitted through (does NOT receive a 403).
     *
     * We use a manager hitting POST /api/eighty-sixed which requires
     * role:admin,manager. The manager role is in the allowed list, so the
     * request should proceed past the role check. We expect a 422 (validation
     * error for missing fields) rather than 403, proving the middleware
     * allowed the request through.
     */
    public function test_check_role_allows_user_with_matching_role(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser('manager', $location);

        // POST /api/eighty-sixed requires role:admin,manager.
        // Send an empty body — we expect a 422 validation error (not 403),
        // which proves the CheckRole middleware allowed the manager through.
        $response = $this->actingAs($manager, 'sanctum')
            ->postJson('/api/eighty-sixed', []);

        // Any status other than 403 means the role check passed.
        // Specifically we expect 422 because required fields are missing.
        $this->assertNotEquals(
            403,
            $response->getStatusCode(),
            'A manager should pass the role:admin,manager middleware check.'
        );
    }

    /**
     * Verify that a user whose role does NOT match the middleware's allowed
     * roles receives a 403 Forbidden response.
     *
     * We use a server (role='server') hitting POST /api/eighty-sixed which
     * only allows admin and manager. The server role is not in the list,
     * so the middleware must block them with 403.
     */
    public function test_check_role_rejects_user_with_non_matching_role(): void
    {
        $location = $this->createLocation();
        $server   = $this->createUser('server', $location);

        // POST /api/eighty-sixed requires role:admin,manager.
        // A server should be blocked by the CheckRole middleware.
        $response = $this->actingAs($server, 'sanctum')
            ->postJson('/api/eighty-sixed', [
                'item_name' => 'Should Be Blocked',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Verify that a user whose role matches ANY of several allowed roles
     * is permitted through.
     *
     * We test with an admin hitting GET /api/users, which requires
     * role:admin,manager. The admin role matches the first allowed role,
     * and the request should proceed (not 403).
     */
    public function test_check_role_allows_user_when_role_matches_any_of_multiple(): void
    {
        $location = $this->createLocation();

        // Create an admin user (admins typically have no location, but we
        // assign one here so the location middleware also passes).
        $admin = $this->createUser('admin', $location);

        // GET /api/users requires role:admin,manager + location middleware.
        // An admin's role matches "admin" in the allowed list.
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users');

        // Admin should pass the role check. We expect 200 (success) because
        // admin also passes the location middleware (admins skip location checks).
        $this->assertNotEquals(
            403,
            $response->getStatusCode(),
            'An admin should pass the role:admin,manager middleware check.'
        );
    }

    /**
     * Verify that an unauthenticated request to a role-protected route
     * is rejected.
     *
     * Without authentication (no actingAs), the middleware cannot find a
     * user and should reject the request. The exact status depends on
     * middleware ordering — auth:sanctum fires first (401), but if
     * CheckRole fires, it returns 403. Either way the request is blocked.
     */
    public function test_check_role_rejects_unauthenticated_request(): void
    {
        // GET /api/locations requires auth:sanctum and role:admin.
        // Without logging in, the request must be rejected.
        $response = $this->getJson('/api/locations');

        // The auth:sanctum middleware fires before role and returns 401.
        // But we want to verify the request is blocked regardless.
        $this->assertTrue(
            in_array($response->getStatusCode(), [401, 403]),
            'An unauthenticated request to a role-protected route must be rejected (401 or 403).'
        );
    }

    // =========================================================================
    //  ENSURE LOCATION ACCESS MIDDLEWARE TESTS
    // =========================================================================
    //
    //  The EnsureLocationAccess middleware is aliased as 'location' and applied
    //  to the large group of location-scoped routes (eighty-sixed, specials,
    //  push items, announcements, schedules, etc.).
    //
    //  Authorization logic:
    //    - No authenticated user        -> 401 Unauthenticated
    //    - Admin (any location_id)      -> allowed through (admins are global)
    //    - Non-admin with location_id   -> allowed through
    //    - Non-admin without location_id -> 403 (no location assigned)
    //
    //  We test against GET /api/eighty-sixed which sits inside the location
    //  middleware group and has no additional role restriction on the GET method.
    // =========================================================================

    /**
     * Verify that an unauthenticated request is rejected with a 401 status.
     *
     * The EnsureLocationAccess middleware checks for an authenticated user
     * first. If there is none, it returns 401 before even checking location.
     * (In practice, auth:sanctum fires first and returns 401, but the
     * location middleware also returns 401 for safety.)
     */
    public function test_location_middleware_rejects_unauthenticated_request(): void
    {
        // Hit a location-scoped endpoint without any authentication.
        $response = $this->getJson('/api/eighty-sixed');

        // Should be rejected with 401 (Unauthenticated).
        $response->assertStatus(401);
    }

    /**
     * Verify that an admin user is allowed through the location middleware
     * even if they have NO location_id assigned.
     *
     * Admins operate globally across all locations, so they should never be
     * blocked by the location check. The middleware explicitly checks
     * isAdmin() and short-circuits to allow the request.
     */
    public function test_location_middleware_allows_admin_without_location_id(): void
    {
        // Create an admin with no location (location_id = null).
        // This is the typical setup for system admins.
        $admin = $this->createUser('admin', null);

        // GET /api/eighty-sixed is inside the location middleware group.
        // Admin should pass through despite having no location_id.
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/eighty-sixed');

        // The request should not be rejected. We expect 200 (empty list) since
        // there are no 86 records. The important thing is it's NOT 401 or 403.
        $this->assertNotEquals(
            401,
            $response->getStatusCode(),
            'Admin should not receive 401 from the location middleware.'
        );
        $this->assertNotEquals(
            403,
            $response->getStatusCode(),
            'Admin should not receive 403 from the location middleware.'
        );
    }

    /**
     * Verify that a non-admin user WITHOUT a location_id is rejected with 403.
     *
     * If a manager or staff member has not been assigned to a location (their
     * location_id is null), the middleware blocks them because location-scoped
     * queries cannot function without a location context.
     */
    public function test_location_middleware_rejects_non_admin_without_location_id(): void
    {
        // Create a manager with NO location — this can happen if an admin
        // creates a user account but forgets to assign a location.
        $manager = $this->createUser('manager', null);

        // GET /api/eighty-sixed is inside the location middleware group.
        // A manager without a location_id should be rejected.
        $response = $this->actingAs($manager, 'sanctum')
            ->getJson('/api/eighty-sixed');

        $response->assertStatus(403);
    }

    /**
     * Verify that a non-admin user WITH a location_id is allowed through
     * the location middleware.
     *
     * This is the happy path for regular staff: they are authenticated AND
     * have a location assignment, so the middleware should let them access
     * location-scoped endpoints.
     */
    public function test_location_middleware_allows_non_admin_with_location_id(): void
    {
        $location = $this->createLocation();

        // Create a server assigned to the location — standard happy path.
        $server = $this->createUser('server', $location);

        // GET /api/eighty-sixed is inside the location middleware group.
        // A server with a valid location_id should pass through.
        $response = $this->actingAs($server, 'sanctum')
            ->getJson('/api/eighty-sixed');

        // Should receive a successful response (200) — not 401 or 403.
        $response->assertStatus(200);
    }
}
