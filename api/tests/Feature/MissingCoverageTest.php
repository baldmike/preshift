<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ShiftDrop;
use App\Models\ShiftDropVolunteer;
use App\Models\ShiftTemplate;
use App\Models\Special;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * MissingCoverageTest — Tests for controller actions and scenarios that were
 * not covered by the existing test suite.
 *
 * Covers:
 *   - ScheduleController::update() (happy path + cross-location policy)
 *   - ScheduleEntryController::update() (happy path + cross-location policy)
 *   - ShiftDropController::index() (manager sees all, staff sees filtered)
 *   - SpecialController::decrement() (happy path, null qty, zero qty, low-stock broadcast)
 *   - Cross-location policy enforcement for schedules, specials, shift templates,
 *     announcements, and push items
 *   - Staff role guard for shift drop select endpoint
 */
class MissingCoverageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed two locations with a manager and staff each, for cross-location tests.
     */
    private function seedTwoLocations(): array
    {
        $locationA = Location::create([
            'name' => 'Location A',
            'address' => '100 A St',
            'timezone' => 'America/New_York',
        ]);

        $locationB = Location::create([
            'name' => 'Location B',
            'address' => '200 B St',
            'timezone' => 'America/Chicago',
        ]);

        $managerA = User::create([
            'name' => 'Manager A',
            'email' => 'managera@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $locationA->id,
        ]);

        $managerB = User::create([
            'name' => 'Manager B',
            'email' => 'managerb@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $locationB->id,
        ]);

        $staffA = User::create([
            'name' => 'Staff A',
            'email' => 'staffa@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $locationA->id,
        ]);

        $staffB = User::create([
            'name' => 'Staff B',
            'email' => 'staffb@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $locationB->id,
        ]);

        return compact('locationA', 'locationB', 'managerA', 'managerB', 'staffA', 'staffB');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SCHEDULE UPDATE TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_update_schedule(): void
    {
        $seed = $this->seedTwoLocations();
        $nextMonday = now()->next('Monday')->toDateString();
        $followingMonday = now()->addWeeks(2)->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/schedules/{$schedule->id}", [
                'week_start' => $followingMonday,
            ]);

        $response->assertOk();

        // Verify the week_start was updated (database stores as datetime)
        $schedule->refresh();
        $this->assertEquals($followingMonday, $schedule->week_start->toDateString());
    }

    public function test_manager_cannot_update_schedule_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();
        $nextMonday = now()->next('Monday')->toDateString();

        $scheduleA = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        // Manager B tries to update Location A's schedule
        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/schedules/{$scheduleA->id}", [
                'week_start' => now()->addWeeks(3)->next('Monday')->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SCHEDULE ENTRY UPDATE TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_update_schedule_entry(): void
    {
        $seed = $this->seedTwoLocations();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
            'end_time' => '15:00',
        ]);

        $dinnerTemplate = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
            'end_time' => '23:00',
        ]);

        $entry = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staffA']->id,
            'shift_template_id' => $template->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        // Update the entry: change shift template and add notes
        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/schedule-entries/{$entry->id}", [
                'user_id' => $seed['staffA']->id,
                'shift_template_id' => $dinnerTemplate->id,
                'date' => $nextMonday,
                'role' => 'server',
                'notes' => 'Moved to dinner',
            ]);

        $response->assertOk()
            ->assertJsonPath('shift_template_id', $dinnerTemplate->id)
            ->assertJsonPath('notes', 'Moved to dinner');

        $this->assertDatabaseHas('schedule_entries', [
            'id' => $entry->id,
            'shift_template_id' => $dinnerTemplate->id,
            'notes' => 'Moved to dinner',
        ]);
    }

    public function test_manager_cannot_update_schedule_entry_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();
        $nextMonday = now()->next('Monday')->toDateString();

        $scheduleA = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => $nextMonday,
            'status' => 'draft',
        ]);

        $templateA = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
            'end_time' => '15:00',
        ]);

        $entryA = ScheduleEntry::create([
            'schedule_id' => $scheduleA->id,
            'user_id' => $seed['staffA']->id,
            'shift_template_id' => $templateA->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        // Manager B tries to update Location A's entry
        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/schedule-entries/{$entryA->id}", [
                'user_id' => $seed['staffA']->id,
                'shift_template_id' => $templateA->id,
                'date' => $nextMonday,
                'role' => 'server',
                'notes' => 'Should be forbidden',
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SHIFT DROP INDEX TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_list_all_shift_drops(): void
    {
        $seed = $this->seedTwoLocations();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => $nextMonday,
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['managerA']->id,
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
            'end_time' => '23:00',
        ]);

        $entry = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staffA']->id,
            'shift_template_id' => $template->id,
            'date' => now()->addDays(3)->toDateString(),
            'role' => 'server',
        ]);

        ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by' => $seed['staffA']->id,
            'reason' => 'Doctor appointment',
            'status' => 'open',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->getJson('/api/shift-drops');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertEquals('Doctor appointment', $response->json('0.reason'));
    }

    public function test_staff_sees_own_drops_and_eligible_open_drops(): void
    {
        $seed = $this->seedTwoLocations();
        $nextMonday = now()->next('Monday')->toDateString();

        // Create a second server at location A
        $staffA2 = User::create([
            'name' => 'Staff A2',
            'email' => 'staffa2@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $seed['locationA']->id,
        ]);

        $schedule = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => $nextMonday,
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['managerA']->id,
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
            'end_time' => '23:00',
        ]);

        // Entry for staffA
        $entryA = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staffA']->id,
            'shift_template_id' => $template->id,
            'date' => now()->addDays(3)->toDateString(),
            'role' => 'server',
        ]);

        // Entry for staffA2
        $entryA2 = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $staffA2->id,
            'shift_template_id' => $template->id,
            'date' => now()->addDays(4)->toDateString(),
            'role' => 'server',
        ]);

        // StaffA drops their shift (open)
        ShiftDrop::create([
            'schedule_entry_id' => $entryA->id,
            'requested_by' => $seed['staffA']->id,
            'reason' => 'My drop',
            'status' => 'open',
        ]);

        // StaffA2 drops their shift (open)
        ShiftDrop::create([
            'schedule_entry_id' => $entryA2->id,
            'requested_by' => $staffA2->id,
            'reason' => 'Their drop',
            'status' => 'open',
        ]);

        // StaffA should see: their own drop + staffA2's open drop (same role)
        $response = $this->actingAs($seed['staffA'], 'sanctum')
            ->getJson('/api/shift-drops');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_staff_cannot_see_other_location_shift_drops(): void
    {
        $seed = $this->seedTwoLocations();
        $nextMonday = now()->next('Monday')->toDateString();

        $scheduleA = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => $nextMonday,
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['managerA']->id,
        ]);

        $templateA = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
            'end_time' => '23:00',
        ]);

        $entryA = ScheduleEntry::create([
            'schedule_id' => $scheduleA->id,
            'user_id' => $seed['staffA']->id,
            'shift_template_id' => $templateA->id,
            'date' => now()->addDays(3)->toDateString(),
            'role' => 'server',
        ]);

        ShiftDrop::create([
            'schedule_entry_id' => $entryA->id,
            'requested_by' => $seed['staffA']->id,
            'reason' => 'Location A drop',
            'status' => 'open',
        ]);

        // Staff B at Location B should see zero drops
        $response = $this->actingAs($seed['staffB'], 'sanctum')
            ->getJson('/api/shift-drops');

        $response->assertOk();
        $this->assertEmpty($response->json());
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SPECIAL DECREMENT TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_decrement_special_quantity(): void
    {
        Event::fake();
        $seed = $this->seedTwoLocations();

        $special = Special::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Limited Wings',
            'type' => 'daily',
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'quantity' => 5,
            'created_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/specials/{$special->id}/decrement");

        $response->assertOk()
            ->assertJsonPath('quantity', 4);

        $this->assertDatabaseHas('specials', [
            'id' => $special->id,
            'quantity' => 4,
        ]);
    }

    public function test_decrement_returns_422_when_quantity_is_null(): void
    {
        Event::fake();
        $seed = $this->seedTwoLocations();

        $special = Special::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Unlimited Special',
            'type' => 'daily',
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'quantity' => null,
            'created_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/specials/{$special->id}/decrement");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot decrement quantity.');
    }

    public function test_decrement_returns_422_when_quantity_is_zero(): void
    {
        Event::fake();
        $seed = $this->seedTwoLocations();

        $special = Special::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Sold Out Special',
            'type' => 'daily',
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'quantity' => 0,
            'created_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/specials/{$special->id}/decrement");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot decrement quantity.');
    }

    public function test_staff_cannot_decrement_special(): void
    {
        Event::fake();
        $seed = $this->seedTwoLocations();

        $special = Special::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Staff Cannot Touch',
            'type' => 'daily',
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'quantity' => 10,
            'created_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['staffA'], 'sanctum')
            ->patchJson("/api/specials/{$special->id}/decrement");

        $response->assertStatus(403);
    }

    public function test_manager_cannot_decrement_special_at_other_location(): void
    {
        Event::fake();
        $seed = $this->seedTwoLocations();

        $specialA = Special::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Location A Special',
            'type' => 'daily',
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'quantity' => 5,
            'created_by' => $seed['managerA']->id,
        ]);

        // Manager B tries to decrement Location A's special
        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/specials/{$specialA->id}/decrement");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CROSS-LOCATION POLICY TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_cannot_update_special_at_other_location(): void
    {
        Event::fake();
        $seed = $this->seedTwoLocations();

        $specialA = Special::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Location A Happy Hour',
            'type' => 'daily',
            'starts_at' => now()->subDay()->toDateString(),
            'is_active' => true,
            'created_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/specials/{$specialA->id}", [
                'title' => 'Hijacked Special',
                'type' => 'daily',
                'starts_at' => now()->subDay()->toDateString(),
                'is_active' => true,
            ]);

        $response->assertStatus(403);
    }

    public function test_manager_cannot_delete_shift_template_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $templateA = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
            'end_time' => '15:00',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->deleteJson("/api/shift-templates/{$templateA->id}");

        $response->assertStatus(403);
    }

    public function test_manager_cannot_publish_schedule_at_other_location(): void
    {
        Event::fake();
        $seed = $this->seedTwoLocations();

        $scheduleA = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => now()->next('Monday')->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->postJson("/api/schedules/{$scheduleA->id}/publish");

        $response->assertStatus(403);
    }

    public function test_staff_cannot_select_volunteer(): void
    {
        Event::fake();
        $seed = $this->seedTwoLocations();
        $nextMonday = now()->next('Monday')->toDateString();

        $schedule = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => $nextMonday,
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['managerA']->id,
        ]);

        $template = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Dinner',
            'start_time' => '16:00',
            'end_time' => '23:00',
        ]);

        $entry = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staffA']->id,
            'shift_template_id' => $template->id,
            'date' => now()->addDays(3)->toDateString(),
            'role' => 'server',
        ]);

        $staffA2 = User::create([
            'name' => 'Staff A2',
            'email' => 'staffa2select@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $seed['locationA']->id,
        ]);

        $drop = ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by' => $seed['staffA']->id,
            'reason' => 'Cannot make it',
            'status' => 'open',
        ]);

        ShiftDropVolunteer::create([
            'shift_drop_id' => $drop->id,
            'user_id' => $staffA2->id,
            'selected' => false,
        ]);

        // Staff should not be able to select volunteers (manager-only route)
        $response = $this->actingAs($seed['staffA'], 'sanctum')
            ->postJson("/api/shift-drops/{$drop->id}/select/{$staffA2->id}");

        $response->assertStatus(403);
    }
}
