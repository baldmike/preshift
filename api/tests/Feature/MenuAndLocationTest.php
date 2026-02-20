<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * MenuAndLocationTest — comprehensive feature tests for Categories, Menu Items,
 * Location management (admin-only), and User management (manager-level).
 *
 * This test class follows the same conventions established in SmokeTest.php:
 *   - RefreshDatabase trait to ensure a clean database state for every test.
 *   - A shared seedLocationAndUsers() helper that provisions a Location, a
 *     manager-role User, and a server-role (staff) User.
 *   - actingAs($user, 'sanctum') for simulating authenticated API requests.
 *   - Direct Eloquent model creation (no factories) for transparency.
 *
 * The tests are organized into four major sections:
 *   1. Categories   — CRUD operations restricted to manager/admin roles.
 *   2. Menu Items   — CRUD, category filtering, and role-based access denial.
 *   3. Locations    — Admin-only CRUD; managers are denied access (403).
 *   4. Users        — Manager-scoped CRUD, password hashing, and auto-assignment
 *                     of location_id when not explicitly provided.
 */
class MenuAndLocationTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────
    // Helper: seed a location + manager + staff user
    // ──────────────────────────────────────────────

    /**
     * Create a base Location, a manager User, and a server (staff) User.
     *
     * This mirrors the exact pattern used in SmokeTest.php so that every test
     * starts with a predictable, minimal dataset. Each test calls this method
     * at the top, then adds whatever additional records it needs.
     *
     * @return array{location: Location, manager: User, staff: User}
     */
    private function seedLocationAndUsers(): array
    {
        // Create a single location that all seeded users will belong to.
        // The location has a name, physical address, and IANA timezone.
        $location = Location::create([
            'name' => 'Test Location',
            'address' => '123 Main St',
            'timezone' => 'America/New_York',
        ]);

        // Create a manager-role user assigned to the location above.
        // Managers can create/edit categories, menu items, users, and other
        // operational resources, but they are scoped to their own location.
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        // Create a server-role user (front-of-house staff) at the same location.
        // Staff members have read-only access to most resources and cannot perform
        // write operations on categories, menu items, or user accounts.
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
    //  CATEGORY TESTS
    // ══════════════════════════════════════════════
    //
    // Categories group menu items into logical sections (e.g., "Appetizers",
    // "Entrees", "Cocktails"). Each category belongs to a single location and
    // has an optional sort_order for display sequencing.
    //
    // The GET endpoint is available to all authenticated users at the location.
    // POST, PATCH, and DELETE are restricted to admin and manager roles via
    // the 'role:admin,manager' middleware.
    // ══════════════════════════════════════════════

    /**
     * Test: Manager can list categories scoped to their location, ordered by sort_order.
     *
     * This verifies several behaviors:
     *   1. The GET /api/categories endpoint returns a 200 OK response.
     *   2. Categories are scoped to the authenticated user's location — a category
     *      belonging to a different location must NOT appear in the response.
     *   3. Results are ordered by the sort_order column in ascending order, so the
     *      category with the lowest sort_order appears first.
     */
    public function test_manager_can_list_categories(): void
    {
        // Seed the base location, manager, and staff user.
        $seed = $this->seedLocationAndUsers();

        // Create three categories at the manager's location with explicit sort
        // orders. We intentionally insert them out of order to verify the API
        // returns them sorted by sort_order ascending.
        Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Desserts',
            'sort_order' => 3, // should appear last
        ]);
        Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Appetizers',
            'sort_order' => 1, // should appear first
        ]);
        Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Entrees',
            'sort_order' => 2, // should appear in the middle
        ]);

        // Create a second location with its own category. This category must NOT
        // appear in the response when the manager queries their own location.
        $otherLocation = Location::create([
            'name' => 'Other Location',
            'address' => '456 Elm St',
            'timezone' => 'America/Chicago',
        ]);
        Category::create([
            'location_id' => $otherLocation->id,
            'name' => 'Other Category',
            'sort_order' => 1,
        ]);

        // Act: The manager requests the list of categories.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/categories');

        // Assert: The response status is 200 OK.
        $response->assertOk();

        // Extract the category names from the response to verify ordering and scoping.
        $names = array_column($response->json(), 'name');

        // There should be exactly 3 categories (only the ones at the manager's location).
        $this->assertCount(3, $names);

        // Verify sort order: Appetizers (1) -> Entrees (2) -> Desserts (3).
        $this->assertEquals('Appetizers', $names[0]);
        $this->assertEquals('Entrees', $names[1]);
        $this->assertEquals('Desserts', $names[2]);

        // The category from the other location must not be present.
        $this->assertNotContains('Other Category', $names);
    }

    /**
     * Test: Manager can create a new category via POST /api/categories.
     *
     * This verifies:
     *   1. A manager-role user can successfully create a category by sending a
     *      POST request with a name and sort_order.
     *   2. The API responds with 201 Created and returns the newly created
     *      category as JSON.
     *   3. The returned JSON includes the correct name and sort_order values.
     *   4. The category record is persisted in the database with the manager's
     *      location_id automatically assigned by the controller.
     */
    public function test_manager_can_create_category(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Act: The manager sends a POST request to create a new "Cocktails" category
        // with a sort_order of 5 (arbitrary positioning within the menu).
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/categories', [
                'name' => 'Cocktails',
                'sort_order' => 5,
            ]);

        // Assert: The response status is 201 Created.
        $response->assertStatus(201);

        // Assert: The response JSON contains the correct name for the new category.
        $response->assertJsonPath('name', 'Cocktails');

        // Assert: The response JSON contains the correct sort_order value.
        $response->assertJsonPath('sort_order', 5);

        // Assert: The category was actually persisted in the database with the
        // manager's location_id, confirming the controller auto-assigned it.
        $this->assertDatabaseHas('categories', [
            'name' => 'Cocktails',
            'sort_order' => 5,
            'location_id' => $seed['location']->id,
        ]);
    }

    /**
     * Test: Manager can update an existing category via PATCH /api/categories/{id}.
     *
     * This verifies:
     *   1. A PATCH request with updated name and sort_order returns 200 OK.
     *   2. The response JSON reflects the updated values.
     *   3. The database record is actually updated (not just returned with stale data).
     */
    public function test_manager_can_update_category(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Create a category that we will subsequently update.
        $category = Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Starters',
            'sort_order' => 1,
        ]);

        // Act: The manager sends a PATCH request to rename the category from
        // "Starters" to "Appetizers" and change the sort_order from 1 to 2.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/categories/{$category->id}", [
                'name' => 'Appetizers',
                'sort_order' => 2,
            ]);

        // Assert: The response status is 200 OK, indicating a successful update.
        $response->assertOk();

        // Assert: The response JSON shows the updated category name.
        $response->assertJsonPath('name', 'Appetizers');

        // Assert: The response JSON shows the updated sort_order.
        $response->assertJsonPath('sort_order', 2);

        // Assert: The change was persisted in the database. We query the database
        // directly to confirm the update was not just in-memory.
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Appetizers',
            'sort_order' => 2,
        ]);
    }

    /**
     * Test: Manager can delete a category via DELETE /api/categories/{id}.
     *
     * This verifies:
     *   1. The DELETE endpoint returns a 204 No Content response (empty body).
     *   2. The category record is actually removed from the database after deletion.
     */
    public function test_manager_can_delete_category(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Create a category that we will delete.
        $category = Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Temporary Category',
            'sort_order' => 99,
        ]);

        // Sanity check: Confirm the category exists in the database before we delete it.
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Temporary Category',
        ]);

        // Act: The manager sends a DELETE request to remove the category.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/categories/{$category->id}");

        // Assert: The response is 204 No Content, the standard for successful deletions.
        $response->assertNoContent();

        // Assert: The category no longer exists in the database.
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    // ══════════════════════════════════════════════
    //  MENU ITEM TESTS
    // ══════════════════════════════════════════════
    //
    // Menu items represent individual dishes and drinks on a location's menu.
    // Each item belongs to a location and optionally to a category. Items have
    // a type (food, drink, or both), price, description, allergen info, and
    // active/new status flags.
    //
    // GET is open to all authenticated users (including staff). POST, PATCH,
    // and DELETE are restricted to admin and manager roles. Filtering by
    // category_id is supported via a query parameter on the index endpoint.
    // ══════════════════════════════════════════════

    /**
     * Test: Staff (server) can list menu items scoped to their location.
     *
     * This verifies:
     *   1. A server-role user can access GET /api/menu-items (read access is not
     *      restricted to managers).
     *   2. The response is 200 OK and contains items for the user's location.
     *   3. Items from a different location are NOT included in the response,
     *      confirming proper location scoping.
     *   4. The related category is eager-loaded and present in the response.
     */
    public function test_staff_can_list_menu_items(): void
    {
        // Seed the base location, manager, and staff user.
        $seed = $this->seedLocationAndUsers();

        // Create a category to associate with the menu item, so we can verify
        // the category relationship is eager-loaded in the response.
        $category = Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Entrees',
            'sort_order' => 1,
        ]);

        // Create a menu item at the staff user's location.
        MenuItem::create([
            'location_id' => $seed['location']->id,
            'category_id' => $category->id,
            'name' => 'Grilled Salmon',
            'description' => 'Fresh Atlantic salmon with lemon butter sauce',
            'price' => 24.99,
            'type' => 'food',
            'is_new' => true,
            'is_active' => true,
            'allergens' => ['fish', 'dairy'],
        ]);

        // Create a second location with its own menu item. This item must NOT
        // appear when the staff user at the first location queries their menu.
        $otherLocation = Location::create([
            'name' => 'Other Location',
            'address' => '456 Elm St',
            'timezone' => 'America/Chicago',
        ]);
        $otherCategory = Category::create([
            'location_id' => $otherLocation->id,
            'name' => 'Sides',
            'sort_order' => 1,
        ]);
        MenuItem::create([
            'location_id' => $otherLocation->id,
            'category_id' => $otherCategory->id,
            'name' => 'Other Location Burger',
            'price' => 15.00,
            'type' => 'food',
        ]);

        // Act: The staff (server) user requests the menu items list.
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/menu-items');

        // Assert: The response status is 200 OK.
        $response->assertOk();

        // Extract the item names from the response payload.
        $names = array_column($response->json(), 'name');

        // Assert: The response includes the menu item from the staff's location.
        $this->assertContains('Grilled Salmon', $names);

        // Assert: The response does NOT include the item from the other location.
        $this->assertNotContains('Other Location Burger', $names);

        // Assert: The category relationship is eager-loaded. The first item in the
        // response should have a nested 'category' key with the correct category name.
        $firstItem = $response->json()[0];
        $this->assertArrayHasKey('category', $firstItem);
        $this->assertEquals('Entrees', $firstItem['category']['name']);
    }

    /**
     * Test: Manager can create a menu item via POST /api/menu-items.
     *
     * This verifies:
     *   1. A manager-role user can create a menu item by providing all required
     *      and optional fields: name, description, price, type, category_id,
     *      is_new, is_active, and allergens.
     *   2. The API responds with 201 Created.
     *   3. The returned JSON reflects all the submitted field values.
     *   4. The menu item is persisted in the database with the manager's
     *      location_id automatically assigned by the controller.
     */
    public function test_manager_can_create_menu_item(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Create a category to assign to the new menu item.
        $category = Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Appetizers',
            'sort_order' => 1,
        ]);

        // Act: The manager creates a new menu item with all available fields.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/menu-items', [
                'name' => 'Truffle Fries',
                'description' => 'Hand-cut fries with truffle oil and parmesan',
                'price' => 12.50,
                'type' => 'food',
                'category_id' => $category->id,
                'is_new' => true,
                'is_active' => true,
                'allergens' => ['gluten', 'dairy'],
            ]);

        // Assert: The response status is 201 Created, confirming successful creation.
        $response->assertStatus(201);

        // Assert: The response JSON contains the correct item name.
        $response->assertJsonPath('name', 'Truffle Fries');

        // Assert: The response JSON contains the correct description.
        $response->assertJsonPath('description', 'Hand-cut fries with truffle oil and parmesan');

        // Assert: The response JSON contains the correct price (formatted as a decimal string).
        $response->assertJsonPath('price', '12.50');

        // Assert: The response JSON contains the correct type classification.
        $response->assertJsonPath('type', 'food');

        // Assert: The response JSON contains the correct category_id foreign key.
        $response->assertJsonPath('category_id', $category->id);

        // Assert: The is_new flag is set to true as submitted.
        $response->assertJsonPath('is_new', true);

        // Assert: The is_active flag is set to true as submitted.
        $response->assertJsonPath('is_active', true);

        // Assert: The allergens array was stored correctly as JSON.
        $response->assertJsonPath('allergens', ['gluten', 'dairy']);

        // Assert: The menu item record exists in the database with the correct
        // location_id (auto-assigned from the manager's location).
        $this->assertDatabaseHas('menu_items', [
            'name' => 'Truffle Fries',
            'location_id' => $seed['location']->id,
            'category_id' => $category->id,
            'type' => 'food',
        ]);
    }

    /**
     * Test: Manager can update an existing menu item via PATCH /api/menu-items/{id}.
     *
     * This verifies:
     *   1. A PATCH request with modified values returns 200 OK.
     *   2. The response JSON reflects the updated field values.
     *   3. The database record is actually updated, not just returned with stale data.
     */
    public function test_manager_can_update_menu_item(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Create a category for the menu item.
        $category = Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Entrees',
            'sort_order' => 1,
        ]);

        // Create the menu item that we will subsequently update.
        $menuItem = MenuItem::create([
            'location_id' => $seed['location']->id,
            'category_id' => $category->id,
            'name' => 'Grilled Chicken',
            'description' => 'Herb-marinated chicken breast',
            'price' => 18.00,
            'type' => 'food',
            'is_new' => false,
            'is_active' => true,
            'allergens' => [],
        ]);

        // Act: The manager updates the menu item's name, price, and description.
        // Note: The 'type' field is required by the update validation rules, so
        // we must include it even if it hasn't changed.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/menu-items/{$menuItem->id}", [
                'name' => 'Grilled Chicken Supreme',
                'description' => 'Herb-marinated chicken breast with seasonal vegetables',
                'price' => 21.00,
                'type' => 'food',
                'category_id' => $category->id,
                'is_new' => true,
                'is_active' => true,
                'allergens' => ['soy'],
            ]);

        // Assert: The response status is 200 OK, confirming a successful update.
        $response->assertOk();

        // Assert: The response JSON reflects the updated name.
        $response->assertJsonPath('name', 'Grilled Chicken Supreme');

        // Assert: The response JSON reflects the updated price.
        $response->assertJsonPath('price', '21.00');

        // Assert: The response JSON reflects the updated description.
        $response->assertJsonPath('description', 'Herb-marinated chicken breast with seasonal vegetables');

        // Assert: The is_new flag was toggled from false to true.
        $response->assertJsonPath('is_new', true);

        // Assert: The allergens array was updated from empty to contain 'soy'.
        $response->assertJsonPath('allergens', ['soy']);

        // Assert: The database record reflects the changes, confirming persistence.
        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'name' => 'Grilled Chicken Supreme',
            'is_new' => true,
        ]);
    }

    /**
     * Test: Manager can delete a menu item via DELETE /api/menu-items/{id}.
     *
     * This verifies:
     *   1. The DELETE endpoint returns a 204 No Content response.
     *   2. The menu item record is removed from the database after deletion.
     */
    public function test_manager_can_delete_menu_item(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Create a category and a menu item that we will delete.
        $category = Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Sides',
            'sort_order' => 3,
        ]);

        $menuItem = MenuItem::create([
            'location_id' => $seed['location']->id,
            'category_id' => $category->id,
            'name' => 'Seasonal Soup',
            'price' => 8.00,
            'type' => 'food',
            'is_active' => true,
        ]);

        // Sanity check: Confirm the menu item exists before we attempt to delete it.
        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'name' => 'Seasonal Soup',
        ]);

        // Act: The manager sends a DELETE request to remove the menu item.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/menu-items/{$menuItem->id}");

        // Assert: The response is 204 No Content, the standard for successful deletions.
        $response->assertNoContent();

        // Assert: The menu item no longer exists in the database.
        $this->assertDatabaseMissing('menu_items', [
            'id' => $menuItem->id,
        ]);
    }

    /**
     * Test: Menu items can be filtered by category_id via query parameter.
     *
     * The GET /api/menu-items endpoint accepts an optional ?category_id=N query
     * parameter. When present, only items belonging to that category are returned.
     *
     * This verifies:
     *   1. When the category_id filter is applied, only items in the specified
     *      category appear in the response.
     *   2. Items in other categories at the same location are excluded.
     */
    public function test_can_filter_menu_items_by_category_id(): void
    {
        // Seed the base location, manager, and staff user.
        $seed = $this->seedLocationAndUsers();

        // Create two distinct categories at the same location.
        $appetizers = Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Appetizers',
            'sort_order' => 1,
        ]);
        $drinks = Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Drinks',
            'sort_order' => 2,
        ]);

        // Create a menu item in the "Appetizers" category.
        MenuItem::create([
            'location_id' => $seed['location']->id,
            'category_id' => $appetizers->id,
            'name' => 'Bruschetta',
            'price' => 9.00,
            'type' => 'food',
            'is_active' => true,
        ]);

        // Create a menu item in the "Drinks" category.
        MenuItem::create([
            'location_id' => $seed['location']->id,
            'category_id' => $drinks->id,
            'name' => 'Margarita',
            'price' => 11.00,
            'type' => 'drink',
            'is_active' => true,
        ]);

        // Act: Request menu items filtered by the "Appetizers" category ID.
        // The query parameter ?category_id=N instructs the controller to further
        // narrow the results beyond location scoping.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson("/api/menu-items?category_id={$appetizers->id}");

        // Assert: The response status is 200 OK.
        $response->assertOk();

        // Extract the item names from the filtered response.
        $names = array_column($response->json(), 'name');

        // Assert: The appetizer item IS included in the filtered results.
        $this->assertContains('Bruschetta', $names);

        // Assert: The drink item is NOT included — it belongs to a different category.
        $this->assertNotContains('Margarita', $names);

        // Assert: There is exactly 1 result (only the appetizer).
        $this->assertCount(1, $response->json());
    }

    /**
     * Test: Staff (server) cannot create menu items — role guard returns 403.
     *
     * The POST /api/menu-items endpoint is protected by the 'role:admin,manager'
     * middleware. A server-role user should receive a 403 Forbidden response when
     * attempting to create a menu item.
     *
     * This verifies:
     *   1. The role middleware correctly blocks the request with a 403 status.
     *   2. No menu item record is created in the database.
     */
    public function test_staff_cannot_create_menu_items(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Create a category so the request payload is otherwise valid.
        $category = Category::create([
            'location_id' => $seed['location']->id,
            'name' => 'Entrees',
            'sort_order' => 1,
        ]);

        // Act: The staff (server) user attempts to create a menu item.
        // This should be blocked by the role middleware before reaching the controller.
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/menu-items', [
                'name' => 'Forbidden Dish',
                'description' => 'Should not be created',
                'price' => 99.99,
                'type' => 'food',
                'category_id' => $category->id,
                'is_active' => true,
            ]);

        // Assert: The response status is 403 Forbidden, not 201 Created.
        $response->assertStatus(403);

        // Assert: No menu item with this name was persisted in the database,
        // confirming the middleware blocked the request before creation.
        $this->assertDatabaseMissing('menu_items', [
            'name' => 'Forbidden Dish',
        ]);
    }

    // ══════════════════════════════════════════════
    //  LOCATION TESTS (ADMIN ONLY)
    // ══════════════════════════════════════════════
    //
    // Location management endpoints live outside the location-scoped middleware
    // group because they manage locations themselves rather than resources within
    // one. These routes are protected by 'role:admin' middleware, meaning only
    // admin-role users can access them.
    //
    // Managers and staff receive 403 Forbidden when attempting to access these
    // endpoints.
    // ══════════════════════════════════════════════

    /**
     * Test: Admin can list all locations via GET /api/locations.
     *
     * This verifies:
     *   1. An admin-role user can successfully access the locations index endpoint.
     *   2. The response is 200 OK and contains all locations in the system.
     *   3. Multiple locations created across the system are all returned.
     */
    public function test_admin_can_list_all_locations(): void
    {
        // Seed the base location, manager, and staff user.
        $seed = $this->seedLocationAndUsers();

        // Create an admin user who has system-wide access to all locations.
        // Admins are not restricted by location scoping and can manage the
        // entire multi-location system.
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $seed['location']->id,
        ]);

        // Create a second location so we can verify the admin sees all of them.
        $secondLocation = Location::create([
            'name' => 'Second Location',
            'address' => '789 Oak Ave',
            'timezone' => 'America/Chicago',
        ]);

        // Act: The admin user requests the list of all locations.
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/locations');

        // Assert: The response status is 200 OK.
        $response->assertOk();

        // Extract location names from the response payload.
        $names = array_column($response->json(), 'name');

        // Assert: Both locations are present in the response — the admin sees everything.
        $this->assertContains('Test Location', $names);
        $this->assertContains('Second Location', $names);

        // Assert: There are exactly 2 locations in the system.
        $this->assertCount(2, $response->json());
    }

    /**
     * Test: Admin can create a new location via POST /api/locations.
     *
     * This verifies:
     *   1. An admin-role user can create a location by providing name, address,
     *      and timezone.
     *   2. The API responds with 201 Created.
     *   3. The returned JSON reflects all submitted field values.
     *   4. The location record is persisted in the database.
     */
    public function test_admin_can_create_location(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Create an admin user for this test.
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $seed['location']->id,
        ]);

        // Act: The admin creates a new location with all fields populated.
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/locations', [
                'name' => 'Downtown Taproom',
                'address' => '100 Craft Beer Lane',
                'timezone' => 'America/Los_Angeles',
            ]);

        // Assert: The response status is 201 Created, confirming successful creation.
        $response->assertStatus(201);

        // Assert: The response JSON contains the correct location name.
        $response->assertJsonPath('name', 'Downtown Taproom');

        // Assert: The response JSON contains the correct address.
        $response->assertJsonPath('address', '100 Craft Beer Lane');

        // Assert: The response JSON contains the correct timezone.
        $response->assertJsonPath('timezone', 'America/Los_Angeles');

        // Assert: The location was persisted in the database.
        $this->assertDatabaseHas('locations', [
            'name' => 'Downtown Taproom',
            'address' => '100 Craft Beer Lane',
            'timezone' => 'America/Los_Angeles',
        ]);
    }

    /**
     * Test: Admin can update an existing location via PATCH /api/locations/{id}.
     *
     * This verifies:
     *   1. A PATCH request with updated fields returns 200 OK.
     *   2. The response JSON reflects the updated values.
     *   3. The database record is actually updated.
     */
    public function test_admin_can_update_location(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Create an admin user for this test.
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $seed['location']->id,
        ]);

        // Act: The admin updates the seeded location's name, address, and timezone.
        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/locations/{$seed['location']->id}", [
                'name' => 'Renamed Location',
                'address' => '999 New Address Blvd',
                'timezone' => 'America/Denver',
            ]);

        // Assert: The response status is 200 OK, confirming a successful update.
        $response->assertOk();

        // Assert: The response JSON reflects the updated name.
        $response->assertJsonPath('name', 'Renamed Location');

        // Assert: The response JSON reflects the updated address.
        $response->assertJsonPath('address', '999 New Address Blvd');

        // Assert: The response JSON reflects the updated timezone.
        $response->assertJsonPath('timezone', 'America/Denver');

        // Assert: The changes were persisted in the database.
        $this->assertDatabaseHas('locations', [
            'id' => $seed['location']->id,
            'name' => 'Renamed Location',
            'address' => '999 New Address Blvd',
            'timezone' => 'America/Denver',
        ]);
    }

    /**
     * Test: Manager cannot access location management endpoints — role guard returns 403.
     *
     * The location management routes (GET/POST/PATCH /api/locations) are protected
     * by the 'role:admin' middleware. A manager-role user should receive a 403
     * Forbidden response when attempting to access any of these endpoints.
     *
     * This verifies:
     *   1. GET /api/locations returns 403 for a manager.
     *   2. POST /api/locations returns 403 for a manager.
     *   3. PATCH /api/locations/{id} returns 403 for a manager.
     */
    public function test_manager_cannot_access_location_endpoints(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Act & Assert: The manager attempts to list all locations.
        // This should fail because the manager does not have the 'admin' role.
        $listResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/locations');
        $listResponse->assertStatus(403);

        // Act & Assert: The manager attempts to create a new location.
        // This should also fail with a 403 Forbidden response.
        $createResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/locations', [
                'name' => 'Unauthorized Location',
                'address' => '000 Nope St',
                'timezone' => 'America/New_York',
            ]);
        $createResponse->assertStatus(403);

        // Act & Assert: The manager attempts to update the existing location.
        // This should also fail with a 403 Forbidden response.
        $updateResponse = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/locations/{$seed['location']->id}", [
                'name' => 'Should Not Work',
            ]);
        $updateResponse->assertStatus(403);

        // Assert: No unauthorized location was created in the database.
        $this->assertDatabaseMissing('locations', [
            'name' => 'Unauthorized Location',
        ]);
    }

    // ══════════════════════════════════════════════
    //  USER MANAGEMENT TESTS
    // ══════════════════════════════════════════════
    //
    // User management is restricted to admin and manager roles via the
    // 'role:admin,manager' middleware group. Managers can list, create, update,
    // and delete user accounts. The controller implements several important
    // behaviors:
    //   - The index is scoped to the manager's own location (non-admins).
    //   - Creating a user with no location_id auto-assigns the creating user's
    //     location_id.
    //   - The email uniqueness check on update excludes the user being updated
    //     (so they can keep their existing email without triggering a duplicate error).
    //   - Passwords are hashed before storage using Hash::make().
    // ══════════════════════════════════════════════

    /**
     * Test: Manager can list users at their location via GET /api/users.
     *
     * This verifies:
     *   1. A manager-role user can access the users index endpoint (200 OK).
     *   2. The response includes users at the manager's own location.
     *   3. Users at a different location are NOT included in the response,
     *      confirming proper location scoping for non-admin users.
     */
    public function test_manager_can_list_users_at_their_location(): void
    {
        // Seed the base location, manager, and staff user.
        $seed = $this->seedLocationAndUsers();

        // Create a second location with its own user. This user should NOT
        // appear when the manager at the first location queries the users list.
        $otherLocation = Location::create([
            'name' => 'Other Location',
            'address' => '456 Elm St',
            'timezone' => 'America/Chicago',
        ]);
        User::create([
            'name' => 'Other Location Staff',
            'email' => 'otherlocation@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $otherLocation->id,
        ]);

        // Act: The manager requests the list of users.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->getJson('/api/users');

        // Assert: The response status is 200 OK.
        $response->assertOk();

        // Extract the user names from the response payload.
        $names = array_column($response->json(), 'name');

        // Assert: The manager and staff from the seeded location are present.
        // The seedLocationAndUsers() helper creates "Manager User" and "Server User".
        $this->assertContains('Manager User', $names);
        $this->assertContains('Server User', $names);

        // Assert: The user from the other location is NOT present.
        $this->assertNotContains('Other Location Staff', $names);

        // Assert: There are exactly 2 users at this location (manager + server).
        $this->assertCount(2, $response->json());
    }

    /**
     * Test: Manager can create a new user via POST /api/users.
     *
     * This verifies:
     *   1. A manager can create a user by providing name, email, password, role,
     *      and location_id.
     *   2. The API responds with 201 Created.
     *   3. The returned JSON contains the correct name, email, and role.
     *   4. The password is stored as a hash in the database — NOT in plain text.
     *   5. The user record is persisted in the database.
     */
    public function test_manager_can_create_user(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Act: The manager creates a new bartender user at their location.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/users', [
                'name' => 'New Bartender',
                'email' => 'newbartender@test.com',
                'password' => 'password123',
                'role' => 'bartender',
                'location_id' => $seed['location']->id,
            ]);

        // Assert: The response status is 201 Created, confirming successful creation.
        $response->assertStatus(201);

        // Assert: The response JSON contains the correct user name.
        $response->assertJsonPath('name', 'New Bartender');

        // Assert: The response JSON contains the correct role.
        $response->assertJsonPath('role', 'bartender');

        // Assert: The user record was persisted in the database with the correct
        // name, email, role, and location_id.
        $this->assertDatabaseHas('users', [
            'name' => 'New Bartender',
            'email' => 'newbartender@test.com',
            'role' => 'bartender',
            'location_id' => $seed['location']->id,
        ]);

        // Assert: The password was hashed before storage. We retrieve the user
        // record directly from the database and verify the stored password is
        // NOT the plain-text value 'password123'. Hash::check confirms the hash
        // matches the original plain-text password, proving it was properly hashed.
        $createdUser = User::where('email', 'newbartender@test.com')->first();
        $this->assertNotEquals('password123', $createdUser->password);
        $this->assertTrue(Hash::check('password123', $createdUser->password));
    }

    /**
     * Test: Manager can update a user via PATCH /api/users/{id}.
     *
     * This verifies:
     *   1. A PATCH request with updated fields returns 200 OK.
     *   2. The response JSON reflects the updated values (name, role).
     *   3. The email uniqueness check excludes the user being updated, so the
     *      user can keep their existing email without triggering a duplicate error.
     *   4. The database record is actually updated.
     */
    public function test_manager_can_update_user(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Act: The manager updates the staff user's name and role. The email
        // is kept the same ('server@test.com') to test that the email uniqueness
        // validation rule correctly excludes the user's own record (via the
        // 'unique:users,email,' . $user->id rule in the controller).
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->patchJson("/api/users/{$seed['staff']->id}", [
                'name' => 'Updated Server',
                'email' => 'server@test.com', // same email — should not trigger uniqueness violation
                'role' => 'bartender',
                'location_id' => $seed['location']->id,
            ]);

        // Assert: The response status is 200 OK, confirming a successful update.
        $response->assertOk();

        // Assert: The response JSON reflects the updated name.
        $response->assertJsonPath('name', 'Updated Server');

        // Assert: The response JSON reflects the updated role.
        $response->assertJsonPath('role', 'bartender');

        // Assert: The changes were persisted in the database.
        $this->assertDatabaseHas('users', [
            'id' => $seed['staff']->id,
            'name' => 'Updated Server',
            'role' => 'bartender',
        ]);
    }

    /**
     * Test: Manager can delete a user via DELETE /api/users/{id}.
     *
     * This verifies:
     *   1. The DELETE endpoint returns a 204 No Content response.
     *   2. The user record is removed from the database after deletion.
     */
    public function test_manager_can_delete_user(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Create a user that we will delete. We create a separate user rather
        // than deleting the seeded staff user, to avoid any side effects on the
        // seeded dataset that might affect the test's readability.
        $userToDelete = User::create([
            'name' => 'Temp Worker',
            'email' => 'tempworker@test.com',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $seed['location']->id,
        ]);

        // Sanity check: Confirm the user exists before we delete them.
        $this->assertDatabaseHas('users', [
            'id' => $userToDelete->id,
            'name' => 'Temp Worker',
        ]);

        // Act: The manager sends a DELETE request to remove the user.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->deleteJson("/api/users/{$userToDelete->id}");

        // Assert: The response is 204 No Content, the standard for successful deletions.
        $response->assertNoContent();

        // Assert: The user record no longer exists in the database.
        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    }

    /**
     * Test: Creating a user without location_id auto-assigns the manager's location.
     *
     * The UserController::store() method contains logic that auto-assigns the
     * creating user's location_id when the 'location_id' field is not provided
     * (or is null) in the request payload. This is a convenience feature so that
     * managers don't have to explicitly specify their own location every time
     * they create a new staff member.
     *
     * This verifies:
     *   1. A user can be created without specifying location_id in the payload.
     *   2. The API responds with 201 Created (validation still passes).
     *   3. The newly created user is automatically assigned the same location_id
     *      as the manager who created them.
     */
    public function test_auto_assigns_location_id_when_not_provided(): void
    {
        // Seed the base location and users.
        $seed = $this->seedLocationAndUsers();

        // Act: The manager creates a new user WITHOUT specifying location_id.
        // The controller should automatically assign the manager's own location_id
        // via the `if (empty($validated['location_id']))` fallback logic.
        $response = $this->actingAs($seed['manager'], 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Auto Location User',
                'email' => 'autolocation@test.com',
                'password' => 'password123',
                'role' => 'server',
                // NOTE: location_id is intentionally omitted here.
            ]);

        // Assert: The response status is 201 Created, confirming the user was
        // successfully created despite the missing location_id.
        $response->assertStatus(201);

        // Assert: The response JSON shows the user's name.
        $response->assertJsonPath('name', 'Auto Location User');

        // Assert: The newly created user was automatically assigned the manager's
        // location_id. We verify this in the database to confirm the auto-assignment
        // logic in the controller worked correctly.
        $this->assertDatabaseHas('users', [
            'email' => 'autolocation@test.com',
            'location_id' => $seed['location']->id,
        ]);

        // Double-check by loading the user and comparing location_ids directly.
        // This provides an extra layer of confidence that the auto-assignment
        // matched the creating manager's location.
        $createdUser = User::where('email', 'autolocation@test.com')->first();
        $this->assertEquals(
            $seed['manager']->location_id,
            $createdUser->location_id,
            'The auto-assigned location_id should match the creating manager\'s location_id'
        );
    }
}
