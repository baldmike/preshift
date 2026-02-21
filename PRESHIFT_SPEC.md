# PreShift — App Specification v1

## Overview

PreShift is a digital pre-shift meeting replacement for restaurants and bars. It serves as a centralized, real-time information hub where managers post operational updates and staff check in before (or during) their shift to see everything they need — tailored to their role.

---

## Tech Stack

- **Frontend:** Vue 3 SPA (Vite + Vue Router + Pinia + Tailwind CSS)
- **Backend:** Laravel 11 API
- **Database:** MySQL
- **Auth:** Laravel Sanctum (token-based SPA authentication)
- **Realtime:** Laravel Reverb (WebSockets)
- **PWA:** No — standard web app for v1. Can add PWA layer later if needed.

---

## Roles (v1)

| Role | Permissions |
|------|------------|
| **admin** | Full access. Manage locations, users, all content. |
| **manager** | Create/edit/delete specials, 86'd items, push items, announcements. View acknowledgment status. Scoped to their location. |
| **server** | View all pre-shift content for their location. Acknowledge items. |
| **bartender** | Same as server, plus bar-specific content visibility. |

---

## Database Schema

### locations
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | auto-increment |
| name | varchar(255) | e.g. "The Roosevelt - Downtown" |
| address | varchar(255) | nullable |
| timezone | varchar(50) | default 'America/New_York' |
| created_at | timestamp | |
| updated_at | timestamp | |

### users
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | |
| email | varchar(255) | unique |
| password | varchar(255) | hashed |
| role | enum | admin, manager, server, bartender |
| location_id | FK → locations | nullable for admin |
| created_at | timestamp | |
| updated_at | timestamp | |

### categories
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| location_id | FK → locations | |
| name | varchar(255) | e.g. "Appetizers", "Cocktails" |
| sort_order | integer | default 0 |
| created_at | timestamp | |
| updated_at | timestamp | |

### menu_items
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| location_id | FK → locations | |
| category_id | FK → categories | nullable |
| name | varchar(255) | |
| description | text | nullable |
| price | decimal(8,2) | nullable |
| type | enum | food, drink, both |
| is_new | boolean | default false |
| is_active | boolean | default true |
| allergens | json | nullable, e.g. ["gluten", "dairy"] |
| created_at | timestamp | |
| updated_at | timestamp | |

### eighty_sixed
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| location_id | FK → locations | |
| menu_item_id | FK → menu_items | nullable (allows freeform) |
| item_name | varchar(255) | always populated (denormalized or freeform) |
| reason | varchar(255) | nullable, e.g. "supplier out" |
| eighty_sixed_by | FK → users | who 86'd it |
| restored_at | timestamp | nullable — null means currently 86'd |
| created_at | timestamp | |
| updated_at | timestamp | |

### specials
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| location_id | FK → locations | |
| menu_item_id | FK → menu_items | nullable |
| title | varchar(255) | |
| description | text | nullable |
| type | enum | daily, weekly, monthly, limited_time |
| starts_at | date | |
| ends_at | date | nullable |
| is_active | boolean | default true |
| created_by | FK → users | |
| created_at | timestamp | |
| updated_at | timestamp | |

### push_items
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| location_id | FK → locations | |
| menu_item_id | FK → menu_items | nullable |
| title | varchar(255) | |
| description | text | nullable, e.g. "We're overstocked on mahi" |
| reason | varchar(255) | nullable |
| priority | enum | low, medium, high |
| is_active | boolean | default true |
| created_by | FK → users | |
| created_at | timestamp | |
| updated_at | timestamp | |

### announcements
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| location_id | FK → locations | |
| title | varchar(255) | |
| body | text | |
| priority | enum | normal, important, urgent |
| target_roles | json | e.g. ["server", "bartender"] — null means all |
| posted_by | FK → users | |
| expires_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### acknowledgments (polymorphic)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| user_id | FK → users | |
| acknowledgable_type | varchar(255) | e.g. "App\Models\Announcement" |
| acknowledgable_id | bigint unsigned | |
| acknowledged_at | timestamp | |

