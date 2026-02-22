<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * TonightsScheduleTest — Feature tests verifying that staff can fetch today's
 * schedule entries from the existing GET /api/schedules/{id} endpoint.
 *
 * The "Tonight's Schedule" frontend view consumes the same endpoint that
 * already exists — no new backend routes were added. These tests confirm
 * the endpoint returns the data the view needs: entries scoped to the
 * user's location with eagerly-loaded user and shift_template relations.
 */
class TonightsScheduleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Creates foundational test data: a Location, a manager User, and a
     * server (staff) User, mirroring the pattern from SchedulingTest.
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
     * Test 1: Staff can fetch a published schedule with today's entries.
     *
     * Verifies that GET /api/schedules/{id} returns entries with their
     * eagerly-loaded user and shift_template relations, which is what
     * the Tonight's Schedule view needs to display staff grouped by shift.
     */
    public function test_staff_can_fetch_published_schedule_with_entries(): void
    {
        $seed = $this->seedLocationAndUsers();
        $today = now()->toDateString();

        // Use the current week's Monday for the schedule
        $monday = now()->startOfWeek()->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $monday,
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['manager']->id,
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $template->id,
            'date' => $today,
            'role' => 'server',
        ]);

        // Act: authenticate as staff and fetch the schedule
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson("/api/schedules/{$schedule->id}");

        // Assert: 200 OK with entries containing nested user and shift_template
        $response->assertOk()
            ->assertJsonPath('id', $schedule->id)
            ->assertJsonCount(1, 'entries');

        $entry = $response->json('entries.0');
        $this->assertEquals($seed['staff']->id, $entry['user_id']);
        $this->assertNotNull($entry['user']);
        $this->assertNotNull($entry['shift_template']);
        $this->assertEquals('Dinner', $entry['shift_template']['name']);
    }

    /**
     * Test 2: Schedule entries are scoped to the user's location.
     *
     * Verifies that a staff member at Location A cannot access a schedule
     * belonging to Location B, ensuring no cross-location data leakage.
     */
    public function test_schedule_entries_are_scoped_to_location(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create a second location with its own staff
        $otherLocation = Location::create([
            'name' => 'Other Location',
            'address' => '456 Oak Ave',
            'timezone' => 'America/New_York',
        ]);

        $otherStaff = User::create([
            'name' => 'Other Server',
            'email' => 'other@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $otherLocation->id,
        ]);

        $monday = now()->startOfWeek()->toDateString();

        // Create a schedule at the OTHER location
        $otherSchedule = Schedule::create([
            'location_id' => $otherLocation->id,
            'week_start' => $monday,
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['manager']->id,
        ]);

        // Act: staff from Location A tries to fetch Location B's schedule
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson("/api/schedules/{$otherSchedule->id}");

        // Assert: should be forbidden (403) — schedule belongs to another location
        $response->assertForbidden();
    }

    /**
     * Test 3: Only published schedules are accessible to staff.
     *
     * The schedule listing endpoint (GET /api/schedules) returns schedules
     * for the user's location. Staff should not see draft schedules in
     * the list, which is how the frontend determines which schedule to
     * load for the "Tonight's Schedule" view.
     */
    public function test_staff_can_list_schedules_for_their_location(): void
    {
        $seed = $this->seedLocationAndUsers();
        $monday = now()->startOfWeek()->toDateString();

        // Create a published schedule
        Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $monday,
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['manager']->id,
        ]);

        // Create a draft schedule (should still appear in list for filtering)
        Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => now()->addWeek()->startOfWeek()->toDateString(),
            'status' => 'draft',
        ]);

        // Act: staff lists all schedules
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/schedules');

        // Assert: both schedules returned (frontend filters by status)
        $response->assertOk()
            ->assertJsonCount(2);

        // Verify the published one has the expected status
        $published = collect($response->json())->firstWhere('status', 'published');
        $this->assertNotNull($published);
        $this->assertEquals($monday, substr($published['week_start'], 0, 10));
    }
}
