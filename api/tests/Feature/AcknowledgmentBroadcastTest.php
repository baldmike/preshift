<?php

namespace Tests\Feature;

use App\Events\AcknowledgmentRecorded;
use App\Models\Acknowledgment;
use App\Models\EightySixed;
use App\Models\Location;
use App\Models\Special;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Feature tests verifying that POST /api/acknowledge dispatches
 * the AcknowledgmentRecorded broadcast event with the correct payload.
 */
class AcknowledgmentBroadcastTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed a location with one manager and one server user, plus an active
     * 86'd item and an active special for the server to acknowledge.
     */
    private function seedData(): array
    {
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        $staff = User::create([
            'name' => 'Server',
            'email' => 'server@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        $eightySixed = EightySixed::create([
            'location_id' => $location->id,
            'item_name' => 'Salmon',
            'eighty_sixed_by' => $manager->id,
        ]);

        $special = Special::create([
            'location_id' => $location->id,
            'title' => 'Happy Hour',
            'type' => 'daily',
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'created_by' => $manager->id,
        ]);

        return compact('location', 'manager', 'staff', 'eightySixed', 'special');
    }

    /**
     * Verify that acknowledging an item dispatches the AcknowledgmentRecorded
     * event with the correct user_id and percentage.
     */
    public function test_acknowledge_dispatches_broadcast_event(): void
    {
        Event::fake([AcknowledgmentRecorded::class]);

        $seed = $this->seedData();

        // Staff acknowledges the 86'd item (1 of 2 total items = 50%)
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/acknowledge', [
                'type' => 'eighty_sixed',
                'id' => $seed['eightySixed']->id,
            ]);

        $response->assertOk();

        Event::assertDispatched(AcknowledgmentRecorded::class, function ($event) use ($seed) {
            return $event->userId === $seed['staff']->id
                && $event->percentage === 50
                && $event->acknowledgedCount === 1
                && $event->totalItems === 2;
        });
    }

    /**
     * Verify that acknowledging all items results in a 100% percentage
     * in the broadcast event.
     */
    public function test_acknowledge_all_items_broadcasts_100_percent(): void
    {
        Event::fake([AcknowledgmentRecorded::class]);

        $seed = $this->seedData();

        // Pre-acknowledge the first item directly
        Acknowledgment::create([
            'user_id' => $seed['staff']->id,
            'acknowledgable_type' => EightySixed::class,
            'acknowledgable_id' => $seed['eightySixed']->id,
            'acknowledged_at' => now(),
        ]);

        // Now acknowledge the second item via API
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/acknowledge', [
                'type' => 'special',
                'id' => $seed['special']->id,
            ]);

        $response->assertOk();

        Event::assertDispatched(AcknowledgmentRecorded::class, function ($event) use ($seed) {
            return $event->userId === $seed['staff']->id
                && $event->percentage === 100
                && $event->acknowledgedCount === 2
                && $event->totalItems === 2;
        });
    }
}
