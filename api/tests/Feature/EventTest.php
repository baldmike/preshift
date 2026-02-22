<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventTest extends TestCase
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
    //  MANAGER CAN LIST EVENTS
    // ══════════════════════════════════════════════

    public function test_manager_can_list_events(): void
    {
        EventFacade::fake();
        $seed = $this->seedLocationAndUsers();

        Event::create([
            'location_id' => $seed['location']->id,
            'title' => 'Wine Tasting',
            'description' => 'In the back room',
            'event_date' => now()->toDateString(),
            'event_time' => '19:00',
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/events');

        $response->assertOk();
        $titles = array_column($response->json(), 'title');
        $this->assertContains('Wine Tasting', $titles);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN CREATE EVENT
    // ══════════════════════════════════════════════

    public function test_manager_can_create_event(): void
    {
        EventFacade::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/events', [
                'title' => 'Private Party',
                'description' => 'Back room 6-9',
                'event_date' => now()->toDateString(),
                'event_time' => '18:00',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'Private Party');
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN UPDATE EVENT
    // ══════════════════════════════════════════════

    public function test_manager_can_update_event(): void
    {
        EventFacade::fake();
        $seed = $this->seedLocationAndUsers();

        $event = Event::create([
            'location_id' => $seed['location']->id,
            'title' => 'Wine Tasting',
            'event_date' => now()->toDateString(),
            'event_time' => '19:00',
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/events/{$event->id}", [
                'title' => 'Beer Tasting',
                'event_date' => now()->toDateString(),
                'event_time' => '20:00',
            ]);

        $response->assertOk()
            ->assertJsonPath('title', 'Beer Tasting');
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN DELETE EVENT
    // ══════════════════════════════════════════════

    public function test_manager_can_delete_event(): void
    {
        EventFacade::fake();
        $seed = $this->seedLocationAndUsers();

        $event = Event::create([
            'location_id' => $seed['location']->id,
            'title' => 'Wine Tasting',
            'event_date' => now()->toDateString(),
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    // ══════════════════════════════════════════════
    //  STAFF CANNOT CREATE EVENTS
    // ══════════════════════════════════════════════

    public function test_staff_cannot_create_events(): void
    {
        EventFacade::fake();
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/events', [
                'title' => 'Unauthorized Event',
                'event_date' => now()->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  STAFF CAN LIST EVENTS (READ-ONLY)
    // ══════════════════════════════════════════════

    public function test_staff_can_list_events(): void
    {
        EventFacade::fake();
        $seed = $this->seedLocationAndUsers();

        Event::create([
            'location_id' => $seed['location']->id,
            'title' => 'Staff Visible Event',
            'event_date' => now()->toDateString(),
            'created_by' => $seed['manager']->id,
        ]);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/events');

        $response->assertOk();
        $titles = array_column($response->json(), 'title');
        $this->assertContains('Staff Visible Event', $titles);
    }

    // ══════════════════════════════════════════════
    //  LOCATION ISOLATION
    // ══════════════════════════════════════════════

    public function test_user_cannot_see_other_locations_events(): void
    {
        EventFacade::fake();
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

        // Create event in location A
        Event::create([
            'location_id' => $seed['location']->id,
            'title' => 'Location A Event',
            'event_date' => now()->toDateString(),
            'created_by' => $seed['manager']->id,
        ]);

        // User in location B should see nothing
        $response = $this->actingAs($userB, 'sanctum')
            ->getJson('/api/events');

        $response->assertOk();
        $this->assertEmpty($response->json());
    }
}
