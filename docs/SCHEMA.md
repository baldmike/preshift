# PreShift — Database Schema

> All tables use bigint unsigned auto-increment primary keys and timestamps.

---

## Core Tables

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

---

## Pre-Shift Content Tables

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

## Scheduling Tables

### shift_templates
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| location_id | FK → locations | |
| name | varchar(255) | "Lunch", "Dinner", etc. |
| start_time | time | e.g. 10:30:00 |
| created_at | timestamp | |
| updated_at | timestamp | |

### schedules
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

### schedule_entries
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

### shift_drops
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

### shift_drop_volunteers
| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| shift_drop_id | FK → shift_drops | |
| user_id | FK → users | staff volunteering to pick up |
| selected | boolean | default false, true when manager picks them |
| created_at | timestamp | |

**Unique constraint:** shift_drop_id + user_id (one volunteer entry per person per drop)

### time_off_requests
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
