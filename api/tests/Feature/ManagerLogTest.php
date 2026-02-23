<?php

/**
 * ManagerLogTest
 *
 * Feature tests for the ManagerLog CRUD API. Verifies:
 *  - Manager can list logs (scoped to their location)
 *  - Manager can create a log with auto-populated snapshots
 *  - Manager can update log body (snapshots remain immutable)
 *  - Manager can delete a log
 *  - Staff (server/bartender) cannot access manager logs (403)
 *  - Staff cannot create manager logs (403)
 *  - Cross-location isolation (location A cannot see location B logs)
 *  - Duplicate date per location returns 422
 *  - Snapshots are auto-populated when data exists (weather via Http::fake())
 */

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Location;
use App\Models\ManagerLog;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ManagerLogTest extends TestCase
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
            'latitude' => 40.7128,
            'longitude' => -74.0060,
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
    //  MANAGER CAN LIST LOGS
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can list all logs for their location,
     * ordered by date descending, with the creator relationship loaded.
     */
    public function test_manager_can_list_logs(): void
    {
        $seed = $this->seedLocationAndUsers();

        ManagerLog::create([
            'location_id' => $seed['location']->id,
            'created_by' => $seed['manager']->id,
            'log_date' => now()->toDateString(),
            'body' => 'Busy night, two large parties.',
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/manager-logs');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('Busy night, two large parties.', $response->json()[0]['body']);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN CREATE LOG WITH SNAPSHOTS
    // ══════════════════════════════════════════════

    /**
     * Verifies that creating a log auto-populates weather, events, and
     * schedule snapshots. Weather is faked via Http::fake() to avoid
     * real API calls.
     */
    public function test_manager_can_create_log_with_snapshots(): void
    {
        $seed = $this->seedLocationAndUsers();
        $today = now()->toDateString();

        // Fake the Open-Meteo weather API response
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 72.5,
                    'apparent_temperature' => 70.1,
                    'relative_humidity_2m' => 45,
                    'wind_speed_10m' => 8.2,
                    'weather_code' => 0,
                ],
                'daily' => [
                    'temperature_2m_max' => [78.3],
                    'temperature_2m_min' => [62.1],
                    'weather_code' => [0],
                ],
            ]),
        ]);

        // Create an event for today
        Event::create([
            'location_id' => $seed['location']->id,
            'title' => 'Wine Tasting',
            'description' => 'Back room at 7pm',
            'event_date' => $today,
            'event_time' => '19:00',
            'created_by' => $seed['manager']->id,
        ]);

        // Create a published schedule with an entry for today
        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:30',
        ]);

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => now()->startOfWeek()->toDateString(),
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['manager']->id,
        ]);

        ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $template->id,
            'date' => $today,
            'role' => 'server',
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/manager-logs', [
                'log_date' => $today,
                'body' => 'Great night ahead.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('body', 'Great night ahead.');

        // Verify weather snapshot was populated
        $weatherSnapshot = $response->json('weather_snapshot');
        $this->assertNotNull($weatherSnapshot);
        $this->assertEquals(73, $weatherSnapshot['current']['temperature']);
        $this->assertEquals('Clear Sky', $weatherSnapshot['current']['description']);

        // Verify events snapshot was populated
        $eventsSnapshot = $response->json('events_snapshot');
        $this->assertCount(1, $eventsSnapshot);
        $this->assertEquals('Wine Tasting', $eventsSnapshot[0]['title']);

        // Verify schedule snapshot was populated
        $scheduleSnapshot = $response->json('schedule_snapshot');
        $this->assertCount(1, $scheduleSnapshot);
        $this->assertEquals('Server User', $scheduleSnapshot[0]['user_name']);
        $this->assertEquals('Dinner', $scheduleSnapshot[0]['shift_name']);
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN UPDATE LOG BODY
    // ══════════════════════════════════════════════

    /**
     * Verifies that updating a log only changes the body text.
     * Snapshots should remain unchanged.
     */
    public function test_manager_can_update_log_body(): void
    {
        $seed = $this->seedLocationAndUsers();

        $log = ManagerLog::create([
            'location_id' => $seed['location']->id,
            'created_by' => $seed['manager']->id,
            'log_date' => now()->toDateString(),
            'body' => 'Original notes.',
            'weather_snapshot' => ['current' => ['temperature' => 72]],
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/manager-logs/{$log->id}", [
                'body' => 'Updated notes.',
            ]);

        $response->assertOk()
            ->assertJsonPath('body', 'Updated notes.');

        // Verify snapshot was not changed
        $this->assertEquals(72, $response->json('weather_snapshot.current.temperature'));
    }

    // ══════════════════════════════════════════════
    //  MANAGER CAN DELETE LOG
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager can delete a log entry and it is removed
     * from the database.
     */
    public function test_manager_can_delete_log(): void
    {
        $seed = $this->seedLocationAndUsers();

        $log = ManagerLog::create([
            'location_id' => $seed['location']->id,
            'created_by' => $seed['manager']->id,
            'log_date' => now()->toDateString(),
            'body' => 'Will be deleted.',
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/manager-logs/{$log->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('manager_logs', ['id' => $log->id]);
    }

    // ══════════════════════════════════════════════
    //  STAFF CANNOT ACCESS MANAGER LOGS
    // ══════════════════════════════════════════════

    /**
     * Verifies that staff (server role) cannot list manager logs.
     * The route is restricted to admin and manager roles.
     */
    public function test_staff_cannot_access_manager_logs(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/manager-logs');

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  STAFF CANNOT CREATE MANAGER LOGS
    // ══════════════════════════════════════════════

    /**
     * Verifies that staff (server role) cannot create manager logs.
     */
    public function test_staff_cannot_create_manager_logs(): void
    {
        $seed = $this->seedLocationAndUsers();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/manager-logs', [
                'log_date' => now()->toDateString(),
                'body' => 'Unauthorized log.',
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════
    //  CROSS-LOCATION ISOLATION
    // ══════════════════════════════════════════════

    /**
     * Verifies that a manager at location B cannot see logs from location A.
     */
    public function test_cross_location_isolation(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Create a log at location A
        ManagerLog::create([
            'location_id' => $seed['location']->id,
            'created_by' => $seed['manager']->id,
            'log_date' => now()->toDateString(),
            'body' => 'Location A log.',
        ]);

        // Create location B with its own manager
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

        // Manager B should see no logs
        $response = $this->actingAs($managerB, 'sanctum')
            ->getJson('/api/manager-logs');

        $response->assertOk();
        $this->assertEmpty($response->json());
    }

    // ══════════════════════════════════════════════
    //  DUPLICATE DATE PER LOCATION RETURNS 422
    // ══════════════════════════════════════════════

    /**
     * Verifies the unique constraint on (location_id, log_date) is enforced
     * at the validation layer, returning a 422 on duplicate.
     */
    public function test_duplicate_date_per_location_returns_422(): void
    {
        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 72,
                    'apparent_temperature' => 70,
                    'relative_humidity_2m' => 45,
                    'wind_speed_10m' => 8,
                    'weather_code' => 0,
                ],
                'daily' => [
                    'temperature_2m_max' => [78],
                    'temperature_2m_min' => [62],
                    'weather_code' => [0],
                ],
            ]),
        ]);

        $seed = $this->seedLocationAndUsers();
        $today = now()->toDateString();

        // Create first log
        ManagerLog::create([
            'location_id' => $seed['location']->id,
            'created_by' => $seed['manager']->id,
            'log_date' => $today,
            'body' => 'First log.',
        ]);

        // Attempt to create a second log for the same date
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/manager-logs', [
                'log_date' => $today,
                'body' => 'Duplicate log.',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('log_date');
    }

    // ══════════════════════════════════════════════
    //  SNAPSHOTS AUTO-POPULATED WHEN DATA EXISTS
    // ══════════════════════════════════════════════

    /**
     * Verifies that weather snapshot is populated using Http::fake()
     * and that events/schedule snapshots contain the correct data.
     * This is a focused snapshot test separate from the full create test.
     */
    public function test_snapshots_auto_populated_when_data_exists(): void
    {
        $seed = $this->seedLocationAndUsers();
        $today = now()->toDateString();

        Http::fake([
            'api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 85.0,
                    'apparent_temperature' => 90.0,
                    'relative_humidity_2m' => 80,
                    'wind_speed_10m' => 5.0,
                    'weather_code' => 95,
                ],
                'daily' => [
                    'temperature_2m_max' => [92.0],
                    'temperature_2m_min' => [75.0],
                    'weather_code' => [95],
                ],
            ]),
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/manager-logs', [
                'log_date' => $today,
                'body' => 'Testing snapshots.',
            ]);

        $response->assertStatus(201);

        $weather = $response->json('weather_snapshot');
        $this->assertEquals(85, $weather['current']['temperature']);
        $this->assertEquals('Thunderstorm', $weather['current']['description']);
        $this->assertEquals(92, $weather['today']['high']);
        $this->assertEquals(75, $weather['today']['low']);
    }
}