**Unique constraint:** user_id + acknowledgable_type + acknowledgable_id

---

## API Endpoints

### Auth (Sanctum)
```
POST   /api/login                    → { email, password } → token
POST   /api/logout                   → revoke token
GET    /api/user                     → current user + role + location
```

### Pre-Shift Dashboard (the hero endpoint)
```
GET    /api/preshift                 → combined payload for current user:
                                       - active 86'd items
                                       - current specials (by date)
                                       - active push items
                                       - announcements (filtered by role)
                                       - acknowledgment status for each
                                       Scoped to user's location.
```

### 86'd Board
```
GET    /api/eighty-sixed             → active 86'd items (restored_at IS NULL)
POST   /api/eighty-sixed             → 86 an item (manager+)
PATCH  /api/eighty-sixed/{id}/restore → restore item (manager+)
```

### Specials
```
GET    /api/specials                 → current specials
POST   /api/specials                 → create (manager+)
PATCH  /api/specials/{id}            → update (manager+)
DELETE /api/specials/{id}            → delete (manager+)
```

### Push Items
```
GET    /api/push-items               → active push items
POST   /api/push-items               → create (manager+)
PATCH  /api/push-items/{id}          → update (manager+)
DELETE /api/push-items/{id}          → delete (manager+)
```

### Announcements
```
GET    /api/announcements            → announcements for user's role
POST   /api/announcements            → create (manager+)
PATCH  /api/announcements/{id}       → update (manager+)
DELETE /api/announcements/{id}       → delete (manager+)
```

### Acknowledgments
```
POST   /api/acknowledge              → { type, id } → mark as read
GET    /api/acknowledgments/status   → manager view: who has/hasn't read what
```

### Locations (admin)
```
GET    /api/locations                → list all locations
POST   /api/locations                → create location
PATCH  /api/locations/{id}           → update location
```

### Users (manager+)
```
GET    /api/users                    → list users at location
POST   /api/users                    → create user (manager+ at their location, admin anywhere)
PATCH  /api/users/{id}               → update user
DELETE /api/users/{id}               → deactivate user
```

---

## Reverb Events (Realtime)

All events broadcast on private channel: `location.{location_id}`

| Event | Trigger | Payload |
|-------|---------|---------|
| `ItemEightySixed` | Item is 86'd | eighty_sixed record |
| `ItemRestored` | 86 is lifted | eighty_sixed record |
| `SpecialCreated` | New special added | special record |
| `SpecialUpdated` | Special modified | special record |
| `SpecialDeleted` | Special removed | special id |
| `PushItemCreated` | New push item | push_item record |
| `PushItemUpdated` | Push item modified | push_item record |
| `PushItemDeleted` | Push item removed | push_item id |
| `AnnouncementPosted` | New announcement | announcement record |
| `AnnouncementUpdated` | Announcement modified | announcement record |
| `AnnouncementDeleted` | Announcement removed | announcement id |

Staff Vue app listens on their location channel and updates Pinia stores in real-time.

---

## Vue Frontend Structure

```
src/
├── views/
│   ├── auth/
│   │   └── LoginView.vue
│   ├── staff/
│   │   ├── DashboardView.vue          ← main pre-shift view
│   │   ├── EightySixedBoard.vue       ← dedicated 86'd view
│   │   └── SpecialsView.vue           ← detailed specials view
│   └── admin/
│       ├── ManageDashboard.vue        ← manager home
│       ├── ManageEightySixed.vue
│       ├── ManageSpecials.vue
│       ├── ManagePushItems.vue
│       ├── ManageAnnouncements.vue
│       ├── ManageMenuItems.vue
│       ├── ManageUsers.vue
│       ├── ManageLocations.vue        ← admin only
│       └── AcknowledgmentTracker.vue
├── components/
│   ├── EightySixedCard.vue
│   ├── SpecialCard.vue
│   ├── PushItemCard.vue
│   ├── AnnouncementCard.vue
│   ├── AcknowledgeButton.vue
│   ├── RealtimeIndicator.vue          ← connection status dot
│   └── ui/                            ← shared UI components
├── composables/
│   ├── useAuth.js
│   ├── usePreshift.js
│   ├── useReverb.js
│   └── useAcknowledgments.js
├── stores/                            ← Pinia
│   ├── auth.js
│   ├── preshift.js
│   └── location.js
├── router/
│   └── index.js                       ← role-based route guards
├── services/
│   └── api.js                         ← axios instance w/ Sanctum config
└── App.vue
```

