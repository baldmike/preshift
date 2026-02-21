<?php

namespace Tests\Unit;

use App\Models\Acknowledgment;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\EightySixed;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\PushItem;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ShiftDrop;
use App\Models\ShiftDropVolunteer;
use App\Models\ShiftTemplate;
use App\Models\Special;
use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Unit tests for all Eloquent models in the application.
 *
 * These tests verify that model relationships, attribute casts, query scopes,
 * and helper methods all behave as expected. Every test creates its own data
 * directly (no factories) so the test is self-contained and easy to reason about.
 *
 * RefreshDatabase is used so each test starts with a clean database, preventing
 * cross-test contamination from leftover rows.
 */
class ModelTest extends TestCase
{
    /**
     * RefreshDatabase wraps each test in a transaction and rolls it back after,
     * giving every test method a pristine database to work with.
     */
    use RefreshDatabase;

    // =========================================================================
    //  SHARED HELPER — creates a Location so FK constraints are satisfied
    // =========================================================================

    /**
     * Create a Location record that other models can reference via location_id.
     * Almost every model in this app requires a location FK, so this helper
     * prevents duplicating the same Location::create() call in every test.
     *
     * @return Location  The freshly created Location model instance.
     */
    private function createLocation(): Location
    {
        return Location::create([
            'name'     => 'Test Location',
            'address'  => '123 Main St',
            'timezone' => 'America/New_York',
        ]);
    }

    /**
     * Create a User record tied to the given location. Accepts an optional
     * array of attribute overrides so tests can easily control role, email,
     * availability, etc.
     *
     * @param  Location            $location  The location this user belongs to.
     * @param  array<string,mixed> $overrides Attributes to override the defaults.
     * @return User                The freshly created User model instance.
     */
    private function createUser(Location $location, array $overrides = []): User
    {
        // Merge caller-provided overrides on top of sensible defaults.
        // Hash::make is used explicitly so the password column is populated
        // without relying on the model's 'hashed' cast (which would double-hash
        // if we passed a pre-hashed value).
        return User::create(array_merge([
            'location_id' => $location->id,
            'name'        => 'Test User',
            'email'       => 'testuser_' . uniqid() . '@example.com', // unique per call
            'password'    => 'password',     // the 'hashed' cast auto-hashes this
            'role'        => 'server',       // default role — override as needed
        ], $overrides));
    }

    // =========================================================================
    //  USER MODEL TESTS
    // =========================================================================

    /**
     * Verify that isAdmin() returns true ONLY when the user's role is 'admin'.
     *
     * The isAdmin() helper is used throughout the app to grant system-wide
     * access (e.g., skipping location checks in EnsureLocationAccess middleware).
     * If this method misbehaves, admins lose access or non-admins gain it.
     */
    public function test_user_is_admin_returns_true_only_for_admin_role(): void
    {
        $location = $this->createLocation();

        // Create an admin user — isAdmin() should return true for this user.
        $admin = $this->createUser($location, ['role' => 'admin']);
        $this->assertTrue(
            $admin->isAdmin(),
            'isAdmin() must return true when role is "admin".'
        );

        // Create a manager — isAdmin() should return false because they are NOT admin.
        $manager = $this->createUser($location, ['role' => 'manager']);
        $this->assertFalse(
            $manager->isAdmin(),
            'isAdmin() must return false when role is "manager".'
        );

        // Create a server — isAdmin() should return false for regular staff.
        $server = $this->createUser($location, ['role' => 'server']);
        $this->assertFalse(
            $server->isAdmin(),
            'isAdmin() must return false when role is "server".'
        );

        // Create a bartender — isAdmin() should return false for regular staff.
        $bartender = $this->createUser($location, ['role' => 'bartender']);
        $this->assertFalse(
            $bartender->isAdmin(),
            'isAdmin() must return false when role is "bartender".'
        );
    }

    /**
     * Verify that isManager() returns true ONLY when the user's role is 'manager'.
     *
     * Managers can create specials, announcements, push items, and 86 items —
     * actions that server/bartender roles cannot perform. This method is the
     * gatekeeper for those capabilities.
     */
    public function test_user_is_manager_returns_true_only_for_manager_role(): void
    {
        $location = $this->createLocation();

        // A manager user — isManager() should be true.
        $manager = $this->createUser($location, ['role' => 'manager']);
        $this->assertTrue(
            $manager->isManager(),
            'isManager() must return true when role is "manager".'
        );

        // An admin is NOT a manager — isManager() should be false.
        $admin = $this->createUser($location, ['role' => 'admin']);
        $this->assertFalse(
            $admin->isManager(),
            'isManager() must return false when role is "admin".'
        );

        // Server is not a manager.
        $server = $this->createUser($location, ['role' => 'server']);
        $this->assertFalse(
            $server->isManager(),
            'isManager() must return false when role is "server".'
        );

        // Bartender is not a manager.
        $bartender = $this->createUser($location, ['role' => 'bartender']);
        $this->assertFalse(
            $bartender->isManager(),
            'isManager() must return false when role is "bartender".'
        );
    }

    /**
     * Verify that isStaff() returns true for "server" and "bartender" roles,
     * and false for "admin" and "manager" roles.
     *
     * "Staff" in this app means front-of-house workers who can view pre-shift
     * data and submit acknowledgments but cannot create or modify content.
     */
    public function test_user_is_staff_returns_true_for_server_and_bartender(): void
    {
        $location = $this->createLocation();

        // Server should be staff.
        $server = $this->createUser($location, ['role' => 'server']);
        $this->assertTrue(
            $server->isStaff(),
            'isStaff() must return true when role is "server".'
        );

        // Bartender should also be staff.
        $bartender = $this->createUser($location, ['role' => 'bartender']);
        $this->assertTrue(
            $bartender->isStaff(),
            'isStaff() must return true when role is "bartender".'
        );

        // Admin is NOT staff — they are above the staff tier.
        $admin = $this->createUser($location, ['role' => 'admin']);
        $this->assertFalse(
            $admin->isStaff(),
            'isStaff() must return false when role is "admin".'
        );

        // Manager is NOT staff — they are above the staff tier.
        $manager = $this->createUser($location, ['role' => 'manager']);
        $this->assertFalse(
            $manager->isStaff(),
            'isStaff() must return false when role is "manager".'
        );
    }

