<?php

namespace Tests\Feature;

use App\Models\Acknowledgment;
use App\Models\Announcement;
use App\Models\EightySixed;
use App\Models\Location;
use App\Models\PushItem;
use App\Models\Special;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SmokeTest
 *
 * End-to-end smoke tests covering the critical happy paths across every
 * major feature area of the PreShift API. Organized into sections for
 * authentication, 86'd items, specials, push items, announcements,
 * acknowledgments, the preshift hero endpoint, location isolation,
 * role-based access guards, user management, and availability.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // Helper: seed a location + manager + staff user
    // ──────────────────────────────────────────────

    private function seedLocationAndUsers(): array
    {
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        $staff = User::create([
            'name' => 'Server User',
            'email' => 'server@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        return compact('location', 'manager', 'staff');
    }

    // ══════════════════════════════════════════════
    //  AUTH TESTS
    // ══════════════════════════════════════════════

    /** Verifies that a valid login returns a user object and a Sanctum token. */
    public function test_login_returns_token(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->postJson('/api/login', [
            'email' => 'manager@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['user', 'token']);
    }

    /** Verifies that login with an incorrect password returns a 401 Unauthorized response. */
    public function test_login_fails_with_bad_credentials(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->postJson('/api/login', [
            'email' => 'manager@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    /** Verifies that logout revokes the current Sanctum token and removes it from the database. */
    public function test_logout_revokes_token(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create a real Sanctum token so currentAccessToken()->delete() works
        $token = $seed['manager']->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Logged out successfully.']);

        // Verify token was revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /** Verifies that GET /api/user returns the currently authenticated user's profile data. */
    public function test_get_user_returns_authenticated_user(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('email', 'manager@test.com')
            ->assertJsonPath('name', 'Manager User');
    }

    // ══════════════════════════════════════════════
    //  86'd TESTS
    // ══════════════════════════════════════════════

    /** Verifies that a manager can create a new 86'd item for their location. */
    public function test_create_eighty_sixed_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/eighty-sixed', [
                'item_name' => 'Salmon',
                'reason' => 'Out of stock',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('item_name', 'Salmon');
    }

    /** Verifies that the 86'd index only returns active items and excludes restored ones. */
    public function test_list_active_eighty_sixed(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        // Create an active 86'd item
        EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Tuna',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        // Create a restored (inactive) item -- should NOT appear
        EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Chicken',
            'eighty_sixed_by' => $seed['manager']->id,
            'restored_at' => now(),
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/eighty-sixed');

        $response->assertOk();

        $items = $response->json();
        $names = array_column($items, 'item_name');
        $this->assertContains('Tuna', $names);
        $this->assertNotContains('Chicken', $names);
    }

    /** Verifies that a manager can restore an 86'd item and that restored_at is set. */
    public function test_restore_eighty_sixed_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $item = EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Lobster',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/eighty-sixed/{$item->id}/restore");

        $response->assertOk();
        $this->assertNotNull($response->json('restored_at'));
    }

    // ══════════════════════════════════════════════
    //  SPECIALS TESTS
    // ══════════════════════════════════════════════

    /** Verifies that a manager can create, list, update, and delete specials for their location. */
    public function test_specials_crud(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        // Use yesterday so SQLite string comparison works with date columns
        // (SQLite stores dates as "YYYY-MM-DD 00:00:00" which is > "YYYY-MM-DD" in string sort)
        $yesterday = now()->subDay()->toDateString();

        // CREATE
        $createResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/specials', [
                'title' => 'Happy Hour',
                'description' => 'Half off drinks',
                'type' => 'daily',
                'starts_at' => $yesterday,
                'is_active' => true,
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('title', 'Happy Hour');

        $specialId = $createResponse->json('id');

        // READ (index)
        $indexResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/specials');

        $indexResponse->assertOk();
        $titles = array_column($indexResponse->json(), 'title');
        $this->assertContains('Happy Hour', $titles);

        // UPDATE
        $updateResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/specials/{$specialId}", [
                'title' => 'Super Happy Hour',
                'description' => 'Half off everything',
                'type' => 'daily',
                'starts_at' => $yesterday,
                'is_active' => true,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('title', 'Super Happy Hour');

        // DELETE
        $deleteResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/specials/{$specialId}");

        $deleteResponse->assertNoContent();
    }

    // ══════════════════════════════════════════════
    //  PUSH ITEMS TESTS
    // ══════════════════════════════════════════════

    /** Verifies that a manager can create, list, update, and delete push items for their location. */
    public function test_push_items_crud(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        // CREATE
        $createResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/push-items', [
                'title' => 'Wagyu Burger',
                'description' => 'Excess inventory',
                'reason' => 'Overstock',
                'priority' => 'high',
                'is_active' => true,
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('title', 'Wagyu Burger');

        $pushItemId = $createResponse->json('id');

        // READ (index)
        $indexResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/push-items');

        $indexResponse->assertOk();
        $titles = array_column($indexResponse->json(), 'title');
        $this->assertContains('Wagyu Burger', $titles);

        // UPDATE
        $updateResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/push-items/{$pushItemId}", [
                'title' => 'Kobe Burger',
                'description' => 'Still excess inventory',
                'reason' => 'Overstock',
                'priority' => 'medium',
                'is_active' => true,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('title', 'Kobe Burger');

        // DELETE
        $deleteResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/push-items/{$pushItemId}");

        $deleteResponse->assertNoContent();
    }

    // ══════════════════════════════════════════════
    //  ANNOUNCEMENTS TESTS
    // ══════════════════════════════════════════════

    /** Verifies that a manager can create, list, update, and delete announcements for their location. */
    public function test_announcements_crud(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        // CREATE
        $createResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/announcements', [
                'title' => 'Staff Meeting',
                'body' => 'Tomorrow at 3 PM.',
                'priority' => 'important',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('title', 'Staff Meeting');

        $announcementId = $createResponse->json('id');

        // READ (index)
        $indexResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/announcements');

        $indexResponse->assertOk();
        $titles = array_column($indexResponse->json(), 'title');
        $this->assertContains('Staff Meeting', $titles);

        // UPDATE
        $updateResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/announcements/{$announcementId}", [
                'title' => 'All-Hands Meeting',
                'body' => 'Tomorrow at 4 PM.',
                'priority' => 'urgent',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('title', 'All-Hands Meeting');

        // DELETE
        $deleteResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/announcements/{$announcementId}");

        $deleteResponse->assertNoContent();
    }

    /** Verifies that role-targeted announcements are only visible to the intended roles and hidden from others. */
    public function test_announcements_filtered_by_role(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        // Create a bartender user
        $bartender = User::create([
            'name' => 'Bartender User',
            'email' => 'bartender@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $seed['location']->id,
        ]);

        // Create an announcement targeted only at servers
        Announcement::create([
            'location_id' => $seed['location']->id,
            'title' => 'Server Only Memo',
            'body' => 'Servers must pre-bus tables.',
            'priority' => 'normal',
            'target_roles' => ['server'],
            'posted_by' => $seed['manager']->id,
        ]);

        // Bartender should NOT see it
        $bartenderResponse = $this->actingAs($bartender, 'sanctum')
            ->getJson('/api/announcements');

        $bartenderResponse->assertOk();
        $bartenderTitles = array_column($bartenderResponse->json(), 'title');
        $this->assertNotContains('Server Only Memo', $bartenderTitles);

        // Server should see it
        $serverResponse = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/announcements');

        $serverResponse->assertOk();
        $serverTitles = array_column($serverResponse->json(), 'title');
        $this->assertContains('Server Only Memo', $serverTitles);
    }

    // ══════════════════════════════════════════════
    //  ACKNOWLEDGMENTS TESTS
    // ══════════════════════════════════════════════

    /** Verifies that staff can acknowledge a content item and the acknowledgment is recorded correctly. */
    public function test_acknowledge_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $item = EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Crab Legs',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/acknowledge', [
                'type' => 'eighty_sixed',
                'id' => $item->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('acknowledgable_id', $item->id)
            ->assertJsonPath('acknowledgable_type', EightySixed::class);
    }

    /** Verifies that the acknowledgment status endpoint returns items grouped by type for the authenticated user. */
    public function test_acknowledgment_status(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $item = EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Oysters',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        // Acknowledge the item
        Acknowledgment::create([
            'user_id' => $seed['staff']->id,
            'acknowledgable_type' => EightySixed::class,
            'acknowledgable_id' => $item->id,
            'acknowledged_at' => now(),
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/acknowledgments/status');

        $response->assertOk();

        // The response is grouped by acknowledgable_type
        $data = $response->json();
        $this->assertArrayHasKey(EightySixed::class, $data);

        $ids = array_column($data[EightySixed::class], 'id');
        $this->assertContains($item->id, $ids);
    }

    // ══════════════════════════════════════════════
    //  PRESHIFT HERO
    // ══════════════════════════════════════════════

    /** Verifies that the preshift hero endpoint returns all content types in a single combined response. */
    public function test_preshift_returns_combined_data(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        // Create one of each type
        EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Mahi Mahi',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        Special::create([
            'location_id' => $seed['location']->id,
            'title' => 'Wine Wednesday',
            'type' => 'weekly',
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        PushItem::create([
            'location_id' => $seed['location']->id,
            'title' => 'Truffle Fries',
            'priority' => 'medium',
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        Announcement::create([
            'location_id' => $seed['location']->id,
            'title' => 'Welcome Back',
            'body' => 'Glad to have everyone here.',
            'priority' => 'normal',
            'posted_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/preshift');

        $response->assertOk()
            ->assertJsonStructure([
                'eighty_sixed',
                'specials',
                'push_items',
                'announcements',
                'acknowledgments',
            ]);

        $data = $response->json();
        $this->assertNotEmpty($data['eighty_sixed']);
        $this->assertNotEmpty($data['specials']);
        $this->assertNotEmpty($data['push_items']);
        $this->assertNotEmpty($data['announcements']);
    }

    // ══════════════════════════════════════════════
    //  LOCATION ISOLATION
    // ══════════════════════════════════════════════

    /** Verifies that a user in one location cannot see 86'd items belonging to a different location. */
    public function test_cannot_see_other_locations_data(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        // Create a second location with its own user
        $locationB = Location::create([
            'name' => 'Other Location',
            'address' => '456 Elm St',
            'timezone' => 'America/Chicago',
        ]);

        $userB = User::create([
            'name' => 'Other User',
            'email' => 'other@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $locationB->id,
        ]);

        // Create 86'd item in location A
        EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Secret Sauce',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        // User in location B should see nothing
        $response = $this->actingAs($userB, 'sanctum')
            ->getJson('/api/eighty-sixed');

        $response->assertOk();
        $this->assertEmpty($response->json());
    }

    // ══════════════════════════════════════════════
    //  ROLE GUARDS
    // ══════════════════════════════════════════════

    /** Verifies that staff users are forbidden from creating 86'd items, which is a manager-only action. */
    public function test_staff_cannot_create_eighty_sixed(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/eighty-sixed', [
                'item_name' => 'Forbidden Item',
            ]);

        $response->assertStatus(403);
    }

    /** Verifies that staff users are forbidden from accessing the user management endpoint. */
    public function test_staff_cannot_manage_users(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/users');

        $response->assertStatus(403);
    }

    /** Verifies that non-admin users (including managers) are forbidden from accessing location management. */
    public function test_non_admin_cannot_manage_locations(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/locations');

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  USERS (MANAGER MANAGEMENT)
    // ══════════════════════════════════════════════

    /** Verifies that a manager can create a new user assigned to their location. */
    public function test_manager_can_create_user(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/users', [
                'name' => 'New Bartender',
                'email' => 'newbartender@test.com',
                'password' => 'password123',
                'role' => 'bartender',
                'location_id' => $seed['location']->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'New Bartender')
            ->assertJsonPath('role', 'bartender');
    }

    /** Verifies that a manager can update a staff user's name and role, and that changes persist to the database. */
    public function test_manager_can_update_user(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/users/{$seed['staff']->id}", [
                'name' => 'Updated Server',
                'email' => 'server@test.com',
                'role' => 'bartender',
                'location_id' => $seed['location']->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Server')
            ->assertJsonPath('role', 'bartender');

        // Verify the change persisted
        $this->assertDatabaseHas('users', [
            'id' => $seed['staff']->id,
            'name' => 'Updated Server',
            'role' => 'bartender',
        ]);
    }

    // ══════════════════════════════════════════════
    //  MY AVAILABILITY (SELF-SERVICE)
    // ══════════════════════════════════════════════

    /**
     * Staff can update their own weekly availability via PUT /api/my-availability.
     */
    public function test_staff_can_update_own_availability(): void
    {
        $seed = $this->seedLocationAndUsers();

        $availability = [
            'monday' => ['10:30', '16:30'],
            'tuesday' => ['open'],
            'wednesday' => [],
            'thursday' => ['10:30'],
            'friday' => ['16:30'],
            'saturday' => ['open'],
            'sunday' => [],
        ];

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/my-availability', [
                'availability' => $availability,
            ]);

        $response->assertOk()
            ->assertJsonPath('availability.monday', ['10:30', '16:30'])
            ->assertJsonPath('availability.tuesday', ['open'])
            ->assertJsonPath('availability.wednesday', []);

        // Verify persistence
        $seed['staff']->refresh();
        $this->assertEquals(['10:30', '16:30'], $seed['staff']->availability['monday']);
        $this->assertEquals(['open'], $seed['staff']->availability['tuesday']);
    }

    /**
     * Availability rejects invalid slot values.
     */
    public function test_availability_rejects_invalid_slots(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->putJson('/api/my-availability', [
                'availability' => [
                    'monday' => ['invalid_slot'],
                ],
            ]);

        $response->assertStatus(422);
    }

    /**
     * Manager can set availability for a user via PATCH /api/users/{id}.
     */
    public function test_manager_can_set_user_availability(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/users/{$seed['staff']->id}", [
                'name' => $seed['staff']->name,
                'email' => $seed['staff']->email,
                'role' => $seed['staff']->role,
                'availability' => [
                    'monday' => ['open'],
                    'tuesday' => ['10:30'],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('availability.monday', ['open'])
            ->assertJsonPath('availability.tuesday', ['10:30']);
    }
}
