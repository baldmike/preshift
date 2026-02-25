<?php

namespace Database\Seeders;

use App\Models\Acknowledgment;
use App\Models\Announcement;
use App\Models\BoardMessage;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\EightySixed;
use App\Models\Event;
use App\Models\Location;
use App\Models\ManagerLog;
use App\Models\MenuItem;
use App\Models\PushItem;
use App\Models\Schedule;
use App\Models\ScheduleEntry;
use App\Models\Setting;
use App\Models\ShiftDrop;
use App\Models\ShiftDropVolunteer;
use App\Models\ShiftTemplate;
use App\Models\Special;
use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Database Seeder — populates the database with comprehensive sample data
 * for local development and demo purposes.
 *
 * Creates six establishments:
 *   - Bald Bar (BM, Sean Hulet + full staff, schedules, content)
 *   - TITZ (Bob Frumkorpritt — menu, specials, events, no other staff yet)
 *   - Field of Dreams (Chip — menu, specials, events, no other staff yet)
 *   - Almost Home (Otto, TBone — menu, specials, events, no other staff yet)
 *   - The Rail (Dean DeRenzis — menu, specials, events, no other staff yet)
 *   - Snooki's (Russell Cougar Springsteen — server, chronically drops shifts)
 *
 * BM has access to all five establishments. Other superadmins only see
 * their own. Bald Bar is fully operational with 28 weeks of schedules,
 * shift drops, board messages, DMs, manager logs, and acknowledgments.
 */
class DatabaseSeeder extends Seeder
{
    /** Generate a random phone number formatted as (XXX) XXX-XXXX. */
    private function randomPhone(): string
    {
        $area = rand(200, 999);
        $prefix = rand(200, 999);
        $line = rand(1000, 9999);
        return "({$area}) {$prefix}-{$line}";
    }

    public function run(): void
    {
        /*
        |------------------------------------------------------------------
        | Location
        |------------------------------------------------------------------
        | Bald Bar — a sports bar in Chicago's River North neighborhood.
        | Coordinates point to downtown Chicago. Timezone is Central.
        */
        $location = Location::create([
            'name'      => 'Bald Bar',
            'address'   => '401 N Wabash Ave, Chicago, IL 60611',
            'city'      => 'Chicago',
            'state'     => 'IL',
            'timezone'  => 'America/Chicago',
            'latitude'  => 41.8893,
            'longitude' => -87.6267,
        ]);

        $titz = Location::create([
            'name'      => 'TITZ',
            'address'   => '1200 N Lake Shore Dr, Chicago, IL 60610',
            'city'      => 'Chicago',
            'state'     => 'IL',
            'timezone'  => 'America/Chicago',
            'latitude'  => 41.9042,
            'longitude' => -87.6268,
        ]);

        $fieldOfDreams = Location::create([
            'name'      => 'Field of Dreams',
            'address'   => '825 W Addison St, Chicago, IL 60613',
            'city'      => 'Chicago',
            'state'     => 'IL',
            'timezone'  => 'America/Chicago',
            'latitude'  => 41.9484,
            'longitude' => -87.6553,
        ]);

        $almostHome = Location::create([
            'name'      => 'Almost Home',
            'address'   => '742 N Clark St, Chicago, IL 60654',
            'city'      => 'Chicago',
            'state'     => 'IL',
            'timezone'  => 'America/Chicago',
            'latitude'  => 41.8961,
            'longitude' => -87.6310,
        ]);

        $theRail = Location::create([
            'name'      => 'The Rail',
            'address'   => '2012 W Roscoe St, Chicago, IL 60618',
            'city'      => 'Chicago',
            'state'     => 'IL',
            'timezone'  => 'America/Chicago',
            'latitude'  => 41.9434,
            'longitude' => -87.6792,
        ]);

        $snookis = Location::create([
            'name'      => "Snooki's",
            'address'   => '2400 W Montana St, Chicago, IL 60647',
            'city'      => 'Chicago',
            'state'     => 'IL',
            'timezone'  => 'America/Chicago',
            'latitude'  => 41.9448,
            'longitude' => -87.6639,
        ]);

        /*
        |------------------------------------------------------------------
        | Settings
        |------------------------------------------------------------------
        */
        Setting::create(['key' => 'establishment_name', 'value' => 'Bald Bar']);
        Setting::create(['key' => 'setup_complete', 'value' => 'true']);
        Setting::create(['key' => 'time_off_advance_days', 'value' => '14']);

        /*
        |------------------------------------------------------------------
        | Users
        |------------------------------------------------------------------
        | Staff — all named after rock stars:
        |   - 3 managers (Mercury, Bowie, Joplin)
        |   - 5 bartenders (Hendrix, Cobain, Morrison, Jagger, Lennon)
        |   - 15 servers
        */
        $superadmin = User::create([
            'name'          => 'BM',
            'email'         => 'bm@preshift.test',
            'password'      => Hash::make('funky101'),
            'role'          => 'admin',
            'location_id'   => $location->id,
            'is_superadmin' => true,
            'phone'         => $this->randomPhone(),
        ]);

        User::create([
            'name'          => 'Otto',
            'email'         => 'otto@preshift.test',
            'password'      => Hash::make('baldsnutz'),
            'role'          => 'admin',
            'location_id'   => $almostHome->id,
            'is_superadmin' => true,
            'phone'         => '(312) 588-2300',
        ]);

        User::create([
            'name'          => 'TBone',
            'email'         => 'tbone@preshift.test',
            'password'      => Hash::make('baldsnutz'),
            'role'          => 'admin',
            'location_id'   => $almostHome->id,
            'is_superadmin' => true,
            'phone'         => '(312) DWN-STRS',
        ]);

        User::create([
            'name'          => 'Chip',
            'email'         => 'chip@preshift.test',
            'password'      => Hash::make('baldsnutz'),
            'role'          => 'admin',
            'location_id'   => $fieldOfDreams->id,
            'is_superadmin' => true,
            'phone'         => '(312) 420-6969',
        ]);

        User::create([
            'name'          => 'Sean Hulet',
            'email'         => 'sean@preshift.test',
            'password'      => Hash::make('baldsnutz'),
            'role'          => 'admin',
            'location_id'   => $location->id,
            'is_superadmin' => true,
            'phone'         => '(312) 899-6539',
        ]);

        User::create([
            'name'          => 'Bob Frumkorpritt',
            'email'         => 'bfc@preshift.test',
            'password'      => Hash::make('baldsnutz'),
            'role'          => 'admin',
            'location_id'   => $titz->id,
            'is_superadmin' => true,
            'phone'         => '(420) 247-8309',
        ]);

        $dean = User::create([
            'name'          => 'Dean DeRenzis',
            'email'         => 'deano@preshift.test',
            'password'      => Hash::make('baldsnutz'),
            'role'          => 'admin',
            'location_id'   => $theRail->id,
            'is_superadmin' => true,
            'phone'         => '(888) 123-4567',
        ]);

        $russell = User::create([
            'name'        => 'Russell Cougar Springsteen',
            'email'       => 'russ@preshift.test',
            'password'    => Hash::make('baldsnutz'),
            'role'        => 'server',
            'location_id' => $snookis->id,
            'phone'       => $this->randomPhone(),
        ]);

        // ── Managers ──
        $manager = User::create([
            'name'        => 'Lisa Mercury',
            'email'       => 'mercury@preshift.test',
            'password'    => Hash::make('baldsnutz'),
            'role'        => 'manager',
            'location_id' => $location->id,
            'phone'       => $this->randomPhone(),
        ]);

        $manager2 = User::create([
            'name'        => 'Dan Bowie',
            'email'       => 'bowie@preshift.test',
            'password'    => Hash::make('baldsnutz'),
            'role'        => 'manager',
            'location_id' => $location->id,
            'phone'       => $this->randomPhone(),
        ]);

        $manager3 = User::create([
            'name'        => 'Rachel Joplin',
            'email'       => 'joplin@preshift.test',
            'password'    => Hash::make('baldsnutz'),
            'role'        => 'manager',
            'location_id' => $location->id,
            'phone'       => $this->randomPhone(),
        ]);

        // ── Bartenders ──
        $bartender = User::create([
            'name'        => 'Kyle Hendrix',
            'email'       => 'hendrix@preshift.test',
            'password'    => Hash::make('baldsnutz'),
            'role'        => 'bartender',
            'location_id' => $location->id,
            'phone'       => $this->randomPhone(),
        ]);

        $bartenders = collect([$bartender]);
        foreach ([
            ['name' => 'Nina Cobain',   'email' => 'cobain@preshift.test'],
            ['name' => 'Alex Morrison', 'email' => 'morrison@preshift.test'],
            ['name' => 'Casey Jagger',  'email' => 'jagger@preshift.test'],
            ['name' => 'Jordan Lennon', 'email' => 'lennon@preshift.test'],
        ] as $b) {
            $bartenders->push(User::create([
                'name'        => $b['name'],
                'email'       => $b['email'],
                'password'    => Hash::make('baldsnutz'),
                'role'        => 'bartender',
                'location_id' => $location->id,
                'phone'       => $this->randomPhone(),
            ]));
        }

        // ── Servers ──
        $serverList = collect();
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
            $serverList->push(User::create([
                'name'        => $s['name'],
                'email'       => $s['email'],
                'password'    => Hash::make('baldsnutz'),
                'role'        => 'server',
                'location_id' => $location->id,
                'phone'       => $this->randomPhone(),
            ]));
        }

        /*
        |------------------------------------------------------------------
        | Employee Availability
        |------------------------------------------------------------------
        | Assign random weekly availability to every employee. Friday and
        | Saturday are heavily weighted toward open/evening availability.
        */
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $allSlots = ['10:30', '16:30', '18:00', '19:00'];
        $eveningSlots = ['16:30', '18:00', '19:00'];

        $allStaff = User::where('location_id', $location->id)->get();

