<?php

/**
 * OrganizationTest
 *
 * Feature tests for the organization layer that groups locations under
 * a single business entity.
 *
 * Tests verify:
 *   1. Admin users see only their org's locations in the index.
 *   2. SuperAdmins see all locations across all orgs.
 *   3. Admin can switch to any location within their org.
 *   4. Admin cannot switch to a location outside their org.
 *   5. Staff switching requires a pivot membership.
 *   6. User creation sets organization_id and creates a pivot row.
 *   7. Setup creates an org + location together.
 *   8. Access pending: user with no pivot gets 403 from EnsureLocationAccess.
 */

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed two organizations, each with one location and an admin.
     *
     * @return array{org1: Organization, org2: Organization, loc1: Location, loc2: Location, admin1: User, admin2: User}
     */
    private function seedTwoOrgs(): array
    {
        $org1 = Organization::create(['name' => 'Org Alpha']);
        $org2 = Organization::create(['name' => 'Org Beta']);

        $loc1 = Location::create([
            'organization_id' => $org1->id,
            'name' => 'Alpha Bar',
            'timezone' => 'America/Chicago',
        ]);

        $loc2 = Location::create([
            'organization_id' => $org2->id,
            'name' => 'Beta Lounge',
            'timezone' => 'America/New_York',
        ]);

        $admin1 = User::create([
            'name' => 'Admin Alpha',
            'email' => 'alpha@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $loc1->id,
            'organization_id' => $org1->id,
            'is_superadmin' => true,
        ]);
        $admin1->locations()->attach($loc1->id, ['role' => 'admin']);

        $admin2 = User::create([
            'name' => 'Admin Beta',
            'email' => 'beta@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $loc2->id,
            'organization_id' => $org2->id,
        ]);
        $admin2->locations()->attach($loc2->id, ['role' => 'admin']);

        return compact('org1', 'org2', 'loc1', 'loc2', 'admin1', 'admin2');
    }

    // ══════════════════════════════════════════════
    //  ORG-SCOPED LOCATION LISTING
    // ══════════════════════════════════════════════

    /**
     * Verifies that an admin only sees locations within their own org
     * from the location index endpoint.
     */
    public function test_admin_sees_only_their_org_locations(): void
    {
        $seed = $this->seedTwoOrgs();

        $response = $this->actingAs($seed['admin2'], 'sanctum')
            ->getJson('/api/locations');

        $response->assertOk();

        $names = collect($response->json())->pluck('name')->all();
        $this->assertContains('Beta Lounge', $names);
        $this->assertNotContains('Alpha Bar', $names);
    }

    // ══════════════════════════════════════════════
    //  SUPERADMIN SEES ALL LOCATIONS
    // ══════════════════════════════════════════════

    /**
     * Verifies that a superadmin sees all locations across all orgs.
     */
    public function test_superadmin_sees_all_locations(): void
    {
        $seed = $this->seedTwoOrgs();

        $response = $this->actingAs($seed['admin1'], 'sanctum')
            ->getJson('/api/locations');

        $response->assertOk();

        $names = collect($response->json())->pluck('name')->all();
        $this->assertContains('Alpha Bar', $names);
        $this->assertContains('Beta Lounge', $names);
    }

    // ══════════════════════════════════════════════
    //  SWITCH WITHIN ORG SUCCEEDS
    // ══════════════════════════════════════════════

    /**
     * Verifies that an admin can switch to any location within their org,
     * even without an explicit pivot row (one is auto-created).
     */
    public function test_admin_can_switch_within_org(): void
    {
        $org = Organization::create(['name' => 'Multi Loc Org']);

        $loc1 = Location::create([
            'organization_id' => $org->id,
            'name' => 'Spot A',
            'timezone' => 'America/Chicago',
        ]);

        $loc2 = Location::create([
            'organization_id' => $org->id,
            'name' => 'Spot B',
            'timezone' => 'America/Chicago',
        ]);

        $admin = User::create([
            'name' => 'Org Admin',
            'email' => 'orgadmin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $loc1->id,
            'organization_id' => $org->id,
        ]);
        $admin->locations()->attach($loc1->id, ['role' => 'admin']);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $loc2->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('user.location_id', $loc2->id);
    }

    // ══════════════════════════════════════════════
    //  SWITCH OUTSIDE ORG FAILS FOR ADMIN
    // ══════════════════════════════════════════════

    /**
     * Verifies that a non-superadmin admin cannot switch to a location
     * outside their organization.
     */
    public function test_admin_cannot_switch_outside_org(): void
    {
        $seed = $this->seedTwoOrgs();

        $response = $this->actingAs($seed['admin2'], 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $seed['loc1']->id,
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  STAFF SWITCH REQUIRES PIVOT
    // ══════════════════════════════════════════════

    /**
     * Verifies that a staff member can only switch to locations where
     * they have an explicit pivot membership.
     */
    public function test_staff_switch_requires_pivot(): void
    {
        $org = Organization::create(['name' => 'Test Org']);

        $loc1 = Location::create([
            'organization_id' => $org->id,
            'name' => 'Staff Bar 1',
            'timezone' => 'America/Chicago',
        ]);

        $loc2 = Location::create([
            'organization_id' => $org->id,
            'name' => 'Staff Bar 2',
            'timezone' => 'America/Chicago',
        ]);

        $server = User::create([
            'name' => 'Test Server',
            'email' => 'server@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $loc1->id,
            'organization_id' => $org->id,
        ]);
        $server->locations()->attach($loc1->id, ['role' => 'server']);

        // Staff should NOT be able to switch to loc2 without pivot
        $response = $this->actingAs($server, 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $loc2->id,
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  USER CREATION SETS ORG + PIVOT
    // ══════════════════════════════════════════════

    /**
     * Verifies that creating a new user via UserController::store sets
     * the organization_id and creates a pivot row.
     */
    public function test_user_creation_sets_org_and_pivot(): void
    {
        $seed = $this->seedTwoOrgs();

        $response = $this->actingAs($seed['admin2'], 'sanctum')
            ->postJson('/api/users', [
                'name' => 'New Employee',
                'email' => 'newbie@test.com',
                'password' => 'password123',
                'role' => 'server',
            ]);

        $response->assertStatus(201);

        $newUser = User::where('email', 'newbie@test.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals($seed['org2']->id, $newUser->organization_id);
        $this->assertTrue($newUser->locations()->where('location_id', $seed['loc2']->id)->exists());
    }

    // ══════════════════════════════════════════════
    //  SETUP CREATES ORG + LOCATION
    // ══════════════════════════════════════════════

    /**
     * Verifies that the setup endpoint creates an organization and a location
     * together, and assigns both to the user.
     */
    public function test_setup_creates_org_and_location(): void
    {
        $admin = User::create([
            'name' => 'Fresh Admin',
            'email' => 'fresh@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => null,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/setup', [
                'organization_name' => 'New Biz',
                'name' => 'New Bar',
                'city' => 'Chicago',
                'state' => 'IL',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'locations']);

        $admin->refresh();
        $this->assertNotNull($admin->organization_id);
        $this->assertNotNull($admin->location_id);

        $org = Organization::find($admin->organization_id);
        $this->assertEquals('New Biz', $org->name);

        $loc = Location::find($admin->location_id);
        $this->assertEquals($org->id, $loc->organization_id);
        $this->assertEquals('New Bar', $loc->name);
    }

    // ══════════════════════════════════════════════
    //  ACCESS PENDING — NO PIVOT GETS 403
    // ══════════════════════════════════════════════

    /**
     * Verifies that a non-admin user with no location assignment gets
     * a 403 from the EnsureLocationAccess middleware.
     */
    public function test_user_with_no_location_gets_403(): void
    {
        $server = User::create([
            'name' => 'Pending Server',
            'email' => 'pending@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => null,
        ]);

        $response = $this->actingAs($server, 'sanctum')
            ->getJson('/api/preshift');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'No location assigned.');
    }

    // ══════════════════════════════════════════════
    //  LOGIN RETURNS ORG-SCOPED LOCATIONS
    // ══════════════════════════════════════════════

    /**
     * Verifies that login returns locations scoped to the user's org
     * for admin users.
     */
    public function test_login_returns_org_scoped_locations_for_admin(): void
    {
        $seed = $this->seedTwoOrgs();

        $response = $this->postJson('/api/login', [
            'email' => 'beta@test.com',
            'password' => 'password',
        ]);

        $response->assertOk();

        $locationNames = collect($response->json('locations'))->pluck('name')->all();
        $this->assertContains('Beta Lounge', $locationNames);
        $this->assertNotContains('Alpha Bar', $locationNames);
    }

    /**
     * Verifies that login returns all locations for superadmin users.
     */
    public function test_login_returns_all_locations_for_superadmin(): void
    {
        $seed = $this->seedTwoOrgs();

        $response = $this->postJson('/api/login', [
            'email' => 'alpha@test.com',
            'password' => 'password',
        ]);

        $response->assertOk();

        $locationNames = collect($response->json('locations'))->pluck('name')->all();
        $this->assertContains('Alpha Bar', $locationNames);
        $this->assertContains('Beta Lounge', $locationNames);
    }
}
