<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Location;
use App\Models\ManagerLog;
use App\Models\MenuItem;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ShiftTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PolicyAuthorizationTest — Tests cross-location policy enforcement for every
 * untested Policy. Each test creates a resource at location A and verifies
 * that manager B at location B gets 403, while manager A succeeds.
 *
 * Covers:
 *   - CategoryPolicy (update/delete: same-location pass, cross-location 403)
 *   - MenuItemPolicy (update/delete: same-location pass, cross-location 403)
 *   - EventPolicy (update/delete: same-location pass, cross-location 403)
 *   - ShiftTemplatePolicy (update: same-location pass, cross-location 403)
 *   - ManagerLogPolicy (update/delete: same-location pass, cross-location 403)
 *   - UserPolicy (update/delete: same-location pass, cross-location 403)
 *   - SchedulePolicy (view/unpublish: same-location pass, cross-location 403)
 *   - ScheduleEntryPolicy (delete: same-location pass, cross-location 403)
 */
class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed two locations with a manager each, for cross-location tests.
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
            'email' => 'managera@policy.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $locationA->id,
        ]);

        $managerB = User::create([
            'name' => 'Manager B',
            'email' => 'managerb@policy.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $locationB->id,
        ]);

        $staffA = User::create([
            'name' => 'Staff A',
            'email' => 'staffa@policy.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $locationA->id,
        ]);

        return compact('locationA', 'locationB', 'managerA', 'managerB', 'staffA');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CATEGORY POLICY
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_update_category_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $category = Category::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Appetizers',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/categories/{$category->id}", [
                'name' => 'Starters',
            ]);

        $response->assertOk();
    }

    public function test_manager_cannot_update_category_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $category = Category::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Appetizers',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/categories/{$category->id}", [
                'name' => 'Hijacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_manager_can_delete_category_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $category = Category::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Delete Me',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertNoContent();
    }

    public function test_manager_cannot_delete_category_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $category = Category::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Protected',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  MENU ITEM POLICY
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_update_menu_item_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $menuItem = MenuItem::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Burger',
            'type' => 'food',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/menu-items/{$menuItem->id}", [
                'name' => 'Cheeseburger',
                'type' => 'food',
            ]);

        $response->assertOk();
    }

    public function test_manager_cannot_update_menu_item_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $menuItem = MenuItem::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Burger',
            'type' => 'food',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/menu-items/{$menuItem->id}", [
                'name' => 'Hijacked',
                'type' => 'food',
            ]);

        $response->assertStatus(403);
    }

    public function test_manager_can_delete_menu_item_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $menuItem = MenuItem::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Delete Me',
            'type' => 'food',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->deleteJson("/api/menu-items/{$menuItem->id}");

        $response->assertNoContent();
    }

    public function test_manager_cannot_delete_menu_item_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $menuItem = MenuItem::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Protected',
            'type' => 'food',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->deleteJson("/api/menu-items/{$menuItem->id}");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EVENT POLICY
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_update_event_at_own_location(): void
    {
        EventFacade::fake();
        $seed = $this->seedTwoLocations();

        $event = Event::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Wine Night',
            'event_date' => now()->addDay()->toDateString(),
            'created_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/events/{$event->id}", [
                'title' => 'Wine & Cheese Night',
                'event_date' => now()->addDay()->toDateString(),
            ]);

        $response->assertOk();
    }

    public function test_manager_cannot_update_event_at_other_location(): void
    {
        EventFacade::fake();
        $seed = $this->seedTwoLocations();

        $event = Event::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Wine Night',
            'event_date' => now()->addDay()->toDateString(),
            'created_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/events/{$event->id}", [
                'title' => 'Hijacked',
                'event_date' => now()->addDay()->toDateString(),
            ]);

        $response->assertStatus(403);
    }

    public function test_manager_can_delete_event_at_own_location(): void
    {
        EventFacade::fake();
        $seed = $this->seedTwoLocations();

        $event = Event::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Delete Me',
            'event_date' => now()->addDay()->toDateString(),
            'created_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertNoContent();
    }

    public function test_manager_cannot_delete_event_at_other_location(): void
    {
        EventFacade::fake();
        $seed = $this->seedTwoLocations();

        $event = Event::create([
            'location_id' => $seed['locationA']->id,
            'title' => 'Protected',
            'event_date' => now()->addDay()->toDateString(),
            'created_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SHIFT TEMPLATE POLICY (update only — delete already in MissingCoverageTest)
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_update_shift_template_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $template = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/shift-templates/{$template->id}", [
                'name' => 'Early Lunch',
                'start_time' => '10:00',
            ]);

        $response->assertOk();
    }

    public function test_manager_cannot_update_shift_template_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $template = ShiftTemplate::create([
            'location_id' => $seed['locationA']->id,
            'name' => 'Lunch',
            'start_time' => '10:30',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/shift-templates/{$template->id}", [
                'name' => 'Hijacked',
                'start_time' => '10:30',
            ]);

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  MANAGER LOG POLICY
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_update_manager_log_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $log = ManagerLog::create([
            'location_id' => $seed['locationA']->id,
            'created_by' => $seed['managerA']->id,
            'log_date' => now()->toDateString(),
            'body' => 'Original log',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/manager-logs/{$log->id}", [
                'body' => 'Updated log',
            ]);

        $response->assertOk();
    }

    public function test_manager_cannot_update_manager_log_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $log = ManagerLog::create([
            'location_id' => $seed['locationA']->id,
            'created_by' => $seed['managerA']->id,
            'log_date' => now()->toDateString(),
            'body' => 'Original log',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/manager-logs/{$log->id}", [
                'body' => 'Hijacked',
            ]);

        $response->assertStatus(403);
    }

    public function test_manager_can_delete_manager_log_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $log = ManagerLog::create([
            'location_id' => $seed['locationA']->id,
            'created_by' => $seed['managerA']->id,
            'log_date' => now()->toDateString(),
            'body' => 'Delete me',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->deleteJson("/api/manager-logs/{$log->id}");

        $response->assertNoContent();
    }

    public function test_manager_cannot_delete_manager_log_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $log = ManagerLog::create([
            'location_id' => $seed['locationA']->id,
            'created_by' => $seed['managerA']->id,
            'log_date' => now()->toDateString(),
            'body' => 'Protected',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->deleteJson("/api/manager-logs/{$log->id}");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  USER POLICY
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_update_user_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->patchJson("/api/users/{$seed['staffA']->id}", [
                'name' => 'Updated Staff',
                'email' => $seed['staffA']->email,
                'role' => 'server',
            ]);

        $response->assertOk();
    }

    public function test_manager_cannot_update_user_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->patchJson("/api/users/{$seed['staffA']->id}", [
                'name' => 'Hijacked',
                'email' => $seed['staffA']->email,
                'role' => 'server',
            ]);

        $response->assertStatus(403);
    }

    public function test_manager_can_delete_user_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->deleteJson("/api/users/{$seed['staffA']->id}");

        $response->assertNoContent();
    }

    public function test_manager_cannot_delete_user_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->deleteJson("/api/users/{$seed['staffA']->id}");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SCHEDULE POLICY (view/unpublish — update/publish already in MissingCoverageTest)
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_view_schedule_at_own_location(): void
    {
        $seed = $this->seedTwoLocations();

        $schedule = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => now()->next('Monday')->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->getJson("/api/schedules/{$schedule->id}");

        $response->assertOk();
    }

    public function test_manager_cannot_view_schedule_at_other_location(): void
    {
        $seed = $this->seedTwoLocations();

        $schedule = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => now()->next('Monday')->toDateString(),
            'status' => 'draft',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->getJson("/api/schedules/{$schedule->id}");

        $response->assertStatus(403);
    }

    public function test_manager_can_unpublish_schedule_at_own_location(): void
    {
        EventFacade::fake();
        $seed = $this->seedTwoLocations();

        $schedule = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => now()->next('Monday')->toDateString(),
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->postJson("/api/schedules/{$schedule->id}/unpublish");

        $response->assertOk();
    }

    public function test_manager_cannot_unpublish_schedule_at_other_location(): void
    {
        EventFacade::fake();
        $seed = $this->seedTwoLocations();

        $schedule = Schedule::create([
            'location_id' => $seed['locationA']->id,
            'week_start' => now()->next('Monday')->toDateString(),
            'status' => 'published',
            'published_at' => now(),
            'published_by' => $seed['managerA']->id,
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->postJson("/api/schedules/{$schedule->id}/unpublish");

        $response->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SCHEDULE ENTRY POLICY (delete — update already in MissingCoverageTest)
    // ══════════════════════════════════════════════════════════════════════

    public function test_manager_can_delete_schedule_entry_at_own_location(): void
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
        ]);

        $entry = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staffA']->id,
            'shift_template_id' => $template->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        $response = $this->actingAs($seed['managerA'], 'sanctum')
            ->deleteJson("/api/schedule-entries/{$entry->id}");

        $response->assertNoContent();
    }

    public function test_manager_cannot_delete_schedule_entry_at_other_location(): void
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
        ]);

        $entry = ScheduleEntry::create([
            'schedule_id' => $schedule->id,
            'user_id' => $seed['staffA']->id,
            'shift_template_id' => $template->id,
            'date' => $nextMonday,
            'role' => 'server',
        ]);

        $response = $this->actingAs($seed['managerB'], 'sanctum')
            ->deleteJson("/api/schedule-entries/{$entry->id}");

        $response->assertStatus(403);
    }
}