---

## Role-Based Views

### Staff Dashboard (Server / Bartender)
The main DashboardView shows — in priority order:

1. **🚫 86'd Board** — prominent, red/warning styling, always at top
2. **📢 Announcements** — with priority indicators (urgent = red banner), acknowledge button
3. **⭐ Today's Specials** — descriptions, pricing, any tasting notes
4. **🎯 Push Items** — what to push tonight with reason/priority
5. **🆕 New Items** — any menu items flagged as new

Each section shows unread badge count. Acknowledge button on announcements and specials.

### Manager Dashboard
Everything staff sees, plus:

- Quick-action buttons to 86/restore items, post announcements, add specials
- Acknowledgment tracker — see which staff have/haven't read today's pre-shift
- CRUD management for all content types
- User management for their location

### Admin
Everything manager sees, plus:

- Location management (create, edit locations)
- User management across all locations
- Can switch between location views

---

## Deployment

**Platform:** Laravel Cloud (Starter plan)
- Zero base monthly fee, pay-per-use with auto-hibernation
- Native support for MySQL, Reverb, and Sanctum — entire stack in one place
- Push-to-deploy via Git
- Auto SSL, custom domains included
- Vue 3 client: deploy to Vercel or Netlify (free tier) or serve from Laravel Cloud

**Alternative options if needed:**
- DigitalOcean/Hetzner VPS ($4-6/month) for flat predictable pricing
- Railway ($5-10/month) for easy usage-based deploy
- Laravel Forge + DigitalOcean ($18/month) for managed server experience

---

## Key Design Decisions

1. **`/api/preshift` is the hero endpoint** — one call loads everything staff needs, filtered by role + location. Makes the app feel instant on open.

2. **Polymorphic acknowledgments** — one table tracks reads on any content type. Flexible and clean.

3. **Menu items are optional** — managers can 86 a menu item OR just type "blue cheese" freeform. Same for specials/push items. Keeps it flexible for shops that don't maintain a full digital menu.

4. **Reverb channels per location** — `private-location.{id}` keeps data isolated. Staff auto-subscribe on login.

5. **"Today" scoping only for v1** — no AM/PM shift splits. Specials use date ranges, 86'd items are active until restored, announcements use expiration dates.

6. **Inventory/low stock tracking deferred** — not in v1.

---

## Future Considerations (v2+)

- PWA support (installable, offline caching)
- Inventory / low stock tracking
- Kitchen/cook and barback roles
- Photo uploads for specials / new items
- Tip pool / tip-out reporting
- Recipe / build sheet viewer
- Training materials section
- Multi-language support
- Analytics (most-pushed items, acknowledgment rates)

---
---

# SPEC CHANGELOG — NEW ADDITIONS BELOW

> **IMPORTANT FOR IMPLEMENTATION:** Everything below this line is NEW and needs to be added to the existing codebase. Create new migrations, models, controllers, routes, events, and Vue components as specified. Do not modify or rebuild existing features — only add the new scheduling system alongside what already exists.
>
> **BREAKING CHANGE:** The shift swap system (swap_requests table, SwapRequest model, swap API endpoints, swap Vue components) has been **removed and replaced** by the Shift Drop / Pickup system. If any swap-related code exists, remove it entirely and implement the shift_drops / shift_drop_volunteers system below instead.

---

## NEW FEATURE: Scheduling System

### Overview

Full weekly schedule builder. Managers create and publish schedules, staff view their shifts, request shift drops (with manager-approved pickup flow), and submit time-off requests. This is a core v1 feature.

