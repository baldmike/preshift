<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Category;
use App\Models\EightySixed;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\PushItem;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\ShiftTemplate;
use App\Models\Special;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Database Seeder -- populates the database with realistic sample data for
 * local development and demo purposes.
 *
 * The seeder creates a complete working environment for a fictional
 * restaurant called "The Anchor". It builds one location, a superadmin
 * (Prince Springsteen), rock-star-named staff across every role tier, a
 * full menu with categories, and representative examples of every pre-shift
 * resource (86'd items, specials, push items, and announcements), plus
 * random availability and a 4-week schedule.
 *
 * All accounts use the password "password" for easy local login.
 *
 * Key accounts:
 *   - prince@preshift.test   — SuperAdmin (admin role, global access)
 *   - mercury@preshift.test  — Manager (creates seed content)
 *   - hendrix@preshift.test  — Bartender
 *
 * Relationship summary:
 *   - The location owns everything: users, categories, menu items, 86'd
 *     items, specials, push items, and announcements.
 *   - Categories group menu items (Appetizers, Entrees, Drinks, Desserts).
 *   - 86'd items, specials, and push items optionally link to a menu item.
 *   - Announcements can target specific roles via the `target_roles` JSON column.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |------------------------------------------------------------------
        | Location
        |------------------------------------------------------------------
        | Single restaurant location. Multi-location support may be added
        | in a future release.
        */
        $location = Location::create([
            'name' => 'The Anchor',
            'address' => '123 Main St, Anytown, USA',
            'timezone' => 'America/New_York',
        ]);

        /*
        |------------------------------------------------------------------
        | Users
        |------------------------------------------------------------------
        | SuperAdmin:
        |   - Prince Springsteen: admin role, is_superadmin. The master
        |     account for configuration.
        |
        | Staff — all named after rock stars:
        |   - 3 managers (Mercury, Bowie, Joplin)
        |   - 5 bartenders
        |   - 15 servers
        |
        | All accounts use password "password" for local dev.
        */
        $superadmin = User::create([
            'name' => 'Prince Springsteen',
            'email' => 'prince@preshift.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'location_id' => $location->id,
            'is_superadmin' => true,
        ]);

        // ── Managers ──
        $manager = User::create([
            'name' => 'Lisa Mercury',
            'email' => 'mercury@preshift.test',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'location_id' => $location->id,
        ]);

        foreach ([
            ['name' => 'Dan Bowie',      'email' => 'bowie@preshift.test'],
            ['name' => 'Rachel Joplin',  'email' => 'joplin@preshift.test'],
        ] as $m) {
            User::create([
                'name' => $m['name'],
                'email' => $m['email'],
                'password' => Hash::make('password'),
                'role' => 'manager',
                'location_id' => $location->id,
            ]);
        }

        // ── Bartenders ──
        $bartender = User::create([
            'name' => 'Kyle Hendrix',
            'email' => 'hendrix@preshift.test',
            'password' => Hash::make('password'),
            'role' => 'bartender',
            'location_id' => $location->id,
        ]);

        foreach ([
            ['name' => 'Nina Cobain',     'email' => 'cobain@preshift.test'],
            ['name' => 'Alex Morrison',   'email' => 'morrison@preshift.test'],
            ['name' => 'Casey Jagger',    'email' => 'jagger@preshift.test'],
            ['name' => 'Jordan Lennon',   'email' => 'lennon@preshift.test'],
        ] as $b) {
            User::create([
                'name' => $b['name'],
                'email' => $b['email'],
                'password' => Hash::make('password'),
                'role' => 'bartender',
                'location_id' => $location->id,
            ]);
        }

        // ── Servers ──
        foreach ([
            ['name' => 'Sam Presley',     'email' => 'presley@preshift.test'],
            ['name' => 'Avery Townshend', 'email' => 'townshend@preshift.test'],
            ['name' => 'Riley Plant',     'email' => 'plant@preshift.test'],
            ['name' => 'Taylor Vedder',   'email' => 'vedder@preshift.test'],
            ['name' => 'Morgan Grohl',    'email' => 'grohl@preshift.test'],
            ['name' => 'Jamie Osbourne',  'email' => 'osbourne@preshift.test'],
            ['name' => 'Drew Hetfield',   'email' => 'hetfield@preshift.test'],
            ['name' => 'Quinn Rose',      'email' => 'rose@preshift.test'],
            ['name' => 'Devon Clapton',   'email' => 'clapton@preshift.test'],
            ['name' => 'Blake Page',      'email' => 'page@preshift.test'],
            ['name' => 'Skyler Bonham',   'email' => 'bonham@preshift.test'],
            ['name' => 'Reese Slash',     'email' => 'slash@preshift.test'],
            ['name' => 'Chris Cornell',   'email' => 'cornell@preshift.test'],
            ['name' => 'Pat Benatar',     'email' => 'benatar@preshift.test'],
            ['name' => 'Ronnie Dio',      'email' => 'dio@preshift.test'],
        ] as $s) {
            User::create([
                'name' => $s['name'],
                'email' => $s['email'],
                'password' => Hash::make('password'),
                'role' => 'server',
                'location_id' => $location->id,
            ]);
        }

        /*
        |------------------------------------------------------------------
        | Categories
        |------------------------------------------------------------------
        | Four menu categories for the location, ordered by
        | sort_order for consistent display in the UI (Appetizers first,
        | Desserts last).
        */
        // ── Settings ──
        Setting::create(['key' => 'establishment_name', 'value' => 'The Anchor']);

        $appetizers = Category::create(['location_id' => $location->id, 'name' => 'Appetizers', 'sort_order' => 1]);
        $entrees = Category::create(['location_id' => $location->id, 'name' => 'Entrees', 'sort_order' => 2]);
        $drinks = Category::create(['location_id' => $location->id, 'name' => 'Drinks', 'sort_order' => 3]);
        $desserts = Category::create(['location_id' => $location->id, 'name' => 'Desserts', 'sort_order' => 4]);

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
            'location_id' => $location->id, 'category_id' => $appetizers->id,
            'name' => 'Buffalo Wings', 'description' => 'Crispy wings tossed in house buffalo sauce',
            'price' => 14.99, 'type' => 'food',
        ]);

        $burger = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $entrees->id,
            'name' => 'Anchor Burger', 'description' => 'Half-pound patty, cheddar, lettuce, tomato, house sauce',
            'price' => 17.99, 'type' => 'food',
        ]);

        $salmon = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $entrees->id,
            'name' => 'Pan-Seared Salmon', 'description' => 'Atlantic salmon, lemon butter, seasonal vegetables',
            'price' => 24.99, 'type' => 'food',
        ]);

        $margarita = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $drinks->id,
            'name' => 'House Margarita', 'description' => 'Tequila, lime, agave, salted rim',
            'price' => 12.00, 'type' => 'drink',
        ]);

        $espressoMartini = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $drinks->id,
            'name' => 'Espresso Martini', 'description' => 'Vodka, coffee liqueur, fresh espresso',
            'price' => 14.00, 'type' => 'drink', 'is_new' => true,
        ]);

        $cheesecake = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $desserts->id,
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
            'location_id' => $location->id,
            'menu_item_id' => $salmon->id,
            'item_name' => 'Pan-Seared Salmon',
            'reason' => 'Supplier delivery delayed until tomorrow',
            'eighty_sixed_by' => $manager->id,
        ]);

        EightySixed::create([
            'location_id' => $location->id,
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
            'location_id' => $location->id,
            'menu_item_id' => $wings->id,
            'title' => 'Half-Price Wings',
            'description' => 'All wing flavors half off during happy hour (4-6pm)',
            'type' => 'daily',
            'starts_at' => now()->toDateString(),
            'ends_at' => null,          // Open-ended daily special
            'created_by' => $manager->id,
        ]);

        Special::create([
            'location_id' => $location->id,
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
            'location_id' => $location->id,
            'menu_item_id' => $espressoMartini->id,
            'title' => 'Push Espresso Martinis',
            'description' => 'New menu item — suggest to tables as an after-dinner drink',
            'reason' => 'New item launch, high margin',
            'priority' => 'high',
            'created_by' => $manager->id,
        ]);

        PushItem::create([
            'location_id' => $location->id,
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
            'location_id' => $location->id,
            'title' => 'Staff Meeting Friday',
            'body' => 'Mandatory all-hands meeting this Friday at 3pm in the back dining room. We\'ll be going over the new spring menu rollout and updated POS procedures.',
            'priority' => 'important',
            'target_roles' => null,     // null = all roles see this announcement
            'posted_by' => $manager->id,
            'expires_at' => now()->addDays(5),
        ]);

        Announcement::create([
            'location_id' => $location->id,
            'title' => 'New Cocktail Training',
            'body' => 'Bartenders: please review the new spring cocktail recipes in the binder before your next shift. We go live Saturday.',
            'priority' => 'normal',
            'target_roles' => ['bartender'],    // Only bartenders see this
            'posted_by' => $manager->id,
            'expires_at' => now()->addDays(3),
        ]);

        Announcement::create([
            'location_id' => $location->id,
            'title' => 'VIP Table Tonight',
            'body' => 'We have a VIP reservation at 7:30pm, table 12. Please give extra attention to service. Ask Lisa for details.',
            'priority' => 'urgent',
            'target_roles' => ['server'],       // Only servers see this
            'posted_by' => $manager->id,
            'expires_at' => now()->addDay(),
        ]);

        /*
        |------------------------------------------------------------------
        | Employee Availability
        |------------------------------------------------------------------
        | Assign random availability to every employee (servers,
        | bartenders, managers). Friday and Saturday evenings are heavily
        | favored since that's when restaurants need the most staff.
        |
        | Slot values: '10:30' (morning), '16:30' (4:30 PM), '18:00' (6 PM),
        |              '19:00' (7 PM), 'open' (all day).
        */
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $allSlots = ['10:30', '16:30', '18:00', '19:00'];
        $eveningSlots = ['16:30', '18:00', '19:00'];

        $staff = User::where('location_id', $location->id)->get();

        foreach ($staff as $staffMember) {
            $availability = [];
            foreach ($days as $day) {
                $isFriSat = in_array($day, ['friday', 'saturday']);
                $roll = rand(1, 100);

                if ($isFriSat) {
                    // 40% open, 40% evening slots, 15% specific slot, 5% off
                    if ($roll <= 40) {
                        $availability[$day] = ['open'];
                    } elseif ($roll <= 80) {
                        // Pick 2-3 evening slots
                        shuffle($eveningSlots);
                        $availability[$day] = array_slice($eveningSlots, 0, rand(2, 3));
                    } elseif ($roll <= 95) {
                        $availability[$day] = [$eveningSlots[array_rand($eveningSlots)]];
                    } else {
                        $availability[$day] = [];
                    }
                } else {
                    // Weekdays: 20% open, 30% 2-3 slots, 25% 1 slot, 25% off
                    if ($roll <= 20) {
                        $availability[$day] = ['open'];
                    } elseif ($roll <= 50) {
                        shuffle($allSlots);
                        $availability[$day] = array_slice($allSlots, 0, rand(2, 3));
                    } elseif ($roll <= 75) {
                        $availability[$day] = [$allSlots[array_rand($allSlots)]];
                    } else {
                        $availability[$day] = [];
                    }
                }
            }
            $staffMember->update(['availability' => $availability]);
        }

        /*
        |------------------------------------------------------------------
        | Shift Templates
        |------------------------------------------------------------------
        | Three standard restaurant shifts for the location.
        */
        $lunch = ShiftTemplate::create([
            'location_id' => $location->id,
            'name' => 'Lunch',
            'start_time' => '10:30:00',
        ]);

        $dinner = ShiftTemplate::create([
            'location_id' => $location->id,
            'name' => 'Dinner',
            'start_time' => '16:30:00',
        ]);

        $close = ShiftTemplate::create([
            'location_id' => $location->id,
            'name' => 'Close',
            'start_time' => '18:00:00',
        ]);

        /*
        |------------------------------------------------------------------
        | 4-Week Schedule (Monday–Sunday)
        |------------------------------------------------------------------
        | Creates published weekly schedules starting from the Monday of the
        | current week, extending 4 weeks out. Each day gets a realistic mix
        | of lunch and dinner shifts populated from available staff.
        |
        | Staffing targets per shift:
        |   - Weekday lunch:  3 servers, 1 bartender
        |   - Weekday dinner: 5 servers, 2 bartenders
        |   - Fri/Sat dinner: 7 servers, 3 bartenders
        |   - Sunday:         4 servers, 2 bartenders (brunch-ish)
        */
        $servers = $staff->filter(fn ($u) => $u->role === 'server')->values();
        $bartenders = $staff->filter(fn ($u) => $u->role === 'bartender')->values();

        // Start from the Monday of the current week
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);

        for ($week = 0; $week < 4; $week++) {
            $monday = $weekStart->copy()->addWeeks($week);

            $schedule = Schedule::create([
                'location_id' => $location->id,
                'week_start' => $monday->toDateString(),
                'status' => 'published',
                'published_at' => now(),
                'published_by' => $manager->id,
            ]);

            // Track which user IDs have already been assigned on each date
            // so the same person is never scheduled for two shifts on one day.
            $assignedByDate = [];

            // Build entries for each day (Mon=0 through Sun=6)
            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                $date = $monday->copy()->addDays($dayOffset);
                $dateStr = $date->toDateString();
                $dayName = strtolower($date->format('l')); // 'monday', 'tuesday', etc.
                $isFriSat = in_array($dayName, ['friday', 'saturday']);
                $isSunday = $dayName === 'sunday';

                // Initialize the assigned set for this date
                if (!isset($assignedByDate[$dateStr])) {
                    $assignedByDate[$dateStr] = collect();
                }

                // Determine staffing targets
                $lunchServers = $isSunday ? 4 : 3;
                $lunchBartenders = $isSunday ? 2 : 1;
                $dinnerServers = $isFriSat ? 7 : 5;
                $dinnerBartenders = $isFriSat ? 3 : 2;

                // Helper: pick N random staff from the pool, preferring those available.
                // Excludes any user IDs already assigned on this date via $excludeIds.
                $pickStaff = function ($pool, $count, $dayName, $shift, $excludeIds) {
                    $isLunch = $shift === 'lunch';
                    $lunchSlot = '10:30';

                    // Filter out anyone already scheduled on this date
                    $available = $pool->filter(fn ($u) => !$excludeIds->contains($u->id));

                    // Sort: available first, then random
                    $sorted = $available->sortByDesc(function ($u) use ($dayName, $isLunch, $lunchSlot) {
                        $slots = $u->availability[$dayName] ?? [];
                        if (in_array('open', $slots)) return 2 + (rand(0, 100) / 1000);
                        if ($isLunch && in_array($lunchSlot, $slots)) return 1 + (rand(0, 100) / 1000);
                        if (!$isLunch && count(array_intersect($slots, ['16:30', '18:00', '19:00'])) > 0) return 1 + (rand(0, 100) / 1000);
                        return rand(0, 100) / 1000; // Not available but fill if needed
                    })->values();

                    return $sorted->take($count);
                };

                // Lunch shift entries
                $lunchServerPicks = $pickStaff($servers, $lunchServers, $dayName, 'lunch', $assignedByDate[$dateStr]);
                foreach ($lunchServerPicks as $s) {
                    ScheduleEntry::create([
                        'schedule_id' => $schedule->id,
                        'user_id' => $s->id,
                        'shift_template_id' => $lunch->id,
                        'date' => $dateStr,
                        'role' => 'server',
                    ]);
                    $assignedByDate[$dateStr]->push($s->id);
                }

                $lunchBartenderPicks = $pickStaff($bartenders, $lunchBartenders, $dayName, 'lunch', $assignedByDate[$dateStr]);
                foreach ($lunchBartenderPicks as $b) {
                    ScheduleEntry::create([
                        'schedule_id' => $schedule->id,
                        'user_id' => $b->id,
                        'shift_template_id' => $lunch->id,
                        'date' => $dateStr,
                        'role' => 'bartender',
                    ]);
                    $assignedByDate[$dateStr]->push($b->id);
                }

                // Dinner shift entries
                $dinnerServerPicks = $pickStaff($servers, $dinnerServers, $dayName, 'dinner', $assignedByDate[$dateStr]);
                foreach ($dinnerServerPicks as $s) {
                    ScheduleEntry::create([
                        'schedule_id' => $schedule->id,
                        'user_id' => $s->id,
                        'shift_template_id' => $dinner->id,
                        'date' => $dateStr,
                        'role' => 'server',
                    ]);
                    $assignedByDate[$dateStr]->push($s->id);
                }

                $dinnerBartenderPicks = $pickStaff($bartenders, $dinnerBartenders, $dayName, 'dinner', $assignedByDate[$dateStr]);
                foreach ($dinnerBartenderPicks as $b) {
                    ScheduleEntry::create([
                        'schedule_id' => $schedule->id,
                        'user_id' => $b->id,
                        'shift_template_id' => $dinner->id,
                        'date' => $dateStr,
                        'role' => 'bartender',
                    ]);
                    $assignedByDate[$dateStr]->push($b->id);
                }

                // Fri/Sat get a Close shift too
                if ($isFriSat) {
                    $closeServerPicks = $pickStaff($servers, 3, $dayName, 'dinner', $assignedByDate[$dateStr]);
                    foreach ($closeServerPicks as $s) {
                        ScheduleEntry::create([
                            'schedule_id' => $schedule->id,
                            'user_id' => $s->id,
                            'shift_template_id' => $close->id,
                            'date' => $dateStr,
                            'role' => 'server',
                        ]);
                        $assignedByDate[$dateStr]->push($s->id);
                    }

                    $closeBartenderPicks = $pickStaff($bartenders, 1, $dayName, 'dinner', $assignedByDate[$dateStr]);
                    foreach ($closeBartenderPicks as $b) {
                        ScheduleEntry::create([
                            'schedule_id' => $schedule->id,
                            'user_id' => $b->id,
                            'shift_template_id' => $close->id,
                            'date' => $dateStr,
                            'role' => 'bartender',
                        ]);
                        $assignedByDate[$dateStr]->push($b->id);
                    }
                }
            }
        }
    }
}
