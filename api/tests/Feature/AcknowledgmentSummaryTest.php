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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests for GET /api/acknowledgments/summary.
 *
 * This endpoint returns per-user acknowledgment counts for a manager's
 * location. Tests cover role-based access control, happy paths with
 * real ack data, zero-ack users, fully-acked users, and the edge case
 * of zero active items (division-by-zero guard).
 */
class AcknowledgmentSummaryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a location with one manager and one server user.
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

    /**
     * Verify that a server (non-manager) receives 403 Forbidden
     * when trying to access the summary endpoint.
     */
    public function test_server_cannot_access_summary(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/acknowledgments/summary');

        $response->assertStatus(403);
    }

    /**
     * Verify that a manager can access the summary endpoint and
     * receives the expected JSON structure.
     */
    public function test_manager_can_access_summary(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/acknowledgments/summary');

        $response->assertOk()
            ->assertJsonStructure([
                'total_items',
                'users' => [
                    '*' => ['user_id', 'user_name', 'role', 'total_items', 'acknowledged_count', 'percentage'],
                ],
            ]);
    }

    /**
     * Verify that a user who has not acknowledged any items shows
     * acknowledged_count = 0 and percentage = 0.
     */
    public function test_summary_shows_zero_for_unacked_user(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create a single active 86'd item -- no one has acknowledged it.
        EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Salmon',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/acknowledgments/summary');

        $response->assertOk();
        $data = $response->json();

        // One active item across the location.
        $this->assertEquals(1, $data['total_items']);

        // The server user should show 0 acknowledged out of 1.
        $staffData = collect($data['users'])->firstWhere('user_id', $seed['staff']->id);
        $this->assertNotNull($staffData);
        $this->assertEquals(0, $staffData['acknowledged_count']);
        $this->assertEquals(0, $staffData['percentage']);
    }

    /**
     * Verify that a user who has acknowledged every active item shows
     * acknowledged_count = total_items and percentage = 100.
     */
    public function test_summary_shows_100_for_fully_acked_user(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create one active item of each of two types.
        $eightySixed = EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Salmon',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        $special = Special::create([
            'location_id' => $seed['location']->id,
            'title' => 'Happy Hour',
            'type' => 'daily',
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'created_by' => $seed['manager']->id,
        ]);

        // Staff acknowledges both items via polymorphic acknowledgments.
        Acknowledgment::create([
            'user_id' => $seed['staff']->id,
            'acknowledgable_type' => EightySixed::class,
            'acknowledgable_id' => $eightySixed->id,
            'acknowledged_at' => now(),
        ]);

        Acknowledgment::create([
            'user_id' => $seed['staff']->id,
            'acknowledgable_type' => Special::class,
            'acknowledgable_id' => $special->id,
            'acknowledged_at' => now(),
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/acknowledgments/summary');

        $response->assertOk();
        $data = $response->json();

        // Two active items total (one 86'd + one special).
        $this->assertEquals(2, $data['total_items']);

        // Staff user should show 2/2 = 100%.
        $staffData = collect($data['users'])->firstWhere('user_id', $seed['staff']->id);
        $this->assertEquals(2, $staffData['acknowledged_count']);
        $this->assertEquals(100, $staffData['percentage']);
    }

    /**
     * Verify the division-by-zero guard: when no active items exist,
     * total_items = 0 and every user shows percentage = 0 (not NaN/error).
     */
    public function test_summary_returns_zero_percentage_when_no_items_exist(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/acknowledgments/summary');

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(0, $data['total_items']);

        // Every user should safely show 0% rather than a division error.
        foreach ($data['users'] as $userData) {
            $this->assertEquals(0, $userData['percentage']);
        }
    }
}