### Shift Templates

Managers define reusable shift types per location (created once, used repeatedly):
- e.g. **Lunch** 10:30am–3:00pm, **Dinner** 4:00pm–11:00pm, **Brunch** 9:00am–3:00pm, **Double** 10:30am–11:00pm
- Custom shift types as needed per location

### Weekly Schedule Flow

1. Manager creates a schedule for a week (identified by week_start date)
2. Schedule starts in `draft` status
3. Manager adds schedule entries — assigning staff to shift templates on specific dates with a role
4. Manager can see approved time-off while building (to avoid conflicts)
5. Manager publishes the schedule — staff are notified via Reverb and can see their shifts
6. Manager can unpublish to make edits, then republish

### Shift Drop / Pickup Flow (replaces swap system)

1. Staff member requests to drop one of their scheduled shifts (optional reason)
2. Manager receives the drop request and approves or denies it
3. If approved, an announcement goes out via Reverb to ALL available staff of the same role at that location
4. Any eligible staff can click "Pick it up" — this registers them as a volunteer
5. Multiple people can volunteer — manager sees all volunteers queued up
6. Manager selects which volunteer gets the shift
7. Schedule entry is reassigned to the selected volunteer — both parties notified

### Time-Off Request Flow

1. Staff submits a request for a date or date range, with optional reason
2. Manager approves or denies
3. Approved time-off is visible in the schedule builder so managers don't double-book

---

### NEW DATABASE TABLES

**shift_templates**
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| location_id | FK → locations | |
| name | varchar(255) | "Lunch", "Dinner", etc. |
| start_time | time | e.g. 10:30:00 |
| end_time | time | e.g. 15:00:00 |
| created_at | timestamp | |
| updated_at | timestamp | |

**schedules**
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| location_id | FK → locations | |
| week_start | date | Monday of the week |
| status | enum | draft, published |
| published_at | timestamp | nullable |
| published_by | FK → users | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Unique constraint:** location_id + week_start

**schedule_entries**
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| schedule_id | FK → schedules | |
| user_id | FK → users | |
| shift_template_id | FK → shift_templates | |
| date | date | specific day |
| role | enum | server, bartender |
| notes | varchar(255) | nullable, e.g. "training", "cut first" |
| created_at | timestamp | |
| updated_at | timestamp | |

**shift_drops**
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| schedule_entry_id | FK → schedule_entries | the shift being dropped |
| requested_by | FK → users | staff dropping the shift |
| reason | varchar(255) | nullable, optional reason |
| status | enum | pending_approval, approved, denied, filled, cancelled |
| approved_by | FK → users | nullable, manager who approved the drop |
| approved_at | timestamp | nullable |
| filled_by | FK → users | nullable, volunteer selected by manager |
| filled_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

**Status flow:** pending_approval → approved (broadcasts to staff) → filled (manager picks volunteer) OR pending_approval → denied

**shift_drop_volunteers**
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| shift_drop_id | FK → shift_drops | |
| user_id | FK → users | staff volunteering to pick up |
| selected | boolean | default false, true when manager picks them |
| created_at | timestamp | |

**Unique constraint:** shift_drop_id + user_id (one volunteer entry per person per drop)

**time_off_requests**
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| user_id | FK → users | |
| location_id | FK → locations | |
| start_date | date | |
| end_date | date | |
| reason | varchar(255) | nullable |
| status | enum | pending, approved, denied |
| resolved_by | FK → users | nullable |
| resolved_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

---

### NEW API ENDPOINTS

**Shift Templates**
```
GET    /api/shift-templates              → list for location
POST   /api/shift-templates              → create (manager+)
PATCH  /api/shift-templates/{id}         → update (manager+)
DELETE /api/shift-templates/{id}         → delete (manager+)
```

**Schedules**
```
GET    /api/schedules                    → list (current/upcoming weeks)
GET    /api/schedules/{id}               → full schedule with all entries
POST   /api/schedules                    → create new week (manager+)
PATCH  /api/schedules/{id}               → update (manager+)
POST   /api/schedules/{id}/publish       → publish schedule (manager+)
POST   /api/schedules/{id}/unpublish     → revert to draft (manager+)
GET    /api/my-shifts                    → staff: my upcoming shifts across weeks
```

