# PreShift

> **Read this entire file before writing any code. This is your operating manual. Follow it exactly.**

---

## Project Overview

PreShift is a digital pre-shift meeting replacement for restaurants and bars. Managers post daily operational updates (86'd items, specials, push items, announcements) and staff check in before their shift to see everything they need — tailored to their role. Includes a full scheduling system with shift drops and time-off requests.

This is a real product intended for real restaurant operations. Every decision should reflect how a busy restaurant actually works — speed, clarity, reliability.

---

## Tech Stack

| Layer | Technology | Notes |
|-------|-----------|-------|
| Frontend | Vue 3 SPA | Vite build, Composition API only |
| Routing | Vue Router | Role-based navigation guards |
| State | Pinia | One store per domain (auth, preshift, schedule, location) |
| Styling | Tailwind CSS | No custom CSS files unless absolutely necessary |
| Backend | Laravel 11 | API-only, no Blade views |
| Auth | Laravel Sanctum | Token-based SPA authentication |
| Realtime | Laravel Reverb | WebSocket events per location channel |
| Database | MySQL | Migrations only, no raw SQL in app code |
| Deployment | Laravel Cloud | Starter plan |

---

## Project Structure

```
/api              ← Laravel 11 API (do NOT put client code here)
/client           ← Vue 3 SPA (do NOT put API code here)
CLAUDE.md         ← This file (you are reading it)
PRESHIFT_SPEC.md  ← Authoritative feature spec (read this for feature details)
DEV_WORKFLOW.md   ← Human dev process reference (not for you)
SPRINT.md         ← Current sprint tasks (not for you)
```

**The spec (`PRESHIFT_SPEC.md`) is the source of truth for features.** If something in the codebase contradicts the spec, the spec wins. If you are unsure about a feature's behavior, read the spec before guessing.

---

## Roles & Permissions

| Role | Access Level |
|------|-------------|
| admin | Full access across all locations. Can manage locations and all users. |
| manager | CRUD all content, build schedules, approve shift drops and time-off. Scoped to their location. |
| server | View pre-shift content, view schedule, request shift drops and time-off. Scoped to their location. |
| bartender | Same as server, plus bar-specific content visibility. Scoped to their location. |

### Permission Rules

- **Every API endpoint must enforce role checks.** Use middleware or Laravel Policies — never rely on frontend hiding buttons as the only protection.
- **Every query must scope to the user's location.** A server at Location A must never see Location B's data. This applies to all models: 86'd items, specials, announcements, schedules, shift drops, everything.
- Admin is the exception — admin can access any location.
- When in doubt about whether a role should access something, default to restricting it. It's easier to open up access than to close a leak.

---

## Key Architecture Decisions

These are settled decisions. Do not revisit or change them without being explicitly asked.

1. **`/api/preshift` is the hero endpoint** — one call returns the full pre-shift payload (86'd items, specials, push items, announcements, acknowledgment status), filtered by the user's role and location. This is what makes the app feel instant.

2. **Polymorphic acknowledgments** — the `acknowledgments` table uses `acknowledgable_type` and `acknowledgable_id` to track reads on any content type (announcements, specials, etc.) without separate tables.

3. **Menu items are optional references** — managers can link to a `menu_item` record OR just type freeform text when 86'ing something, creating specials, or setting push items. Not every restaurant will maintain a full digital menu.

4. **Reverb channels per location** — all realtime events broadcast on `private-location.{location_id}`. Staff auto-subscribe to their location channel on login.

5. **Shift templates** — reusable shift type definitions (Lunch, Dinner, Brunch, etc.) with start/end times, defined per location. Schedules reference these templates.

6. **Shift drop/pickup model (not swaps)** — when staff can't work a shift: they request a drop → manager approves → eligible same-role staff are notified → volunteers sign up → manager picks one. There is no direct swap system.

7. **"Today" scoping only** — no AM/PM or named shift splits for content. Specials use date ranges, 86'd items are active until restored, announcements use expiration dates.

8. **Separate API and client** — the Vue SPA and Laravel API are independent applications in separate directories (`/api` and `/client`). They communicate only through the API. No Inertia, no Blade.

---

## Code Style & Conventions

### General

- Be consistent with what already exists in the codebase. If there's an established pattern, follow it.
- **Conventions in this file apply to NEW code you write.** Do not retroactively refactor existing working code to match these conventions unless explicitly asked. If you see old code that doesn't follow a convention, leave it alone — we'll clean it up in dedicated refactor branches.
- Keep files focused — one controller per resource, one composable per domain, one store per domain.
- Name things clearly. `ShiftDropController` not `SDController`. `useSchedule` not `useS`.
- No magic strings — use enums, constants, or config values.
- No commented-out code in committed files. Delete it or don't commit it.

### Backend (Laravel)

- **Controllers** — thin controllers. Validate with Form Request classes, authorize with Policies, format responses with API Resources. Controllers should mostly just orchestrate.
- **Models** — define all relationships, scopes, and casts. Use `$fillable` not `$guarded`.
- **Migrations** — one migration per change. Never modify an existing migration file. Always create a new one. Name them descriptively: `create_shift_drops_table`, `add_notes_to_schedule_entries`.
- **Form Requests** — all validation lives here, not in controllers. One Form Request per action if rules differ (e.g. `StoreShiftDropRequest`, `ApproveShiftDropRequest`).
- **API Resources** — all JSON responses go through Resource classes. Never return raw models from controllers.
- **Policies** — use for authorization logic. Register in `AuthServiceProvider`. Check in controllers with `$this->authorize()`.
- **Events** — all Reverb broadcasts use dedicated Event classes in `app/Events/`. Events implement `ShouldBroadcast` and return the full resource payload.
- **Routes** — API routes in `routes/api.php`. Group by feature area. Apply middleware at the group level.
- **Factories & Seeders** — every model gets a factory. Seeders for demo/dev data only.

### Frontend (Vue 3)

- **Composition API only** — all components use `<script setup>`. No Options API. No mixins.
- **Components** — single-file components (`.vue`). Template → script → style order.
- **Props** — always define with types and defaults. Use `defineProps` with TypeScript-style validation where possible.
- **Composables** — reusable logic lives in `src/composables/`. Named with `use` prefix. Return reactive refs and methods.
- **Stores (Pinia)** — one store per domain. Use `defineStore` with setup syntax. Stores handle API calls and cache state.
- **Router** — role-based guards in `beforeEach`. Redirect unauthorized users, don't just hide routes.
- **API calls** — all go through the shared `src/services/api.js` axios instance. Never import axios directly in components.
- **Error handling** — API errors should be caught and displayed to the user. Never silently fail. Use toast notifications or inline error messages.
- **Loading states** — every API call should have a loading indicator. No blank screens while data loads.

### Styling (Tailwind)

- Tailwind utility classes only. No custom CSS unless truly unavoidable.
- Mobile-first responsive design — this app will primarily be used on phones.
- Consistent spacing, color, and typography — follow what's already established.
- 86'd items should be visually urgent (red/warning). Announcements should reflect priority (urgent = red, important = yellow, normal = neutral).

---

## Realtime (Reverb) Conventions

- All events broadcast on `private-location.{location_id}`
- Frontend subscribes via `useReverb` composable on login, unsubscribes on logout
- Events include the FULL resource payload so the frontend can update Pinia stores directly without re-fetching from the API
- Event naming matches the action: `ItemEightySixed`, `ShiftDropApproved`, `SchedulePublished`, etc.
- When an event arrives, the corresponding Pinia store updates its state immediately — the UI should feel instant

---

## Testing Conventions

- **Feature tests** (HTTP/API) for every controller action — these are the priority
- **Happy path + at least one auth/role check** per endpoint at minimum
- Test file naming mirrors controllers: `ShiftDropController` → `tests/Feature/ShiftDropTest.php`
- Use factories for all test data — never hardcode IDs or create records manually
- **Always test location scoping** — verify user at Location A gets 403 or empty results for Location B data
- **Always test role restrictions** — verify staff can't hit manager endpoints
- Run `php artisan test` before considering any feature complete
- If a test fails, fix it before moving on — don't leave broken tests

---

## Error Handling

### Backend
- Validation errors return 422 with field-level error messages
- Unauthorized returns 403 with a clear message
- Unauthenticated returns 401
- Not found returns 404
- Server errors return 500 — log the exception, return a generic message to the client
- Never expose stack traces, SQL, or internal paths in API responses

### Frontend
- Catch all API errors in composables/stores — never let them bubble uncaught
- Display user-friendly error messages — "Something went wrong" is acceptable, a stack trace is not
- Handle 401 globally — redirect to login
- Handle 403 — show "you don't have permission" message
- Handle network errors — show "connection lost" or similar

---

## Git & Workflow Rules

- **Never commit directly to `main`**
- **Before starting ANY new feature, fix, or change:** always run `git checkout main && git pull origin main && git checkout -b <branch-name>` to create a fresh branch. Do this EVERY time — no exceptions. Do not reuse old branches.
- All work happens in feature branches off `main`
- Branch naming: `feature/`, `fix/`, `chore/`, `refactor/` + short hyphenated description
- When done, open a PR: feature branch → `main`, review the diff, squash and merge
- Write small, focused commits with clear present-tense messages
- One logical change per commit
- Do not modify existing migration files — create new migrations
- Do not remove or refactor working code unless explicitly asked
- When you create a new file, follow the existing directory structure and naming patterns

---

## DO NOT — Hard Boundaries

These are things you must never do unless I explicitly override them:

- ❌ Do not install new packages without being asked
- ❌ Do not modify `.env` — only update `.env.example` when adding new variables
- ❌ Do not modify existing migration files — always create new migrations
- ❌ Do not add PWA or service worker functionality (deferred)
- ❌ Do not build inventory or stock tracking features (deferred to v2)
- ❌ Do not add kitchen/cook or barback roles (deferred to v2)
- ❌ Do not use Vue Options API or mixins
- ❌ Do not use Blade templates or Inertia
- ❌ Do not add features not in the spec without being asked
- ❌ Do not "improve" the architecture by changing settled decisions listed above
- ❌ Do not combine API and client into one directory
- ❌ Do not use raw SQL queries in application code — use Eloquent
- ❌ Do not return raw model data from controllers — use API Resources
- ❌ Do not skip authorization checks on any endpoint
- ❌ Do not skip location scoping on any query
- ❌ Do not leave `console.log` or `dd()` / `dump()` in committed code
- ❌ Do not create overly generic or abstract code "for future flexibility" — build what's needed now

---

## When In Doubt

1. Read `PRESHIFT_SPEC.md` — it has the full feature spec with database schemas, endpoints, events, and component structure
2. Follow existing patterns in the codebase
3. If neither helps, ask me — do not guess and build something that might need to be torn down
