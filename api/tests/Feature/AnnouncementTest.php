<?php

/**
 * AnnouncementTest
 *
 * Feature tests for the AnnouncementController endpoints.
 *
 * Tests verify:
 *   1. A manager can list active announcements for their location.
 *   2. A manager can create a new announcement (POST returns 201).
 *   3. A manager can update an existing announcement (PATCH returns updated data).
 *   4. A manager can delete an announcement (DELETE returns 204).
 *   5. A staff (server) user cannot create an announcement (role guard returns 403).
 *   6. A manager at location B cannot update location A's announcement (cross-location guard returns 403).
 *   7. Role-targeted announcements filter correctly — a bartender-only announcement is
 *      visible to a bartender but hidden from a server in the GET list.
 */

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnnouncementTest extends TestCase
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
    //  MANAGER CAN LIST ANNOUNCEMENTS
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can GET /api/announcements and receive
     * a list of active, non-expired announcements for their location.
     */
    public function test_manager_can_list_announcements(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        Announcement::create([
            'location_id' => $seed['location']->id,
            'title' => 'Team Meeting Tomorrow',
            'body' => 'All hands at 3 PM in the back room.',
            'priority' => 'normal',
            'posted_by' => $seed['manager']->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->getJson('/api/announcements');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Team Meeting Tomorrow']);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN CREATE ANNOUNCEMENT
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can POST /api/announcements with valid data
     * and receives a 201 response containing the newly created announcement.
     */
    public function test_manager_can_create_announcement(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->postJson('/api/announcements', [
                'title' => 'New Happy Hour Menu',
                'body' => 'Starting next Monday we roll out the new happy hour menu.',
                'priority' => 'normal',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'New Happy Hour Menu');

        $this->assertDatabaseHas('announcements', [
            'title' => 'New Happy Hour Menu',
            'location_id' => $seed['location']->id,
            'posted_by' => $seed['manager']->id,
        ]);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN UPDATE ANNOUNCEMENT
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can PATCH /api/announcements/{id} to update
     * an announcement at their location, and the response reflects the changes.
     */
    public function test_manager_can_update_announcement(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $announcement = Announcement::create([
            'location_id' => $seed['location']->id,
            'title' => 'Original Title',
            'body' => 'Original body content.',
            'priority' => 'normal',
            'posted_by' => $seed['manager']->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->patchJson("/api/announcements/{$announcement->id}", [
                'title' => 'Updated Title',
                'body' => 'Updated body content.',
                'priority' => 'normal',
            ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Updated Title')
            ->assertJsonPath('body', 'Updated body content.');

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Updated Title',
            'body' => 'Updated body content.',
        ]);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN DELETE ANNOUNCEMENT
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can DELETE /api/announcements/{id} and
     * receives a 204 No Content response, with the record removed from the database.
     */
    public function test_manager_can_delete_announcement(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $announcement = Announcement::create([
            'location_id' => $seed['location']->id,
            'title' => 'Announcement To Delete',
            'body' => 'This will be removed.',
            'priority' => 'normal',
            'posted_by' => $seed['manager']->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->deleteJson("/api/announcements/{$announcement->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('announcements', [
            'id' => $announcement->id,
        ]);
    }

    // ══════════════════════════════════════════════
    //  STAFF CANNOT CREATE ANNOUNCEMENT
    // ══════════════════════════════════════════════

    /**
     * Verifies that a server-role user is blocked by the role middleware
     * and receives a 403 when attempting to POST a new announcement.
     */
    public function test_staff_cannot_create_announcement(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->postJson('/api/announcements', [
                'title' => 'Unauthorized Announcement',
                'body' => 'Staff should not be able to post this.',
                'priority' => 'normal',
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  CROSS-LOCATION MANAGER CANNOT UPDATE
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager at location B cannot PATCH an announcement
     * belonging to location A, enforcing location-scoped authorization
     * via the AnnouncementPolicy.
     */
    public function test_cross_location_manager_cannot_update(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

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

        $announcement = Announcement::create([
            'location_id' => $seed['location']->id,
            'title' => 'Location A Announcement',
            'body' => 'This belongs to location A.',
            'priority' => 'normal',
            'posted_by' => $seed['manager']->id,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($managerB, 'sanctum')
            ->withHeaders(['X-Location-Id' => $locationB->id])
            ->patchJson("/api/announcements/{$announcement->id}", [
                'title' => 'Cross-location hack',
                'body' => 'This should be rejected.',
                'priority' => 'normal',
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  ROLE-TARGETED ANNOUNCEMENTS FILTER CORRECTLY
    // ══════════════════════════════════════════════

    /**
     * Verifies that announcements with target_roles=['bartender'] are only
     * visible to bartender users, not to server users, when listing via
     * GET /api/announcements. The forRole scope should filter the results
     * so each role only sees announcements targeted at them.
     */
    public function test_role_targeted_announcements_filter_correctly(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $bartender = User::create([
            'name' => 'Bartender User',
            'email' => 'bartender@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $seed['location']->id,
        ]);

        Announcement::create([
            'location_id' => $seed['location']->id,
            'title' => 'Bartender Only Update',
            'body' => 'New cocktail spec for tonight.',
            'priority' => 'normal',
            'target_roles' => ['bartender'],
            'posted_by' => $seed['manager']->id,
            'expires_at' => now()->addDays(7),
        ]);

        /* Server should NOT see the bartender-targeted announcement */
        $serverResponse = $this->actingAs($seed['staff'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->getJson('/api/announcements');

        $serverResponse->assertOk()
            ->assertJsonCount(0);

        /* Bartender SHOULD see the announcement */
        $bartenderResponse = $this->actingAs($bartender, 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->getJson('/api/announcements');

        $bartenderResponse->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'Bartender Only Update']);
    }
}