        foreach ($allStaff as $staffMember) {
            $availability = [];
            foreach ($days as $day) {
                $isFriSat = in_array($day, ['friday', 'saturday']);
                $roll = rand(1, 100);

                if ($isFriSat) {
                    if ($roll <= 40) {
                        $availability[$day] = ['open'];
                    } elseif ($roll <= 80) {
                        shuffle($eveningSlots);
                        $availability[$day] = array_slice($eveningSlots, 0, rand(2, 3));
                    } elseif ($roll <= 95) {
                        $availability[$day] = [$eveningSlots[array_rand($eveningSlots)]];
                    } else {
                        $availability[$day] = [];
                    }
                } else {
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
        | Location–User Pivot (Multi-Establishment Memberships)
        |------------------------------------------------------------------
        | Backfill the location_user pivot table so every seeded user has
        | a membership row matching their location_id + role.
        */
        $allSeededUsers = User::where('location_id', $location->id)->get();
        foreach ($allSeededUsers as $seededUser) {
            $seededUser->locations()->syncWithoutDetaching([
                $location->id => ['role' => $seededUser->role],
            ]);
        }

        // BM gets access to ALL establishments
        $superadmin->locations()->syncWithoutDetaching([
            $titz->id          => ['role' => 'admin'],
            $fieldOfDreams->id => ['role' => 'admin'],
            $almostHome->id    => ['role' => 'admin'],
            $theRail->id       => ['role' => 'admin'],
            $snookis->id       => ['role' => 'admin'],
        ]);

        // Backfill pivot for new location users (each only their own)
        foreach ([$titz, $fieldOfDreams, $almostHome, $theRail, $snookis] as $loc) {
            $locUsers = User::where('location_id', $loc->id)->get();
            foreach ($locUsers as $locUser) {
                $locUser->locations()->syncWithoutDetaching([
                    $loc->id => ['role' => $locUser->role],
                ]);
            }
        }

        /*
        |------------------------------------------------------------------
        | Categories & Menu Items
        |------------------------------------------------------------------
        | Full bar/restaurant menu with 6 categories and 40+ items.
        */
        $appetizers  = Category::create(['location_id' => $location->id, 'name' => 'Appetizers',      'sort_order' => 1]);
        $entrees     = Category::create(['location_id' => $location->id, 'name' => 'Entrees',         'sort_order' => 2]);
        $sandwiches  = Category::create(['location_id' => $location->id, 'name' => 'Sandwiches',      'sort_order' => 3]);
        $sides       = Category::create(['location_id' => $location->id, 'name' => 'Sides',           'sort_order' => 4]);
        $drinks      = Category::create(['location_id' => $location->id, 'name' => 'Cocktails & Beer','sort_order' => 5]);
        $desserts    = Category::create(['location_id' => $location->id, 'name' => 'Desserts',        'sort_order' => 6]);

        // ── Appetizers ──
        $wings = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $appetizers->id,
            'name' => 'Buffalo Wings', 'description' => 'Crispy wings tossed in house buffalo sauce, served with ranch and celery',
            'price' => 14.99, 'type' => 'food',
        ]);
        $nachos = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $appetizers->id,
            'name' => 'Loaded Nachos', 'description' => 'Tortilla chips, queso, jalapeños, pico, sour cream, guacamole',
            'price' => 13.99, 'type' => 'food',
        ]);
        $pretzel = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $appetizers->id,
            'name' => 'Bavarian Pretzel', 'description' => 'Jumbo soft pretzel with beer cheese and whole grain mustard',
            'price' => 11.99, 'type' => 'food',
        ]);
        $calamari = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $appetizers->id,
            'name' => 'Fried Calamari', 'description' => 'Lightly breaded, marinara and lemon aioli',
            'price' => 13.99, 'type' => 'food',
        ]);
        $sliders = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $appetizers->id,
            'name' => 'Brisket Sliders', 'description' => 'Three smoked brisket sliders with pickled onion and BBQ aioli',
            'price' => 15.99, 'type' => 'food',
        ]);
        $spinachDip = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $appetizers->id,
            'name' => 'Spinach Artichoke Dip', 'description' => 'Warm dip served with toasted pita chips',
            'price' => 12.99, 'type' => 'food',
        ]);

        // ── Entrees ──
        $burger = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $entrees->id,
            'name' => 'Bald Burger', 'description' => 'Half-pound patty, aged cheddar, lettuce, tomato, house sauce, brioche bun',
            'price' => 17.99, 'type' => 'food',
        ]);
        $salmon = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $entrees->id,
            'name' => 'Pan-Seared Salmon', 'description' => 'Atlantic salmon, lemon butter, seasonal vegetables',
            'price' => 24.99, 'type' => 'food',
        ]);
        $steak = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $entrees->id,
            'name' => '12oz Ribeye', 'description' => 'USDA Choice ribeye, garlic herb butter, roasted potatoes',
            'price' => 34.99, 'type' => 'food',
        ]);
        $chickenParm = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $entrees->id,
            'name' => 'Chicken Parmesan', 'description' => 'Breaded chicken breast, marinara, mozzarella, over spaghetti',
            'price' => 19.99, 'type' => 'food',
        ]);
        $fishTacos = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $entrees->id,
            'name' => 'Blackened Fish Tacos', 'description' => 'Three tacos with mahi mahi, mango salsa, chipotle crema, cilantro slaw',
            'price' => 17.99, 'type' => 'food',
        ]);
        $pasta = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $entrees->id,
            'name' => 'Truffle Mac & Cheese', 'description' => 'Cavatappi, four cheese blend, truffle oil, panko crust',
            'price' => 16.99, 'type' => 'food', 'is_new' => true,
        ]);

        // ── Sandwiches ──
        $cubano = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $sandwiches->id,
            'name' => 'Cubano Press', 'description' => 'Roasted pork, ham, Swiss, pickles, mustard on pressed Cuban bread',
            'price' => 15.99, 'type' => 'food',
        ]);
        $bltItem = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $sandwiches->id,
            'name' => 'BLT Club', 'description' => 'Thick-cut bacon, lettuce, tomato, avocado, garlic mayo, triple-stacked',
            'price' => 14.99, 'type' => 'food',
        ]);
        $philly = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $sandwiches->id,
            'name' => 'Philly Cheesesteak', 'description' => 'Shaved ribeye, peppers, onions, provolone on a hoagie roll',
            'price' => 16.99, 'type' => 'food',
        ]);

        // ── Sides ──
        $fries = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $sides->id,
            'name' => 'Seasoned Fries', 'description' => 'House-seasoned, served with ketchup and garlic aioli',
            'price' => 6.99, 'type' => 'food',
        ]);
        $onionRings = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $sides->id,
            'name' => 'Onion Rings', 'description' => 'Beer-battered and golden fried',
            'price' => 7.99, 'type' => 'food',
        ]);
        $coleslaw = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $sides->id,
            'name' => 'Creamy Coleslaw', 'description' => 'Classic creamy coleslaw',
            'price' => 4.99, 'type' => 'food',
        ]);
        $salad = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $sides->id,
            'name' => 'House Salad', 'description' => 'Mixed greens, cherry tomato, cucumber, red onion, balsamic vinaigrette',
            'price' => 8.99, 'type' => 'food',
        ]);

        // ── Cocktails & Beer ──
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
        $oldFashioned = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $drinks->id,
            'name' => 'Old Fashioned', 'description' => 'Bourbon, Angostura bitters, sugar, orange peel',
            'price' => 13.00, 'type' => 'drink',
        ]);
        $mojito = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $drinks->id,
            'name' => 'Mojito', 'description' => 'White rum, mint, lime, soda, sugar',
            'price' => 12.00, 'type' => 'drink',
        ]);
        $moscowMule = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $drinks->id,
            'name' => 'Moscow Mule', 'description' => 'Vodka, ginger beer, lime, served in a copper mug',
            'price' => 12.00, 'type' => 'drink',
        ]);
        $chicagoHandshake = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $drinks->id,
            'name' => 'Chicago Handshake', 'description' => 'Shot of Jeppson\'s Malört with an Old Style beer back',
            'price' => 10.00, 'type' => 'drink',
        ]);
        $ipaItem = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $drinks->id,
            'name' => 'Goose Island IPA', 'description' => 'Chicago\'s own — 16oz draft pour',
            'price' => 8.00, 'type' => 'drink',
        ]);
        $daisy = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $drinks->id,
            'name' => 'Half Acre Daisy Cutter', 'description' => 'Pale ale — 16oz draft pour',
            'price' => 8.00, 'type' => 'drink',
        ]);
        $domesticBucket = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $drinks->id,
            'name' => 'Domestic Bucket', 'description' => '5 bottles — Bud Light, Miller Lite, or Coors Light',
            'price' => 22.00, 'type' => 'drink',
        ]);

        // ── Desserts ──
        $cheesecake = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $desserts->id,
            'name' => 'NY Cheesecake', 'description' => 'Classic cheesecake with berry compote',
            'price' => 10.99, 'type' => 'food',
        ]);
        $brownie = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $desserts->id,
            'name' => 'Warm Brownie Sundae', 'description' => 'Chocolate brownie, vanilla ice cream, hot fudge, whipped cream',
            'price' => 11.99, 'type' => 'food',
        ]);
        $churros = MenuItem::create([
            'location_id' => $location->id, 'category_id' => $desserts->id,
            'name' => 'Churros', 'description' => 'Cinnamon sugar churros with chocolate and caramel dipping sauces',
            'price' => 9.99, 'type' => 'food',
        ]);

        /*
        |------------------------------------------------------------------
        | 86'd Items
        |------------------------------------------------------------------
        | Active and restored items demonstrating both linked and freeform.
        */
        EightySixed::create([
            'location_id'     => $location->id,
            'menu_item_id'    => $salmon->id,
            'item_name'       => 'Pan-Seared Salmon',
            'reason'          => 'Supplier delivery delayed until tomorrow — fish market truck broke down',
            'eighty_sixed_by' => $manager->id,
        ]);

        EightySixed::create([
            'location_id'     => $location->id,
            'menu_item_id'    => null,
            'item_name'       => 'Oat Milk',
            'reason'          => 'Out of stock — reorder placed, arriving Wednesday',
            'eighty_sixed_by' => $bartender->id,
        ]);

        EightySixed::create([
            'location_id'     => $location->id,
            'menu_item_id'    => $steak->id,
            'item_name'       => '12oz Ribeye',
            'reason'          => 'Down to last 2 steaks — 86 after they sell',
            'eighty_sixed_by' => $manager->id,
        ]);

        // Restored item (was 86'd earlier, now back)
        EightySixed::create([
            'location_id'     => $location->id,
            'menu_item_id'    => $calamari->id,
            'item_name'       => 'Fried Calamari',
            'reason'          => 'Fryer was down for maintenance',
            'eighty_sixed_by' => $manager2->id,
            'restored_at'     => now()->subHours(3),
        ]);

        /*
        |------------------------------------------------------------------
        | Specials
        |------------------------------------------------------------------
        | Multiple special types: daily, weekly, monthly, limited_time.
        | Mix of menu-item-linked and standalone specials.
        */
        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $wings->id,
            'title'        => 'Half-Price Wings',
            'description'  => 'All wing flavors half off during happy hour (4–6 PM)',
            'type'         => 'daily',
            'starts_at'    => '2026-01-01',
            'ends_at'      => null,
            'created_by'   => $manager->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $margarita->id,
            'title'        => '$8 Marg Wednesdays',
            'description'  => 'House margaritas all day, every Wednesday',
            'type'         => 'weekly',
            'starts_at'    => '2026-01-01',
            'ends_at'      => '2026-07-15',
            'created_by'   => $manager->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $domesticBucket->id,
            'title'        => 'Game Day Bucket Deal',
            'description'  => '$18 domestic buckets during any televised Chicago sports game',
            'type'         => 'daily',
            'starts_at'    => '2026-01-01',
            'ends_at'      => null,
            'created_by'   => $manager->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $chicagoHandshake->id,
            'title'        => '$7 Chicago Handshakes',
            'description'  => 'The classic Malört + Old Style combo, discounted all night on Fridays',
            'type'         => 'weekly',
            'starts_at'    => '2026-01-01',
            'ends_at'      => null,
            'created_by'   => $manager->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $pasta->id,
            'title'        => 'New Menu Launch — Truffle Mac',
            'description'  => '$12 introductory price on the new Truffle Mac & Cheese (reg. $16.99)',
            'type'         => 'limited_time',
            'starts_at'    => now()->subDays(3)->toDateString(),
            'ends_at'      => now()->addDays(11)->toDateString(),
            'quantity'     => 30,
            'created_by'   => $manager->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => null,
            'title'        => 'Industry Night — Sundays',
            'description'  => '20% off food and $5 well drinks for service industry workers (show pay stub)',
            'type'         => 'weekly',
            'starts_at'    => '2026-01-01',
            'ends_at'      => null,
            'created_by'   => $manager->id,
        ]);

        /*
        |------------------------------------------------------------------
        | Push Items
        |------------------------------------------------------------------
        | Items management wants staff to actively upsell.
        */
        PushItem::create([
            'location_id'  => $location->id,
            'menu_item_id' => $espressoMartini->id,
            'title'        => 'Push Espresso Martinis',
            'description'  => 'New menu item — suggest to every table as an after-dinner drink. High margin.',
            'reason'       => 'New item launch, high margin',
            'priority'     => 'high',
            'created_by'   => $manager->id,
        ]);

        PushItem::create([
            'location_id'  => $location->id,
            'menu_item_id' => $cheesecake->id,
            'title'        => 'Move the Cheesecake',
            'description'  => 'We over-prepped this week. Mention it with the dessert pitch at every table.',
            'reason'       => 'Overstock — need to sell through before Friday',
            'priority'     => 'medium',
            'created_by'   => $manager->id,
        ]);

        PushItem::create([
            'location_id'  => $location->id,
            'menu_item_id' => $pasta->id,
            'title'        => 'Truffle Mac Feature',
            'description'  => 'Brand new on the menu — talk it up! Describe the truffle oil and four-cheese blend.',
            'reason'       => 'New item, building awareness',
            'priority'     => 'high',
            'created_by'   => $manager->id,
        ]);

        PushItem::create([
            'location_id'  => $location->id,
            'menu_item_id' => $sliders->id,
            'title'        => 'Brisket Sliders as App',
            'description'  => 'Great shareable appetizer. Suggest when tables are still deciding.',
            'reason'       => 'High margin, pairs well with beer specials',
            'priority'     => 'low',
            'created_by'   => $manager2->id,
        ]);

        /*
        |------------------------------------------------------------------
        | Announcements
        |------------------------------------------------------------------
        | Varied priorities and role targeting. Mix of active and expired.
        */
        Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'NBA Finals Watch Parties',
            'body'         => 'The NBA Finals start June 3. We\'re running watch party specials on all games — $18 buckets, half-price wings. Expect packed houses. All hands on deck for Game 1.',
            'priority'     => 'important',
            'target_roles' => null,
            'posted_by'    => $manager->id,
            'expires_at'   => now()->addDays(14),
        ]);

        Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'New Cocktail Training',
            'body'         => 'Bartenders: please review the new summer cocktail recipes in the binder before your next shift. The Espresso Martini and Truffle Mac pairing pitch need to be second nature. Training session Thursday at 3 PM.',
            'priority'     => 'normal',
            'target_roles' => ['bartender'],
            'posted_by'    => $manager->id,
            'expires_at'   => now()->addDays(5),
        ]);

        Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'VIP Reservation Tonight',
            'body'         => 'We have a VIP 12-top at 7:30 PM, table 12. Corporate dinner — they\'re spending big. Extra attention on service. Ask Lisa for the pre-order details.',
            'priority'     => 'urgent',
            'target_roles' => ['server'],
            'posted_by'    => $manager->id,
            'expires_at'   => now()->addDay(),
        ]);

        Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'Health Inspection Next Week',
            'body'         => 'Annual health inspection is scheduled for next Tuesday. Double-check your stations: clean under equipment, label and date all prep containers, ensure hand-washing stations are stocked. Let\'s nail this.',
            'priority'     => 'important',
            'target_roles' => null,
            'posted_by'    => $manager2->id,
            'expires_at'   => now()->addDays(7),
        ]);

        Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'New POS System Training',
            'body'         => 'We\'re upgrading the POS terminals this weekend. Training video links are in the group chat. Please watch before your Monday shift. The new system is faster but the menu layout has changed.',
            'priority'     => 'normal',
            'target_roles' => null,
            'posted_by'    => $manager->id,
            'expires_at'   => now()->addDays(3),
        ]);

        /*
        |------------------------------------------------------------------
        | Events — Real Chicago Sports & Entertainment (Jan–Jul 2026)
        |------------------------------------------------------------------
        | Sourced from actual schedules: Bears, Bulls, Blackhawks, Cubs,
        | White Sox, Fire FC, Sky (WNBA), plus national events (Super Bowl,
        | March Madness, NBA Finals, World Cup, etc.).
        */
        $eventData = [
            // ── JANUARY 2026 ──
            ['2026-01-01', null,    'Blackhawks vs Stars — New Year\'s Day', 'Home game at United Center. New Year\'s Day crowd will be bar-hopping after. Open early, extra bartenders.'],
            ['2026-01-04', '19:00', 'Blackhawks vs Golden Knights', 'Home game at United Center. Vegas always draws a crowd.'],
            ['2026-01-07', '19:00', 'Blackhawks vs Blues — Rivalry Night', 'Home game. Blues rivalry = packed house. Push beer specials.'],
            ['2026-01-10', '19:00', 'Bulls vs Mavericks', 'Home game at United Center. Luka vs the Bulls.'],
            ['2026-01-12', '19:00', 'Blackhawks vs Oilers', 'Connor McDavid at United Center. Hockey fans will be out in force.'],
            ['2026-01-19', '14:00', 'Blackhawks vs Jets — Toews Returns', 'Jonathan Toews returns to United Center for the first time since his comeback. Huge emotional event for Hawks fans. Expect overflow.'],
            ['2026-01-24', '19:00', 'Bulls vs Celtics', 'Marquee home matchup. Jayson Tatum in town.'],
            ['2026-01-26', '14:00', 'Bulls vs Lakers', 'LeBron/Bronny vs Bulls. Sunday afternoon — open early for pre-game crowd.'],
            ['2026-01-25', null,    'NFL Conference Championships', 'All-day watch party. Set up extra TVs. Playoff football = capacity crowd.'],

            // ── FEBRUARY 2026 ──
            ['2026-02-02', '19:00', 'Blackhawks vs Sharks', 'Last home game before the Olympic break.'],
            ['2026-02-06', null,    'Winter Olympics Begin — Milan', 'NHL players in the Olympics for the first time since 2014. Hockey games will be huge draws — check broadcast times.'],
            ['2026-02-08', '17:30', 'SUPER BOWL LX Watch Party', 'Seahawks vs Patriots at Levi\'s Stadium. Biggest bar night of the year. All hands on deck. Specials: $18 buckets, $8 Chicago Handshakes, half-price wings. Doors open at 3 PM.'],
            ['2026-02-15', '19:00', 'NBA All-Star Game — Los Angeles', 'All-Star Sunday. Turn all TVs to TNT/ESPN. Celebrity game, dunk contest, three-point contest throughout the weekend.'],
            ['2026-02-22', '19:00', 'Bulls vs Knicks', 'Home game. Knicks always travel well — expect visiting fans.'],
            ['2026-02-28', '13:30', 'Fire FC Home Opener vs Montreal', 'First MLS home game of the season at Soldier Field. Soccer crowd will want food and drinks before/after.'],

            // ── MARCH 2026 ──
            ['2026-03-01', '14:30', 'Bulls vs Bucks — Rivalry', 'Sunday afternoon home game. Milwaukee rivalry. Push game day specials.'],
            ['2026-03-03', '19:00', 'Bulls vs Thunder', 'OKC is a top team — marquee Monday night game.'],
            ['2026-03-10', null,    'Big Ten Tournament Begins — United Center', 'Six straight days of basketball at the United Center (Mar 10–15). This is one of the biggest bar weeks of the year in Chicago. All-day crowds.'],
            ['2026-03-15', null,    'Selection Sunday — March Madness', 'NCAA bracket reveal. Bar will be packed for the CBS broadcast. Run bracket contest promos.'],
            ['2026-03-17', null,    'St. Patrick\'s Day + First Four', 'St. Patrick\'s Day AND the first games of March Madness. Double whammy. River will be green. All-day party. Full staff.'],
            ['2026-03-19', null,    'March Madness — First Round Day 1', 'Games from noon to midnight. Wall-to-wall basketball. Keep TVs on CBS/TBS/TNT/truTV.'],
            ['2026-03-20', null,    'March Madness — First Round Day 2', 'Second day of first-round games. Same drill.'],
            ['2026-03-21', null,    'March Madness — Second Round Day 1', 'Weekend games. Expect brunch-through-close crowds.'],
            ['2026-03-26', '14:20', 'Cubs Opening Day at Wrigley!', 'Cubs vs Nationals. First game of the MLB season. Wrigleyville will be insane. We will get overflow from the neighborhood. Open at noon.'],
            ['2026-03-26', null,    'March Madness — Sweet 16 Day 1', 'Sweet 16 AND Cubs Opening Day. Monster day.'],
            ['2026-03-28', null,    'March Madness — Elite Eight', 'Elite Eight weekend. Final Four is next weekend.'],
            ['2026-03-31', '20:00', 'Mexico vs Belgium at Soldier Field', 'International friendly — pre-World Cup warm-up. Huge event for Chicago\'s soccer community.'],

            // ── APRIL 2026 ──
            ['2026-04-02', '16:10', 'White Sox Home Opener vs Blue Jays', 'Opening Day at Rate Field. South Side fans celebrate. We\'ll see some crossover traffic.'],
            ['2026-04-04', null,    'NCAA Final Four — Indianapolis', 'National semifinals on TV. Big bar night.'],
            ['2026-04-06', '21:00', 'NCAA Championship Game', 'Monday night championship. Every sports bar in America will be packed.'],
            ['2026-04-11', '16:00', 'Blackhawks vs Blues — Rivalry', 'Late-season Saturday home game. Blues rivalry.'],
            ['2026-04-17', '19:10', 'Cubs vs Mets at Wrigley', 'Three-game Mets series starts. Good walkover crowd from Wrigleyville.'],
            ['2026-04-18', null,    'NBA Playoffs Begin', 'First round tips off. Multiple games daily through the end of April.'],
            ['2026-04-18', null,    'Stanley Cup Playoffs Begin', 'NHL first round starts same day as NBA. Sports overload — run double-header specials.'],
            ['2026-04-23', null,    'NFL Draft Day 1 — Pittsburgh', 'Bears fans will be glued to TVs. First-round drama. Run draft night specials.'],

            // ── MAY 2026 ──
            ['2026-05-02', null,    'Kentucky Derby Watch Party', '152nd Kentucky Derby. Mint julep specials. Dress code optional but encouraged.',],
            ['2026-05-15', '19:10', 'Crosstown Classic — Cubs at White Sox', 'First game of the Crosstown Classic at Rate Field. Biggest local rivalry series of the year. Every bar in the city will be rocking.'],
            ['2026-05-16', '18:10', 'Crosstown Classic Game 2', 'Saturday night Crosstown game. Expect a late-night crowd after.'],
            ['2026-05-17', '13:10', 'Crosstown Classic Game 3', 'Sunday afternoon finale. Loser buys.'],
            ['2026-05-20', '19:00', 'Chicago Sky Home Opener vs Wings', 'WNBA season opener at Wintrust Arena. Growing fanbase — promote on socials.'],
            ['2026-05-23', null,    'MLS Season Pauses for World Cup', 'Fire FC\'s last game before the FIFA World Cup break. MLS resumes mid-July.'],

            // ── JUNE 2026 ──
            ['2026-06-03', '20:30', 'NBA Finals — Game 1', 'NBA Finals begin. Prime time ABC. Push bucket specials and wings.'],
            ['2026-06-06', '13:30', 'USA vs Germany at Soldier Field', 'USMNT send-off match before the World Cup. This is IN CHICAGO at Soldier Field. Pre-game and post-game crowds will be massive. Open at 11 AM.'],
            ['2026-06-11', null,    'FIFA World Cup Kicks Off', 'The World Cup begins! 48 teams across the US, Mexico, and Canada. Games will run daily through July 19. Every match is a potential bar event.'],
            ['2026-06-12', '19:10', 'White Sox vs Dodgers at Rate Field', 'LA Dodgers in town for a marquee series. Shohei Ohtani visiting.'],
            ['2026-06-17', '19:00', 'Chicago Sky vs NY Liberty', 'WNBA marquee matchup at Wintrust Arena.'],
            ['2026-06-19', null,    'NBA Finals — Game 7 (if needed)', 'Potential Game 7. If it happens, this is the biggest TV night since the Super Bowl.'],
            ['2026-06-28', '19:00', 'Chicago Sky vs Las Vegas Aces', 'Home game at United Center — big-venue WNBA showcase.'],

            // ── JULY 2026 (through Jul 15) ──
            ['2026-07-03', '14:20', 'Cubs vs Cardinals — July 4th Weekend', 'Independence Day weekend. Cubs vs Cardinals at Wrigley. THE rivalry. One of the biggest Wrigley weekends of the year. Extra staff all 3 days.'],
            ['2026-07-04', '14:20', 'Cubs vs Cardinals + 4th of July', 'Cubs-Cards on the 4th of July. Plus rooftop fireworks in Wrigleyville. We\'ll be slammed from noon to 2 AM.'],
            ['2026-07-05', '13:20', 'Cubs vs Cardinals — Series Finale', 'Sunday finale of the Independence Day series.'],
            ['2026-07-07', '19:10', 'White Sox vs Red Sox at Rate Field', 'Red Sox in town. Good visiting fanbase.'],
            ['2026-07-14', null,    'MLB All-Star Game — Philadelphia', 'Midsummer Classic on FOX. America\'s 250th birthday celebration tie-in. Run red/white/blue drink specials.'],
        ];

        foreach ($eventData as [$date, $time, $title, $desc]) {
            Event::create([
                'location_id' => $location->id,
                'title'       => $title,
                'description' => $desc,
                'event_date'  => $date,
                'event_time'  => $time,
                'created_by'  => collect([$manager->id, $manager2->id, $manager3->id])->random(),
            ]);
        }

        /*
        |------------------------------------------------------------------
        | Shift Templates
        |------------------------------------------------------------------
        */
        $lunch = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Lunch',
            'start_time'  => '10:30:00',
        ]);

        $dinner = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Dinner',
            'start_time'  => '16:30:00',
        ]);

        $close = ShiftTemplate::create([
            'location_id' => $location->id,
            'name'        => 'Close',
            'start_time'  => '18:00:00',
        ]);

        /*
        |------------------------------------------------------------------
        | 28-Week Schedule (Jan 5, 2026 – Jul 13, 2026)
        |------------------------------------------------------------------
        | Published weekly schedules from the first Monday of 2026 through
        | mid-July. Each day gets a realistic mix of lunch and dinner shifts
        | populated from available staff.
        |
        | Staffing targets per shift:
        |   - Weekday lunch:  3 servers, 1 bartender
        |   - Weekday dinner: 5 servers, 2 bartenders
        |   - Fri/Sat dinner: 7 servers, 3 bartenders
        |   - Sunday:         4 servers, 2 bartenders (brunch-ish)
        */
        $servers = $serverList;

        $scheduleStart = Carbon::parse('2026-01-05'); // First Monday of 2026
        $scheduleEnd = Carbon::parse('2026-07-13');   // Last Monday within range
        $totalWeeks = (int) $scheduleStart->diffInWeeks($scheduleEnd) + 1;

        for ($week = 0; $week < $totalWeeks; $week++) {
            $monday = $scheduleStart->copy()->addWeeks($week);

            $schedule = Schedule::create([
                'location_id'  => $location->id,
                'week_start'   => $monday->toDateString(),
                'status'       => 'published',
                'published_at' => $monday->copy()->subDays(3),
                'published_by' => collect([$manager->id, $manager2->id, $manager3->id])->random(),
            ]);

            $assignedByDate = [];

            for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
                $date = $monday->copy()->addDays($dayOffset);
                $dateStr = $date->toDateString();
                $dayName = strtolower($date->format('l'));
                $isFriSat = in_array($dayName, ['friday', 'saturday']);
                $isSunday = $dayName === 'sunday';

                if (!isset($assignedByDate[$dateStr])) {
                    $assignedByDate[$dateStr] = collect();
                }

                $lunchServers = $isSunday ? 4 : 3;
                $lunchBartenders = $isSunday ? 2 : 1;
                $dinnerServers = $isFriSat ? 7 : 5;
                $dinnerBartenders = $isFriSat ? 3 : 2;

                $pickStaff = function ($pool, $count, $dayName, $shift, $excludeIds) {
                    $isLunch = $shift === 'lunch';
                    $lunchSlot = '10:30';

                    $available = $pool->filter(fn ($u) => !$excludeIds->contains($u->id));
                    $sorted = $available->sortByDesc(function ($u) use ($dayName, $isLunch, $lunchSlot) {
                        $slots = $u->availability[$dayName] ?? [];
                        if (in_array('open', $slots)) return 2 + (rand(0, 100) / 1000);
                        if ($isLunch && in_array($lunchSlot, $slots)) return 1 + (rand(0, 100) / 1000);
                        if (!$isLunch && count(array_intersect($slots, ['16:30', '18:00', '19:00'])) > 0) return 1 + (rand(0, 100) / 1000);
                        return rand(0, 100) / 1000;
                    })->values();

                    return $sorted->take($count);
                };

                // Lunch shift
                foreach ($pickStaff($servers, $lunchServers, $dayName, 'lunch', $assignedByDate[$dateStr]) as $s) {
                    ScheduleEntry::create([
                        'schedule_id'       => $schedule->id,
                        'user_id'           => $s->id,
                        'shift_template_id' => $lunch->id,
                        'date'              => $dateStr,
                        'role'              => 'server',
                    ]);
                    $assignedByDate[$dateStr]->push($s->id);
                }
                foreach ($pickStaff($bartenders, $lunchBartenders, $dayName, 'lunch', $assignedByDate[$dateStr]) as $b) {
                    ScheduleEntry::create([
                        'schedule_id'       => $schedule->id,
                        'user_id'           => $b->id,
                        'shift_template_id' => $lunch->id,
                        'date'              => $dateStr,
                        'role'              => 'bartender',
                    ]);
                    $assignedByDate[$dateStr]->push($b->id);
                }

                // Dinner shift
                foreach ($pickStaff($servers, $dinnerServers, $dayName, 'dinner', $assignedByDate[$dateStr]) as $s) {
                    ScheduleEntry::create([
                        'schedule_id'       => $schedule->id,
                        'user_id'           => $s->id,
                        'shift_template_id' => $dinner->id,
                        'date'              => $dateStr,
                        'role'              => 'server',
                    ]);
                    $assignedByDate[$dateStr]->push($s->id);
                }
                foreach ($pickStaff($bartenders, $dinnerBartenders, $dayName, 'dinner', $assignedByDate[$dateStr]) as $b) {
                    ScheduleEntry::create([
                        'schedule_id'       => $schedule->id,
                        'user_id'           => $b->id,
                        'shift_template_id' => $dinner->id,
                        'date'              => $dateStr,
                        'role'              => 'bartender',
                    ]);
                    $assignedByDate[$dateStr]->push($b->id);
                }

                // Close shift on Fri/Sat
                if ($isFriSat) {
                    foreach ($pickStaff($servers, 3, $dayName, 'dinner', $assignedByDate[$dateStr]) as $s) {
                        ScheduleEntry::create([
                            'schedule_id'       => $schedule->id,
                            'user_id'           => $s->id,
                            'shift_template_id' => $close->id,
                            'date'              => $dateStr,
                            'role'              => 'server',
                        ]);
                        $assignedByDate[$dateStr]->push($s->id);
                    }
                    foreach ($pickStaff($bartenders, 1, $dayName, 'dinner', $assignedByDate[$dateStr]) as $b) {
                        ScheduleEntry::create([
                            'schedule_id'       => $schedule->id,
                            'user_id'           => $b->id,
                            'shift_template_id' => $close->id,
                            'date'              => $dateStr,
                            'role'              => 'bartender',
                        ]);
                        $assignedByDate[$dateStr]->push($b->id);
                    }
                }
            }
        }

        /*
        |------------------------------------------------------------------
        | Shift Drops
        |------------------------------------------------------------------
        | Three open shift drop requests from next week's schedule, plus
        | one volunteer pickup awaiting manager approval.
        */
        $currentMonday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $nextMonday = $currentMonday->copy()->addWeek();
        $nextSchedule = Schedule::where('week_start', $nextMonday->toDateString())->first();

        if (!$nextSchedule) {
            // Fallback to current week if next week doesn't exist
            $nextSchedule = Schedule::where('week_start', $currentMonday->toDateString())->first();
            $nextMonday = $currentMonday;
        }

        $managersToNotify = User::where('location_id', $location->id)
            ->whereIn('role', ['admin', 'manager'])
            ->get();

        $notifyManagers = function (array $data) use ($managersToNotify) {
            foreach ($managersToNotify as $mgr) {
                $mgr->notifications()->create([
                    'id'   => Str::uuid()->toString(),
                    'type' => 'App\\Notifications\\ShiftDropRequestedNotification',
                    'data' => $data,
                ]);
            }
        };

        if ($nextSchedule) {
            $nextWeekEntries = ScheduleEntry::where('schedule_id', $nextSchedule->id)
                ->orderBy('date')
                ->get();

            $entriesOn = fn ($templateId, $role, $dateStr) => $nextWeekEntries
                ->filter(fn ($e) =>
                    $e->shift_template_id === $templateId
                    && $e->role === $role
                    && $e->date->toDateString() === $dateStr
                );

            // Drop 1: server dinner Monday — family birthday
            $drop1Entry = $entriesOn($dinner->id, 'server', $nextMonday->toDateString())->first();
            if ($drop1Entry) {
                $drop1 = ShiftDrop::create([
                    'schedule_entry_id' => $drop1Entry->id,
                    'requested_by'      => $drop1Entry->user_id,
                    'reason'            => 'Family birthday dinner, can\'t miss it.',
                    'status'            => 'open',
                ]);
                $name = User::find($drop1Entry->user_id)->name;
                $notifyManagers([
                    'type'      => 'shift_drop_requested',
                    'title'     => 'Shift Drop Request',
                    'body'      => "{$name} wants to drop their {$nextMonday->toDateString()} shift.",
                    'link'      => '/manage/shift-drops',
                    'source_id' => $drop1->id,
                ]);
            }

            // Drop 2: bartender lunch Tuesday — doctor appointment
            $tuesday = $nextMonday->copy()->addDay();
            $drop2Entry = $entriesOn($lunch->id, 'bartender', $tuesday->toDateString())->first();
            if ($drop2Entry) {
                $drop2 = ShiftDrop::create([
                    'schedule_entry_id' => $drop2Entry->id,
                    'requested_by'      => $drop2Entry->user_id,
                    'reason'            => 'Doctor appointment that can\'t be rescheduled.',
                    'status'            => 'open',
                ]);
                $name = User::find($drop2Entry->user_id)->name;
                $notifyManagers([
                    'type'      => 'shift_drop_requested',
                    'title'     => 'Shift Drop Request',
                    'body'      => "{$name} wants to drop their {$tuesday->toDateString()} shift.",
                    'link'      => '/manage/shift-drops',
                    'source_id' => $drop2->id,
                ]);
            }

            // Drop 3: server dinner Wednesday — feeling ill (+ volunteer)
            $wednesday = $nextMonday->copy()->addDays(2);
            $drop3Entry = $entriesOn($dinner->id, 'server', $wednesday->toDateString())->first();
            if ($drop3Entry) {
                $drop3 = ShiftDrop::create([
                    'schedule_entry_id' => $drop3Entry->id,
                    'requested_by'      => $drop3Entry->user_id,
                    'reason'            => 'Not feeling well, might be coming down with something.',
                    'status'            => 'open',
                ]);
                $name = User::find($drop3Entry->user_id)->name;
                $notifyManagers([
                    'type'      => 'shift_drop_requested',
                    'title'     => 'Shift Drop Request',
                    'body'      => "{$name} wants to drop their {$wednesday->toDateString()} shift.",
                    'link'      => '/manage/shift-drops',
                    'source_id' => $drop3->id,
                ]);

                // Volunteer: a server not scheduled on Wednesday
                $scheduledOnWednesday = $nextWeekEntries
                    ->filter(fn ($e) => $e->date->toDateString() === $wednesday->toDateString())
                    ->pluck('user_id');

                $volunteerUser = $servers
                    ->filter(fn ($u) => $u->id !== $drop3Entry->user_id)
                    ->filter(fn ($u) => !$scheduledOnWednesday->contains($u->id))
                    ->first();

                if ($volunteerUser) {
                    ShiftDropVolunteer::create([
                        'shift_drop_id' => $drop3->id,
                        'user_id'       => $volunteerUser->id,
                        'selected'      => false,
                    ]);
                    foreach ($managersToNotify as $mgr) {
                        $mgr->notifications()->create([
                            'id'   => Str::uuid()->toString(),
                            'type' => 'App\\Notifications\\ShiftDropVolunteeredNotification',
                            'data' => [
                                'type'      => 'shift_drop_volunteered',
                                'title'     => 'Shift Drop Volunteer',
                                'body'      => "{$volunteerUser->name} volunteered to pick up the {$wednesday->toDateString()} shift.",
                                'link'      => '/manage/shift-drops',
                                'source_id' => $drop3->id,
                            ],
                        ]);
                    }
                }
            }
        }

        /*
        |------------------------------------------------------------------
        | Time-Off Requests
        |------------------------------------------------------------------
        | Mix of pending, approved, and denied requests to show all states.
        */
        $timeOffUsers = $serverList->merge($bartenders)->shuffle();

        TimeOffRequest::create([
            'user_id'     => $timeOffUsers[0]->id,
            'location_id' => $location->id,
            'start_date'  => now()->addDays(10)->toDateString(),
            'end_date'    => now()->addDays(12)->toDateString(),
            'reason'      => 'Wedding out of town — flying to Denver Friday, back Sunday night.',
            'status'      => 'pending',
        ]);

        TimeOffRequest::create([
            'user_id'     => $timeOffUsers[1]->id,
            'location_id' => $location->id,
            'start_date'  => now()->addDays(18)->toDateString(),
            'end_date'    => now()->addDays(18)->toDateString(),
            'reason'      => 'Dentist appointment, half-day only.',
            'status'      => 'pending',
        ]);

        TimeOffRequest::create([
            'user_id'     => $timeOffUsers[2]->id,
            'location_id' => $location->id,
            'start_date'  => now()->addDays(5)->toDateString(),
            'end_date'    => now()->addDays(7)->toDateString(),
            'reason'      => 'Family reunion in Michigan.',
            'status'      => 'approved',
            'resolved_by' => $manager->id,
            'resolved_at' => now()->subDays(2),
        ]);

        TimeOffRequest::create([
            'user_id'     => $timeOffUsers[3]->id,
            'location_id' => $location->id,
            'start_date'  => now()->addDays(3)->toDateString(),
            'end_date'    => now()->addDays(3)->toDateString(),
            'reason'      => 'Concert tickets — bought months ago.',
            'status'      => 'denied',
            'resolved_by' => $manager2->id,
            'resolved_at' => now()->subDay(),
        ]);

        TimeOffRequest::create([
            'user_id'     => $timeOffUsers[4]->id,
            'location_id' => $location->id,
            'start_date'  => now()->addDays(21)->toDateString(),
            'end_date'    => now()->addDays(25)->toDateString(),
            'reason'      => 'Vacation — Cancun. Already booked flights.',
            'status'      => 'approved',
            'resolved_by' => $manager->id,
            'resolved_at' => now()->subDays(5),
        ]);

        /*
        |------------------------------------------------------------------
        | Board Messages
        |------------------------------------------------------------------
        | A mix of top-level posts and threaded replies, including a
        | pinned post and a managers-only post.
        */
        $pinned = BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $manager->id,
            'parent_id'   => null,
            'body'        => 'Reminder: staff meal is at 4 PM every day before dinner service. Chef\'s doing a taco bar today — don\'t miss it! Great way to fuel up before the rush.',
            'visibility'  => 'all',
            'pinned'      => true,
        ]);

        $post1 = BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $serverList[0]->id,
            'parent_id'   => null,
            'body'        => 'Does anyone have a ride to the team outing next Saturday? I\'m coming from Lincoln Park.',
            'visibility'  => 'all',
            'pinned'      => false,
        ]);

        BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $serverList[3]->id,
            'parent_id'   => $post1->id,
            'body'        => 'I can pick you up! I\'m on Fullerton. DM me your address.',
            'visibility'  => 'all',
            'pinned'      => false,
        ]);

        BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $bartender->id,
            'parent_id'   => $post1->id,
            'body'        => 'I\'m driving from Lakeview if you need a backup option.',
            'visibility'  => 'all',
            'pinned'      => false,
        ]);

        $post2 = BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $serverList[5]->id,
            'parent_id'   => null,
            'body'        => 'Heads up — the restroom in the back hallway has a leaky faucet again. I put an out of order sign on it for now.',
            'visibility'  => 'all',
            'pinned'      => false,
        ]);

        BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $manager2->id,
            'parent_id'   => $post2->id,
            'body'        => 'Thanks for flagging. Plumber is coming tomorrow morning.',
            'visibility'  => 'all',
            'pinned'      => false,
        ]);

        $post3 = BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $serverList[8]->id,
            'parent_id'   => null,
            'body'        => 'The Cubs-Cardinals series starts July 3rd. Can we do something special for the 4th? Maybe a cookout on the patio?',
            'visibility'  => 'all',
            'pinned'      => false,
        ]);

        BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $manager->id,
            'parent_id'   => $post3->id,
            'body'        => 'Great idea. I\'ll plan something. We\'ll need all hands on deck that weekend anyway.',
            'visibility'  => 'all',
            'pinned'      => false,
        ]);

        // Managers-only post
        BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $manager->id,
            'parent_id'   => null,
            'body'        => 'Managers: Shoutout to the whole team for crushing last weekend. Revenue was up 18% over the same week last year. Let\'s keep that energy going into the holiday weekend.',
            'visibility'  => 'managers',
            'pinned'      => false,
        ]);

        BoardMessage::create([
            'location_id' => $location->id,
            'user_id'     => $serverList[2]->id,
            'parent_id'   => null,
            'body'        => 'Found a set of keys in the lost and found bin near the host stand. Black Toyota fob with a Cubs keychain. Might be a customer\'s.',
            'visibility'  => 'all',
            'pinned'      => false,
        ]);

        /*
        |------------------------------------------------------------------
        | Direct Message Conversations
        |------------------------------------------------------------------
        | Two sample DM threads between staff members.
        */
        // Conversation 1: Manager ↔ Bartender
        $convo1 = Conversation::create(['location_id' => $location->id]);
        $convo1->participants()->attach([
            $manager->id  => ['last_read_at' => now()],
            $bartender->id => ['last_read_at' => now()->subHours(2)],
        ]);

        DirectMessage::create([
            'conversation_id' => $convo1->id,
            'sender_id'       => $manager->id,
            'body'            => 'Hey Kyle, can you come in 30 minutes early tomorrow? We have a private event and I need the bar set up by 4.',
            'created_at'      => now()->subHours(3),
            'updated_at'      => now()->subHours(3),
        ]);
        DirectMessage::create([
            'conversation_id' => $convo1->id,
            'sender_id'       => $bartender->id,
            'body'            => 'Yeah, no problem. Do they have a special drink menu or just our regular cocktails?',
            'created_at'      => now()->subHours(2)->subMinutes(45),
            'updated_at'      => now()->subHours(2)->subMinutes(45),
        ]);
        DirectMessage::create([
            'conversation_id' => $convo1->id,
            'sender_id'       => $manager->id,
            'body'            => 'Regular menu plus a signature cocktail I\'ll show you. Thanks!',
            'created_at'      => now()->subHours(2)->subMinutes(30),
            'updated_at'      => now()->subHours(2)->subMinutes(30),
        ]);

        // Conversation 2: Server ↔ Server
        $convo2 = Conversation::create(['location_id' => $location->id]);
        $convo2->participants()->attach([
            $serverList[0]->id => ['last_read_at' => now()->subHour()],
            $serverList[4]->id => ['last_read_at' => now()->subHours(4)],
        ]);

        DirectMessage::create([
            'conversation_id' => $convo2->id,
            'sender_id'       => $serverList[0]->id,
            'body'            => 'Are you working Friday? Want to trade sections? I\'d rather have the patio.',
            'created_at'      => now()->subHours(5),
            'updated_at'      => now()->subHours(5),
        ]);
        DirectMessage::create([
            'conversation_id' => $convo2->id,
            'sender_id'       => $serverList[4]->id,
            'body'            => 'I\'m on dinner. Which section do you have?',
            'created_at'      => now()->subHours(4)->subMinutes(30),
            'updated_at'      => now()->subHours(4)->subMinutes(30),
        ]);
        DirectMessage::create([
            'conversation_id' => $convo2->id,
            'sender_id'       => $serverList[0]->id,
            'body'            => 'I have the bar-side booths. They\'re great for tips but I want the fresh air.',
            'created_at'      => now()->subHours(4),
            'updated_at'      => now()->subHours(4),
        ]);
        DirectMessage::create([
            'conversation_id' => $convo2->id,
            'sender_id'       => $serverList[4]->id,
            'body'            => 'Deal. I\'ll take the booths. Just clear it with Lisa.',
            'created_at'      => now()->subHours(3)->subMinutes(45),
            'updated_at'      => now()->subHours(3)->subMinutes(45),
        ]);

        // Conversation 3: Manager ↔ Server (unread message)
        $convo3 = Conversation::create(['location_id' => $location->id]);
        $convo3->participants()->attach([
            $manager->id       => ['last_read_at' => now()],
            $serverList[7]->id => ['last_read_at' => now()->subDays(1)],
        ]);

        DirectMessage::create([
            'conversation_id' => $convo3->id,
            'sender_id'       => $manager->id,
            'body'            => 'Quinn, great job with that big party last night. The customer called back to compliment your service. Keep it up!',
            'created_at'      => now()->subMinutes(30),
            'updated_at'      => now()->subMinutes(30),
        ]);

        /*
        |------------------------------------------------------------------
        | Manager Logs
        |------------------------------------------------------------------
        | Sample daily operational logs with weather/event/schedule snapshots.
        */
        ManagerLog::create([
            'location_id'      => $location->id,
            'created_by'       => $manager->id,
            'log_date'         => now()->subDay()->toDateString(),
            'body'             => "Solid Tuesday night. 86'd the salmon early — supplier issue. Bar was busy for the Bulls game. Kyle handled the rush well solo until Nina came in at 6. Sold through most of the cheesecake overstock. Plumber needed for back restroom faucet — called AAA Plumbing, they're coming tomorrow morning. Overall revenue looked strong for a weeknight.",
            'weather_snapshot' => [
                'current' => ['temperature' => 72, 'feels_like' => 74, 'humidity' => 55, 'wind_speed' => 8, 'weather_code' => 1, 'description' => 'Partly cloudy'],
                'today'   => ['high' => 78, 'low' => 62, 'weather_code' => 1, 'description' => 'Partly cloudy'],
            ],
            'events_snapshot'   => [
                ['title' => 'Bulls vs Celtics', 'event_time' => '19:00'],
            ],
            'schedule_snapshot' => [
                ['user_name' => 'Kyle Hendrix', 'role' => 'bartender', 'shift_name' => 'Dinner', 'start_time' => '16:00'],
                ['user_name' => 'Sam Presley', 'role' => 'server', 'shift_name' => 'Dinner', 'start_time' => '16:00'],
                ['user_name' => 'Riley Plant', 'role' => 'server', 'shift_name' => 'Dinner', 'start_time' => '16:00'],
                ['user_name' => 'Taylor Vedder', 'role' => 'server', 'shift_name' => 'Dinner', 'start_time' => '16:00'],
            ],
        ]);

        ManagerLog::create([
            'location_id'      => $location->id,
            'created_by'       => $manager2->id,
            'log_date'         => now()->subDays(2)->toDateString(),
            'body'             => "Monday was slow at lunch but dinner picked up. VIP party of 12 at table 12 went great — they ordered two bottles of wine and the ribeye special. Left a fat tip. Need to restock Goose Island kegs, running low. Patio furniture needs to be hosed down before the weekend.",
            'weather_snapshot' => [
                'current' => ['temperature' => 68, 'feels_like' => 66, 'humidity' => 60, 'wind_speed' => 12, 'weather_code' => 3, 'description' => 'Overcast'],
                'today'   => ['high' => 70, 'low' => 58, 'weather_code' => 3, 'description' => 'Overcast'],
            ],
            'events_snapshot'   => [],
            'schedule_snapshot' => [
                ['user_name' => 'Nina Cobain', 'role' => 'bartender', 'shift_name' => 'Lunch', 'start_time' => '10:00'],
                ['user_name' => 'Avery Townshend', 'role' => 'server', 'shift_name' => 'Lunch', 'start_time' => '10:00'],
                ['user_name' => 'Morgan Grohl', 'role' => 'server', 'shift_name' => 'Lunch', 'start_time' => '10:00'],
            ],
        ]);

        ManagerLog::create([
            'location_id'      => $location->id,
            'created_by'       => $manager->id,
            'log_date'         => now()->subDays(3)->toDateString(),
            'body'             => "Sunday brunch was packed — Industry Night brought in about 20 service industry folks. The 20% food discount plus \$5 wells was a big hit. We should consider making it a permanent thing. Churros were the top dessert seller. Patio was full all afternoon — guests loved the weather. Great energy from the whole team.",
            'weather_snapshot' => [
                'current' => ['temperature' => 65, 'feels_like' => 63, 'humidity' => 70, 'wind_speed' => 15, 'weather_code' => 61, 'description' => 'Light rain'],
                'today'   => ['high' => 66, 'low' => 55, 'weather_code' => 61, 'description' => 'Light rain'],
            ],
            'events_snapshot'   => [
                ['title' => 'Industry Night — Sundays', 'event_time' => null],
            ],
            'schedule_snapshot' => [
                ['user_name' => 'Kyle Hendrix', 'role' => 'bartender', 'shift_name' => 'Lunch', 'start_time' => '10:00'],
                ['user_name' => 'Casey Jagger', 'role' => 'bartender', 'shift_name' => 'Lunch', 'start_time' => '10:00'],
                ['user_name' => 'Sam Presley', 'role' => 'server', 'shift_name' => 'Lunch', 'start_time' => '10:00'],
                ['user_name' => 'Drew Hetfield', 'role' => 'server', 'shift_name' => 'Lunch', 'start_time' => '10:00'],
                ['user_name' => 'Blake Page', 'role' => 'server', 'shift_name' => 'Lunch', 'start_time' => '10:00'],
                ['user_name' => 'Ronnie Dio', 'role' => 'server', 'shift_name' => 'Lunch', 'start_time' => '10:00'],
            ],
        ]);

        /*
        |------------------------------------------------------------------
        | Acknowledgments
        |------------------------------------------------------------------
        | Simulate some staff having acknowledged today's pre-shift content
        | and others not — this powers the "read receipt" percentage tracker.
        */
        $ackableItems = collect();

        // Gather all active 86'd items
        $eightySixedItems = EightySixed::whereNull('restored_at')->get();
        foreach ($eightySixedItems as $item) {
            $ackableItems->push(['type' => EightySixed::class, 'id' => $item->id]);
        }

        // Gather all active specials
        $activeSpecials = Special::where('is_active', true)->get();
        foreach ($activeSpecials as $special) {
            $ackableItems->push(['type' => Special::class, 'id' => $special->id]);
        }

        // Gather all active push items
        $activePushItems = PushItem::where('is_active', true)->get();
        foreach ($activePushItems as $pushItem) {
            $ackableItems->push(['type' => PushItem::class, 'id' => $pushItem->id]);
        }

        // Gather all non-expired announcements
        $activeAnnouncements = Announcement::where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })->get();
        foreach ($activeAnnouncements as $ann) {
            $ackableItems->push(['type' => Announcement::class, 'id' => $ann->id]);
        }

        // ~60% of staff have acknowledged everything, ~25% acknowledged some, ~15% none
        $staffForAcks = $allStaff->filter(fn ($u) => in_array($u->role, ['server', 'bartender']));

        foreach ($staffForAcks as $staffMember) {
            $roll = rand(1, 100);

            if ($roll <= 60) {
                // Acknowledged all items
                foreach ($ackableItems as $item) {
                    Acknowledgment::create([
                        'user_id'             => $staffMember->id,
                        'acknowledgable_type' => $item['type'],
                        'acknowledgable_id'   => $item['id'],
                        'acknowledged_at'     => now()->subMinutes(rand(10, 480)),
                    ]);
                }
            } elseif ($roll <= 85) {
                // Acknowledged some items (random 40-70%)
                $subset = $ackableItems->shuffle()->take((int) ($ackableItems->count() * (rand(40, 70) / 100)));
                foreach ($subset as $item) {
                    Acknowledgment::create([
                        'user_id'             => $staffMember->id,
                        'acknowledgable_type' => $item['type'],
                        'acknowledgable_id'   => $item['id'],
                        'acknowledged_at'     => now()->subMinutes(rand(10, 480)),
                    ]);
                }
            }
            // else: no acknowledgments (simulates staff who haven't checked in yet)
        }

        /*
        |------------------------------------------------------------------
        | Seed Content for Additional Locations
        |------------------------------------------------------------------
        | Each new location gets the same menu, specials, events, and shift
        | templates as Bald Bar. Staff will be added later through the app.
        */
        $this->seedLocationContent($titz, User::where('email', 'bfc@preshift.test')->first());
        $this->seedLocationContent($fieldOfDreams, User::where('email', 'chip@preshift.test')->first());
        $this->seedLocationContent($almostHome, User::where('email', 'otto@preshift.test')->first());
        $this->seedLocationContent($theRail, $dean);
        $this->seedLocationContent($snookis, $superadmin);
    }

    /**
     * Seed menu categories, items, operational content, events, and shift
     * templates for a given location. Creates the same content structure as
     * Bald Bar so each establishment is immediately functional.
     */
    private function seedLocationContent(Location $location, User $admin): void
    {
        /*
        |------------------------------------------------------------------
        | Categories & Menu Items
        |------------------------------------------------------------------
        */
        $appetizers = Category::create(['location_id' => $location->id, 'name' => 'Appetizers',       'sort_order' => 1]);
        $entrees    = Category::create(['location_id' => $location->id, 'name' => 'Entrees',          'sort_order' => 2]);
        $sandwiches = Category::create(['location_id' => $location->id, 'name' => 'Sandwiches',       'sort_order' => 3]);
        $sides      = Category::create(['location_id' => $location->id, 'name' => 'Sides',            'sort_order' => 4]);
        $drinks     = Category::create(['location_id' => $location->id, 'name' => 'Cocktails & Beer', 'sort_order' => 5]);
        $desserts   = Category::create(['location_id' => $location->id, 'name' => 'Desserts',         'sort_order' => 6]);

        $items = [];
        $menuData = [
            // Appetizers
            [$appetizers->id, 'Buffalo Wings',         'Crispy wings tossed in house buffalo sauce, served with ranch and celery',              14.99, 'food', false],
            [$appetizers->id, 'Loaded Nachos',         'Tortilla chips, queso, jalapeños, pico, sour cream, guacamole',                        13.99, 'food', false],
            [$appetizers->id, 'Bavarian Pretzel',      'Jumbo soft pretzel with beer cheese and whole grain mustard',                           11.99, 'food', false],
            [$appetizers->id, 'Fried Calamari',        'Lightly breaded, marinara and lemon aioli',                                            13.99, 'food', false],
            [$appetizers->id, 'Brisket Sliders',       'Three smoked brisket sliders with pickled onion and BBQ aioli',                        15.99, 'food', false],
            [$appetizers->id, 'Spinach Artichoke Dip', 'Warm dip served with toasted pita chips',                                              12.99, 'food', false],
            // Entrees
            [$entrees->id, 'Bald Burger',           'Half-pound patty, aged cheddar, lettuce, tomato, house sauce, brioche bun',            17.99, 'food', false],
            [$entrees->id, 'Pan-Seared Salmon',     'Atlantic salmon, lemon butter, seasonal vegetables',                                  24.99, 'food', false],
            [$entrees->id, '12oz Ribeye',           'USDA Choice ribeye, garlic herb butter, roasted potatoes',                            34.99, 'food', false],
            [$entrees->id, 'Chicken Parmesan',      'Breaded chicken breast, marinara, mozzarella, over spaghetti',                        19.99, 'food', false],
            [$entrees->id, 'Blackened Fish Tacos',  'Three tacos with mahi mahi, mango salsa, chipotle crema, cilantro slaw',              17.99, 'food', false],
            [$entrees->id, 'Truffle Mac & Cheese',  'Cavatappi, four cheese blend, truffle oil, panko crust',                              16.99, 'food', true],
            // Sandwiches
            [$sandwiches->id, 'Cubano Press',       'Roasted pork, ham, Swiss, pickles, mustard on pressed Cuban bread',                   15.99, 'food', false],
            [$sandwiches->id, 'BLT Club',           'Thick-cut bacon, lettuce, tomato, avocado, garlic mayo, triple-stacked',              14.99, 'food', false],
            [$sandwiches->id, 'Philly Cheesesteak', 'Shaved ribeye, peppers, onions, provolone on a hoagie roll',                         16.99, 'food', false],
            // Sides
            [$sides->id, 'Seasoned Fries',  'House-seasoned, served with ketchup and garlic aioli',                                6.99, 'food', false],
            [$sides->id, 'Onion Rings',     'Beer-battered and golden fried',                                                      7.99, 'food', false],
            [$sides->id, 'Creamy Coleslaw', 'Classic creamy coleslaw',                                                             4.99, 'food', false],
            [$sides->id, 'House Salad',     'Mixed greens, cherry tomato, cucumber, red onion, balsamic vinaigrette',              8.99, 'food', false],
            // Cocktails & Beer
            [$drinks->id, 'House Margarita',         'Tequila, lime, agave, salted rim',                                           12.00, 'drink', false],
            [$drinks->id, 'Espresso Martini',        'Vodka, coffee liqueur, fresh espresso',                                      14.00, 'drink', true],
            [$drinks->id, 'Old Fashioned',           'Bourbon, Angostura bitters, sugar, orange peel',                             13.00, 'drink', false],
            [$drinks->id, 'Mojito',                  'White rum, mint, lime, soda, sugar',                                         12.00, 'drink', false],
            [$drinks->id, 'Moscow Mule',             'Vodka, ginger beer, lime, served in a copper mug',                           12.00, 'drink', false],
            [$drinks->id, 'Chicago Handshake',       "Shot of Jeppson's Malört with an Old Style beer back",                       10.00, 'drink', false],
            [$drinks->id, 'Goose Island IPA',        "Chicago's own — 16oz draft pour",                                            8.00, 'drink', false],
            [$drinks->id, 'Half Acre Daisy Cutter',  'Pale ale — 16oz draft pour',                                                 8.00, 'drink', false],
            [$drinks->id, 'Domestic Bucket',         '5 bottles — Bud Light, Miller Lite, or Coors Light',                         22.00, 'drink', false],
            // Desserts
            [$desserts->id, 'NY Cheesecake',        'Classic cheesecake with berry compote',                                       10.99, 'food', false],
            [$desserts->id, 'Warm Brownie Sundae',  'Chocolate brownie, vanilla ice cream, hot fudge, whipped cream',              11.99, 'food', false],
            [$desserts->id, 'Churros',              'Cinnamon sugar churros with chocolate and caramel dipping sauces',             9.99, 'food', false],
        ];

        foreach ($menuData as [$catId, $name, $desc, $price, $type, $isNew]) {
            $item = MenuItem::create([
                'location_id' => $location->id,
                'category_id' => $catId,
                'name'        => $name,
                'description' => $desc,
                'price'       => $price,
                'type'        => $type,
                'is_new'      => $isNew,
            ]);
            $items[$name] = $item;
        }

        /*
        |------------------------------------------------------------------
        | 86'd Items
        |------------------------------------------------------------------
        */
        EightySixed::create([
            'location_id'     => $location->id,
            'menu_item_id'    => $items['Pan-Seared Salmon']->id,
            'item_name'       => 'Pan-Seared Salmon',
            'reason'          => 'Supplier delivery delayed until tomorrow — fish market truck broke down',
            'eighty_sixed_by' => $admin->id,
        ]);

        EightySixed::create([
            'location_id'     => $location->id,
            'menu_item_id'    => null,
            'item_name'       => 'Oat Milk',
            'reason'          => 'Out of stock — reorder placed, arriving Wednesday',
            'eighty_sixed_by' => $admin->id,
        ]);

        EightySixed::create([
            'location_id'     => $location->id,
            'menu_item_id'    => $items['12oz Ribeye']->id,
            'item_name'       => '12oz Ribeye',
            'reason'          => 'Down to last 2 steaks — 86 after they sell',
            'eighty_sixed_by' => $admin->id,
        ]);

        EightySixed::create([
            'location_id'     => $location->id,
            'menu_item_id'    => $items['Fried Calamari']->id,
            'item_name'       => 'Fried Calamari',
            'reason'          => 'Fryer was down for maintenance',
            'eighty_sixed_by' => $admin->id,
            'restored_at'     => now()->subHours(3),
        ]);

        /*
        |------------------------------------------------------------------
        | Specials
        |------------------------------------------------------------------
        */
        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $items['Buffalo Wings']->id,
            'title'        => 'Half-Price Wings',
            'description'  => 'All wing flavors half off during happy hour (4–6 PM)',
            'type'         => 'daily',
            'starts_at'    => '2026-01-01',
            'ends_at'      => null,
            'created_by'   => $admin->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $items['House Margarita']->id,
            'title'        => '$8 Marg Wednesdays',
            'description'  => 'House margaritas all day, every Wednesday',
            'type'         => 'weekly',
            'starts_at'    => '2026-01-01',
            'ends_at'      => '2026-07-15',
            'created_by'   => $admin->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $items['Domestic Bucket']->id,
            'title'        => 'Game Day Bucket Deal',
            'description'  => '$18 domestic buckets during any televised Chicago sports game',
            'type'         => 'daily',
            'starts_at'    => '2026-01-01',
            'ends_at'      => null,
            'created_by'   => $admin->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $items['Chicago Handshake']->id,
            'title'        => '$7 Chicago Handshakes',
            'description'  => 'The classic Malört + Old Style combo, discounted all night on Fridays',
            'type'         => 'weekly',
            'starts_at'    => '2026-01-01',
            'ends_at'      => null,
            'created_by'   => $admin->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => $items['Truffle Mac & Cheese']->id,
            'title'        => 'New Menu Launch — Truffle Mac',
            'description'  => '$12 introductory price on the new Truffle Mac & Cheese (reg. $16.99)',
            'type'         => 'limited_time',
            'starts_at'    => now()->subDays(3)->toDateString(),
            'ends_at'      => now()->addDays(11)->toDateString(),
            'quantity'     => 30,
            'created_by'   => $admin->id,
        ]);

        Special::create([
            'location_id'  => $location->id,
            'menu_item_id' => null,
            'title'        => 'Industry Night — Sundays',
            'description'  => '20% off food and $5 well drinks for service industry workers (show pay stub)',
            'type'         => 'weekly',
            'starts_at'    => '2026-01-01',
            'ends_at'      => null,
            'created_by'   => $admin->id,
        ]);

        /*
        |------------------------------------------------------------------
        | Push Items
        |------------------------------------------------------------------
        */
        PushItem::create([
            'location_id'  => $location->id,
            'menu_item_id' => $items['Espresso Martini']->id,
            'title'        => 'Push Espresso Martinis',
            'description'  => 'New menu item — suggest to every table as an after-dinner drink. High margin.',
            'reason'       => 'New item launch, high margin',
            'priority'     => 'high',
            'created_by'   => $admin->id,
        ]);

        PushItem::create([
            'location_id'  => $location->id,
            'menu_item_id' => $items['NY Cheesecake']->id,
            'title'        => 'Move the Cheesecake',
            'description'  => 'We over-prepped this week. Mention it with the dessert pitch at every table.',
            'reason'       => 'Overstock — need to sell through before Friday',
            'priority'     => 'medium',
            'created_by'   => $admin->id,
        ]);

        PushItem::create([
            'location_id'  => $location->id,
            'menu_item_id' => $items['Truffle Mac & Cheese']->id,
            'title'        => 'Truffle Mac Feature',
            'description'  => 'Brand new on the menu — talk it up! Describe the truffle oil and four-cheese blend.',
            'reason'       => 'New item, building awareness',
            'priority'     => 'high',
            'created_by'   => $admin->id,
        ]);

        PushItem::create([
            'location_id'  => $location->id,
            'menu_item_id' => $items['Brisket Sliders']->id,
            'title'        => 'Brisket Sliders as App',
            'description'  => 'Great shareable appetizer. Suggest when tables are still deciding.',
            'reason'       => 'High margin, pairs well with beer specials',
            'priority'     => 'low',
            'created_by'   => $admin->id,
        ]);

        /*
        |------------------------------------------------------------------
        | Announcements
        |------------------------------------------------------------------
        */
        Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'NBA Finals Watch Parties',
            'body'         => 'The NBA Finals start June 3. We\'re running watch party specials on all games — $18 buckets, half-price wings. Expect packed houses. All hands on deck for Game 1.',
            'priority'     => 'important',
            'target_roles' => null,
            'posted_by'    => $admin->id,
            'expires_at'   => now()->addDays(14),
        ]);

        Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'New Cocktail Training',
            'body'         => 'Bartenders: please review the new summer cocktail recipes in the binder before your next shift. The Espresso Martini and Truffle Mac pairing pitch need to be second nature. Training session Thursday at 3 PM.',
            'priority'     => 'normal',
            'target_roles' => ['bartender'],
            'posted_by'    => $admin->id,
            'expires_at'   => now()->addDays(5),
        ]);

        Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'Health Inspection Next Week',
            'body'         => 'Annual health inspection is scheduled for next Tuesday. Double-check your stations: clean under equipment, label and date all prep containers, ensure hand-washing stations are stocked. Let\'s nail this.',
            'priority'     => 'important',
            'target_roles' => null,
            'posted_by'    => $admin->id,
            'expires_at'   => now()->addDays(7),
        ]);

        Announcement::create([
            'location_id'  => $location->id,
            'title'        => 'New POS System Training',
            'body'         => 'We\'re upgrading the POS terminals this weekend. Training video links are in the group chat. Please watch before your Monday shift. The new system is faster but the menu layout has changed.',
            'priority'     => 'normal',
            'target_roles' => null,
            'posted_by'    => $admin->id,
            'expires_at'   => now()->addDays(3),
        ]);

        /*
        |------------------------------------------------------------------
        | Events — Real Chicago Sports & Entertainment (Jan–Jul 2026)
        |------------------------------------------------------------------
        */
        $eventData = [
            ['2026-01-01', null,    'Blackhawks vs Stars — New Year\'s Day',     'Home game at United Center. New Year\'s Day crowd will be bar-hopping after. Open early, extra bartenders.'],
            ['2026-01-04', '19:00', 'Blackhawks vs Golden Knights',              'Home game at United Center. Vegas always draws a crowd.'],
            ['2026-01-07', '19:00', 'Blackhawks vs Blues — Rivalry Night',       'Home game. Blues rivalry = packed house. Push beer specials.'],
            ['2026-01-10', '19:00', 'Bulls vs Mavericks',                        'Home game at United Center. Luka vs the Bulls.'],
            ['2026-01-12', '19:00', 'Blackhawks vs Oilers',                      'Connor McDavid at United Center. Hockey fans will be out in force.'],
            ['2026-01-19', '14:00', 'Blackhawks vs Jets — Toews Returns',        'Jonathan Toews returns to United Center for the first time since his comeback. Huge emotional event for Hawks fans. Expect overflow.'],
            ['2026-01-24', '19:00', 'Bulls vs Celtics',                          'Marquee home matchup. Jayson Tatum in town.'],
            ['2026-01-26', '14:00', 'Bulls vs Lakers',                           'LeBron/Bronny vs Bulls. Sunday afternoon — open early for pre-game crowd.'],
            ['2026-01-25', null,    'NFL Conference Championships',               'All-day watch party. Set up extra TVs. Playoff football = capacity crowd.'],
            ['2026-02-02', '19:00', 'Blackhawks vs Sharks',                      'Last home game before the Olympic break.'],
            ['2026-02-06', null,    'Winter Olympics Begin — Milan',              'NHL players in the Olympics for the first time since 2014. Hockey games will be huge draws — check broadcast times.'],
            ['2026-02-08', '17:30', 'SUPER BOWL LX Watch Party',                 'Seahawks vs Patriots at Levi\'s Stadium. Biggest bar night of the year. All hands on deck. Specials: $18 buckets, $8 Chicago Handshakes, half-price wings. Doors open at 3 PM.'],
            ['2026-02-15', '19:00', 'NBA All-Star Game — Los Angeles',           'All-Star Sunday. Turn all TVs to TNT/ESPN. Celebrity game, dunk contest, three-point contest throughout the weekend.'],
            ['2026-02-22', '19:00', 'Bulls vs Knicks',                           'Home game. Knicks always travel well — expect visiting fans.'],
            ['2026-02-28', '13:30', 'Fire FC Home Opener vs Montreal',           'First MLS home game of the season at Soldier Field. Soccer crowd will want food and drinks before/after.'],
            ['2026-03-01', '14:30', 'Bulls vs Bucks — Rivalry',                  'Sunday afternoon home game. Milwaukee rivalry. Push game day specials.'],
            ['2026-03-03', '19:00', 'Bulls vs Thunder',                          'OKC is a top team — marquee Monday night game.'],
            ['2026-03-10', null,    'Big Ten Tournament Begins — United Center', 'Six straight days of basketball at the United Center (Mar 10–15). This is one of the biggest bar weeks of the year in Chicago. All-day crowds.'],
            ['2026-03-15', null,    'Selection Sunday — March Madness',          'NCAA bracket reveal. Bar will be packed for the CBS broadcast. Run bracket contest promos.'],
            ['2026-03-17', null,    'St. Patrick\'s Day + First Four',           'St. Patrick\'s Day AND the first games of March Madness. Double whammy. River will be green. All-day party. Full staff.'],
            ['2026-03-19', null,    'March Madness — First Round Day 1',         'Games from noon to midnight. Wall-to-wall basketball. Keep TVs on CBS/TBS/TNT/truTV.'],
            ['2026-03-20', null,    'March Madness — First Round Day 2',         'Second day of first-round games. Same drill.'],
            ['2026-03-21', null,    'March Madness — Second Round Day 1',        'Weekend games. Expect brunch-through-close crowds.'],
            ['2026-03-26', '14:20', 'Cubs Opening Day at Wrigley!',              'Cubs vs Nationals. First game of the MLB season. Wrigleyville will be insane. We will get overflow from the neighborhood. Open at noon.'],
            ['2026-03-26', null,    'March Madness — Sweet 16 Day 1',            'Sweet 16 AND Cubs Opening Day. Monster day.'],
            ['2026-03-28', null,    'March Madness — Elite Eight',               'Elite Eight weekend. Final Four is next weekend.'],
            ['2026-03-31', '20:00', 'Mexico vs Belgium at Soldier Field',        'International friendly — pre-World Cup warm-up. Huge event for Chicago\'s soccer community.'],
            ['2026-04-02', '16:10', 'White Sox Home Opener vs Blue Jays',        'Opening Day at Rate Field. South Side fans celebrate. We\'ll see some crossover traffic.'],
            ['2026-04-04', null,    'NCAA Final Four — Indianapolis',            'National semifinals on TV. Big bar night.'],
            ['2026-04-06', '21:00', 'NCAA Championship Game',                    'Monday night championship. Every sports bar in America will be packed.'],
            ['2026-04-11', '16:00', 'Blackhawks vs Blues — Rivalry',             'Late-season Saturday home game. Blues rivalry.'],
            ['2026-04-17', '19:10', 'Cubs vs Mets at Wrigley',                   'Three-game Mets series starts. Good walkover crowd from Wrigleyville.'],
            ['2026-04-18', null,    'NBA Playoffs Begin',                        'First round tips off. Multiple games daily through the end of April.'],
            ['2026-04-18', null,    'Stanley Cup Playoffs Begin',                'NHL first round starts same day as NBA. Sports overload — run double-header specials.'],
            ['2026-04-23', null,    'NFL Draft Day 1 — Pittsburgh',              'Bears fans will be glued to TVs. First-round drama. Run draft night specials.'],
            ['2026-05-02', null,    'Kentucky Derby Watch Party',                '152nd Kentucky Derby. Mint julep specials. Dress code optional but encouraged.'],
            ['2026-05-15', '19:10', 'Crosstown Classic — Cubs at White Sox',     'First game of the Crosstown Classic at Rate Field. Biggest local rivalry series of the year. Every bar in the city will be rocking.'],
            ['2026-05-16', '18:10', 'Crosstown Classic Game 2',                  'Saturday night Crosstown game. Expect a late-night crowd after.'],
            ['2026-05-17', '13:10', 'Crosstown Classic Game 3',                  'Sunday afternoon finale. Loser buys.'],
            ['2026-05-20', '19:00', 'Chicago Sky Home Opener vs Wings',          'WNBA season opener at Wintrust Arena. Growing fanbase — promote on socials.'],
            ['2026-05-23', null,    'MLS Season Pauses for World Cup',           'Fire FC\'s last game before the FIFA World Cup break. MLS resumes mid-July.'],
            ['2026-06-03', '20:30', 'NBA Finals — Game 1',                       'NBA Finals begin. Prime time ABC. Push bucket specials and wings.'],
            ['2026-06-06', '13:30', 'USA vs Germany at Soldier Field',           'USMNT send-off match before the World Cup. This is IN CHICAGO at Soldier Field. Pre-game and post-game crowds will be massive. Open at 11 AM.'],
            ['2026-06-11', null,    'FIFA World Cup Kicks Off',                  'The World Cup begins! 48 teams across the US, Mexico, and Canada. Games will run daily through July 19. Every match is a potential bar event.'],
            ['2026-06-12', '19:10', 'White Sox vs Dodgers at Rate Field',        'LA Dodgers in town for a marquee series. Shohei Ohtani visiting.'],
            ['2026-06-17', '19:00', 'Chicago Sky vs NY Liberty',                 'WNBA marquee matchup at Wintrust Arena.'],
            ['2026-06-19', null,    'NBA Finals — Game 7 (if needed)',           'Potential Game 7. If it happens, this is the biggest TV night since the Super Bowl.'],
            ['2026-06-28', '19:00', 'Chicago Sky vs Las Vegas Aces',             'Home game at United Center — big-venue WNBA showcase.'],
            ['2026-07-03', '14:20', 'Cubs vs Cardinals — July 4th Weekend',     'Independence Day weekend. Cubs vs Cardinals at Wrigley. THE rivalry. One of the biggest Wrigley weekends of the year. Extra staff all 3 days.'],
            ['2026-07-04', '14:20', 'Cubs vs Cardinals + 4th of July',           'Cubs-Cards on the 4th of July. Plus rooftop fireworks in Wrigleyville. We\'ll be slammed from noon to 2 AM.'],
            ['2026-07-05', '13:20', 'Cubs vs Cardinals — Series Finale',         'Sunday finale of the Independence Day series.'],
            ['2026-07-07', '19:10', 'White Sox vs Red Sox at Rate Field',        'Red Sox in town. Good visiting fanbase.'],
            ['2026-07-14', null,    'MLB All-Star Game — Philadelphia',          'Midsummer Classic on FOX. America\'s 250th birthday celebration tie-in. Run red/white/blue drink specials.'],
        ];

        foreach ($eventData as [$date, $time, $title, $desc]) {
            Event::create([
                'location_id' => $location->id,
                'title'       => $title,
                'description' => $desc,
                'event_date'  => $date,
                'event_time'  => $time,
                'created_by'  => $admin->id,
            ]);
        }

        /*
        |------------------------------------------------------------------
        | Shift Templates
        |------------------------------------------------------------------
        */
        ShiftTemplate::create(['location_id' => $location->id, 'name' => 'Lunch',  'start_time' => '10:30:00']);
        ShiftTemplate::create(['location_id' => $location->id, 'name' => 'Dinner', 'start_time' => '16:30:00']);
        ShiftTemplate::create(['location_id' => $location->id, 'name' => 'Close',  'start_time' => '18:00:00']);
    }
}
