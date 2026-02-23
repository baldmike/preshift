<?php

/**
 * SpecialTest
 *
 * Feature tests for the SpecialController API endpoints.
 *
 * Tests verify:
 *   1. A manager can list current specials at their location.
 *   2. A manager can create a new special (POST returns 201).
 *   3. A manager can update an existing special (PATCH returns updated data).
 *   4. A manager can delete a special (DELETE returns 204).
 *   5. A manager can decrement a special's quantity by 1.
 *   6. A staff (server) user cannot create a special (role guard returns 403).
 *   7. A manager at location B cannot update location A's special (cross-location guard returns 403).
 */

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Special;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SpecialTest extends TestCase
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
    //  MANAGER CAN LIST SPECIALS
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can GET /api/specials and receive a JSON array
     * of current specials scoped to their location. Only active specials
     * within their date range should be returned.
     */
    public function test_manager_can_list_specials(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        Special::create([
            'location_id' => $seed['location']->id,
            'title' => '$5 Margaritas',
            'type' => 'daily',
            'starts_at' => now()->subDay()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->getJson('/api/specials');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $response->assertJsonFragment(['title' => '$5 Margaritas']);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN CREATE A SPECIAL
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can POST /api/specials with valid payload
     * and receive a 201 response containing the newly created special.
     */
    public function test_manager_can_create_special(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $payload = [
            'title' => 'Half-Price Wings',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->postJson('/api/specials', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'Half-Price Wings');

        $this->assertDatabaseHas('specials', [
            'title' => 'Half-Price Wings',
            'location_id' => $seed['location']->id,
            'created_by' => $seed['manager']->id,
        ]);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN UPDATE A SPECIAL
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can PATCH /api/specials/{id} to update a
     * special at their own location. The response should reflect the
     * updated title.
     */
    public function test_manager_can_update_special(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $special = Special::create([
            'location_id' => $seed['location']->id,
            'title' => '$5 Margaritas',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->patchJson("/api/specials/{$special->id}", [
                'title' => '$3 Margaritas',
                'type' => 'daily',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertOk()
            ->assertJsonPath('title', '$3 Margaritas');

        $this->assertDatabaseHas('specials', [
            'id' => $special->id,
            'title' => '$3 Margaritas',
        ]);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN DELETE A SPECIAL
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can DELETE /api/specials/{id} to permanently
     * remove a special at their location. The response should be 204 No Content.
     */
    public function test_manager_can_delete_special(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $special = Special::create([
            'location_id' => $seed['location']->id,
            'title' => '$5 Margaritas',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->deleteJson("/api/specials/{$special->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('specials', [
            'id' => $special->id,
        ]);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN DECREMENT SPECIAL QUANTITY
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can PATCH /api/specials/{id}/decrement to
     * reduce the special's quantity by 1. A special created with quantity 5
     * should return quantity 4 after one decrement.
     */
    public function test_manager_can_decrement_special_quantity(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $special = Special::create([
            'location_id' => $seed['location']->id,
            'title' => 'Limited Lobster Rolls',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'is_active' => true,
            'quantity' => 5,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->patchJson("/api/specials/{$special->id}/decrement");

        $response->assertOk()
            ->assertJsonPath('quantity', 4);

        $this->assertDatabaseHas('specials', [
            'id' => $special->id,
            'quantity' => 4,
        ]);
    }

    // ══════════════════════════════════════════════
    //  STAFF CANNOT CREATE A SPECIAL
    // ══════════════════════════════════════════════

    /**
     * Verifies that a server-role user is blocked by the role middleware
     * and receives a 403 when attempting to POST a new special. Only
     * admin and manager roles are permitted to create specials.
     */
    public function test_staff_cannot_create_special(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $payload = [
            'title' => 'Unauthorized Special',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->postJson('/api/specials', $payload);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('specials', [
            'title' => 'Unauthorized Special',
        ]);
    }

    // ══════════════════════════════════════════════
    //  CROSS-LOCATION MANAGER CANNOT UPDATE
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager at location B cannot PATCH a special that
     * belongs to location A. The policy enforces location-scoped
     * authorization, returning a 403 Forbidden response.
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

        $special = Special::create([
            'location_id' => $seed['location']->id,
            'title' => '$5 Margaritas',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($managerB, 'sanctum')
            ->withHeaders(['X-Location-Id' => $locationB->id])
            ->patchJson("/api/specials/{$special->id}", [
                'title' => 'Cross-location hack',
                'type' => 'daily',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertStatus(403);
    }
}
