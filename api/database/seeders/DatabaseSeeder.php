<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\EightySixed;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\PushItem;
use App\Models\Special;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Database Seeder -- populates the database with realistic sample data for
 * local development and demo purposes.
 *
 * The seeder creates a complete working environment for a fictional
 * multi-location restaurant group called "The Anchor". It builds two
 * locations, five users across every role tier, a full menu with categories,
 * and representative examples of every pre-shift resource (86'd items,
 * specials, push items, and announcements).
 *
 * All test accounts use the password "password" for easy local login.
 *
 * Relationship summary:
 *   - Locations own everything: users, categories, menu items, 86'd items,
 *     specials, push items, and announcements.
 *   - Categories group menu items (Appetizers, Entrees, Drinks, Desserts).
 *   - 86'd items, specials, and push items optionally link to a menu item.
 *   - Announcements can target specific roles via the `target_roles` JSON column.
 *   - The Midtown location is intentionally sparse (just a manager) to test
 *     multi-location isolation -- its dashboard should be empty.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |------------------------------------------------------------------
        | Locations
        |------------------------------------------------------------------
        | Two restaurant locations. Downtown is the "fully loaded" location
        | with all seed data; Midtown exists to verify location-scoping and
        | data isolation across venues.
        */
        $downtown = Location::create([
            'name' => 'The Anchor - Downtown',
            'address' => '123 Main St, Anytown, USA',
            'timezone' => 'America/New_York',
        ]);

        $midtown = Location::create([
            'name' => 'The Anchor - Midtown',
            'address' => '456 Elm St, Anytown, USA',
            'timezone' => 'America/New_York',
        ]);

        /*
        |------------------------------------------------------------------
        | Users
        |------------------------------------------------------------------
        | One user per role to exercise every permission tier:
        |   - admin:     Global access, no location_id (null). Can manage all
        |                locations and subscribe to any broadcast channel.
        |   - manager:   Scoped to Downtown. Can create/edit/delete all
        |                pre-shift resources and manage staff.
        |   - server:    Scoped to Downtown. Read-only on pre-shift content;
        |                can acknowledge items.
        |   - bartender: Scoped to Downtown. Same permissions as server;
        |                receives bartender-targeted announcements.
        |   - midtown manager: Scoped to Midtown. Verifies cross-location
        |                isolation (should not see Downtown data).
        */
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@preshift.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => null,  // Admins are not bound to any single location
        ]);

        $manager = User::create([
            'name' => 'Sarah Manager',
            'email' => 'manager@preshift.test',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $downtown->id,
        ]);

        $server = User::create([
            'name' => 'Jake Server',
            'email' => 'server@preshift.test',
            'password' => Hash::make('password'),
            'role' => 'server',
            'location_id' => $downtown->id,
        ]);

        $bartender = User::create([
            'name' => 'Mia Bartender',
            'email' => 'bartender@preshift.test',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $downtown->id,
        ]);

        $midtownManager = User::create([
            'name' => 'Tom Manager',
            'email' => 'tom@preshift.test',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $midtown->id,
        ]);

        /*
        |------------------------------------------------------------------
        | Categories
        |------------------------------------------------------------------
        | Four menu categories for the Downtown location, ordered by
        | sort_order for consistent display in the UI (Appetizers first,
        | Desserts last).
        */
        $appetizers = Category::create(['location_id' => $downtown->id, 'name' => 'Appetizers', 'sort_order' => 1]);
        $entrees = Category::create(['location_id' => $downtown->id, 'name' => 'Entrees', 'sort_order' => 2]);
        $drinks = Category::create(['location_id' => $downtown->id, 'name' => 'Drinks', 'sort_order' => 3]);
        $desserts = Category::create(['location_id' => $downtown->id, 'name' => 'Desserts', 'sort_order' => 4]);

        /*
        |------------------------------------------------------------------
        | Menu Items
        |------------------------------------------------------------------
        | Six items spread across the four categories. The Espresso Martini
        | is marked `is_new => true` to test the "new item" badge feature.
        | Each item has a type (food or drink) and a price. These menu
        | items are referenced by 86'd entries, specials, and push items
        | below, demonstrating the foreign-key relationships.
        */
        $wings = MenuItem::create([
            'location_id' => $downtown->id, 'category_id' => $appetizers->id,
            'name' => 'Buffalo Wings', 'description' => 'Crispy wings tossed in house buffalo sauce',
            'price' => 14.99, 'type' => 'food',
        ]);

        $burger = MenuItem::create([
            'location_id' => $downtown->id, 'category_id' => $entrees->id,
            'name' => 'Anchor Burger', 'description' => 'Half-pound patty, cheddar, lettuce, tomato, house sauce',
            'price' => 17.99, 'type' => 'food',
        ]);

        $salmon = MenuItem::create([
            'location_id' => $downtown->id, 'category_id' => $entrees->id,
            'name' => 'Pan-Seared Salmon', 'description' => 'Atlantic salmon, lemon butter, seasonal vegetables',
            'price' => 24.99, 'type' => 'food',
        ]);

        $margarita = MenuItem::create([
            'location_id' => $downtown->id, 'category_id' => $drinks->id,
            'name' => 'House Margarita', 'description' => 'Tequila, lime, agave, salted rim',
            'price' => 12.00, 'type' => 'drink',
        ]);

        $espressoMartini = MenuItem::create([
            'location_id' => $downtown->id, 'category_id' => $drinks->id,
            'name' => 'Espresso Martini', 'description' => 'Vodka, coffee liqueur, fresh espresso',
            'price' => 14.00, 'type' => 'drink', 'is_new' => true,
        ]);

        $cheesecake = MenuItem::create([
            'location_id' => $downtown->id, 'category_id' => $desserts->id,
            'name' => 'NY Cheesecake', 'description' => 'Classic cheesecake with berry compote',
            'price' => 10.99, 'type' => 'food',
        ]);

        /*
        |------------------------------------------------------------------
        | 86'd Items
        |------------------------------------------------------------------
        | Two examples of unavailable items:
        |   1. Salmon -- linked to a menu_item_id, 86'd by the manager due
        |      to a delayed supplier delivery.
        |   2. Oat Milk -- a free-text entry (menu_item_id is null) 86'd by
        |      the bartender. Demonstrates that non-menu ingredients can
        |      also be tracked on the 86'd board.
        */
        EightySixed::create([
            'location_id' => $downtown->id,
            'menu_item_id' => $salmon->id,
            'item_name' => 'Pan-Seared Salmon',
            'reason' => 'Supplier delivery delayed until tomorrow',
            'eighty_sixed_by' => $manager->id,
        ]);

        EightySixed::create([
            'location_id' => $downtown->id,
            'menu_item_id' => null,     // Free-text item, not tied to a menu entry
            'item_name' => 'Oat Milk',
            'reason' => 'Out of stock',
            'eighty_sixed_by' => $bartender->id,
        ]);

        /*
        |------------------------------------------------------------------
        | Specials
        |------------------------------------------------------------------
        | Two specials demonstrating different recurrence types:
        |   1. Half-Price Wings -- a "daily" special starting today with no
        |      end date (ongoing until manually removed).
        |   2. $8 Margaritas -- a "weekly" special running from the start
        |      to the end of the current week.
        | Both are linked to menu items so the front end can display the
        | item's details alongside the special.
        */
        Special::create([
            'location_id' => $downtown->id,
            'menu_item_id' => $wings->id,
            'title' => 'Half-Price Wings',
            'description' => 'All wing flavors half off during happy hour (4-6pm)',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => null,          // Open-ended daily special
            'created_by' => $manager->id,
        ]);

        Special::create([
            'location_id' => $downtown->id,
            'menu_item_id' => $margarita->id,
            'title' => '$8 Margaritas',
            'description' => 'House margaritas all day, every Wednesday',
            'type' => 'weekly',
            'starts_at' => now()->startOfWeek()->toDateString(),
            'ends_at' => now()->endOfWeek()->toDateString(),
            'created_by' => $manager->id,
        ]);

        /*
        |------------------------------------------------------------------
        | Push Items
        |------------------------------------------------------------------
        | Two items management wants staff to actively upsell:
        |   1. Espresso Martini -- high priority, new menu launch with a
        |      high profit margin. Staff should suggest it as an after-
        |      dinner drink.
        |   2. NY Cheesecake -- medium priority, overstock situation that
        |      needs to be sold through before it goes to waste.
        | Both link to a menu_item_id so the front end can pull in the
        | item's price and description.
        */
        PushItem::create([
            'location_id' => $downtown->id,
            'menu_item_id' => $espressoMartini->id,
            'title' => 'Push Espresso Martinis',
            'description' => 'New menu item — suggest to tables as an after-dinner drink',
            'reason' => 'New item launch, high margin',
            'priority' => 'high',
            'created_by' => $manager->id,
        ]);

        PushItem::create([
            'location_id' => $downtown->id,
            'menu_item_id' => $cheesecake->id,
            'title' => 'Move Cheesecake',
            'description' => 'We over-prepped today, need to sell through',
            'reason' => 'Overstock',
            'priority' => 'medium',
            'created_by' => $manager->id,
        ]);

        /*
        |------------------------------------------------------------------
        | Announcements
        |------------------------------------------------------------------
        | Three announcements demonstrating the priority tiers and role
        | targeting:
        |   1. "Staff Meeting Friday" -- priority: important, targets ALL
        |      roles (target_roles is null = everyone). Expires in 5 days.
        |   2. "New Cocktail Training" -- priority: normal, targets only
        |      bartenders. Expires in 3 days.
        |   3. "VIP Table Tonight" -- priority: urgent, targets only
        |      servers. Expires tomorrow. Tests that urgent announcements
        |      are visually distinguished in the UI.
        */
        Announcement::create([
            'location_id' => $downtown->id,
            'title' => 'Staff Meeting Friday',
            'body' => 'Mandatory all-hands meeting this Friday at 3pm in the back dining room. We\'ll be going over the new spring menu rollout and updated POS procedures.',
            'priority' => 'important',
            'target_roles' => null,     // null = all roles see this announcement
            'posted_by' => $manager->id,
            'expires_at' => now()->addDays(5),
        ]);

        Announcement::create([
            'location_id' => $downtown->id,
            'title' => 'New Cocktail Training',
            'body' => 'Bartenders: please review the new spring cocktail recipes in the binder before your next shift. We go live Saturday.',
            'priority' => 'normal',
            'target_roles' => ['bartender'],    // Only bartenders see this
            'posted_by' => $manager->id,
            'expires_at' => now()->addDays(3),
        ]);

        Announcement::create([
            'location_id' => $downtown->id,
            'title' => 'VIP Table Tonight',
            'body' => 'We have a VIP reservation at 7:30pm, table 12. Please give extra attention to service. Ask Sarah for details.',
            'priority' => 'urgent',
            'target_roles' => ['server'],       // Only servers see this
            'posted_by' => $manager->id,
            'expires_at' => now()->addDay(),
        ]);
    }
}