    /**
     * Verify that isSuperAdmin() returns true only when is_superadmin is true.
     */
    public function test_user_is_superadmin_returns_correct_boolean(): void
    {
        $location = $this->createLocation();

        $superadmin = $this->createUser($location, ['is_superadmin' => true]);
        $this->assertTrue($superadmin->isSuperAdmin());

        $regular = $this->createUser($location, ['is_superadmin' => false]);
        $this->assertFalse($regular->isSuperAdmin());

        // Default (not explicitly set) should be false
        $default = $this->createUser($location);
        $this->assertFalse($default->isSuperAdmin());
    }

    /**
     * Verify that User->location() returns a BelongsTo relationship pointing
     * at the Location model, and that it resolves to the correct location.
     *
     * This relationship is critical because location_id scopes almost every
     * query in the app. If this relationship breaks, users would appear
     * orphaned from their venue.
     */
    public function test_user_location_relationship_returns_belongs_to(): void
    {
        $location = $this->createLocation();
        $user     = $this->createUser($location);

        // Assert that the relationship method itself returns a BelongsTo instance.
        // This catches accidental changes (e.g., someone refactoring it to HasOne).
        $this->assertInstanceOf(
            BelongsTo::class,
            $user->location(),
            'User->location() must return a BelongsTo relationship.'
        );

        // Assert that resolving the relationship gives back the correct Location.
        $this->assertEquals(
            $location->id,
            $user->location->id,
            'User->location should resolve to the Location the user was created with.'
        );
    }

    /**
     * Verify that User->acknowledgments() returns a HasMany relationship
     * and that Acknowledgment records created for the user are returned.
     *
     * Acknowledgments are the "read receipt" records that prove a staff member
     * has seen a piece of pre-shift content (86, special, announcement, etc.).
     */
    public function test_user_acknowledgments_relationship_returns_has_many(): void
    {
        $location = $this->createLocation();
        $user     = $this->createUser($location, ['role' => 'manager']);

        // Create an 86'd item so we have something to acknowledge.
        $eightySixed = EightySixed::create([
            'location_id'    => $location->id,
            'item_name'      => 'Test Item',
            'eighty_sixed_by' => $user->id,
        ]);

        // Create an acknowledgment linked to the user (polymorphic on the 86 record).
        Acknowledgment::create([
            'user_id'             => $user->id,
            'acknowledgable_type' => EightySixed::class,
            'acknowledgable_id'   => $eightySixed->id,
            'acknowledged_at'     => now(),
        ]);

        // The relationship method must return a HasMany instance.
        $this->assertInstanceOf(
            HasMany::class,
            $user->acknowledgments(),
            'User->acknowledgments() must return a HasMany relationship.'
        );

        // The user should have exactly one acknowledgment.
        $this->assertCount(
            1,
            $user->acknowledgments,
            'User should have one acknowledgment record after creating one.'
        );
    }

    /**
     * Verify that the 'availability' attribute is cast to an array.
     *
     * The availability column stores a JSON string in the database (e.g.,
     * {"monday": true, "tuesday": false}). The 'array' cast should automatically
     * decode it when reading and encode it when writing.
     */
    public function test_user_availability_is_cast_to_array(): void
    {
        $location = $this->createLocation();

        // The availability data we want to store — a PHP array that should be
        // JSON-encoded in the DB and decoded back to an array on read.
        $availabilityData = [
            'monday'    => true,
            'tuesday'   => false,
            'wednesday' => true,
            'thursday'  => true,
            'friday'    => false,
        ];

        $user = $this->createUser($location, [
            'availability' => $availabilityData,
        ]);

        // Re-fetch from the database to ensure the cast round-trips correctly.
        $freshUser = User::find($user->id);

        // The availability attribute must be a PHP array (not a JSON string).
        $this->assertIsArray(
            $freshUser->availability,
            'User->availability must be cast to a PHP array.'
        );

        // The decoded array must match what we originally stored.
        $this->assertEquals(
            $availabilityData,
            $freshUser->availability,
            'User->availability should round-trip through JSON encoding correctly.'
        );
    }

    // =========================================================================
    //  LOCATION MODEL TESTS
    // =========================================================================

    /**
     * Verify that Location->users() returns a HasMany relationship
     * containing User records that belong to this location.
     *
     * This is the inverse of User->location(). It lets managers see all
     * staff assigned to their venue.
     */
    public function test_location_users_relationship_returns_has_many(): void
    {
        $location = $this->createLocation();

        // Create two users at this location to verify the collection count.
        $this->createUser($location, ['role' => 'server']);
        $this->createUser($location, ['role' => 'bartender']);

        // The method itself must return a HasMany instance.
        $this->assertInstanceOf(
            HasMany::class,
            $location->users(),
            'Location->users() must return a HasMany relationship.'
        );

        // The location should have exactly 2 users.
        $this->assertCount(
            2,
            $location->users,
            'Location should have 2 users after creating 2.'
        );
    }

    /**
     * Verify that Location->menuItems() returns a HasMany relationship
     * containing MenuItem records scoped to this location.
     *
     * Menu items are the core data that 86 records, specials, and push items
     * reference. This relationship powers the menu management UI.
     */
    public function test_location_menu_items_relationship_returns_has_many(): void
    {
        $location = $this->createLocation();

        // Create a category first (menu items require a category FK).
        $category = Category::create([
            'location_id' => $location->id,
            'name'        => 'Appetizers',
            'sort_order'  => 1,
        ]);

        // Create a menu item at this location.
        MenuItem::create([
            'location_id' => $location->id,
            'category_id' => $category->id,
            'name'        => 'Nachos',
            'price'       => 12.99,
            'type'        => 'food',
        ]);

        // The method must return a HasMany instance.
        $this->assertInstanceOf(
            HasMany::class,
            $location->menuItems(),
            'Location->menuItems() must return a HasMany relationship.'
        );

        // There should be exactly 1 menu item at this location.
        $this->assertCount(
            1,
            $location->menuItems,
            'Location should have 1 menu item after creating one.'
        );
    }

