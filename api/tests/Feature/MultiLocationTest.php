<?php

/**
 * MultiLocationTest
 *
 * Feature tests for the multi-location login flow and data isolation.
 *
 * Tests verify:
 *   1. Login with multiple locations returns all the user's establishments.
 *   2. Login with a single location returns only that one establishment.
 *   3. A user only sees their OWN locations — not every location in the system.
 *   4. GET /api/user also returns only the user's own locations.
 *   5. After switching to Location A, 86'd items are scoped to A only.
 *   6. After switching to Location B, 86'd items are scoped to B only.
 *   7. Specials are scoped to the user's active location after switching.
 *   8. Push items are scoped to the user's active location after switching.
 *   9. Announcements are scoped to the user's active location after switching.
 *  10. A user cannot switch to a location they have no membership at.
 *  11. The preshift hero endpoint returns only the active location's content.
 *  12. Menu items are scoped to the active location.
 */

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\EightySixed;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\Category;
use App\Models\PushItem;
use App\Models\Special;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiLocationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed two locations with distinct content, plus three users:
     *   - admin:   memberships at BOTH locations (manager at A, manager at B)
     *   - managerA: membership at Location A only
     *   - serverB:  membership at Location B only
     *
     * Each location gets its own category, menu item, 86'd item, special,
     * push item, and announcement so we can verify data isolation.
     *
     * @return array{locationA: Location, locationB: Location, admin: User, managerA: User, serverB: User}
     */
    private function seedTwoLocationsWithContent(): array
    {
        // ── Locations ──
        $locationA = Location::create([
            'name'     => 'Bald Bar',
            'address'  => '401 N Wabash Ave',
            'timezone' => 'America/Chicago',
        ]);

        $locationB = Location::create([
            'name'     => 'TITZ',
            'address'  => '1200 N Lake Shore Dr',
            'timezone' => 'America/Chicago',
        ]);

        // ── Users ──
        $admin = User::create([
            'name'          => 'BM',
            'email'         => 'bm@preshift.test',
            'password'      => Hash::make('password'),
            'role'          => 'admin',
            'location_id'   => $locationA->id,
            'is_superadmin' => true,
        ]);
        $admin->locations()->attach($locationA->id, ['role' => 'admin']);
        $admin->locations()->attach($locationB->id, ['role' => 'admin']);

        $managerA = User::create([
            'name'        => 'Lisa Mercury',
            'email'       => 'mercury@preshift.test',
            'password'    => Hash::make('password'),
            'role'        => 'manager',
            'location_id' => $locationA->id,
        ]);
        $managerA->locations()->attach($locationA->id, ['role' => 'manager']);

        $serverB = User::create([
            'name'        => 'Sam Presley',
            'email'       => 'presley@preshift.test',
            'password'    => Hash::make('password'),
            'role'        => 'server',
            'location_id' => $locationB->id,
        ]);
        $serverB->locations()->attach($locationB->id, ['role' => 'server']);

        // ── Location A content ──
        $catA = Category::create(['location_id' => $locationA->id, 'name' => 'Appetizers', 'sort_order' => 1]);
        $menuItemA = MenuItem::create([
            'location_id' => $locationA->id,
            'category_id' => $catA->id,
            'name'        => 'Buffalo Wings',
            'description' => 'House buffalo sauce',
            'price'       => 14.99,
            'type'        => 'food',
        ]);
        $eightySixedA = EightySixed::create([
            'location_id'     => $locationA->id,
            'menu_item_id'    => $menuItemA->id,
            'item_name'       => 'Buffalo Wings',
            'reason'          => 'Sold out',
            'eighty_sixed_by' => $managerA->id,
        ]);
        $specialA = Special::create([
            'location_id'  => $locationA->id,
            'menu_item_id' => $menuItemA->id,
            'title'        => 'Half-Price Wings',
            'description'  => 'Wings deal at Bald Bar',
            'type'         => 'daily',
            'starts_at'    => '2026-01-01',
            'created_by'   => $managerA->id,
        ]);
        $pushItemA = PushItem::create([
            'location_id'  => $locationA->id,
            'menu_item_id' => $menuItemA->id,
            'title'        => 'Push Wings',
            'description'  => 'Upsell wings at Bald Bar',
            'reason'       => 'Overstock',
            'priority'     => 'high',
            'created_by'   => $managerA->id,
        ]);
        $announcementA = Announcement::create([
            'location_id'  => $locationA->id,
            'title'        => 'Bald Bar Staff Meeting',
            'body'         => 'Meeting at 3 PM.',
            'priority'     => 'normal',
            'posted_by'    => $managerA->id,
            'expires_at'   => now()->addDays(7),
        ]);

        // ── Location B content ──
        $catB = Category::create(['location_id' => $locationB->id, 'name' => 'Entrees', 'sort_order' => 1]);
        $menuItemB = MenuItem::create([
            'location_id' => $locationB->id,
            'category_id' => $catB->id,
            'name'        => 'Ribeye Steak',
            'description' => 'USDA Choice',
            'price'       => 34.99,
            'type'        => 'food',
        ]);
        $eightySixedB = EightySixed::create([
            'location_id'     => $locationB->id,
            'menu_item_id'    => $menuItemB->id,
            'item_name'       => 'Ribeye Steak',
            'reason'          => 'Supplier issue',
            'eighty_sixed_by' => $admin->id,
        ]);
        $specialB = Special::create([
            'location_id'  => $locationB->id,
            'menu_item_id' => $menuItemB->id,
            'title'        => 'Steak Night',
            'description'  => 'Steak deal at TITZ',
            'type'         => 'weekly',
            'starts_at'    => '2026-01-01',
            'created_by'   => $admin->id,
        ]);
        $pushItemB = PushItem::create([
            'location_id'  => $locationB->id,
            'menu_item_id' => $menuItemB->id,
            'title'        => 'Push Steak',
            'description'  => 'Upsell steak at TITZ',
            'reason'       => 'High margin',
            'priority'     => 'medium',
            'created_by'   => $admin->id,
        ]);
        $announcementB = Announcement::create([
            'location_id'  => $locationB->id,
            'title'        => 'TITZ Grand Opening',
            'body'         => 'Opening night Friday.',
            'priority'     => 'important',
            'posted_by'    => $admin->id,
            'expires_at'   => now()->addDays(7),
        ]);

        return compact(
            'locationA', 'locationB',
            'admin', 'managerA', 'serverB',
            'menuItemA', 'menuItemB',
            'eightySixedA', 'eightySixedB',
            'specialA', 'specialB',
            'pushItemA', 'pushItemB',
            'announcementA', 'announcementB',
        );
    }

    // ══════════════════════════════════════════════
    //  LOGIN — MULTI-LOCATION USER SEES ALL THEIR LOCATIONS
    // ══════════════════════════════════════════════

    /**
     * Verifies that an admin with memberships at two locations receives
     * both locations in the login response, triggering the location picker.
     */
    public function test_login_with_multiple_locations_returns_all_user_locations(): void
    {
        $seed = $this->seedTwoLocationsWithContent();

        $response = $this->postJson('/api/login', [
            'email'    => 'bm@preshift.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token', 'locations'])
            ->assertJsonCount(2, 'locations');

        $locationNames = collect($response->json('locations'))->pluck('name')->sort()->values();
        $this->assertEquals(['Bald Bar', 'TITZ'], $locationNames->toArray());
    }

    // ══════════════════════════════════════════════
    //  LOGIN — SINGLE-LOCATION USER SEES ONLY THEIRS
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager with membership at only one location receives
     * exactly one location in the login response (no picker needed).
     */
    public function test_login_with_single_location_returns_only_that_location(): void
    {
        $seed = $this->seedTwoLocationsWithContent();

        $response = $this->postJson('/api/login', [
            'email'    => 'mercury@preshift.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'locations')
            ->assertJsonPath('locations.0.name', 'Bald Bar')
            ->assertJsonPath('locations.0.role', 'manager');
    }

    // ══════════════════════════════════════════════
    //  LOGIN — USER ONLY SEES THEIR OWN LOCATIONS
    // ══════════════════════════════════════════════

    /**
     * Verifies that a server at Location B only sees Location B in the
     * login response — NOT Location A, even though it exists in the system.
     */
    public function test_user_only_sees_own_locations_not_all_system_locations(): void
    {
        $seed = $this->seedTwoLocationsWithContent();

        $response = $this->postJson('/api/login', [
            'email'    => 'presley@preshift.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'locations')
            ->assertJsonPath('locations.0.name', 'TITZ')
            ->assertJsonPath('locations.0.role', 'server');

        // Ensure Location A is NOT in the response
        $locationNames = collect($response->json('locations'))->pluck('name');
        $this->assertNotContains('Bald Bar', $locationNames);
    }

    // ══════════════════════════════════════════════
    //  GET /api/user — ALSO RETURNS ONLY OWN LOCATIONS
    // ══════════════════════════════════════════════

    /**
     * Verifies that GET /api/user (page reload / token rehydration) also
     * returns only the authenticated user's own location memberships.
     */
    public function test_get_user_returns_only_own_locations(): void
    {
        $seed = $this->seedTwoLocationsWithContent();

        // Multi-location admin sees both
        $response = $this->actingAs($seed['admin'], 'sanctum')
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonCount(2, 'locations');

        // Single-location manager sees only theirs
        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonCount(1, 'locations')
            ->assertJsonPath('locations.0.name', 'Bald Bar');
    }

    // ══════════════════════════════════════════════
    //  86'D ITEMS — SCOPED TO ACTIVE LOCATION
    // ══════════════════════════════════════════════

    /**
     * Verifies that after the admin switches to Location A, GET /api/eighty-sixed
     * returns only Location A's 86'd items and not Location B's.
     */
    public function test_eighty_sixed_scoped_to_active_location_A(): void
    {
        $seed = $this->seedTwoLocationsWithContent();
        $admin = $seed['admin'];

        // Admin starts at Location A
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/eighty-sixed');

        $response->assertOk();
        $items = collect($response->json('data') ?? $response->json());
        $names = $items->pluck('item_name');

        $this->assertTrue($names->contains('Buffalo Wings'));
        $this->assertFalse($names->contains('Ribeye Steak'));
    }

    /**
     * Verifies that after the admin switches to Location B, GET /api/eighty-sixed
     * returns only Location B's 86'd items and not Location A's.
     */
    public function test_eighty_sixed_scoped_to_active_location_B(): void
    {
        $seed = $this->seedTwoLocationsWithContent();
        $admin = $seed['admin'];

        // Switch to Location B
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $seed['locationB']->id,
            ])
            ->assertOk();

        $admin->refresh();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/eighty-sixed');

        $response->assertOk();
        $items = collect($response->json('data') ?? $response->json());
        $names = $items->pluck('item_name');

        $this->assertTrue($names->contains('Ribeye Steak'));
        $this->assertFalse($names->contains('Buffalo Wings'));
    }

    // ══════════════════════════════════════════════
    //  SPECIALS — SCOPED TO ACTIVE LOCATION
    // ══════════════════════════════════════════════

    /**
     * Verifies that specials are scoped to the admin's active location
     * after switching. Location A's specials should not appear at B.
     */
    public function test_specials_scoped_to_active_location(): void
    {
        $seed = $this->seedTwoLocationsWithContent();
        $admin = $seed['admin'];

        // At Location A → should see "Half-Price Wings"
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/specials');

        $response->assertOk();
        $titles = collect($response->json('data') ?? $response->json())->pluck('title');
        $this->assertTrue($titles->contains('Half-Price Wings'));
        $this->assertFalse($titles->contains('Steak Night'));

        // Switch to Location B → should see "Steak Night"
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $seed['locationB']->id,
            ]);

        $admin->refresh();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/specials');

        $response->assertOk();
        $titles = collect($response->json('data') ?? $response->json())->pluck('title');
        $this->assertTrue($titles->contains('Steak Night'));
        $this->assertFalse($titles->contains('Half-Price Wings'));
    }

    // ══════════════════════════════════════════════
    //  PUSH ITEMS — SCOPED TO ACTIVE LOCATION
    // ══════════════════════════════════════════════

    /**
     * Verifies that push items are scoped to the admin's active location.
     */
    public function test_push_items_scoped_to_active_location(): void
    {
        $seed = $this->seedTwoLocationsWithContent();
        $admin = $seed['admin'];

        // At Location A → should see "Push Wings"
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/push-items');

        $response->assertOk();
        $titles = collect($response->json('data') ?? $response->json())->pluck('title');
        $this->assertTrue($titles->contains('Push Wings'));
        $this->assertFalse($titles->contains('Push Steak'));

        // Switch to Location B → should see "Push Steak"
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $seed['locationB']->id,
            ]);

        $admin->refresh();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/push-items');

        $response->assertOk();
        $titles = collect($response->json('data') ?? $response->json())->pluck('title');
        $this->assertTrue($titles->contains('Push Steak'));
        $this->assertFalse($titles->contains('Push Wings'));
    }

    // ══════════════════════════════════════════════
    //  ANNOUNCEMENTS — SCOPED TO ACTIVE LOCATION
    // ══════════════════════════════════════════════

    /**
     * Verifies that announcements are scoped to the admin's active location.
     */
    public function test_announcements_scoped_to_active_location(): void
    {
        $seed = $this->seedTwoLocationsWithContent();
        $admin = $seed['admin'];

        // At Location A → should see "Bald Bar Staff Meeting"
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/announcements');

        $response->assertOk();
        $titles = collect($response->json('data') ?? $response->json())->pluck('title');
        $this->assertTrue($titles->contains('Bald Bar Staff Meeting'));
        $this->assertFalse($titles->contains('TITZ Grand Opening'));

        // Switch to Location B → should see "TITZ Grand Opening"
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $seed['locationB']->id,
            ]);

        $admin->refresh();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/announcements');

        $response->assertOk();
        $titles = collect($response->json('data') ?? $response->json())->pluck('title');
        $this->assertTrue($titles->contains('TITZ Grand Opening'));
        $this->assertFalse($titles->contains('Bald Bar Staff Meeting'));
    }

    // ══════════════════════════════════════════════
    //  CANNOT SWITCH TO A LOCATION WITHOUT MEMBERSHIP
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager at Location A cannot switch to Location B
     * where they have no pivot membership (403 Forbidden).
     */
    public function test_user_cannot_switch_to_location_without_membership(): void
    {
        $seed = $this->seedTwoLocationsWithContent();

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $seed['locationB']->id,
            ]);

        $response->assertStatus(403);

        // Verify location_id was NOT changed
        $seed['managerA']->refresh();
        $this->assertEquals($seed['locationA']->id, $seed['managerA']->location_id);
    }

    // ══════════════════════════════════════════════
    //  PRESHIFT HERO — SCOPED TO ACTIVE LOCATION
    // ══════════════════════════════════════════════

    /**
     * Verifies that GET /api/preshift returns only content from the user's
     * active location. The preshift endpoint aggregates 86'd items, specials,
     * push items, and announcements — all must be scoped.
     */
    public function test_preshift_returns_only_active_location_content(): void
    {
        $seed = $this->seedTwoLocationsWithContent();
        $admin = $seed['admin'];

        // At Location A
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/preshift');

        $response->assertOk();
        $json = $response->json();

        // 86'd items from A
        $eightySixedNames = collect($json['eighty_sixed'] ?? [])->pluck('item_name');
        $this->assertTrue($eightySixedNames->contains('Buffalo Wings'));
        $this->assertFalse($eightySixedNames->contains('Ribeye Steak'));

        // Specials from A
        $specialTitles = collect($json['specials'] ?? [])->pluck('title');
        $this->assertTrue($specialTitles->contains('Half-Price Wings'));
        $this->assertFalse($specialTitles->contains('Steak Night'));

        // Switch to Location B and check again
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $seed['locationB']->id,
            ]);

        $admin->refresh();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/preshift');

        $response->assertOk();
        $json = $response->json();

        // 86'd items from B
        $eightySixedNames = collect($json['eighty_sixed'] ?? [])->pluck('item_name');
        $this->assertTrue($eightySixedNames->contains('Ribeye Steak'));
        $this->assertFalse($eightySixedNames->contains('Buffalo Wings'));

        // Specials from B
        $specialTitles = collect($json['specials'] ?? [])->pluck('title');
        $this->assertTrue($specialTitles->contains('Steak Night'));
        $this->assertFalse($specialTitles->contains('Half-Price Wings'));
    }

    // ══════════════════════════════════════════════
    //  MENU ITEMS — SCOPED TO ACTIVE LOCATION
    // ══════════════════════════════════════════════

    /**
     * Verifies that GET /api/menu-items returns only the active location's
     * menu items, not items from other locations.
     */
    public function test_menu_items_scoped_to_active_location(): void
    {
        $seed = $this->seedTwoLocationsWithContent();
        $admin = $seed['admin'];

        // At Location A → should see "Buffalo Wings"
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/menu-items');

        $response->assertOk();
        $names = collect($response->json('data') ?? $response->json())->pluck('name');
        $this->assertTrue($names->contains('Buffalo Wings'));
        $this->assertFalse($names->contains('Ribeye Steak'));

        // Switch to Location B → should see "Ribeye Steak"
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/switch-location', [
                'location_id' => $seed['locationB']->id,
            ]);

        $admin->refresh();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/menu-items');

        $response->assertOk();
        $names = collect($response->json('data') ?? $response->json())->pluck('name');
        $this->assertTrue($names->contains('Ribeye Steak'));
        $this->assertFalse($names->contains('Buffalo Wings'));
    }
}
