<?php

/**
 * PreShiftTest
 *
 * Feature tests for the GET /api/preshift aggregated dashboard endpoint.
 *
 * Tests verify:
 *   1. A server-role user can access the preshift endpoint and receives all 6 data keys.
 *   2. Data is scoped to the authenticated user's location (cross-location isolation).
 *   3. Role-filtered announcements are respected — a server does not see bartender-only
 *      announcements, but a bartender does.
 *   4. Unauthenticated requests are rejected with a 401 status.
 */

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\EightySixed;
use App\Models\Event;
use App\Models\Location;
use App\Models\PushItem;
use App\Models\Special;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PreShiftTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // Helper: seed a location + manager + staff user
    // ──────────────────────────────────────────────

    /**
     * Create a location with a manager and a server user for testing.
     *
     * @return array{location: Location, manager: User, staff: User}
     */
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
    //  STAFF CAN ACCESS PRESHIFT
    // ══════════════════════════════════════════════

    /**
     * Verifies that a server-role user can GET /api/preshift and the response
     * contains all six expected top-level keys: eighty_sixed, specials,
     * push_items, announcements, events, and acknowledgments.
     */
    public function test_staff_can_access_preshift(): void
    {
        EventFacade::fake();
        $seed = $this->seedLocationAndUsers();

        EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Salmon',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        Special::create([
            'location_id' => $seed['location']->id,
            'title' => 'Half-Price Wings',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        PushItem::create([
            'location_id' => $seed['location']->id,
            'title' => 'Push the Lobster Risotto',
            'priority' => 'medium',
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        Announcement::create([
            'location_id' => $seed['location']->id,
            'title' => 'New Uniform Policy',
            'body' => 'All staff must wear black shoes starting next week.',
            'priority' => 'normal',
            'posted_by' => $seed['manager']->id,
            'expires_at' => now()->addDays(7),
        ]);

        Event::create([
            'location_id' => $seed['location']->id,
            'title' => 'Wine Tasting',
            'event_date' => now()->toDateString(),
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->getJson('/api/preshift');

        $response->assertOk()
            ->assertJsonStructure([
                'eighty_sixed',
                'specials',
                'push_items',
                'announcements',
                'events',
                'acknowledgments',
            ]);
    }

    // ══════════════════════════════════════════════
    //  PRESHIFT RETURNS LOCATION-SCOPED DATA
    // ══════════════════════════════════════════════

    /**
     * Verifies that the preshift endpoint only returns data belonging to the
     * authenticated user's location. Creates data for two locations and asserts
     * that a user at location A does not see location B's records.
     */
    public function test_preshift_returns_location_scoped_data(): void
    {
        EventFacade::fake();
        $seed = $this->seedLocationAndUsers();

        // Create a second location with its own manager
        $locationB = Location::create([
            'name' => 'Other Location',
            'address' => '456 Elm St',
            'timezone' => 'America/Chicago',
        ]);

        $managerB = User::create([
            'name' => 'Manager B',
            'email' => 'managerb@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $locationB->id,
        ]);

        // Create an 86'd item at location A
        EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Salmon',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        // Create an 86'd item at location B — should NOT appear for location A users
        EightySixed::create([
            'location_id' => $locationB->id,
            'item_name' => 'Tuna',
            'eighty_sixed_by' => $managerB->id,
        ]);

        // Create a special at location B — should NOT appear for location A users
        Special::create([
            'location_id' => $locationB->id,
            'title' => 'Location B Special',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'is_active' => true,
            'created_by' => $managerB->id,
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->getJson('/api/preshift');

        $response->assertOk();

        // Verify location A's 86'd item is present
        $eightySixedNames = collect($response->json('eighty_sixed'))->pluck('item_name')->all();
        $this->assertContains('Salmon', $eightySixedNames);
        $this->assertNotContains('Tuna', $eightySixedNames);

        // Verify location B's special is not present
        $specialTitles = collect($response->json('specials'))->pluck('title')->all();
        $this->assertNotContains('Location B Special', $specialTitles);
    }

    // ══════════════════════════════════════════════
    //  PRESHIFT RETURNS ROLE-FILTERED ANNOUNCEMENTS
    // ══════════════════════════════════════════════

    /**
     * Verifies that announcements with target_roles are filtered by the user's role.
     * A bartender-only announcement should not appear for a server, but should
     * appear for a bartender at the same location.
     */
    public function test_preshift_returns_role_filtered_announcements(): void
    {
        EventFacade::fake();
        $seed = $this->seedLocationAndUsers();

        // Create a bartender user at the same location
        $bartender = User::create([
            'name' => 'Bartender User',
            'email' => 'bartender@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $seed['location']->id,
        ]);

        // Create an announcement targeted only at bartenders
        Announcement::create([
            'location_id' => $seed['location']->id,
            'title' => 'New Cocktail Menu',
            'body' => 'Review the updated cocktail recipes before your shift.',
            'priority' => 'normal',
            'target_roles' => ['bartender'],
            'posted_by' => $seed['manager']->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Server should NOT see the bartender-only announcement
        $serverResponse = $this->actingAs($seed['staff'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->getJson('/api/preshift');

        $serverResponse->assertOk();
        $serverTitles = collect($serverResponse->json('announcements'))->pluck('title')->all();
        $this->assertNotContains('New Cocktail Menu', $serverTitles);

        // Bartender SHOULD see the announcement
        $bartenderResponse = $this->actingAs($bartender, 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->getJson('/api/preshift');

        $bartenderResponse->assertOk();
        $bartenderTitles = collect($bartenderResponse->json('announcements'))->pluck('title')->all();
        $this->assertContains('New Cocktail Menu', $bartenderTitles);
    }

    // ══════════════════════════════════════════════
    //  UNAUTHENTICATED USER CANNOT ACCESS PRESHIFT
    // ══════════════════════════════════════════════

    /**
     * Verifies that an unauthenticated request to GET /api/preshift is rejected
     * with a 401 Unauthenticated status code.
     */
    public function test_unauthenticated_user_cannot_access_preshift(): void
    {
        $response = $this->getJson('/api/preshift');

        $response->assertStatus(401);
    }
}
