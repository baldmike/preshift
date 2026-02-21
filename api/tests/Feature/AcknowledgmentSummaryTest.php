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

class AcknowledgmentSummaryTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_server_cannot_access_summary(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/acknowledgments/summary');

        $response->assertStatus(403);
    }

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

    public function test_summary_shows_zero_for_unacked_user(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create an active 86'd item
        EightySixed::create([
            'location_id' => $seed['location']->id,
            'item_name' => 'Salmon',
            'eighty_sixed_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/acknowledgments/summary');

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(1, $data['total_items']);

        // Find the staff user in the response
        $staffData = collect($data['users'])->firstWhere('user_id', $seed['staff']->id);
        $this->assertNotNull($staffData);
        $this->assertEquals(0, $staffData['acknowledged_count']);
        $this->assertEquals(0, $staffData['percentage']);
    }

    public function test_summary_shows_100_for_fully_acked_user(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create one active item of each type
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

        // Staff acknowledges both items
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

        $this->assertEquals(2, $data['total_items']);

        $staffData = collect($data['users'])->firstWhere('user_id', $seed['staff']->id);
        $this->assertEquals(2, $staffData['acknowledged_count']);
        $this->assertEquals(100, $staffData['percentage']);
    }

    public function test_summary_returns_zero_percentage_when_no_items_exist(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/acknowledgments/summary');

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(0, $data['total_items']);

        // All users should show 0%
        foreach ($data['users'] as $userData) {
            $this->assertEquals(0, $userData['percentage']);
        }
    }
}