    /**
     * Verify that the Location model defines HasMany relationships for
     * categories, eightySixed, specials, pushItems, and announcements.
     *
     * Rather than creating data for each one (which the individual model tests
     * already cover), we simply verify the relationship methods exist and
     * return the correct relationship type. This guards against accidental
     * deletion of a relationship method during refactoring.
     */
    public function test_location_has_other_expected_relationships(): void
    {
        $location = $this->createLocation();

        // categories() — HasMany<Category>
        $this->assertInstanceOf(
            HasMany::class,
            $location->categories(),
            'Location->categories() must return a HasMany relationship.'
        );

        // eightySixed() — HasMany<EightySixed>
        $this->assertInstanceOf(
            HasMany::class,
            $location->eightySixed(),
            'Location->eightySixed() must return a HasMany relationship.'
        );

        // specials() — HasMany<Special>
        $this->assertInstanceOf(
            HasMany::class,
            $location->specials(),
            'Location->specials() must return a HasMany relationship.'
        );

        // pushItems() — HasMany<PushItem>
        $this->assertInstanceOf(
            HasMany::class,
            $location->pushItems(),
            'Location->pushItems() must return a HasMany relationship.'
        );

        // announcements() — HasMany<Announcement>
        $this->assertInstanceOf(
            HasMany::class,
            $location->announcements(),
            'Location->announcements() must return a HasMany relationship.'
        );
    }

    // =========================================================================
    //  SCHEDULE MODEL TESTS
    // =========================================================================

