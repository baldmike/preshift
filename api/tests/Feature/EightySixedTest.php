<?php

/**
 * EightySixedTest
 *
 * Feature tests for the PATCH /api/eighty-sixed/{id} update endpoint.
 *
 * Tests verify:
 *   1. A manager can update an 86'd item at their location (happy path).
 *   2. A staff (server) user cannot update an 86'd item (role guard).
 *   3. A manager at location B cannot update location A's 86'd item (cross-location guard).
 */

namespace Tests\Feature;

use App\Models\EightySixed;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EightySixedTest extends TestCase
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
    //  MANAGER CAN UPDATE AN 86'd ITEM
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can PATCH an 86'd item at their own location.
     * The response should contain the updated item_name and reason.
     */
    public function test_manager_can_update_eighty_sixed_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $item = EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Salmon',
            'reason' => 'Ran out',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/eighty-sixed/{$item->id}", [
                'item_name' => 'Atlantic Salmon',
                'reason' => 'Supplier issue',
            ]);

        $response->assertOk()
            ->assertJsonPath('item_name', 'Atlantic Salmon')
            ->assertJsonPath('reason', 'Supplier issue');

        $this->assertDatabaseHas('eighty_sixed', [
            'id' => $item->id,
            'item_name' => 'Atlantic Salmon',
            'reason' => 'Supplier issue',
        ]);
    }

    // ══════════════════════════════════════════════
    //  STAFF CANNOT UPDATE AN 86'd ITEM
    // ══════════════════════════════════════════════

    /**
     * Verifies that a server-role user is blocked by the role middleware
     * and receives a 403 when attempting to update an 86'd item.
     */
    public function test_staff_cannot_update_eighty_sixed_item(): void
    {
        Event::fake();
        $seed = $this->seedLocationAndUsers();

        $item = EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Salmon',
            'reason' => 'Ran out',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->patchJson("/api/eighty-sixed/{$item->id}", [
                'item_name' => 'Hacked Salmon',
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  CROSS-LOCATION GUARD
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager at location B cannot update an 86'd item
     * belonging to location A, enforcing location-scoped authorization.
     */
    public function test_manager_cannot_update_other_locations_item(): void
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

        $item = EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Salmon',
            'reason' => 'Ran out',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($managerB, 'sanctum')
            ->patchJson("/api/eighty-sixed/{$item->id}", [
                'item_name' => 'Cross-location hack',
            ]);

        $response->assertStatus(403);
    }
}