**Schedule Entries**
```
POST   /api/schedule-entries             → assign staff to a shift (manager+)
PATCH  /api/schedule-entries/{id}        → update entry (manager+)
DELETE /api/schedule-entries/{id}        → remove entry (manager+)
```

**Shift Drops**
```
GET    /api/shift-drops                  → staff: see approved open drops for my role; manager: all for location
POST   /api/shift-drops                  → request to drop a shift (staff, own shifts only)
POST   /api/shift-drops/{id}/approve     → approve the drop, broadcasts to eligible staff (manager+)
POST   /api/shift-drops/{id}/deny        → deny the drop request (manager+)
POST   /api/shift-drops/{id}/volunteer   → volunteer to pick up (eligible staff, same role)
POST   /api/shift-drops/{id}/select/{userId} → manager selects a volunteer, reassigns shift (manager+)
POST   /api/shift-drops/{id}/cancel      → cancel own drop request (staff, only if still pending_approval)
```

**Time-Off Requests**
```
GET    /api/time-off-requests            → staff sees own, manager sees all for location
POST   /api/time-off-requests            → submit request (staff)
POST   /api/time-off-requests/{id}/approve → approve (manager+)
POST   /api/time-off-requests/{id}/deny    → deny (manager+)
```

---

### NEW REVERB EVENTS

| Event | Trigger | Channel |
|-------|---------|---------|
| `SchedulePublished` | Manager publishes a schedule | `private-location.{id}` |
| `ShiftDropRequested` | Staff requests to drop a shift (manager sees) | `private-location.{id}` |
| `ShiftDropApproved` | Manager approves drop, broadcasts to eligible same-role staff | `private-location.{id}` |
| `ShiftDropDenied` | Manager denies drop request (requester notified) | `private-location.{id}` |
| `ShiftDropVolunteer` | Someone volunteers to pick up (manager sees) | `private-location.{id}` |
| `ShiftDropFilled` | Manager selects volunteer, shift reassigned (both parties notified) | `private-location.{id}` |
| `TimeOffResolved` | Manager approves/denies time-off | `private-location.{id}` |

---

### NEW VUE COMPONENTS & VIEWS

Add to existing client structure:

```
src/views/staff/
├── MyScheduleView.vue               ← staff: my upcoming shifts
├── ShiftDropBoardView.vue           ← staff: open shifts available to pick up
└── TimeOffRequestView.vue           ← staff: submit time-off

src/views/admin/
├── ScheduleBuilderView.vue          ← manager: weekly grid, drag/assign staff
├── ManageShiftDrops.vue             ← manager: approve drops, see volunteers, assign
└── ManageTimeOff.vue                ← manager: approve/deny time-off, see calendar

src/components/
├── ShiftCard.vue
├── ScheduleGrid.vue                 ← weekly grid component
├── ShiftDropCard.vue                ← shows drop details, volunteer count, pick-up button
├── VolunteerList.vue                ← manager: list of volunteers for a drop, select button
└── TimeOffBadge.vue

src/composables/
└── useSchedule.js

src/stores/
└── schedule.js                      ← Pinia store for schedule state
```

---

### UPDATES TO EXISTING VIEWS

**Staff DashboardView.vue** — Add a "My Shifts" summary widget showing next upcoming shift and link to full schedule.

**Manager ManageDashboard.vue** — Add quick links to schedule builder, pending shift drop requests count, and pending time-off requests count.

**Role-Based Views update:**
- Staff sees: 📅 My next shift + schedule link (add to dashboard priority list)
- Manager sees: Schedule builder access, pending requests badges

---

### REMOVED FROM FUTURE CONSIDERATIONS

The following item is now part of v1 and should be removed from the "Future Considerations" list:
- ~~Shift-based scoping (AM/PM, named shifts)~~ → Implemented via shift_templates
