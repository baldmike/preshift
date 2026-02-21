# PreShift — API Endpoints

> All endpoints return JSON. All endpoints except `/api/login` require Sanctum bearer token. All data is scoped to the authenticated user's location unless the user is an admin.

---

## Auth (Sanctum)
```
POST   /api/login                    → { email, password } → token
POST   /api/logout                   → revoke token
GET    /api/user                     → current user + role + location
```

## Pre-Shift Dashboard
```
GET    /api/preshift                 → combined payload for current user:
                                       - active 86'd items
                                       - current specials (by date)
                                       - active push items
                                       - announcements (filtered by role)
                                       - acknowledgment status for each
                                       Scoped to user's location.
```

## 86'd Board
```
GET    /api/eighty-sixed             → active 86'd items (restored_at IS NULL)
POST   /api/eighty-sixed             → 86 an item (manager+)
PATCH  /api/eighty-sixed/{id}/restore → restore item (manager+)
```

## Specials
```
GET    /api/specials                 → current specials
POST   /api/specials                 → create (manager+)
PATCH  /api/specials/{id}            → update (manager+)
DELETE /api/specials/{id}            → delete (manager+)
```

## Push Items
```
GET    /api/push-items               → active push items
POST   /api/push-items               → create (manager+)
PATCH  /api/push-items/{id}          → update (manager+)
DELETE /api/push-items/{id}          → delete (manager+)
```

## Announcements
```
GET    /api/announcements            → announcements for user's role
POST   /api/announcements            → create (manager+)
PATCH  /api/announcements/{id}       → update (manager+)
DELETE /api/announcements/{id}       → delete (manager+)
```

## Acknowledgments
```
POST   /api/acknowledge              → { type, id } → mark as read
GET    /api/acknowledgments/status   → manager view: who has/hasn't read what
```

## Locations (admin)
```
GET    /api/locations                → list all locations
POST   /api/locations                → create location
PATCH  /api/locations/{id}           → update location
```

## Users (manager+)
```
GET    /api/users                    → list users at location
POST   /api/users                    → create user (manager+ at their location, admin anywhere)
PATCH  /api/users/{id}               → update user
DELETE /api/users/{id}               → deactivate user
```

---

## Scheduling

### Shift Templates
```
GET    /api/shift-templates              → list for location
POST   /api/shift-templates              → create (manager+)
PATCH  /api/shift-templates/{id}         → update (manager+)
DELETE /api/shift-templates/{id}         → delete (manager+)
```

### Schedules
```
GET    /api/schedules                    → list (current/upcoming weeks)
GET    /api/schedules/{id}               → full schedule with all entries
POST   /api/schedules                    → create new week (manager+)
PATCH  /api/schedules/{id}               → update (manager+)
POST   /api/schedules/{id}/publish       → publish schedule (manager+)
POST   /api/schedules/{id}/unpublish     → revert to draft (manager+)
GET    /api/my-shifts                    → staff: my upcoming shifts across weeks
```

### Schedule Entries
```
POST   /api/schedule-entries             → assign staff to a shift (manager+)
PATCH  /api/schedule-entries/{id}        → update entry (manager+)
DELETE /api/schedule-entries/{id}        → remove entry (manager+)
```

### Shift Drops
```
GET    /api/shift-drops                  → staff: see approved open drops for my role; manager: all for location
POST   /api/shift-drops                  → request to drop a shift (staff, own shifts only)
POST   /api/shift-drops/{id}/approve     → approve the drop, broadcasts to eligible staff (manager+)
POST   /api/shift-drops/{id}/deny        → deny the drop request (manager+)
POST   /api/shift-drops/{id}/volunteer   → volunteer to pick up (eligible staff, same role)
POST   /api/shift-drops/{id}/select/{userId} → manager selects a volunteer, reassigns shift (manager+)
POST   /api/shift-drops/{id}/cancel      → cancel own drop request (staff, only if still pending_approval)
```

### Time-Off Requests
```
GET    /api/time-off-requests            → staff sees own, manager sees all for location
POST   /api/time-off-requests            → submit request (staff)
POST   /api/time-off-requests/{id}/approve → approve (manager+)
POST   /api/time-off-requests/{id}/deny    → deny (manager+)
```
