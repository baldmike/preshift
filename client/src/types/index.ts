/**
 * types/index.ts
 *
 * Central type definitions for the Pre-Shift Meeting application.
 * Every interface here maps 1-to-1 with a Laravel Eloquent model on the
 * backend.  Timestamps (`created_at`, `updated_at`) are ISO-8601 strings
 * because they are serialised as JSON from the API.
 */

/**
 * Represents a business organization that owns one or more locations.
 * Organizations sit above locations in the hierarchy — each restaurant
 * group is an organization containing one or more Location records.
 */
export interface Organization {
  /** Primary key */
  id: number
  /** Business/organization name */
  name: string
  /** Street address; nullable */
  address: string | null
  /** City name; nullable */
  city: string | null
  /** State abbreviation; nullable */
  state: string | null
  /** IANA timezone identifier; nullable */
  timezone: string | null
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * Represents a physical restaurant / bar location.
 * A location is the top-level tenant; almost every other entity belongs to
 * exactly one location via `location_id`.
 */
export interface Location {
  /** Primary key */
  id: number
  /** Foreign key to the owning Organization; nullable */
  organization_id: number | null
  /** Human-readable venue name (e.g. "Downtown Bistro") */
  name: string
  /** Street address; nullable if not yet provided */
  address: string | null
  /** City name (e.g. "Austin"); nullable */
  city: string | null
  /** State abbreviation or name (e.g. "TX"); nullable */
  state: string | null
  /** IANA timezone identifier (e.g. "America/New_York"); nullable */
  timezone: string | null
  /** GPS latitude for weather lookups; nullable if not configured */
  latitude: number | null
  /** GPS longitude for weather lookups; nullable if not configured */
  longitude: number | null
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * Lightweight representation of a user's membership at one location.
 * Returned by the login and user endpoints alongside the full User object
 * to power the location picker and switcher UI.
 */
export interface LocationMembership {
  /** Location primary key */
  id: number
  /** Human-readable venue name */
  name: string
  /** The user's role at this specific location */
  role: 'admin' | 'manager' | 'server' | 'bartender'
}

/**
 * Represents an authenticated user (staff member or manager).
 * The `role` field drives permission checks throughout the frontend
 * (route guards, UI visibility, etc.).
 */
export interface User {
  /** Primary key */
  id: number
  /** Foreign key to the Location this user belongs to */
  location_id: number
  /** Foreign key to the Organization this user belongs to; nullable */
  organization_id: number | null
  /** Display name */
  name: string
  /** Login email address */
  email: string
  /**
   * Role determines access level:
   *  - 'admin'     : full system access, can manage locations and users
   *  - 'manager'   : can manage menu items, specials, 86'd items, announcements
   *  - 'server'    : front-of-house staff, read-only dashboard access
   *  - 'bartender' : bar staff, read-only dashboard access
   */
  role: 'admin' | 'manager' | 'server' | 'bartender'
  /** Additional roles for multi-role staff; null means primary role only */
  roles: ('admin' | 'manager' | 'server' | 'bartender')[] | null
  /** Whether this user has SuperAdmin privileges */
  is_superadmin: boolean
  /** Contact phone number; null if not provided */
  phone: string | null
  /**
   * Weekly availability grid. Each day maps to an array of slot strings:
   *   - "10:30"  → available 10:30 AM – 6:00 PM
   *   - "16:30"  → available 4:30 PM – close
   *   - "open"   → open availability (any shift)
   * Null means the employee hasn't set availability yet.
   */
  availability: Record<string, string[]> | null
  /** Full URL to the user's profile photo; null if no photo uploaded */
  profile_photo_url: string | null
  /** Eagerly-loaded Location relationship (optional, depends on API include) */
  location?: Location
  /** Eagerly-loaded Organization relationship (optional, depends on API include) */
  organization?: Organization
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * Menu category used to group MenuItems (e.g. "Appetizers", "Entrees", "Cocktails").
 * Categories are location-scoped and ordered by `sort_order` for display.
 */
export interface Category {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Category display name */
  name: string
  /** Integer used to order categories in the UI (lower = first) */
  sort_order: number
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * A single item on a location's menu (food or drink).
 * MenuItem is referenced by EightySixed, Special, and PushItem entities
 * when those records relate to a specific dish/drink.
 */
export interface MenuItem {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Foreign key to the Category; null if uncategorised */
  category_id: number | null
  /** Item display name (e.g. "Truffle Fries") */
  name: string
  /** Optional long-form description of the dish */
  description: string | null
  /** Price stored as a string to preserve decimal formatting (e.g. "14.99") */
  price: string
  /** Optional type label (e.g. "food", "drink", "dessert") */
  type: string | null
  /** Flag indicating whether this is a newly-added menu item */
  is_new: boolean
  /** Flag indicating whether the item is currently available on the menu */
  is_active: boolean
  /** List of allergen tags (e.g. ["gluten", "dairy"]); null if none specified */
  allergens: string[] | null
  /** Eagerly-loaded Category relationship (optional) */
  category?: Category
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * Represents an "86'd" (unavailable) item at a location.
 * In restaurant jargon, "86'd" means the kitchen has run out of an item.
 * When `restored_at` is non-null the item has been un-86'd (back in stock).
 */
export interface EightySixed {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Foreign key to the related MenuItem; null if the item is ad-hoc / not on the menu */
  menu_item_id: number | null
  /** Human-readable item name (may differ from the MenuItem name for ad-hoc items) */
  item_name: string
  /** Optional reason the item was 86'd (e.g. "ran out of salmon") */
  reason: string | null
  /** Foreign key (User.id) of the staff member who flagged the item as 86'd */
  eighty_sixed_by: number
  /** ISO-8601 timestamp when the item was restored; null while still 86'd */
  restored_at: string | null
  /** Eagerly-loaded MenuItem relationship (optional) */
  menu_item?: MenuItem
  /** Eagerly-loaded User who 86'd the item (optional) */
  user?: User
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * A daily or time-bound special (e.g. happy-hour pricing, chef's special).
 * Specials have a validity window defined by `starts_at` / `ends_at` and
 * can be toggled on or off with `is_active`.
 */
export interface Special {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Foreign key to a MenuItem; null for specials not tied to a specific dish */
  menu_item_id: number | null
  /** Short headline for the special */
  title: string
  /** Optional detailed description */
  description: string | null
  /** Optional type/category label (e.g. "happy_hour", "lunch") */
  type: string | null
  /** ISO-8601 datetime when the special becomes effective */
  starts_at: string
  /** ISO-8601 datetime when the special expires; null if open-ended */
  ends_at: string | null
  /** Whether the special is currently active/visible */
  is_active: boolean
  /** Limited quantity available; null means unlimited */
  quantity: number | null
  /** Foreign key (User.id) of the manager/admin who created the special */
  created_by: number
  /** Eagerly-loaded MenuItem relationship (optional) */
  menu_item?: MenuItem
  /** Eagerly-loaded User who created the special (optional) */
  creator?: User
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * A "push item" -- a menu item that management wants the staff to actively
 * promote / upsell to guests (e.g. an overstocked wine or a high-margin dish).
 */
export interface PushItem {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Foreign key to the related MenuItem; null for ad-hoc push suggestions */
  menu_item_id: number | null
  /** Short headline for the push item */
  title: string
  /** Optional detailed description or talking points for staff */
  description: string | null
  /** Optional reason management wants this item pushed (e.g. "overstocked") */
  reason: string | null
  /** Optional priority level (e.g. "high", "medium", "low") */
  priority: string | null
  /** Whether the push item is currently active/visible */
  is_active: boolean
  /** Foreign key (User.id) of the manager/admin who created the push item */
  created_by: number
  /** Eagerly-loaded MenuItem relationship (optional) */
  menu_item?: MenuItem
  /** Eagerly-loaded User who created the push item (optional) */
  creator?: User
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * A management announcement broadcast to staff at a location.
 * Announcements can optionally target specific roles and have an expiry date.
 */
export interface Announcement {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Short headline / subject of the announcement */
  title: string
  /** Optional long-form body text (may contain markdown) */
  body: string | null
  /** Optional priority level (e.g. "urgent", "normal", "low") */
  priority: string | null
  /**
   * If non-null, only users whose role is in this array should see the
   * announcement (e.g. ["server", "bartender"]).  Null means all roles.
   */
  target_roles: string[] | null
  /** Foreign key (User.id) of the manager/admin who posted the announcement */
  posted_by: number
  /** ISO-8601 datetime when the announcement should stop displaying; null if indefinite */
  expires_at: string | null
  /** Eagerly-loaded User who posted the announcement (optional) */
  poster?: User
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * A full acknowledgment record returned by the API.
 * Uses Laravel's polymorphic relationship pattern: `acknowledgable_type` is
 * the fully-qualified model class (e.g. "App\\Models\\Announcement") and
 * `acknowledgable_id` is the primary key of the related entity.
 * This confirms that a specific user has read/acknowledged a specific item.
 */
export interface Acknowledgment {
  /** Primary key */
  id: number
  /** Foreign key to the User who acknowledged */
  user_id: number
  /** Polymorphic type string (Laravel morph class) */
  acknowledgable_type: string
  /** Primary key of the acknowledged entity */
  acknowledgable_id: number
  /** ISO-8601 datetime when the acknowledgment occurred */
  acknowledged_at: string
}

/**
 * Lightweight reference used on the client side to track which items the
 * current user has already acknowledged.  Unlike the full `Acknowledgment`,
 * this only carries the polymorphic type shorthand and the entity id --
 * just enough to do a lookup in the local store.
 */
export interface AcknowledgmentRef {
  /** Short type key (e.g. "announcement", "special") -- NOT the full Laravel class */
  type: string
  /** Primary key of the acknowledged entity */
  id: number
}

/**
 * The combined payload returned by `GET /api/preshift`.
 * This single endpoint provides everything the staff dashboard needs in one
 * request, avoiding multiple round-trips.  The `acknowledgments` array
 * contains only the current user's acknowledgment refs so the UI can mark
 * items as "seen".
 */
export interface PreShiftData {
  /** All currently-86'd items for the user's location */
  eighty_sixed: EightySixed[]
  /** All active specials for the user's location */
  specials: Special[]
  /** All active push items for the user's location */
  push_items: PushItem[]
  /** All current announcements targeted at the user's role */
  announcements: Announcement[]
  /** Today's events for the user's location */
  events: Event[]
  /** Acknowledgment refs for items the current user has already acknowledged */
  acknowledgments: AcknowledgmentRef[]
}

/**
 * A daily event at a location (e.g. "Wine tasting at 7pm", "Private party 6-9").
 * Events are date-scoped and posted by managers so staff know what's happening.
 */
export interface Event {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Short headline for the event */
  title: string
  /** Optional details about the event */
  description: string | null
  /** The day the event applies to (ISO date string) */
  event_date: string
  /** Optional "HH:MM" display time */
  event_time: string | null
  /** Foreign key (User.id) of the manager/admin who created the event */
  created_by: number
  /** Eagerly-loaded User who created the event (optional) */
  creator?: User
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

// ─── Scheduling System Types ─────────────────────────────────────────────

/**
 * A reusable shift definition (e.g. "Lunch 10:30").
 * Created once per location, referenced when building weekly schedules.
 * Only stores start_time — shift names already communicate what the shift
 * is, and rigid end times don't reflect how restaurant shifts actually work.
 */
export interface ShiftTemplate {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Short label (e.g. "Dinner", "Double") */
  name: string
  /** HH:MM:SS when the shift begins */
  start_time: string
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * A weekly schedule for a location.
 * Contains shift entries and transitions between draft and published states.
 */
export interface Schedule {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Monday of the target week (ISO date string) */
  week_start: string
  /** "draft" or "published" */
  status: 'draft' | 'published'
  /** ISO-8601 timestamp of last publish; null while still a draft */
  published_at: string | null
  /** Foreign key to the User who published; null if never published */
  published_by: number | null
  /** Eagerly-loaded publisher relationship (optional) */
  publisher?: User
  /** Eagerly-loaded schedule entries (optional) */
  entries?: ScheduleEntry[]
  /** Count of entries (when loaded via withCount) */
  entries_count?: number
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * One staff member assigned to one shift on a specific date.
 */
export interface ScheduleEntry {
  /** Primary key */
  id: number
  /** Foreign key to the parent Schedule */
  schedule_id: number
  /** Foreign key to the assigned User */
  user_id: number
  /** Foreign key to the ShiftTemplate */
  shift_template_id: number
  /** The specific calendar day (ISO date string) */
  date: string
  /** The role for this shift: "server" or "bartender" */
  role: 'server' | 'bartender'
  /** Optional manager notes */
  notes: string | null
  /** Eagerly-loaded User relationship (optional) */
  user?: User
  /** Eagerly-loaded ShiftTemplate relationship (optional) */
  shift_template?: ShiftTemplate
  /** Eagerly-loaded Schedule relationship (optional) */
  schedule?: Schedule
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * A volunteer who offered to pick up a dropped shift.
 */
export interface ShiftDropVolunteer {
  id: number
  shift_drop_id: number
  user_id: number
  selected: boolean
  user?: User
  created_at: string
}

/**
 * A shift drop — staff gives up a shift, other staff volunteer to pick it up,
 * manager selects the volunteer who gets the shift.
 * Status flow: open → filled (or cancelled).
 */
export interface ShiftDrop {
  id: number
  schedule_entry_id: number
  requested_by: number
  reason: string | null
  status: 'open' | 'filled' | 'cancelled'
  filled_by: number | null
  filled_at: string | null
  schedule_entry?: ScheduleEntry
  requester?: User
  filler?: User
  volunteers?: ShiftDropVolunteer[]
  has_volunteered?: boolean
  created_at: string
  updated_at: string
}

/**
 * A staff member's request for time off on a date range.
 * Lifecycle: pending → approved/denied.
 */
export interface TimeOffRequest {
  /** Primary key */
  id: number
  /** Foreign key to the requesting User */
  user_id: number
  /** Foreign key to the Location */
  location_id: number
  /** First day of time off (ISO date string) */
  start_date: string
  /** Last day of time off (ISO date string) */
  end_date: string
  /** Optional reason */
  reason: string | null
  /** Current state */
  status: 'pending' | 'approved' | 'denied'
  /** Foreign key to the deciding manager */
  resolved_by: number | null
  /** ISO-8601 timestamp of when the decision was made */
  resolved_at: string | null
  /** Eagerly-loaded User relationship (optional) */
  user?: User
  /** Eagerly-loaded resolver (optional) */
  resolver?: User
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

// ─── Manager Log Types ──────────────────────────────────────────────────

/**
 * Weather data snapshot frozen at log creation time.
 * Contains current conditions and today's forecast summary.
 */
export interface WeatherSnapshot {
  /** Current weather conditions */
  current: {
    /** Temperature in Fahrenheit */
    temperature: number
    /** Feels-like temperature in Fahrenheit */
    feels_like: number
    /** Relative humidity percentage */
    humidity: number
    /** Wind speed in mph */
    wind_speed: number
    /** WMO weather interpretation code */
    weather_code: number
    /** Human-readable weather description */
    description: string
  }
  /** Today's forecast summary */
  today: {
    /** High temperature in Fahrenheit */
    high: number
    /** Low temperature in Fahrenheit */
    low: number
    /** WMO weather interpretation code */
    weather_code: number
    /** Human-readable weather description */
    description: string
  }
}

/**
 * A single event record within an events snapshot.
 */
export interface EventSnapshot {
  /** Primary key of the original event */
  id: number
  /** Short headline for the event */
  title: string
  /** Optional details about the event */
  description: string | null
  /** Optional "HH:MM" display time */
  event_time: string | null
  /** Name of the manager who created the event */
  created_by: string | null
}

/**
 * A single schedule entry within a schedule snapshot.
 */
export interface ScheduleSnapshot {
  /** Primary key of the original schedule entry */
  id: number
  /** Name of the assigned staff member */
  user_name: string | null
  /** The role for this shift: "server" or "bartender" */
  role: string
  /** Name of the shift template (e.g. "Dinner") */
  shift_name: string | null
  /** HH:MM:SS start time of the shift */
  start_time: string | null
}

/**
 * A daily operational log entry created by a manager.
 * Contains freeform notes and immutable snapshots of weather, events,
 * and scheduled staff frozen at creation time.
 */
export interface ManagerLog {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Foreign key (User.id) of the manager who created the log */
  created_by: number
  /** The date this log covers (ISO date string) */
  log_date: string
  /** Freeform manager notes */
  body: string
  /** Weather data frozen at creation time; null if unavailable */
  weather_snapshot: WeatherSnapshot | null
  /** Events data frozen at creation time */
  events_snapshot: EventSnapshot[] | null
  /** Schedule data frozen at creation time */
  schedule_snapshot: ScheduleSnapshot[] | null
  /** Eagerly-loaded creator (optional) */
  creator?: User
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

// ─── Message Board & Direct Messaging Types ────────────────────────────

/**
 * A post on the location-scoped message board.
 * Top-level posts have `parent_id = null`; replies reference the parent.
 * Supports one level of threading only (no nested replies).
 */
export interface BoardMessage {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** Foreign key to the author */
  user_id: number
  /** Foreign key to parent post (null for top-level posts) */
  parent_id: number | null
  /** The message body text */
  body: string
  /** Visibility: 'all' for everyone, 'managers' for admin/manager only */
  visibility: 'all' | 'managers'
  /** Whether this post is pinned to the top of the board */
  pinned: boolean
  /** Eagerly-loaded author (optional) */
  user?: User
  /** Eagerly-loaded replies (optional) */
  replies?: BoardMessage[]
  /** Count of replies (when loaded via withCount) */
  replies_count?: number
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * A private 1-on-1 direct message conversation between two staff members.
 * Includes participant info, the latest message preview, and unread count.
 */
export interface Conversation {
  /** Primary key */
  id: number
  /** Foreign key to the owning Location */
  location_id: number
  /** The two participants in this conversation */
  participants: User[]
  /** Most recent message for preview display; null if no messages yet */
  latest_message?: DirectMessage | null
  /** Number of unread messages from the other participant */
  unread_count: number
  /** ISO-8601 creation timestamp */
  created_at: string
  /** ISO-8601 last-update timestamp */
  updated_at: string
}

/**
 * A single direct message within a Conversation.
 * Messages are not editable once sent (standard chat behavior).
 */
export interface DirectMessage {
  /** Primary key */
  id: number
  /** Foreign key to the parent Conversation */
  conversation_id: number
  /** Foreign key to the sending User */
  sender_id: number
  /** The message body text */
  body: string
  /** Eagerly-loaded sender info (optional) */
  sender?: User
  /** ISO-8601 creation timestamp */
  created_at: string
}

/**
 * An in-app notification for managers/admins.
 * Mirrors the data payload from Laravel's database notification channel.
 */
export interface AppNotification {
  /** UUID primary key */
  id: string
  /** Notification subtype (e.g. "shift_drop_requested", "time_off_requested") */
  type: string
  /** Short headline */
  title: string
  /** Descriptive body text */
  body: string
  /** Frontend route to navigate to when clicked */
  link: string
  /** Primary key of the related source record */
  source_id: number
  /** ISO-8601 timestamp when the notification was read; null if unread */
  read_at: string | null
  /** ISO-8601 creation timestamp */
  created_at: string
}
