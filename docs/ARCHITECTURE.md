# PreShift — Architecture & Decisions

> Settled decisions. Do not revisit or change without being explicitly asked.

---

## High-Level Architecture

```
┌─────────────────┐       HTTP/JSON        ┌─────────────────┐
│                 │  ←───────────────────→  │                 │
│   /client       │                        │   /api          │
│   Vue 3 SPA     │                        │   Laravel 11    │
│   Pinia stores  │  ←── WebSocket ──────  │   Reverb        │
│   Tailwind CSS  │                        │   Sanctum auth  │
│                 │                        │   MySQL         │
└─────────────────┘                        └─────────────────┘
```

- The Vue SPA and Laravel API are **independent applications** in separate directories
- They communicate **only through the API** — no Inertia, no Blade
- Realtime updates flow through Reverb WebSockets on `private-location.{location_id}`

---

## Key Decisions

### 1. Hero Endpoint: `/api/preshift`
One call returns the full pre-shift payload (86'd items, specials, push items, announcements, acknowledgment status), filtered by the user's role and location. This is what makes the app feel instant on open.

### 2. Polymorphic Acknowledgments
The `acknowledgments` table uses `acknowledgable_type` and `acknowledgable_id` to track reads on any content type (announcements, specials, etc.) without separate tables per content type.

### 3. Optional Menu Item References
Managers can link to a `menu_item` record OR just type freeform text when 86'ing something, creating specials, or setting push items. Not every restaurant will maintain a full digital menu in the system.

### 4. Reverb Channels Per Location
All realtime events broadcast on `private-location.{location_id}`. Staff auto-subscribe to their location channel on login. This keeps data isolated between locations.

### 5. Shift Templates
Reusable shift type definitions (Lunch, Dinner, Brunch, etc.) with start/end times, defined per location. Schedules reference these templates rather than having times on every entry.

### 6. Shift Drop/Pickup Model (NOT Swaps)
There is no direct swap system. When staff can't work a shift:
1. Staff requests a drop (optional reason)
2. Manager approves or denies the drop
3. If approved, announcement broadcasts to all eligible same-role staff
4. Multiple staff can volunteer to pick it up
5. Manager sees all volunteers and selects one
6. Schedule entry is reassigned — both parties notified

### 7. "Today" Scoping Only
No AM/PM or named shift splits for content. Specials use date ranges, 86'd items are active until restored, announcements use expiration dates.

### 8. Separate /api and /client
Two independent applications. Never combine them. Never add Blade or Inertia.

---

## Role Hierarchy

```
admin → can do everything, across all locations
  └── manager → CRUD all content, scheduling, approve drops/time-off (scoped to location)
        └── server → view content, view schedule, request drops/time-off (scoped to location)
        └── bartender → same as server + bar-specific content visibility
```

- Every endpoint enforces role checks via middleware or Policies
- Every query scopes to the user's location (except admin)
- When in doubt, restrict access — easier to open up later

---

## Data Flow Examples

### Staff opens the app
```
1. Client loads → Vue Router checks auth (Sanctum token in localStorage)
2. GET /api/user → get role + location
3. GET /api/preshift → load everything for dashboard
4. Subscribe to Reverb channel: private-location.{id}
5. Dashboard renders with 86'd board, specials, announcements, push items
6. Reverb events update Pinia stores in real-time as managers make changes
```

### Manager 86's an item
```
1. Manager submits form → POST /api/eighty-sixed
2. Controller validates, creates record, returns via API Resource
3. ItemEightySixed event broadcasts on private-location.{id}
4. All connected staff clients receive event
5. Pinia preshift store adds item to eightySixed array
6. Every component watching that data re-renders instantly
```

### Staff drops a shift
```
1. Staff submits drop request → POST /api/shift-drops
2. Status: pending_approval
3. ShiftDropRequested event → manager sees notification
4. Manager approves → POST /api/shift-drops/{id}/approve
5. Status: approved
6. ShiftDropApproved event → all same-role staff at location see the open shift
7. Staff volunteer → POST /api/shift-drops/{id}/volunteer
8. ShiftDropVolunteer event → manager sees volunteer count update
9. Manager selects → POST /api/shift-drops/{id}/select/{userId}
10. Status: filled, schedule_entry.user_id updated
11. ShiftDropFilled event → both parties notified
```

---

## What Is NOT In v1

- PWA / service worker
- Inventory / stock tracking
- Kitchen/cook and barback roles
- Photo uploads for specials / new items
- Tip pool / tip-out reporting
- Recipe / build sheet viewer
- Training materials
- Multi-language support
- Analytics
