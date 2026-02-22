<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\Setting;
use App\Models\ShiftTemplate;
use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TimeOffAutomationTest extends TestCase
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

        $superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
            'is_superadmin' => true,
        ]);

        return compact('location', 'manager', 'staff', 'superadmin');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  ADVANCE NOTICE VALIDATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_time_off_rejected_when_start_date_less_than_n_days_away(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Default advance is 14 days; request 5 days from now should fail
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(7)->toDateString(),
                'reason' => 'Too soon',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('start_date');

        $errors = $response->json('errors.start_date');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('14 days', $errors[0]);
    }

    public function test_time_off_accepted_when_start_date_exactly_n_days_away(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Default advance is 14 days; request exactly 14 days from now should pass
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => now()->addDays(14)->toDateString(),
                'end_date' => now()->addDays(16)->toDateString(),
                'reason' => 'Planned vacation',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'pending');
    }

    public function test_advance_notice_respects_custom_setting_value(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Set advance days to 3
        Setting::set('time_off_advance_days', '3');

        // Request 4 days out should pass with custom 3-day setting
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => now()->addDays(4)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
                'reason' => 'Short notice OK',
            ]);

        $response->assertStatus(201);
    }

    public function test_advance_notice_defaults_to_14_when_setting_not_configured(): void
    {
        $seed = $this->seedLocationAndUsers();

        // Ensure no setting exists
        Setting::where('key', 'time_off_advance_days')->delete();

        // 10 days out should fail (default 14)
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => now()->addDays(10)->toDateString(),
                'end_date' => now()->addDays(12)->toDateString(),
                'reason' => 'Should fail',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('start_date');

        // 15 days out should pass
        $response2 = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/time-off-requests', [
                'start_date' => now()->addDays(15)->toDateString(),
                'end_date' => now()->addDays(17)->toDateString(),
                'reason' => 'Should pass',
            ]);

        $response2->assertStatus(201);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SCHEDULE ENTRY BLOCKED BY APPROVED TIME OFF
    // ══════════════════════════════════════════════════════════════════════

    public function test_schedule_entry_rejected_when_user_has_approved_time_off(): void
    {
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

        // Create approved time off covering the target date
        TimeOffRequest::create([
            'user_id' => $seed['staff']->id,
            'location_id' => $seed['location']->id,
            'start_date' => $nextMonday,
            'end_date' => now()->next('Monday')->addDays(2)->toDateString(),
            'reason' => 'Vacation',
            'status' => 'approved',
            'resolved_by' => $seed['manager']->id,
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $template->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('user_id');

        $errors = $response->json('errors.user_id');
        $this->assertContains('This user has approved time off on this date.', $errors);
    }

    public function test_schedule_entry_allowed_when_user_has_no_time_off(): void
    {
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

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $template->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        $response->assertStatus(201);
    }

    public function test_schedule_entry_allowed_when_time_off_is_pending(): void
    {
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

        // Create PENDING (not approved) time off covering the target date
        TimeOffRequest::create([
            'user_id' => $seed['staff']->id,
            'location_id' => $seed['location']->id,
            'start_date' => $nextMonday,
            'end_date' => now()->next('Monday')->addDays(2)->toDateString(),
            'reason' => 'Maybe vacation',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/schedule-entries', [
                'schedule_id' => $schedule->id,
                'user_id' => $seed['staff']->id,
                'shift_template_id' => $template->id,
                'date' => $nextMonday,
                'role' => 'server',
            ]);

        $response->assertStatus(201);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SUPERADMIN SETTING
    // ══════════════════════════════════════════════════════════════════════

    public function test_superadmin_can_update_time_off_advance_days_setting(): void
    {
        $seed = $this->seedLocationAndUsers();

        Setting::set('establishment_name', 'Test Place');

        $response = $this->actingAs($seed['superadmin'], 'sanctum')
            ->putJson('/api/config/settings', [
                'establishment_name' => 'Test Place',
                'time_off_advance_days' => 7,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('7', Setting::get('time_off_advance_days'));
    }
}
