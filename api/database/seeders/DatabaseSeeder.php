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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Locations ──
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

        // ── Users ──
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@preshift.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => null,
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

        // ── Categories ──
        $appetizers = Category::create(['location_id' => $downtown->id, 'name' => 'Appetizers', 'sort_order' => 1]);
        $entrees = Category::create(['location_id' => $downtown->id, 'name' => 'Entrees', 'sort_order' => 2]);
        $drinks = Category::create(['location_id' => $downtown->id, 'name' => 'Drinks', 'sort_order' => 3]);
        $desserts = Category::create(['location_id' => $downtown->id, 'name' => 'Desserts', 'sort_order' => 4]);

        // ── Menu Items ──
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

        // ── 86'd Items ──
        EightySixed::create([
            'location_id' => $downtown->id,
            'menu_item_id' => $salmon->id,
            'item_name' => 'Pan-Seared Salmon',
            'reason' => 'Supplier delivery delayed until tomorrow',
            'eighty_sixed_by' => $manager->id,
        ]);

        EightySixed::create([
            'location_id' => $downtown->id,
            'menu_item_id' => null,
            'item_name' => 'Oat Milk',
            'reason' => 'Out of stock',
            'eighty_sixed_by' => $bartender->id,
        ]);

        // ── Specials ──
        Special::create([
            'location_id' => $downtown->id,
            'menu_item_id' => $wings->id,
            'title' => 'Half-Price Wings',
            'description' => 'All wing flavors half off during happy hour (4-6pm)',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => null,
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

        // ── Push Items ──
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

        // ── Announcements ──
        Announcement::create([
            'location_id' => $downtown->id,
            'title' => 'Staff Meeting Friday',
            'body' => 'Mandatory all-hands meeting this Friday at 3pm in the back dining room. We\'ll be going over the new spring menu rollout and updated POS procedures.',
            'priority' => 'important',
            'target_roles' => null,
            'posted_by' => $manager->id,
            'expires_at' => now()->addDays(5),
        ]);

        Announcement::create([
            'location_id' => $downtown->id,
            'title' => 'New Cocktail Training',
            'body' => 'Bartenders: please review the new spring cocktail recipes in the binder before your next shift. We go live Saturday.',
            'priority' => 'normal',
            'target_roles' => ['bartender'],
            'posted_by' => $manager->id,
            'expires_at' => now()->addDays(3),
        ]);

        Announcement::create([
            'location_id' => $downtown->id,
            'title' => 'VIP Table Tonight',
            'body' => 'We have a VIP reservation at 7:30pm, table 12. Please give extra attention to service. Ask Sarah for details.',
            'priority' => 'urgent',
            'target_roles' => ['server'],
            'posted_by' => $manager->id,
            'expires_at' => now()->addDay(),
        ]);
    }
}
