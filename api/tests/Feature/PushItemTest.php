<?php

/**
 * PushItemTest
 *
 * Feature tests for the PushItem API endpoints.
 *
 * Tests verify:
 *   1. A manager can list active push items at their location.
 *   2. A manager can create a push item (POST returns 201).
 *   3. A manager can update a push item (PATCH returns updated data).
 *   4. A manager can delete a push item (DELETE returns 204).
 *   5. A staff (server) user cannot create a push item (role guard returns 403).
 *   6. A staff (server) user cannot delete a push item (role guard returns 403).
 *   7. A manager at location B cannot update location A's push item (cross-location guard returns 403).
 */

namespace Tests\Feature;

use App\Models\Location;
use App\Models\PushItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PushItemTest extends TestCase
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
    //  MANAGER CAN LIST PUSH ITEMS
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can retrieve the list of active push items
     * at their location via GET /api/push-items.
     */
    public function test_manager_can_list_push_items(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        PushItem::create([
            'location_id' => $seed['location']->id,
            'title' => 'Push the Lobster Risotto',
            'priority' => 'high',
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        PushItem::create([
            'location_id' => $seed['location']->id,
            'title' => 'Suggest the House Red',
            'priority' => 'medium',
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->getJson('/api/push-items');

        $response->assertOk()
            ->assertJsonCount(2);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN CREATE A PUSH ITEM
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can create a new push item via POST /api/push-items
     * and receives a 201 response with the created resource.
     */
    public function test_manager_can_create_push_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->postJson('/api/push-items', [
                'title' => 'Push the Wagyu Burger',
                'description' => 'High margin item, mention the truffle aioli',
                'reason' => 'Excess ground wagyu prep',
                'priority' => 'high',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'Push the Wagyu Burger')
            ->assertJsonPath('priority', 'high');

        $this->assertDatabaseHas('push_items', [
            'title' => 'Push the Wagyu Burger',
            'priority' => 'high',
            'location_id' => $seed['location']->id,
            'created_by' => $seed['manager']->id,
        ]);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN UPDATE A PUSH ITEM
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can update an existing push item via
     * PATCH /api/push-items/{id} and receives the updated resource.
     */
    public function test_manager_can_update_push_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $pushItem = PushItem::create([
            'location_id' => $seed['location']->id,
            'title' => 'Push the Lobster Risotto',
            'priority' => 'high',
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->patchJson("/api/push-items/{$pushItem->id}", [
                'title' => 'Push the Truffle Risotto',
                'priority' => 'medium',
            ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Push the Truffle Risotto')
            ->assertJsonPath('priority', 'medium');

        $this->assertDatabaseHas('push_items', [
            'id' => $pushItem->id,
            'title' => 'Push the Truffle Risotto',
            'priority' => 'medium',
        ]);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN DELETE A PUSH ITEM
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can delete a push item via
     * DELETE /api/push-items/{id} and receives a 204 No Content response.
     */
    public function test_manager_can_delete_push_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $pushItem = PushItem::create([
            'location_id' => $seed['location']->id,
            'title' => 'Push the Lobster Risotto',
            'priority' => 'high',
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->deleteJson("/api/push-items/{$pushItem->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('push_items', [
            'id' => $pushItem->id,
        ]);
    }

    // ══════════════════════════════════════════════
    //  STAFF CANNOT CREATE A PUSH ITEM
    // ══════════════════════════════════════════════

    /**
     * Verifies that a server-role user is blocked by the role middleware
     * and receives a 403 when attempting to create a push item.
     */
    public function test_staff_cannot_create_push_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->postJson('/api/push-items', [
                'title' => 'Unauthorized Push',
                'priority' => 'low',
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  STAFF CANNOT DELETE A PUSH ITEM
    // ══════════════════════════════════════════════

    /**
     * Verifies that a server-role user is blocked by the role middleware
     * and receives a 403 when attempting to delete a push item.
     */
    public function test_staff_cannot_delete_push_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $pushItem = PushItem::create([
            'location_id' => $seed['location']->id,
            'title' => 'Push the Lobster Risotto',
            'priority' => 'high',
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->withHeaders(['X-Location-Id' => $seed['location']->id])
            ->deleteJson("/api/push-items/{$pushItem->id}");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  CROSS-LOCATION GUARD
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager at location B cannot update a push item
     * belonging to location A, enforcing location-scoped authorization.
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

        $pushItem = PushItem::create([
            'location_id' => $seed['location']->id,
            'title' => 'Push the Lobster Risotto',
            'priority' => 'high',
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($managerB, 'sanctum')
            ->withHeaders(['X-Location-Id' => $locationB->id])
            ->patchJson("/api/push-items/{$pushItem->id}", [
                'title' => 'Cross-location hack',
                'priority' => 'low',
            ]);

        $response->assertStatus(403);
    }
}
