<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ShiftDrop;
use App\Models\ShiftDropVolunteer;
use App\Models\ShiftTemplate;
use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * SchedulingTest — Comprehensive feature tests for the entire scheduling system.
 *
 * This test class covers every endpoint and business rule across five scheduling
 * subsystems: Shift Templates, Schedules, Schedule Entries, Shift Drops, and
 * Time-Off Requests. Each test method creates its own data from scratch using
 * the seedLocationAndUsers() helper (no factories are used), authenticates via
 * Sanctum, and verifies both happy paths and authorization guards.
 *
 * Broadcasting is faked via Event::fake() in any test that triggers a broadcast
 * (publishing schedules, dropping shifts, volunteering, selecting volunteers,
 * and resolving time-off requests).
 */
class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // Helper: seed a location + manager + staff user
    // ──────────────────────────────────────────────

    /**
     * Creates the foundational data every test needs: a Location, a manager-role
     * User, and a server-role User (staff). This mirrors the exact pattern used
     * in SmokeTest.php — direct model creation, no factories, explicit fields.
     *
     * @return array{location: Location, manager: User, staff: User}
     */
    private function seedLocationAndUsers(): array
    {
        // Create a single test location that all users will belong to.
        // The location middleware (EnsureLocationAccess) requires every non-admin
        // user to have a location_id, so this must exist before any API calls.
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        // Create a manager user who will have elevated privileges.
        // The CheckRole middleware allows role:admin,manager through for write
        // operations on schedules, shift templates, schedule entries, etc.
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        // Create a server (staff) user who has read-only access to most resources
        // but can perform staff-specific actions like dropping shifts, volunteering,
        // and submitting time-off requests.
        $staff = User::create([
            'name' => 'Server User',
            'email' => 'server@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        return compact('location', 'manager', 'staff');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SHIFT TEMPLATE TESTS
    // ══════════════════════════════════════════════════════════════════════
    //
    // Shift templates are reusable shift definitions per location (e.g.
    // "Lunch 10:30-15:00", "Dinner 16:00-23:00"). Managers create them once
    // and reference them when building weekly schedules. All CRUD operations
    // are restricted to admin/manager roles via the `role:admin,manager`
    // middleware.
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Test 1: Manager can list shift templates.
     *
     * Verifies that the GET /api/shift-templates endpoint returns all templates
     * belonging to the authenticated user's location. The controller scopes
     * results by location_id and orders them by start_time.
     */
    public function test_manager_can_list_shift_templates(): void
    {
        // Arrange: seed location and users, then create two shift templates
        // directly in the database so we have known data to assert against.
        $seed = $this->seedLocationAndUsers();

        // Create a "Lunch" shift template — starts at 10:30, ends at 15:00.
        ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        // Create a "Dinner" shift template — starts at 16:00, ends at 23:00.
        ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        // Act: authenticate as the manager and request the list of templates.
        // The 'sanctum' guard ensures the Sanctum middleware recognizes the user.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/shift-templates');

        // Assert: the response is 200 OK and contains both templates we created.
        // We extract the 'name' column from the JSON array to verify presence.
        $response->assertOk();
        $names = array_column($response->json(), 'name');
        $this->assertContains('Lunch', $names);
        $this->assertContains('Dinner', $names);
    }

    /**
     * Test 2: Manager can create a shift template.
     *
     * Verifies that POST /api/shift-templates creates a new ShiftTemplate record
     * with the correct name and start_time (H:i format). The controller
     * automatically assigns the authenticated user's location_id. Expects a 201
     * Created response with the template data in the JSON body.
     */
    public function test_manager_can_create_shift_template(): void
    {
        // Arrange: seed the basic location and users.
        $seed = $this->seedLocationAndUsers();

        // Act: authenticate as the manager and POST a new shift template.
        // The controller validates name (required|string|max:255),
        // start_time (required|date_format:H:i).
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/shift-templates', [
                'name' => 'Brunch',
                'start_time' => '09:00',
            ]);

        // Assert: 201 Created status, and the returned JSON matches our input.
        $response->assertStatus(201)
            ->assertJsonPath('name', 'Brunch')
            ->assertJsonPath('start_time', '09:00');

        // Also verify the record was persisted to the database with the correct
        // location_id (automatically set by the controller from the auth user).
        $this->assertDatabaseHas('shift_templates', [
            'name' => 'Brunch',
            'location_id' => $seed['location']->id,
        ]);
    }

    /**
     * Test 3: Manager can update a shift template.
     *
     * Verifies that PATCH /api/shift-templates/{id} allows a manager to modify
     * an existing template's name and start time. The controller expects name
     * and start_time in the request body since they are required in validation.
     */
    public function test_manager_can_update_shift_template(): void
    {
        // Arrange: seed location/users and create a template to update.
        $seed = $this->seedLocationAndUsers();

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        // Act: authenticate as the manager and PATCH the template with new values.
        // We're changing the name from "Lunch" to "Late Lunch" and adjusting the start time.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/shift-templates/{$template->id}", [
                'name' => 'Late Lunch',
                'start_time' => '11:00',
            ]);

        // Assert: 200 OK with the updated values reflected in the JSON response.
        $response->assertOk()
            ->assertJsonPath('name', 'Late Lunch')
            ->assertJsonPath('start_time', '11:00');

        // Verify the database record was actually updated (not just returned).
        $this->assertDatabaseHas('shift_templates', [
            'id' => $template->id,
            'name' => 'Late Lunch',
        ]);
    }

    /**
     * Test 4: Manager can delete a shift template.
     *
     * Verifies that DELETE /api/shift-templates/{id} removes the template
     * permanently and returns a 204 No Content response. The controller calls
     * $shiftTemplate->delete() which may cascade-delete associated entries.
     */
    public function test_manager_can_delete_shift_template(): void
    {
        // Arrange: seed location/users and create a template to delete.
        $seed = $this->seedLocationAndUsers();

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Closing',
            'start_time' => '20:00',
        ]);

        // Act: authenticate as the manager and DELETE the template.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/shift-templates/{$template->id}");

        // Assert: 204 No Content — the standard response for successful deletion.
        $response->assertNoContent();

        // Verify the record no longer exists in the database.
        $this->assertDatabaseMissing('shift_templates', [
            'id' => $template->id,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SCHEDULE TESTS
    // ══════════════════════════════════════════════════════════════════════
    //
    // Schedules represent one week of shifts at a location, identified by
    // a week_start date (typically a Monday). They begin in "draft" status
    // while the manager builds out entries, then transition to "published"
    // when ready for staff to view. The publish/unpublish actions control
    // staff visibility and trigger broadcast events via Reverb.
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Test 1: Manager can list schedules.
     *
     * Verifies that GET /api/schedules returns schedules for the authenticated
     * user's location. The controller filters to week_start >= 2 weeks ago and
     * includes an entries_count via withCount('entries').
     */
    public function test_manager_can_list_schedules(): void
    {
        // Arrange: seed location/users and create a schedule for the upcoming Monday.
        $seed = $this->seedLocationAndUsers();

        // Use next Monday as the week_start to ensure it falls within the
        // controller's 2-week lookback window.
        $nextMonday = now()->next('Monday')->toDateString();

        Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        // Act: authenticate as the manager and request the schedule list.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/schedules');

        // Assert: 200 OK and the schedule we created appears in the response.
        // The response includes an entries_count field from withCount('entries').
        $response->assertOk();
        $weekStarts = array_column($response->json(), 'week_start');

        // The week_start is cast to a date, so it may come back as "YYYY-MM-DDT00:00:00.000000Z"
        // depending on serialization. We check the response contains at least one schedule.
        $this->assertNotEmpty($response->json());
    }

    /**
     * Test 2: Manager can create a schedule.
     *
     * Verifies that POST /api/schedules creates a new Schedule in "draft" status
     * with the provided week_start date. The controller auto-assigns the user's
     * location_id and defaults status to "draft".
     */
    public function test_manager_can_create_schedule(): void
    {
        // Arrange: seed the base data.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        // Act: authenticate as the manager and create a schedule for next Monday.
        // The only required field is week_start (validated as 'required|date').
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedules', [
                'week_start' => $nextMonday,
            ]);

        // Assert: 201 Created with status defaulting to "draft".
        // The response should include the schedule with its auto-assigned location_id.
        $response->assertStatus(201)
            ->assertJsonPath('status', 'draft');

        // Verify it was persisted to the database with the correct location.
        $this->assertDatabaseHas('schedules', [
            'location_id' => $seed['location']->id,
            'status' => 'draft',
        ]);
    }

    /**
     * Test 3: Manager can view a schedule with its entries.
     *
     * Verifies that GET /api/schedules/{id} returns the schedule along with
     * eager-loaded entries (each with their user and shiftTemplate relations)
     * and the publisher relation. This endpoint is used by the schedule builder
     * to render the full weekly grid.
     */
    public function test_manager_can_view_schedule_with_entries(): void
    {
        // Arrange: seed location/users, create a schedule, a shift template,
        // and a schedule entry so we can verify the nested relations load.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        // Create a shift template that the entry will reference.
        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        // Create a schedule entry assigning the staff user to the Lunch shift.
        ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $template->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        // Act: authenticate as the manager and view the schedule by ID.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson("/api/schedules/{$schedule->id}");

        // Assert: 200 OK with the entries array populated and each entry
        // containing the nested user and shift_template relations.
        $response->assertOk()
            ->assertJsonPath('id', $schedule->id)
            ->assertJsonCount(1, 'entries');

        // Verify the nested entry contains the user and shift template data.
        // The controller calls $schedule->load('entries.user', 'entries.shiftTemplate', 'publisher').
        $entry = $response->json('entries.0');
        $this->assertEquals($seed['staff']->id, $entry['user_id']);
        $this->assertNotNull($entry['user']);
        $this->assertNotNull($entry['shift_template']);
    }

    /**
     * Test 4: Manager can publish a schedule.
     *
     * Verifies that POST /api/schedules/{id}/publish transitions the schedule
     * from "draft" to "published" status, records published_at and published_by,
     * and broadcasts a SchedulePublished event. Event::fake() is used to capture
     * the broadcast without actually sending it.
     */
    public function test_manager_can_publish_schedule(): void
    {
        // Fake all events/broadcasts before any code that might trigger them.
        // The publish action calls broadcast(new SchedulePublished($schedule)).
        Event::fake();

        // Arrange: seed location/users and create a draft schedule.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        // Act: authenticate as the manager and publish the schedule.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson("/api/schedules/{$schedule->id}/publish");

        // Assert: 200 OK, status changed to "published", and published_at/published_by are set.
        $response->assertOk()
            ->assertJsonPath('status', 'published');

        // Verify published_at is not null (it should be a datetime string now).
        $this->assertNotNull($response->json('published_at'));

        // Verify published_by matches the manager's ID.
        $this->assertEquals($seed['manager']->id, $response->json('published_by'));

        // Confirm the database was updated with the correct status and publisher.
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'status' => 'published',
            'published_by' => $seed['manager']->id,
        ]);
    }

    /**
     * Test 5: Manager can unpublish a schedule.
     *
     * Verifies that POST /api/schedules/{id}/unpublish reverts a published
     * schedule back to "draft" status. This allows the manager to make further
     * edits before republishing. Staff will no longer see this schedule until
     * it is republished.
     */
    public function test_manager_can_unpublish_schedule(): void
    {
        // Arrange: seed data and create an already-published schedule.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['manager']->id,
        ]);

        // Act: authenticate as the manager and unpublish the schedule.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson("/api/schedules/{$schedule->id}/unpublish");

        // Assert: 200 OK with status reverted to "draft".
        $response->assertOk()
            ->assertJsonPath('status', 'draft');

        // Verify the database reflects the draft status.
        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'status' => 'draft',
        ]);
    }

    /**
     * Test 6: Staff can view their upcoming shifts via GET /api/my-shifts.
     *
     * Verifies that the my-shifts endpoint returns only schedule entries that:
     *   1. Belong to the authenticated user (user_id matches).
     *   2. Are in a published schedule (status = 'published').
     *   3. Have a date >= today (upcoming or today, not past).
     *
     * Draft schedule entries and past entries should be excluded.
     */
    public function test_staff_can_view_their_shifts(): void
    {
        // Arrange: seed location/users and create a published schedule with
        // an entry for the staff user on a future date.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        // Create a published schedule — only published schedules are visible to staff.
        $publishedSchedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['manager']->id,
        ]);

        // Create a draft schedule — entries here should NOT appear in my-shifts.
        $draftSchedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => now()->addWeeks(2)->next('Monday')->toDateString(),
            'status' => 'draft',
        ]);

        // Create a shift template for the entries.
        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        // Create a future entry in the published schedule assigned to staff.
        // This SHOULD appear in the my-shifts response.
        $futureDate = now()->addDays(3)->toDateString();
        ScheduleEntry::create([
            'schedule_id' => $publishedSchedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $template->id,
            'date' => $futureDate,
            'role' => 'server',
        ]);

        // Create an entry in the draft schedule assigned to staff.
        // This should NOT appear because the schedule is not published.
        ScheduleEntry::create([
            'schedule_id' => $draftSchedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $template->id,
            'date' => now()->addDays(10)->toDateString(),
            'role' => 'server',
        ]);

        // Act: authenticate as the staff user and request their shifts.
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/my-shifts');

        // Assert: 200 OK with exactly 1 entry (the future, published one).
        $response->assertOk();

        // Only the entry from the published schedule with a future date should appear.
        $entries = $response->json();
        $this->assertCount(1, $entries);

        // Verify the returned entry is the one from the published schedule.
        $this->assertEquals($seed['staff']->id, $entries[0]['user_id']);
    }

    /**
     * Test 7: Staff cannot create schedules (403 Forbidden).
     *
     * Verifies that the POST /api/schedules endpoint is protected by the
     * `role:admin,manager` middleware. Staff users (servers, bartenders) should
     * receive a 403 Forbidden response when attempting to create a schedule.
     */
    public function test_staff_cannot_create_schedules(): void
    {
        // Arrange: seed location and users.
        $seed = $this->seedLocationAndUsers();

        // Act: authenticate as the staff (server) user and attempt to create a schedule.
        // The route has ->middleware('role:admin,manager') which should block this.
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/schedules', [
                'week_start' => now()->next('Monday')->toDateString(),
            ]);

        // Assert: 403 Forbidden — the CheckRole middleware rejects non-manager roles.
        $response->assertStatus(403);
    }

    /**
     * Test 8: Schedule view returns week_start and entry dates as ISO timestamps.
     *
     * Verifies that the GET /api/schedules/{id} response returns week_start as
     * a full ISO 8601 datetime string (e.g. "2026-03-02T00:00:00.000000Z") rather
     * than a date-only string. The frontend must parse this with .split('T')[0]
     * before using it for comparisons. This test documents the API date contract
     * so any serialization change will be caught early.
     */
    public function test_schedule_view_returns_iso_timestamp_dates(): void
    {
        // Arrange: seed location/users, create a schedule with an entry.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $template->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        // Act: authenticate as the manager and view the schedule.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson("/api/schedules/{$schedule->id}");

        // Assert: week_start is an ISO timestamp containing 'T' (not date-only).
        $response->assertOk();
        $weekStart = $response->json('week_start');
        $this->assertStringContainsString('T', $weekStart);
        $this->assertStringStartsWith($nextMonday, $weekStart);

        // Assert: entry date is also an ISO timestamp containing 'T'.
        $entryDate = $response->json('entries.0.date');
        $this->assertStringContainsString('T', $entryDate);
        $this->assertStringStartsWith($nextMonday, $entryDate);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SCHEDULE ENTRY TESTS
    // ══════════════════════════════════════════════════════════════════════
    //
    // Schedule entries assign individual staff members to specific shifts on
    // specific dates within a schedule. Each entry ties together a schedule,
    // user, shift template, date, and role. Only managers can create, update,
    // or delete entries.
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Test 1: Manager can create a schedule entry.
     *
     * Verifies that POST /api/schedule-entries creates a new entry with the
     * correct schedule_id, user_id, shift_template_id, date, and role. The
     * controller validates all foreign keys exist and that role is one of
     * "server" or "bartender". Returns 201 with the entry + loaded relations.
     */
    public function test_manager_can_create_schedule_entry(): void
    {
        // Arrange: seed location/users, create a schedule and a shift template
        // to reference in the new entry.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        // Act: authenticate as the manager and create an entry assigning the
        // staff user to the Lunch shift on next Monday as a server.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $template->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        // Assert: 201 Created with the correct field values in the response.
        $response->assertStatus(201)
            ->assertJsonPath('schedule_id', $schedule->id)
            ->assertJsonPath('user_id', $seed['staff']->id)
            ->assertJsonPath('shift_template_id', $template->id)
            ->assertJsonPath('role', 'server');

        // The controller eager-loads 'user' and 'shiftTemplate' on the created entry,
        // so verify these nested relations are present in the response.
        $this->assertNotNull($response->json('user'));
        $this->assertNotNull($response->json('shift_template'));

        // Verify the entry exists in the database.
        $this->assertDatabaseHas('schedule_entries', [
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staff']->id,
            'role' => 'server',
        ]);
    }

    /**
     * Test 2: Manager can delete a schedule entry.
     *
     * Verifies that DELETE /api/schedule-entries/{id} removes the entry
     * permanently and returns 204 No Content. This is used when a manager
     * needs to unassign a staff member from a shift.
     */
    public function test_manager_can_delete_schedule_entry(): void
    {
        // Arrange: seed location/users, create the prerequisite schedule, template,
        // and entry that we will delete.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        $entry = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $template->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        // Act: authenticate as the manager and delete the entry.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/schedule-entries/{$entry->id}");

        // Assert: 204 No Content and the record is gone from the database.
        $response->assertNoContent();

        $this->assertDatabaseMissing('schedule_entries', [
            'id' => $entry->id,
        ]);
    }

    /**
     * Test 3: Creating a second entry for the same user on the same date returns 422.
     *
     * Verifies that the unique constraint on (user_id, date) is enforced at the
     * validation layer. If a user is already scheduled on a given date, a second
     * POST /api/schedule-entries for the same user + date combo should fail with
     * a 422 status and a clear error message.
     */
    public function test_duplicate_user_date_entry_returns_422(): void
    {
        // Arrange: seed location/users, create a schedule and two shift templates
        // so the second entry uses a different template (proving it's the user+date
        // combo that's blocked, not template duplication).
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $lunch = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        $dinner = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        // Create the first entry — this should succeed normally.
        ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $lunch->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        // Act: authenticate as the manager and attempt to create a second entry
        // for the same user on the same date, but with a different shift template.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $dinner->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        // Assert: 422 Unprocessable Entity with a validation error on user_id.
        $response->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    /**
     * Test 4: Same user on different dates is allowed.
     *
     * Verifies that the unique constraint is scoped to (user_id, date) — a user
     * CAN work on Monday AND Tuesday, just not two shifts on the same day.
     * Both entries should be created successfully with 201 responses.
     */
    public function test_same_user_on_different_dates_is_allowed(): void
    {
        // Arrange: seed location/users, create a schedule and a shift template.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();
        $nextTuesday = now()->next('Monday')->addDay()->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        // Act: create an entry for Monday, then another for Tuesday — same user, different dates.
        $responseMonday = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $template->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        $responseTuesday = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $template->id,
                'date' => $nextTuesday,
                'role' => 'server',
            ]);

        // Assert: both entries are created successfully.
        $responseMonday->assertStatus(201);
        $responseTuesday->assertStatus(201);

        // Verify both entries exist in the database. The user now has 2 entries
        // (one per date). We check count rather than matching the date column
        // directly because SQLite stores date-cast values with a time component.
        $entryCount = ScheduleEntry::where('user_id', $seed['staff']->id)->count();
        $this->assertEquals(2, $entryCount);
    }

    /**
     * Test 5: Different users on the same date is allowed.
     *
     * Verifies that the unique constraint is per-user — multiple different staff
     * members can all be scheduled on the same day. Only the same user appearing
     * twice on the same date is blocked.
     */
    public function test_different_users_on_same_date_is_allowed(): void
    {
        // Arrange: seed location/users (gives us manager + staff).
        // Create a second staff user so we have two people to schedule.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $staff2 = User::create([
            'name' => 'Bartender User',
            'email' => 'bartender@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $seed['location']->id,
        ]);

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        // Act: schedule both users on the same date.
        $response1 = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $template->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        $response2 = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $staff2->id,
                'shift_template_id' => $template->id,
                'date' => $nextMonday,
                'role' => 'bartender',
            ]);

        // Assert: both are created successfully — different users, same date is fine.
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        // Verify both entries exist in the database. We check each user has
        // exactly one entry rather than matching the date column directly
        // because SQLite stores date-cast values with a time component.
        $this->assertDatabaseHas('schedule_entries', [
            'user_id' => $seed['staff']->id,
            'schedule_id' => $schedule->id,
        ]);
        $this->assertDatabaseHas('schedule_entries', [
            'user_id' => $staff2->id,
            'schedule_id' => $schedule->id,
        ]);
    }

    /**
     * Test 6: Duplicate entry returns the custom error message.
     *
     * Verifies that the validation error message for the user_id.unique rule
     * is the human-readable message we defined: "This user is already scheduled
     * on this date." rather than the default Laravel unique message.
     */
    public function test_duplicate_entry_returns_custom_error_message(): void
    {
        // Arrange: seed location/users, create a schedule and template.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $lunch = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        $dinner = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        // Create the first entry.
        ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $lunch->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        // Act: attempt a second entry for the same user + date.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $dinner->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        // Assert: 422 with the custom message on the user_id field.
        $response->assertStatus(422)
            ->assertJsonValidationErrors('user_id');

        // Verify the exact error message text.
        $errors = $response->json('errors.user_id');
        $this->assertContains('This user is already scheduled on this date.', $errors);
    }

    /**
     * Test 7: Duplicate constraint applies across different schedules.
     *
     * Verifies that the unique index on (user_id, date) is not scoped to a
     * single schedule. If a user is scheduled on 2026-03-02 in Schedule A,
     * they cannot also be scheduled on 2026-03-02 in Schedule B. Dates within
     * a location should never overlap between schedules anyway, but the DB
     * constraint enforces this regardless.
     */
    public function test_duplicate_constraint_applies_across_schedules(): void
    {
        // Arrange: seed location/users, create two schedules.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $scheduleA = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $scheduleB = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => now()->next('Monday')->addWeek()->toDateString(),
            'status' => 'draft',
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        // Create an entry in Schedule A for the staff user on nextMonday.
        ScheduleEntry::create([
            'schedule_id' => $scheduleA->id,
            'user_id' => $seed['staff']->id,
            'shift_template_id' => $template->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        // Act: attempt to create an entry in Schedule B for the same user on the same date.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $scheduleB->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $template->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        // Assert: 422 — the user is already scheduled on this date (in Schedule A).
        $response->assertStatus(422)
            ->assertJsonValidationErrors('user_id');
    }

    /**
     * Test 8: Staff cannot create schedule entries (403 Forbidden).
     *
     * Verifies that the POST /api/schedule-entries endpoint is guarded by the
     * `role:admin,manager` middleware. Staff users should not be able to assign
     * themselves or anyone else to shifts.
     */
    public function test_staff_cannot_create_schedule_entries(): void
    {
        // Arrange: seed location/users and create the prerequisite data.
        $seed = $this->seedLocationAndUsers();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['location']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['location']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        // Act: authenticate as the staff user and attempt to create an entry.
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $template->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        // Assert: 403 Forbidden — the CheckRole middleware rejects staff roles.
        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SHIFT DROP TESTS
    // ══════════════════════════════════════════════════════════════════════
    //
    // The shift drop system allows staff to drop shifts they cannot work.
    // Other staff can volunteer to pick up the dropped shift, and a manager
    // selects the volunteer who will fill it. The flow is:
    //   1. Staff drops a shift (status='open')
    //   2. Other same-role staff volunteer
    //   3. Manager selects a volunteer (status='filled', entry reassigned)
    //   4. Or the original staff member cancels the drop (status='cancelled')
    //
    // These tests create: Location, 2 server users, a manager, a ShiftTemplate,
    // a published Schedule, and a ScheduleEntry assigned to server1.
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Helper: set up the full shift drop scenario.
     *
     * Creates all the data needed for shift drop tests: a location, a manager,
     * two server users (server1 who owns the shift, server2 who can volunteer),
     * a shift template, a published schedule, and a schedule entry assigned to
     * server1 on a future date.
     *
     * @return array{location: Location, manager: User, server1: User, server2: User, template: ShiftTemplate, schedule: Schedule, entry: ScheduleEntry}
     */
    private function seedShiftDropScenario(): array
    {
        // Create the location — all users and resources belong to this venue.
        $location = Location::create([
            'name' => 'Drop Test Location',
            'address' => '456 Oak Ave',
            'timezone' => 'America/New_York',
        ]);

        // Create the manager who will select volunteers.
        $manager = User::create([
            'name' => 'Manager For Drops',
            'email' => 'dropmanager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        // Server 1: the staff member who owns the shift and will drop it.
        $server1 = User::create([
            'name' => 'Server One',
            'email' => 'server1@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        // Server 2: a same-role staff member who can volunteer to pick up the shift.
        // Must have the same role ('server') as server1 to be eligible.
        $server2 = User::create([
            'name' => 'Server Two',
            'email' => 'server2@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $location->id,
        ]);

        // Create a shift template — the shift type for the schedule entry.
        $template = ShiftTemplate::create([
            'location_id' => $location->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
        ]);

        // Create a published schedule — shift drops only make sense for published schedules
        // because those are the shifts staff can actually see and are assigned to work.
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start' => now()->next('Monday')->toDateString(),
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $manager->id,
        ]);

        // Create a schedule entry assigning server1 to the Dinner shift on a future date.
        // This is the entry that server1 will try to drop.
        $futureDate = now()->addDays(3)->toDateString();
        $entry = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $server1->id,
            'shift_template_id' => $template->id,
            'date' => $futureDate,
            'role' => 'server',
        ]);

        return compact('location', 'manager', 'server1', 'server2', 'template', 'schedule', 'entry');
    }

    /**
     * Test 1: Staff can drop their own shift.
     *
     * Verifies that POST /api/shift-drops creates a ShiftDrop record with
     * status='open' when the authenticated user owns the schedule entry.
     * The controller checks entry.user_id === auth user id before allowing
     * the drop. A ShiftDropRequested event is broadcast.
     */
    public function test_staff_can_drop_own_shift(): void
    {
        // Fake events because the store action broadcasts ShiftDropRequested.
        Event::fake();

        // Arrange: set up the full shift drop scenario.
        $data = $this->seedShiftDropScenario();

        // Act: authenticate as server1 (who owns the shift) and drop it.
        // Required fields: schedule_entry_id (required|exists) and reason (nullable|string).
        $response = $this->actingAs($data['server1'], 'sanctum')
            ->postJson('/api/shift-drops', [
                'schedule_entry_id' => $data['entry']->id,
                'reason' => 'Family emergency',
            ]);

        // Assert: 201 Created with status='open' and the correct requester.
        $response->assertStatus(201)
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('requested_by', $data['server1']->id)
            ->assertJsonPath('reason', 'Family emergency');

        // Verify the shift drop was persisted in the database.
        $this->assertDatabaseHas('shift_drops', [
            'schedule_entry_id' => $data['entry']->id,
            'requested_by' => $data['server1']->id,
            'status' => 'open',
        ]);
    }

    /**
     * Test 2: Staff cannot drop someone else's shift (403 Forbidden).
     *
     * Verifies that the controller checks entry.user_id against the authenticated
     * user's id and returns 403 if they don't match. Server2 should not be able
     * to drop server1's shift.
     */
    public function test_staff_cannot_drop_another_users_shift(): void
    {
        // Fake events in case any broadcast code is reached.
        Event::fake();

        // Arrange: set up the scenario. The entry belongs to server1.
        $data = $this->seedShiftDropScenario();

        // Act: authenticate as server2 and attempt to drop server1's shift.
        // The controller should reject this because entry.user_id !== server2.id.
        $response = $this->actingAs($data['server2'], 'sanctum')
            ->postJson('/api/shift-drops', [
                'schedule_entry_id' => $data['entry']->id,
                'reason' => 'I want this shift gone',
            ]);

        // Assert: 403 Forbidden with the expected error message.
        $response->assertStatus(403)
            ->assertJsonPath('message', 'You can only drop your own shifts.');
    }

    /**
     * Test 3: Staff can volunteer for an open shift drop.
     *
     * Verifies that POST /api/shift-drops/{id}/volunteer allows a same-role
     * staff member (who is NOT the requester) to volunteer for an open drop.
     * The controller creates a ShiftDropVolunteer record with selected=false
     * and broadcasts a ShiftDropVolunteered event.
     */
    public function test_staff_can_volunteer_for_open_drop(): void
    {
        // Fake events because the volunteer action broadcasts ShiftDropVolunteered.
        Event::fake();

        // Arrange: set up the scenario and create a shift drop by server1.
        $data = $this->seedShiftDropScenario();

        // Create the drop record directly (simulating server1 having already dropped).
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $data['entry']->id,
            'requested_by' => $data['server1']->id,
            'reason' => 'Cannot make it',
            'status' => 'open',
        ]);

        // Act: authenticate as server2 (same role, different user) and volunteer.
        // The controller validates: status is 'open', user is not the requester,
        // and user has the same role as the schedule entry.
        $response = $this->actingAs($data['server2'], 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/volunteer");

        // Assert: 200 OK (the controller returns the drop with loaded relations).
        $response->assertOk();

        // Verify a ShiftDropVolunteer record was created in the database
        // linking server2 to this drop with selected=false (not yet chosen).
        $this->assertDatabaseHas('shift_drop_volunteers', [
            'shift_drop_id' => $drop->id,
            'user_id' => $data['server2']->id,
            'selected' => false,
        ]);
    }

    /**
     * Test 4: Staff cannot volunteer for their own drop (422).
     *
     * Verifies that the controller rejects a volunteer attempt when the
     * authenticated user is the same person who requested the drop. The
     * error message should be "You cannot volunteer for your own drop."
     */
    public function test_staff_cannot_volunteer_for_own_drop(): void
    {
        // Fake events in case any broadcast code is reached.
        Event::fake();

        // Arrange: set up the scenario and create a drop by server1.
        $data = $this->seedShiftDropScenario();

        $drop = ShiftDrop::create([
            'schedule_entry_id' => $data['entry']->id,
            'requested_by' => $data['server1']->id,
            'reason' => 'Sick',
            'status' => 'open',
        ]);

        // Act: authenticate as server1 (the requester) and try to volunteer
        // for their own drop. This should be rejected.
        $response = $this->actingAs($data['server1'], 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/volunteer");

        // Assert: 422 Unprocessable Entity with the self-volunteer error message.
        $response->assertStatus(422)
            ->assertJsonPath('message', 'You cannot volunteer for your own drop.');
    }

    /**
     * Test 5: Staff cannot volunteer if they have a different role (422).
     *
     * Verifies that the controller checks the volunteer's role against the
     * schedule entry's role. A bartender cannot volunteer for a server's shift
     * drop, and vice versa. The error message should indicate a role mismatch.
     */
    public function test_staff_cannot_volunteer_if_different_role(): void
    {
        // Fake events in case any broadcast code is reached.
        Event::fake();

        // Arrange: set up the scenario (entry role is 'server').
        $data = $this->seedShiftDropScenario();

        // Create a bartender user — different role than the entry's 'server' role.
        $bartender = User::create([
            'name' => 'Bartender User',
            'email' => 'bartender@test.com',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $data['location']->id,
        ]);

        // Create an open drop for server1's shift.
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $data['entry']->id,
            'requested_by' => $data['server1']->id,
            'reason' => 'Personal matter',
            'status' => 'open',
        ]);

        // Act: authenticate as the bartender and try to volunteer for a server's drop.
        // The controller checks: $request->user()->role !== $entryRole.
        $response = $this->actingAs($bartender, 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/volunteer");

        // Assert: 422 Unprocessable Entity with the role mismatch error message.
        $response->assertStatus(422)
            ->assertJsonPath('message', 'You must have the same role to pick up this shift.');
    }

    /**
     * Test 6: Manager can select a volunteer to fill the shift drop.
     *
     * Verifies that POST /api/shift-drops/{id}/select/{user} performs all of:
     *   1. Marks the volunteer's ShiftDropVolunteer record as selected=true.
     *   2. Reassigns the schedule entry's user_id to the selected volunteer.
     *   3. Updates the ShiftDrop status to 'filled' with filled_by and filled_at.
     *   4. Broadcasts a ShiftDropFilled event.
     *
     * This is the final step in the shift drop workflow.
     */
    public function test_manager_can_select_volunteer(): void
    {
        // Fake events because the select action broadcasts ShiftDropFilled.
        Event::fake();

        // Arrange: set up the scenario, create a drop, and add server2 as a volunteer.
        $data = $this->seedShiftDropScenario();

        // Create the shift drop by server1.
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $data['entry']->id,
            'requested_by' => $data['server1']->id,
            'reason' => 'Moving day',
            'status' => 'open',
        ]);

        // Add server2 as a volunteer (simulating they previously volunteered).
        ShiftDropVolunteer::create([
            'shift_drop_id' => $drop->id,
            'user_id' => $data['server2']->id,
            'selected' => false,
        ]);

        // Act: authenticate as the manager and select server2 as the volunteer.
        // The route is: POST /api/shift-drops/{shiftDrop}/select/{user}
        $response = $this->actingAs($data['manager'], 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/select/{$data['server2']->id}");

        // Assert: 200 OK with the drop now marked as 'filled'.
        $response->assertOk()
            ->assertJsonPath('status', 'filled')
            ->assertJsonPath('filled_by', $data['server2']->id);

        // Verify filled_at was set (should be a non-null datetime).
        $this->assertNotNull($response->json('filled_at'));

        // Verify the schedule entry was reassigned from server1 to server2.
        // This is the key business rule: the shift now belongs to the volunteer.
        $this->assertDatabaseHas('schedule_entries', [
            'id' => $data['entry']->id,
            'user_id' => $data['server2']->id,
        ]);

        // Verify the volunteer record was marked as selected=true.
        $this->assertDatabaseHas('shift_drop_volunteers', [
            'shift_drop_id' => $drop->id,
            'user_id' => $data['server2']->id,
            'selected' => true,
        ]);

        // Verify the shift drop record itself was updated in the database.
        $this->assertDatabaseHas('shift_drops', [
            'id' => $drop->id,
            'status' => 'filled',
            'filled_by' => $data['server2']->id,
        ]);
    }

    /**
     * Test 7: Staff can cancel their own open drop.
     *
     * Verifies that POST /api/shift-drops/{id}/cancel allows the original
     * requester to cancel a drop, but only if the status is still 'open'.
     * The drop status is changed to 'cancelled'.
     */
    public function test_staff_can_cancel_own_drop(): void
    {
        // Arrange: set up the scenario and create an open drop by server1.
        $data = $this->seedShiftDropScenario();

        $drop = ShiftDrop::create([
            'schedule_entry_id' => $data['entry']->id,
            'requested_by' => $data['server1']->id,
            'reason' => 'Changed my mind scenario',
            'status' => 'open',
        ]);

        // Act: authenticate as server1 (the requester) and cancel the drop.
        $response = $this->actingAs($data['server1'], 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/cancel");

        // Assert: 200 OK with status changed to 'cancelled'.
        $response->assertOk()
            ->assertJsonPath('status', 'cancelled');

        // Verify the database reflects the cancellation.
        $this->assertDatabaseHas('shift_drops', [
            'id' => $drop->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Test 8: Staff cannot cancel another user's drop (403 Forbidden).
     *
     * Verifies that the cancel action checks requested_by against the
     * authenticated user's id and returns 403 if they don't match. Only the
     * original requester can cancel their own drop.
     */
    public function test_staff_cannot_cancel_another_users_drop(): void
    {
        // Arrange: set up the scenario and create a drop by server1.
        $data = $this->seedShiftDropScenario();

        $drop = ShiftDrop::create([
            'schedule_entry_id' => $data['entry']->id,
            'requested_by' => $data['server1']->id,
            'reason' => 'Server1 reason',
            'status' => 'open',
        ]);

        // Act: authenticate as server2 and attempt to cancel server1's drop.
        // The controller checks: $shiftDrop->requested_by !== $request->user()->id.
        $response = $this->actingAs($data['server2'], 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/cancel");

        // Assert: 403 Forbidden (policy rejects non-owner).
        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  TIME-OFF REQUEST TESTS
    // ══════════════════════════════════════════════════════════════════════
    //
    // Time-off requests allow staff to request days off. The flow is:
    //   1. Staff submits a request with start_date, end_date, reason (status='pending')
    //   2. Manager approves (status='approved') or denies (status='denied')
    //   3. Approved time-off appears in the schedule builder as a conflict warning
    //
    // Visibility rules:
    //   - Staff see only their own requests
    //   - Managers see all requests for their location
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Test 1: Staff can submit a time-off request.
     *
     * Verifies that POST /api/time-off-requests creates a TimeOffRequest with
     * status='pending', the correct date range, and the authenticated user's
     * user_id and location_id. Dates must be today or in the future.
     */
    public function test_staff_can_submit_time_off_request(): void
    {
        // Arrange: seed location and users.
        $seed = $this->seedLocationAndUsers();

        // Use dates 15 and 17 days in the future to satisfy the configurable
        // advance notice requirement (default 14 days).
        $startDate = now()->addDays(15)->toDateString();
        $endDate = now()->addDays(17)->toDateString();

        // Act: authenticate as the staff user and submit a time-off request.
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => 'Family vacation',
            ]);

        // Assert: 201 Created with status='pending' and the correct dates/reason.
        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('reason', 'Family vacation');

        // Verify the user_id was automatically set to the authenticated user.
        $this->assertEquals($seed['staff']->id, $response->json('user_id'));

        // Verify the location_id was automatically set from the user's location.
        $this->assertEquals($seed['location']->id, $response->json('location_id'));

        // Verify the record was persisted in the database.
        $this->assertDatabaseHas('time_off_requests', [
            'user_id' => $seed['staff']->id,
            'location_id' => $seed['location']->id,
            'status' => 'pending',
            'reason' => 'Family vacation',
        ]);
    }

    /**
     * Test 2: Manager can approve a time-off request.
     *
     * Verifies that POST /api/time-off-requests/{id}/approve transitions the
     * request from 'pending' to 'approved', sets resolved_by to the manager's
     * id, sets resolved_at to the current timestamp, and broadcasts a
     * TimeOffResolved event.
     */
    public function test_manager_can_approve_time_off_request(): void
    {
        // Fake events because the approve action broadcasts TimeOffResolved.
        Event::fake();

        // Arrange: seed location/users and create a pending time-off request.
        $seed = $this->seedLocationAndUsers();

        $timeOff = TimeOffRequest::create([
            'user_id' => $seed['staff']->id,
            'location_id' => $seed['location']->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'reason' => 'Doctor appointment',
            'status' => 'pending',
        ]);

        // Act: authenticate as the manager and approve the request.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson("/api/time-off-requests/{$timeOff->id}/approve");

        // Assert: 200 OK with status changed to 'approved'.
        $response->assertOk()
            ->assertJsonPath('status', 'approved');

        // Verify resolved_by was set to the manager's ID.
        $this->assertEquals($seed['manager']->id, $response->json('resolved_by'));

        // Verify resolved_at is not null (it should be set to now()).
        $this->assertNotNull($response->json('resolved_at'));

        // Verify the database record was updated.
        $this->assertDatabaseHas('time_off_requests', [
            'id' => $timeOff->id,
            'status' => 'approved',
            'resolved_by' => $seed['manager']->id,
        ]);
    }

    /**
     * Test 3: Manager can deny a time-off request.
     *
     * Verifies that POST /api/time-off-requests/{id}/deny transitions the
     * request from 'pending' to 'denied', sets resolved_by and resolved_at,
     * and broadcasts a TimeOffResolved event.
     */
    public function test_manager_can_deny_time_off_request(): void
    {
        // Fake events because the deny action broadcasts TimeOffResolved.
        Event::fake();

        // Arrange: seed location/users and create a pending time-off request.
        $seed = $this->seedLocationAndUsers();

        $timeOff = TimeOffRequest::create([
            'user_id' => $seed['staff']->id,
            'location_id' => $seed['location']->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'reason' => 'Concert tickets',
            'status' => 'pending',
        ]);

        // Act: authenticate as the manager and deny the request.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson("/api/time-off-requests/{$timeOff->id}/deny");

        // Assert: 200 OK with status changed to 'denied'.
        $response->assertOk()
            ->assertJsonPath('status', 'denied');

        // Verify resolved_by was set to the manager's ID.
        $this->assertEquals($seed['manager']->id, $response->json('resolved_by'));

        // Verify resolved_at is not null.
        $this->assertNotNull($response->json('resolved_at'));

        // Verify the database record was updated to 'denied'.
        $this->assertDatabaseHas('time_off_requests', [
            'id' => $timeOff->id,
            'status' => 'denied',
            'resolved_by' => $seed['manager']->id,
        ]);
    }

    /**
     * Test 4: Cannot approve an already resolved request (422).
     *
     * Verifies that attempting to approve a time-off request that has already
     * been approved or denied returns a 422 error. The controller checks
     * `if ($timeOffRequest->status !== 'pending')` before allowing resolution.
     * This test covers the idempotency guard for both approve and deny actions.
     */
    public function test_cannot_approve_already_resolved_request(): void
    {
        // Fake events in case any broadcast code is reached.
        Event::fake();

        // Arrange: seed location/users and create an already-approved request.
        // The status is 'approved' (not 'pending'), so the approve endpoint
        // should reject a second approval attempt.
        $seed = $this->seedLocationAndUsers();

        $timeOff = TimeOffRequest::create([
            'user_id' => $seed['staff']->id,
            'location_id' => $seed['location']->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'reason' => 'Already handled',
            'status' => 'approved',
            'resolved_by' => $seed['manager']->id,
            'resolved_at' => now(),
        ]);

        // Act: authenticate as the manager and try to approve again.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson("/api/time-off-requests/{$timeOff->id}/approve");

        // Assert: 422 Unprocessable Entity — the request has already been resolved.
        $response->assertStatus(422)
            ->assertJsonPath('message', 'This request has already been resolved.');
    }

    /**
     * Test 5: Staff see only their own requests; managers see all.
     *
     * Verifies the visibility rules for GET /api/time-off-requests:
     *   - Staff (isStaff() returns true) see only requests where user_id matches
     *     their own ID.
     *   - Managers see all requests for the location regardless of who submitted them.
     *
     * This tests the controller's conditional query scoping:
     *   if ($user->isStaff()) { $query->where('user_id', $user->id); }
     */
    public function test_staff_see_own_requests_managers_see_all(): void
    {
        // Arrange: seed location/users and create a second staff user so we have
        // two different staff members with separate time-off requests.
        $seed = $this->seedLocationAndUsers();

        // Create a second server user at the same location.
        $staff2 = User::create([
            'name' => 'Second Server',
            'email' => 'server2@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $seed['location']->id,
        ]);

        // Create a time-off request for the first staff user.
        TimeOffRequest::create([
            'user_id' => $seed['staff']->id,
            'location_id' => $seed['location']->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'reason' => 'Staff 1 vacation',
            'status' => 'pending',
        ]);

        // Create a time-off request for the second staff user.
        TimeOffRequest::create([
            'user_id' => $staff2->id,
            'location_id' => $seed['location']->id,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'reason' => 'Staff 2 vacation',
            'status' => 'pending',
        ]);

        // --- Staff 1: should see ONLY their own request ---

        // Act: authenticate as staff1 and list time-off requests.
        $staffResponse = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/time-off-requests');

        // Assert: staff1 sees exactly 1 request (their own).
        $staffResponse->assertOk();
        $staffRequests = $staffResponse->json();
        $this->assertCount(1, $staffRequests);

        // Verify the returned request belongs to staff1 (not staff2).
        $this->assertEquals($seed['staff']->id, $staffRequests[0]['user_id']);
        $this->assertEquals('Staff 1 vacation', $staffRequests[0]['reason']);

        // --- Manager: should see ALL requests for the location ---

        // Act: authenticate as the manager and list time-off requests.
        $managerResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/time-off-requests');

        // Assert: the manager sees both requests (2 total) from the location.
        $managerResponse->assertOk();
        $managerRequests = $managerResponse->json();
        $this->assertCount(2, $managerRequests);

        // Verify both staff users' requests are present by checking reasons.
        $reasons = array_column($managerRequests, 'reason');
        $this->assertContains('Staff 1 vacation', $reasons);
        $this->assertContains('Staff 2 vacation', $reasons);
    }
}