    /**
     * Verify that isPublished() returns true when status is 'published'
     * and false when status is 'draft'.
     *
     * This helper is used by controllers and policies to decide whether
     * staff can view the schedule. If it breaks, staff might see draft
     * schedules or miss published ones.
     */
    public function test_schedule_is_published_returns_correct_boolean(): void
    {
        $location = $this->createLocation();

        // A published schedule — isPublished() should be true.
        $published = Schedule::create([
            'location_id'  => $location->id,
            'week_start'   => '2026-02-23',
            'status'       => 'published',
            'published_at' => now(),
        ]);

        $this->assertTrue(
            $published->isPublished(),
            'isPublished() must return true when status is "published".'
        );

        // A draft schedule — isPublished() should be false.
        $draft = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-03-02',
            'status'      => 'draft',
        ]);

        $this->assertFalse(
            $draft->isPublished(),
            'isPublished() must return false when status is "draft".'
        );
    }

    /**
     * Verify that Schedule->location() returns a BelongsTo relationship
     * resolving to the correct Location.
     */
    public function test_schedule_location_relationship(): void
    {
        $location = $this->createLocation();
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-02-23',
            'status'      => 'draft',
        ]);

        // The relationship method must return a BelongsTo instance.
        $this->assertInstanceOf(
            BelongsTo::class,
            $schedule->location(),
            'Schedule->location() must return a BelongsTo relationship.'
        );

        // It must resolve to the same location we created.
        $this->assertEquals(
            $location->id,
            $schedule->location->id,
            'Schedule->location should resolve to the correct Location.'
        );
    }

    /**
     * Verify that Schedule->entries() returns a HasMany of ScheduleEntry
     * and that Schedule->publisher() returns a BelongsTo via the
     * custom 'published_by' foreign key.
     */
    public function test_schedule_entries_and_publisher_relationships(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);
        $server   = $this->createUser($location, ['role' => 'server']);

        // Create a shift template (required FK for schedule entries).
        $template = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Dinner',
            'start_time'  => '16:00:00',
        ]);

        // Create a published schedule with a publisher.
        $schedule = Schedule::create([
            'location_id'  => $location->id,
            'week_start'   => '2026-02-23',
            'status'       => 'published',
            'published_at' => now(),
            'published_by' => $manager->id,
        ]);

        // Create a schedule entry inside this schedule.
        ScheduleEntry::create([
            'schedule_id'       => $schedule->id,
            'user_id'           => $server->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-02-24',
            'role'              => 'server',
        ]);

        // entries() must be HasMany and contain the entry we created.
        $this->assertInstanceOf(
            HasMany::class,
            $schedule->entries(),
            'Schedule->entries() must return a HasMany relationship.'
        );
        $this->assertCount(
            1,
            $schedule->entries,
            'Schedule should have 1 entry after creating one.'
        );

        // publisher() must be BelongsTo and resolve to the manager.
        $this->assertInstanceOf(
            BelongsTo::class,
            $schedule->publisher(),
            'Schedule->publisher() must return a BelongsTo relationship.'
        );
        $this->assertEquals(
            $manager->id,
            $schedule->publisher->id,
            'Schedule->publisher should resolve to the user who published it.'
        );
    }

    /**
     * Verify that the 'week_start' attribute is cast to a date (Carbon instance)
     * and that 'published_at' is cast to a datetime (Carbon instance).
     *
     * Proper casting ensures date comparisons and formatting work correctly
     * throughout the schedule management features.
     */
    public function test_schedule_date_casts(): void
    {
        $location = $this->createLocation();

        $schedule = Schedule::create([
            'location_id'  => $location->id,
            'week_start'   => '2026-02-23',
            'status'       => 'published',
            'published_at' => '2026-02-22 10:30:00',
        ]);

        // Re-fetch to test the casts from raw DB values.
        $fresh = Schedule::find($schedule->id);

        // week_start should be a Carbon instance (the 'date' cast).
        $this->assertInstanceOf(
            Carbon::class,
            $fresh->week_start,
            'Schedule->week_start must be cast to a Carbon date instance.'
        );

        // published_at should be a Carbon instance (the 'datetime' cast).
        $this->assertInstanceOf(
            Carbon::class,
            $fresh->published_at,
            'Schedule->published_at must be cast to a Carbon datetime instance.'
        );
    }

    // =========================================================================
    //  SCHEDULE ENTRY MODEL TESTS
    // =========================================================================

    /**
     * Verify that ScheduleEntry has correct BelongsTo relationships for
     * schedule(), user(), and shiftTemplate().
     *
     * These relationships are the backbone of the scheduling system — each
     * entry links a person to a shift on a specific date within a weekly
     * schedule.
     */
    public function test_schedule_entry_belongs_to_relationships(): void
    {
        $location = $this->createLocation();
        $user     = $this->createUser($location, ['role' => 'server']);
        $template = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Lunch',
            'start_time'  => '10:30:00',
        ]);
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-02-23',
            'status'      => 'draft',
        ]);

        $entry = ScheduleEntry::create([
            'schedule_id'       => $schedule->id,
            'user_id'           => $user->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-02-24',
            'role'              => 'server',
        ]);

        // schedule() — must be BelongsTo and resolve correctly.
        $this->assertInstanceOf(BelongsTo::class, $entry->schedule());
        $this->assertEquals(
            $schedule->id,
            $entry->schedule->id,
            'ScheduleEntry->schedule should resolve to the parent Schedule.'
        );

        // user() — must be BelongsTo and resolve correctly.
        $this->assertInstanceOf(BelongsTo::class, $entry->user());
        $this->assertEquals(
            $user->id,
            $entry->user->id,
            'ScheduleEntry->user should resolve to the assigned User.'
        );

        // shiftTemplate() — must be BelongsTo and resolve correctly.
        $this->assertInstanceOf(BelongsTo::class, $entry->shiftTemplate());
        $this->assertEquals(
            $template->id,
            $entry->shiftTemplate->id,
            'ScheduleEntry->shiftTemplate should resolve to the ShiftTemplate.'
        );
    }

    /**
     * Verify that ScheduleEntry->shiftDrops() returns a HasMany relationship
     * for ShiftDrop records filed against this entry.
     */
    public function test_schedule_entry_shift_drops_relationship(): void
    {
        $location = $this->createLocation();
        $user     = $this->createUser($location, ['role' => 'server']);
        $template = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Dinner',
            'start_time'  => '16:00:00',
        ]);
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-02-23',
            'status'      => 'published',
        ]);
        $entry = ScheduleEntry::create([
            'schedule_id'       => $schedule->id,
            'user_id'           => $user->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-02-25',
            'role'              => 'server',
        ]);

        // Create a shift drop for this entry.
        ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by'      => $user->id,
            'reason'            => 'Sick',
            'status'            => 'open',
        ]);

        // shiftDrops() must be HasMany.
        $this->assertInstanceOf(
            HasMany::class,
            $entry->shiftDrops(),
            'ScheduleEntry->shiftDrops() must return a HasMany relationship.'
        );

        // Should contain the drop we just created.
        $this->assertCount(
            1,
            $entry->shiftDrops,
            'ScheduleEntry should have 1 shift drop after creating one.'
        );
    }

    /**
     * Verify that ScheduleEntry->date is cast to a Carbon date instance,
     * ensuring date operations work correctly.
     */
    public function test_schedule_entry_date_cast(): void
    {
        $location = $this->createLocation();
        $user     = $this->createUser($location, ['role' => 'server']);
        $template = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Brunch',
            'start_time'  => '09:00:00',
        ]);
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-02-23',
            'status'      => 'draft',
        ]);

        $entry = ScheduleEntry::create([
            'schedule_id'       => $schedule->id,
            'user_id'           => $user->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-02-24',
            'role'              => 'server',
        ]);

        $fresh = ScheduleEntry::find($entry->id);

        $this->assertInstanceOf(
            Carbon::class,
            $fresh->date,
            'ScheduleEntry->date must be cast to a Carbon date instance.'
        );
    }

    // =========================================================================
    //  SHIFT DROP MODEL TESTS
    // =========================================================================

    /**
     * Verify that ShiftDrop has correct BelongsTo relationships for
     * scheduleEntry(), requester() (custom FK 'requested_by'), and
     * filler() (custom FK 'filled_by').
     *
     * These relationships connect a dropped shift back to the original
     * schedule entry, the person who dropped it, and (eventually) the
     * person who picked it up.
     */
    public function test_shift_drop_belongs_to_relationships(): void
    {
        $location  = $this->createLocation();
        $requester = $this->createUser($location, ['role' => 'server']);
        $filler    = $this->createUser($location, ['role' => 'bartender']);
        $template  = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Dinner',
            'start_time'  => '16:00:00',
        ]);
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-02-23',
            'status'      => 'published',
        ]);
        $entry = ScheduleEntry::create([
            'schedule_id'       => $schedule->id,
            'user_id'           => $requester->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-02-26',
            'role'              => 'server',
        ]);

        // Create a shift drop that has been filled.
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by'      => $requester->id,
            'reason'            => 'Family emergency',
            'status'            => 'filled',
            'filled_by'         => $filler->id,
            'filled_at'         => now(),
        ]);

        // scheduleEntry() — must resolve to the correct ScheduleEntry.
        $this->assertInstanceOf(BelongsTo::class, $drop->scheduleEntry());
        $this->assertEquals(
            $entry->id,
            $drop->scheduleEntry->id,
            'ShiftDrop->scheduleEntry should resolve to the correct ScheduleEntry.'
        );

        // requester() — uses custom FK 'requested_by', must resolve to the requester user.
        $this->assertInstanceOf(BelongsTo::class, $drop->requester());
        $this->assertEquals(
            $requester->id,
            $drop->requester->id,
            'ShiftDrop->requester should resolve to the user who requested the drop.'
        );

        // filler() — uses custom FK 'filled_by', must resolve to the filler user.
        $this->assertInstanceOf(BelongsTo::class, $drop->filler());
        $this->assertEquals(
            $filler->id,
            $drop->filler->id,
            'ShiftDrop->filler should resolve to the user who filled the shift.'
        );
    }

    /**
     * Verify that ShiftDrop->volunteers() returns a HasMany of
     * ShiftDropVolunteer records.
     */
    public function test_shift_drop_volunteers_relationship(): void
    {
        $location  = $this->createLocation();
        $requester = $this->createUser($location, ['role' => 'server']);
        $volunteer = $this->createUser($location, ['role' => 'bartender']);
        $template  = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Lunch',
            'start_time'  => '10:30:00',
        ]);
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-02-23',
            'status'      => 'published',
        ]);
        $entry = ScheduleEntry::create([
            'schedule_id'       => $schedule->id,
            'user_id'           => $requester->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-02-24',
            'role'              => 'server',
        ]);
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by'      => $requester->id,
            'status'            => 'open',
        ]);

        // A volunteer raises their hand for this dropped shift.
        ShiftDropVolunteer::create([
            'shift_drop_id' => $drop->id,
            'user_id'       => $volunteer->id,
            'selected'      => false,
        ]);

        // volunteers() must be HasMany and contain the volunteer record.
        $this->assertInstanceOf(
            HasMany::class,
            $drop->volunteers(),
            'ShiftDrop->volunteers() must return a HasMany relationship.'
        );
        $this->assertCount(
            1,
            $drop->volunteers,
            'ShiftDrop should have 1 volunteer after creating one.'
        );
    }

    /**
     * Verify that ShiftDrop->filled_at is cast to a datetime (Carbon instance).
     *
     * This timestamp records when a manager selected a volunteer to fill
     * the dropped shift. Casting ensures Carbon methods work on it.
     */
    public function test_shift_drop_filled_at_cast(): void
    {
        $location  = $this->createLocation();
        $requester = $this->createUser($location, ['role' => 'server']);
        $template  = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Dinner',
            'start_time'  => '16:00:00',
        ]);
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-02-23',
            'status'      => 'published',
        ]);
        $entry = ScheduleEntry::create([
            'schedule_id'       => $schedule->id,
            'user_id'           => $requester->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-02-25',
            'role'              => 'server',
        ]);

        $drop = ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by'      => $requester->id,
            'status'            => 'filled',
            'filled_at'         => '2026-02-24 14:30:00',
        ]);

        // Re-fetch to test cast from raw DB value.
        $fresh = ShiftDrop::find($drop->id);

        $this->assertInstanceOf(
            Carbon::class,
            $fresh->filled_at,
            'ShiftDrop->filled_at must be cast to a Carbon datetime instance.'
        );
    }

    // =========================================================================
    //  SHIFT DROP VOLUNTEER MODEL TESTS
    // =========================================================================

    /**
     * Verify that ShiftDropVolunteer sets UPDATED_AT to null (disabling
     * the updated_at column) and that 'selected' is cast to boolean.
     *
     * The shift_drop_volunteers table intentionally has no updated_at column
     * (only created_at). Setting UPDATED_AT = null tells Eloquent not to
     * try writing to a column that does not exist.
     */
    public function test_shift_drop_volunteer_updated_at_is_null_and_selected_is_boolean(): void
    {
        // Verify the UPDATED_AT constant is null at the class level.
        $this->assertNull(
            ShiftDropVolunteer::UPDATED_AT,
            'ShiftDropVolunteer::UPDATED_AT must be null (no updated_at column).'
        );

        // Now create a record and verify the 'selected' cast.
        $location  = $this->createLocation();
        $requester = $this->createUser($location, ['role' => 'server']);
        $volunteer = $this->createUser($location, ['role' => 'bartender']);
        $template  = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Dinner',
            'start_time'  => '16:00:00',
        ]);
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-02-23',
            'status'      => 'published',
        ]);
        $entry = ScheduleEntry::create([
            'schedule_id'       => $schedule->id,
            'user_id'           => $requester->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-02-26',
            'role'              => 'server',
        ]);
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by'      => $requester->id,
            'status'            => 'open',
        ]);

        // Create volunteer record with selected = false (integer 0 in DB).
        $vol = ShiftDropVolunteer::create([
            'shift_drop_id' => $drop->id,
            'user_id'       => $volunteer->id,
            'selected'      => false,
        ]);

        // Re-fetch and verify that 'selected' is a boolean, not an integer.
        $fresh = ShiftDropVolunteer::find($vol->id);
        $this->assertIsBool(
            $fresh->selected,
            'ShiftDropVolunteer->selected must be cast to a boolean.'
        );
        $this->assertFalse(
            $fresh->selected,
            'ShiftDropVolunteer->selected should be false when stored as 0/false.'
        );
    }

    /**
     * Verify that ShiftDropVolunteer has BelongsTo relationships for
     * shiftDrop() and user().
     *
     * Each volunteer record connects a user who raised their hand to
     * the specific shift drop they are volunteering for.
     */
    public function test_shift_drop_volunteer_relationships(): void
    {
        $location  = $this->createLocation();
        $requester = $this->createUser($location, ['role' => 'server']);
        $volunteer = $this->createUser($location, ['role' => 'bartender']);
        $template  = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Lunch',
            'start_time'  => '10:30:00',
        ]);
        $schedule = Schedule::create([
            'location_id' => $location->id,
            'week_start'  => '2026-02-23',
            'status'      => 'published',
        ]);
        $entry = ScheduleEntry::create([
            'schedule_id'       => $schedule->id,
            'user_id'           => $requester->id,
            'shift_template_id' => $template->id,
            'date'              => '2026-02-24',
            'role'              => 'server',
        ]);
        $drop = ShiftDrop::create([
            'schedule_entry_id' => $entry->id,
            'requested_by'      => $requester->id,
            'status'            => 'open',
        ]);

        $vol = ShiftDropVolunteer::create([
            'shift_drop_id' => $drop->id,
            'user_id'       => $volunteer->id,
            'selected'      => false,
        ]);

        // shiftDrop() — must resolve to the parent ShiftDrop.
        $this->assertInstanceOf(BelongsTo::class, $vol->shiftDrop());
        $this->assertEquals(
            $drop->id,
            $vol->shiftDrop->id,
            'ShiftDropVolunteer->shiftDrop should resolve to the parent ShiftDrop.'
        );

        // user() — must resolve to the volunteering User.
        $this->assertInstanceOf(BelongsTo::class, $vol->user());
        $this->assertEquals(
            $volunteer->id,
            $vol->user->id,
            'ShiftDropVolunteer->user should resolve to the volunteer User.'
        );
    }

    // =========================================================================
    //  EIGHTY SIXED MODEL TESTS
    // =========================================================================

    /**
     * Verify that scopeActive() only returns records where restored_at IS NULL.
     *
     * An "active" 86 means the item is STILL unavailable. Once restored_at is
     * set, the item is back in stock and should be excluded from active queries.
     * This scope is used on every 86 list endpoint.
     */
    public function test_eighty_sixed_scope_active_filters_correctly(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);

        // Create an active 86 record (restored_at is NULL — item is still out).
        $active = EightySixed::create([
            'location_id'     => $location->id,
            'item_name'       => 'Salmon',
            'eighty_sixed_by' => $manager->id,
            // restored_at defaults to null
        ]);

        // Create a restored 86 record (restored_at is set — item is back).
        $restored = EightySixed::create([
            'location_id'     => $location->id,
            'item_name'       => 'Tuna',
            'eighty_sixed_by' => $manager->id,
            'restored_at'     => now(), // item has been restored
        ]);

        // Apply the active scope — should only return Salmon.
        $activeRecords = EightySixed::active()->get();

        $this->assertCount(
            1,
            $activeRecords,
            'scopeActive() should return only records with NULL restored_at.'
        );
        $this->assertEquals(
            'Salmon',
            $activeRecords->first()->item_name,
            'The active record should be the one without a restored_at timestamp.'
        );
    }

    /**
     * Verify that EightySixed->user() uses the custom FK 'eighty_sixed_by'
     * and resolves to the correct User who flagged the item.
     *
     * The column is NOT named 'user_id' (it is 'eighty_sixed_by'), so the
     * relationship must explicitly pass this custom column name to belongsTo.
     */
    public function test_eighty_sixed_user_relationship_uses_custom_fk(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);

        $eightySixed = EightySixed::create([
            'location_id'     => $location->id,
            'item_name'       => 'Lobster',
            'eighty_sixed_by' => $manager->id,
        ]);

        // user() must be BelongsTo.
        $this->assertInstanceOf(
            BelongsTo::class,
            $eightySixed->user(),
            'EightySixed->user() must return a BelongsTo relationship.'
        );

        // It must resolve to the manager who performed the 86.
        $this->assertEquals(
            $manager->id,
            $eightySixed->user->id,
            'EightySixed->user should resolve to the user identified by eighty_sixed_by FK.'
        );
    }

    /**
     * Verify that EightySixed->acknowledgments() returns a MorphMany
     * relationship (polymorphic), allowing staff to acknowledge 86 records.
     */
    public function test_eighty_sixed_acknowledgments_morph_many(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);
        $server   = $this->createUser($location, ['role' => 'server']);

        $eightySixed = EightySixed::create([
            'location_id'     => $location->id,
            'item_name'       => 'Crab Cakes',
            'eighty_sixed_by' => $manager->id,
        ]);

        // Server acknowledges the 86.
        Acknowledgment::create([
            'user_id'             => $server->id,
            'acknowledgable_type' => EightySixed::class,
            'acknowledgable_id'   => $eightySixed->id,
            'acknowledged_at'     => now(),
        ]);

        // acknowledgments() must be MorphMany.
        $this->assertInstanceOf(
            MorphMany::class,
            $eightySixed->acknowledgments(),
            'EightySixed->acknowledgments() must return a MorphMany relationship.'
        );

        // Should have exactly 1 acknowledgment.
        $this->assertCount(
            1,
            $eightySixed->acknowledgments,
            'EightySixed should have 1 acknowledgment after creating one.'
        );
    }

    // =========================================================================
    //  ANNOUNCEMENT MODEL TESTS
    // =========================================================================

    /**
     * Verify that scopeActive() includes announcements that:
     *   - Have a NULL expires_at (never expire), and
     *   - Have an expires_at in the future (not yet expired)
     * And EXCLUDES announcements where expires_at is in the past.
     *
     * This scope is applied on every announcements listing to hide stale content.
     */
    public function test_announcement_scope_active_includes_non_expired_and_null(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);

        // Announcement with no expiry — should always be included.
        $noExpiry = Announcement::create([
            'location_id' => $location->id,
            'title'       => 'Permanent Notice',
            'body'        => 'This never expires.',
            'priority'    => 'normal',
            'posted_by'   => $manager->id,
            // expires_at defaults to null
        ]);

        // Announcement expiring in the future — should be included.
        $futureExpiry = Announcement::create([
            'location_id' => $location->id,
            'title'       => 'Future Expiry',
            'body'        => 'Expires tomorrow.',
            'priority'    => 'normal',
            'posted_by'   => $manager->id,
            'expires_at'  => now()->addDay(),
        ]);

        // Announcement that has already expired — should be EXCLUDED.
        $pastExpiry = Announcement::create([
            'location_id' => $location->id,
            'title'       => 'Expired Notice',
            'body'        => 'This expired yesterday.',
            'priority'    => 'normal',
            'posted_by'   => $manager->id,
            'expires_at'  => now()->subDay(),
        ]);

        // Run the active scope.
        $activeAnnouncements = Announcement::active()->get();
        $activeTitles        = $activeAnnouncements->pluck('title')->toArray();

        // Should include the non-expiring and future-expiring announcements.
        $this->assertContains(
            'Permanent Notice',
            $activeTitles,
            'scopeActive() must include announcements with NULL expires_at.'
        );
        $this->assertContains(
            'Future Expiry',
            $activeTitles,
            'scopeActive() must include announcements with future expires_at.'
        );

        // Should exclude the expired announcement.
        $this->assertNotContains(
            'Expired Notice',
            $activeTitles,
            'scopeActive() must exclude announcements with past expires_at.'
        );
    }

    /**
     * Verify that scopeForRole() includes announcements with NULL target_roles
     * (visible to everyone) AND announcements whose target_roles JSON array
     * contains the specified role. It must EXCLUDE announcements targeted at
     * a different role.
     *
     * This scope ensures that a server only sees announcements meant for
     * servers (or all roles), and not announcements targeted at bartenders only.
     */
    public function test_announcement_scope_for_role_filters_correctly(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);

        // Announcement for everyone (null target_roles).
        $forAll = Announcement::create([
            'location_id' => $location->id,
            'title'       => 'For Everyone',
            'body'        => 'All staff read this.',
            'priority'    => 'normal',
            'posted_by'   => $manager->id,
            // target_roles defaults to null
        ]);

        // Announcement targeted at servers only.
        $forServers = Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'Server Memo',
            'body'         => 'Servers only.',
            'priority'     => 'normal',
            'posted_by'    => $manager->id,
            'target_roles' => ['server'],
        ]);

        // Announcement targeted at bartenders only.
        $forBartenders = Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'Bartender Memo',
            'body'         => 'Bartenders only.',
            'priority'     => 'normal',
            'posted_by'    => $manager->id,
            'target_roles' => ['bartender'],
        ]);

        // Filter for the 'server' role.
        $serverAnnouncements = Announcement::forRole('server')->get();
        $serverTitles        = $serverAnnouncements->pluck('title')->toArray();

        // Server should see the "for everyone" and "server memo" announcements.
        $this->assertContains(
            'For Everyone',
            $serverTitles,
            'scopeForRole() must include announcements with NULL target_roles.'
        );
        $this->assertContains(
            'Server Memo',
            $serverTitles,
            'scopeForRole("server") must include announcements targeted at "server".'
        );

        // Server should NOT see the bartender-only announcement.
        $this->assertNotContains(
            'Bartender Memo',
            $serverTitles,
            'scopeForRole("server") must exclude announcements targeted only at "bartender".'
        );
    }

    /**
     * Verify that the 'target_roles' attribute is cast to an array.
     *
     * This column stores a JSON array in the database (e.g., ["server","bartender"]).
     * The 'array' cast decodes it to a PHP array on read and encodes it on write.
     */
    public function test_announcement_target_roles_cast_to_array(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);

        $announcement = Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'Targeted',
            'body'         => 'Test body.',
            'priority'     => 'normal',
            'posted_by'    => $manager->id,
            'target_roles' => ['server', 'bartender'],
        ]);

        // Re-fetch from DB to verify the cast round-trips correctly.
        $fresh = Announcement::find($announcement->id);

        $this->assertIsArray(
            $fresh->target_roles,
            'Announcement->target_roles must be cast to a PHP array.'
        );
        $this->assertEquals(
            ['server', 'bartender'],
            $fresh->target_roles,
            'Announcement->target_roles should round-trip through JSON correctly.'
        );
    }

    /**
     * Verify that Announcement->poster() returns a BelongsTo relationship
     * using the custom FK 'posted_by' and resolves to the authoring user.
     */
    public function test_announcement_poster_relationship_uses_custom_fk(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);

        $announcement = Announcement::create([
            'location_id' => $location->id,
            'title'       => 'Test Announcement',
            'body'        => 'Test body.',
            'priority'    => 'normal',
            'posted_by'   => $manager->id,
        ]);

        // poster() must be BelongsTo.
        $this->assertInstanceOf(
            BelongsTo::class,
            $announcement->poster(),
            'Announcement->poster() must return a BelongsTo relationship.'
        );

        // It must resolve to the manager who posted it.
        $this->assertEquals(
            $manager->id,
            $announcement->poster->id,
            'Announcement->poster should resolve to the user identified by posted_by FK.'
        );
    }

    // =========================================================================
    //  SPECIAL MODEL TESTS
    // =========================================================================

    /**
     * Verify that scopeCurrent() returns only specials that are:
     *   1. is_active = true
     *   2. starts_at <= today
     *   3. ends_at is null OR ends_at >= today
     *
     * This scope drives the pre-shift dashboard's specials section. If it
     * misbehaves, staff will see expired or inactive specials.
     */
    public function test_special_scope_current_filters_correctly(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);

        // Active special that started yesterday, no end date — should be included.
        $currentOpen = Special::create([
            'location_id' => $location->id,
            'title'       => 'Current Open-Ended',
            'type'        => 'daily',
            'starts_at'   => now()->subDay()->toDateString(),
            'is_active'   => true,
            'created_by'  => $manager->id,
        ]);

        // Active special that started yesterday and ends tomorrow — should be included.
        $currentBounded = Special::create([
            'location_id' => $location->id,
            'title'       => 'Current Bounded',
            'type'        => 'weekly',
            'starts_at'   => now()->subDay()->toDateString(),
            'ends_at'     => now()->addDay()->toDateString(),
            'is_active'   => true,
            'created_by'  => $manager->id,
        ]);

        // Inactive special (is_active = false) — should be EXCLUDED even if dates are valid.
        $inactive = Special::create([
            'location_id' => $location->id,
            'title'       => 'Inactive Special',
            'type'        => 'daily',
            'starts_at'   => now()->subDay()->toDateString(),
            'is_active'   => false,
            'created_by'  => $manager->id,
        ]);

        // Active special that ended yesterday — should be EXCLUDED.
        $expired = Special::create([
            'location_id' => $location->id,
            'title'       => 'Expired Special',
            'type'        => 'limited_time',
            'starts_at'   => now()->subWeek()->toDateString(),
            'ends_at'     => now()->subDay()->toDateString(),
            'is_active'   => true,
            'created_by'  => $manager->id,
        ]);

        // Run the current scope.
        $currentSpecials = Special::current()->get();
        $currentTitles   = $currentSpecials->pluck('title')->toArray();

        // Should include both current specials.
        $this->assertContains(
            'Current Open-Ended',
            $currentTitles,
            'scopeCurrent() must include active, open-ended specials that have started.'
        );
        $this->assertContains(
            'Current Bounded',
            $currentTitles,
            'scopeCurrent() must include active specials within their date range.'
        );

        // Should exclude the inactive and expired specials.
        $this->assertNotContains(
            'Inactive Special',
            $currentTitles,
            'scopeCurrent() must exclude specials where is_active is false.'
        );
        $this->assertNotContains(
            'Expired Special',
            $currentTitles,
            'scopeCurrent() must exclude specials whose ends_at is in the past.'
        );
    }

    /**
     * Verify that Special->creator() returns a BelongsTo relationship
     * using the custom FK 'created_by' and resolves to the creating user.
     */
    public function test_special_creator_relationship_uses_custom_fk(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);

        $special = Special::create([
            'location_id' => $location->id,
            'title'       => 'Happy Hour',
            'type'        => 'daily',
            'starts_at'   => now()->toDateString(),
            'is_active'   => true,
            'created_by'  => $manager->id,
        ]);

        // creator() must be BelongsTo.
        $this->assertInstanceOf(
            BelongsTo::class,
            $special->creator(),
            'Special->creator() must return a BelongsTo relationship.'
        );

        // It must resolve to the manager who created the special.
        $this->assertEquals(
            $manager->id,
            $special->creator->id,
            'Special->creator should resolve to the user identified by created_by FK.'
        );
    }

    /**
     * Verify that the 'quantity' attribute is cast to an integer.
     *
     * The quantity column stores the limited number of a special available
     * (e.g., "only 20 orders of the prix fixe"). The integer cast ensures
     * arithmetic and comparisons work correctly, rather than treating it
     * as a string.
     */
    public function test_special_quantity_cast_to_integer(): void
    {
        $location = $this->createLocation();
        $manager  = $this->createUser($location, ['role' => 'manager']);

        $special = Special::create([
            'location_id' => $location->id,
            'title'       => 'Limited Offer',
            'type'        => 'limited_time',
            'starts_at'   => now()->toDateString(),
            'is_active'   => true,
            'quantity'    => 25,
            'created_by'  => $manager->id,
        ]);

        // Re-fetch from DB to verify the cast.
        $fresh = Special::find($special->id);

        $this->assertIsInt(
            $fresh->quantity,
            'Special->quantity must be cast to an integer.'
        );
        $this->assertSame(
            25,
            $fresh->quantity,
            'Special->quantity should return the exact integer value stored.'
        );
    }

    // =========================================================================
    //  TIME OFF REQUEST MODEL TESTS
    // =========================================================================

    /**
     * Verify that scopeApproved() only returns records with status='approved'.
     *
     * This scope is used in the schedule builder to display approved time-off
     * so managers can avoid scheduling conflicts. If pending or denied requests
     * leak through, the schedule builder shows incorrect availability.
     */
    public function test_time_off_request_scope_approved(): void
    {
        $location = $this->createLocation();
        $user     = $this->createUser($location, ['role' => 'server']);

        // Create three requests with different statuses.
        TimeOffRequest::create([
            'user_id'     => $user->id,
            'location_id' => $location->id,
            'start_date'  => '2026-03-01',
            'end_date'    => '2026-03-02',
            'status'      => 'approved',
        ]);

        TimeOffRequest::create([
            'user_id'     => $user->id,
            'location_id' => $location->id,
            'start_date'  => '2026-03-05',
            'end_date'    => '2026-03-06',
            'status'      => 'pending',
        ]);

        TimeOffRequest::create([
            'user_id'     => $user->id,
            'location_id' => $location->id,
            'start_date'  => '2026-03-10',
            'end_date'    => '2026-03-11',
            'status'      => 'denied',
        ]);

        // Only the approved request should appear.
        $approved = TimeOffRequest::approved()->get();

        $this->assertCount(
            1,
            $approved,
            'scopeApproved() should return only records with status "approved".'
        );
        $this->assertEquals(
            'approved',
            $approved->first()->status,
            'The returned record must have status "approved".'
        );
    }

    /**
     * Verify that TimeOffRequest->user() and TimeOffRequest->resolver() both
     * return BelongsTo relationships and resolve to the correct users.
     *
     * user() uses the standard FK (user_id), while resolver() uses the
     * custom FK 'resolved_by' pointing to the manager who approved/denied.
     */
    public function test_time_off_request_user_and_resolver_relationships(): void
    {
        $location = $this->createLocation();
        $staff    = $this->createUser($location, ['role' => 'server']);
        $manager  = $this->createUser($location, ['role' => 'manager']);

        $request = TimeOffRequest::create([
            'user_id'     => $staff->id,
            'location_id' => $location->id,
            'start_date'  => '2026-03-01',
            'end_date'    => '2026-03-02',
            'status'      => 'approved',
            'resolved_by' => $manager->id,
            'resolved_at' => now(),
        ]);

        // user() — standard FK, must resolve to the staff member.
        $this->assertInstanceOf(BelongsTo::class, $request->user());
        $this->assertEquals(
            $staff->id,
            $request->user->id,
            'TimeOffRequest->user should resolve to the requesting staff member.'
        );

        // resolver() — custom FK 'resolved_by', must resolve to the manager.
        $this->assertInstanceOf(BelongsTo::class, $request->resolver());
        $this->assertEquals(
            $manager->id,
            $request->resolver->id,
            'TimeOffRequest->resolver should resolve to the manager who resolved it.'
        );
    }

    /**
     * Verify that TimeOffRequest date casts work correctly for start_date,
     * end_date, and resolved_at.
     *
     * start_date and end_date are 'date' casts (Carbon date without time),
     * while resolved_at is 'datetime' (Carbon with time). Proper casting
     * ensures date arithmetic and comparisons work in the schedule builder.
     */
    public function test_time_off_request_date_casts(): void
    {
        $location = $this->createLocation();
        $user     = $this->createUser($location, ['role' => 'server']);

        $request = TimeOffRequest::create([
            'user_id'     => $user->id,
            'location_id' => $location->id,
            'start_date'  => '2026-03-01',
            'end_date'    => '2026-03-03',
            'status'      => 'approved',
            'resolved_at' => '2026-02-28 09:15:00',
        ]);

        // Re-fetch to test casts from raw DB values.
        $fresh = TimeOffRequest::find($request->id);

        // start_date — should be a Carbon instance (date cast).
        $this->assertInstanceOf(
            Carbon::class,
            $fresh->start_date,
            'TimeOffRequest->start_date must be cast to a Carbon date instance.'
        );

        // end_date — should be a Carbon instance (date cast).
        $this->assertInstanceOf(
            Carbon::class,
            $fresh->end_date,
            'TimeOffRequest->end_date must be cast to a Carbon date instance.'
        );

        // resolved_at — should be a Carbon instance (datetime cast).
        $this->assertInstanceOf(
            Carbon::class,
            $fresh->resolved_at,
            'TimeOffRequest->resolved_at must be cast to a Carbon datetime instance.'
        );
    }
}
