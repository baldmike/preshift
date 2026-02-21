# PreShift

> **Read this entire file before writing any code. This is your operating manual.**

## What Is This

PreShift is a digital pre-shift meeting replacement for restaurants and bars. Vue 3 SPA + Laravel 11 API + MySQL + Sanctum + Reverb. Mobile-first web app.

## Project Structure

```
/api              ← Laravel 11 API
/client           ← Vue 3 SPA
/docs             ← Reference documentation (read as needed)
CLAUDE.md         ← This file (rules and conventions)
PRESHIFT_SPEC.md  ← Full feature spec (read for feature details)
```

## Reference Docs

Read these when working on related areas — don't memorize them, just look them up:

- **Database schema:** `docs/SCHEMA.md`
- **API endpoints:** `docs/API.md`
- **Reverb events:** `docs/EVENTS.md`
- **Architecture & decisions:** `docs/ARCHITECTURE.md`
- **Full feature spec:** `PRESHIFT_SPEC.md`

---

## Git Rules

- **Never commit directly to `main`**
- **Before starting ANY work:** `git checkout main && git pull origin main && git checkout -b <branch-name>`. Every time. No exceptions. No reusing old branches.
- **Do not merge any branch.** Create a PR and I will review and merge it myself.
- Branch naming: `feature/`, `fix/`, `chore/`, `refactor/` + short hyphenated description
- Small, focused commits. Clear present-tense messages. One logical change per commit.

---

## Code Rules

### General
- **Conventions apply to NEW code only.** Do not retroactively refactor existing code unless explicitly asked.
- Follow existing patterns in the codebase. Be consistent.
- **Verbose comments and docblocks on everything.** Every class, method, property, and non-trivial logic block. Write for someone reading this code for the first time.
- No magic strings — use enums, constants, or config values.
- No commented-out code in commits.

### Laravel (/api)
- Thin controllers — validate with Form Requests, authorize with Policies, respond with API Resources
- Models: `$fillable` not `$guarded`. Define all relationships, scopes, casts.
- Never modify existing migrations — always create new ones
- API Resources for all JSON responses — never return raw models
- Events implement `ShouldBroadcast` with full resource payload
- Routes in `routes/api.php`, grouped by feature, middleware at group level
- Every model gets a factory

### Vue (/client)
- Composition API with `<script setup>` only — no Options API, no mixins
- Pinia stores: one per domain, setup syntax, stores handle API calls
- Composables in `src/composables/` with `use` prefix
- All API calls through `src/services/api.js` — never import axios directly
- Role-based route guards in `beforeEach`
- Loading states on every API call. Error handling on every API call. No silent failures.

### Styling
- Tailwind utility classes only. Mobile-first.
- 86'd items = red/warning. Urgent announcements = red. Important = yellow.

---

## Security Rules

- **Every endpoint enforces role checks** via middleware or Policies
- **Every query scopes to user's location** — Location A never sees Location B
- Admin is the only exception to location scoping
- When in doubt, restrict access

---

## Testing Rules

- **Every change gets tests. No exceptions.**
- Feature tests for every controller action (happy path + auth/role check minimum)
- Test location scoping: user at Location A gets 403 or empty for Location B
- Test role restrictions: staff can't hit manager endpoints
- Use factories — never hardcode IDs
- `php artisan test` must pass before any PR
- Test names read like English: `test_manager_can_approve_shift_drop`

---

## DO NOT

- ❌ Install packages without being asked
- ❌ Modify `.env` — only update `.env.example`
- ❌ Modify existing migrations
- ❌ Add PWA / service workers
- ❌ Build inventory, stock tracking, kitchen/cook roles, barback roles (v2)
- ❌ Use Options API, mixins, Blade, or Inertia
- ❌ Add features not in the spec
- ❌ Change settled architecture decisions (see `docs/ARCHITECTURE.md`)
- ❌ Combine `/api` and `/client`
- ❌ Return raw models from controllers
- ❌ Skip auth checks or location scoping
- ❌ Leave `console.log`, `dd()`, or `dump()` in committed code
- ❌ Over-engineer for "future flexibility" — build what's needed now

---

## When In Doubt

1. Check the relevant doc in `/docs`
2. Check `PRESHIFT_SPEC.md`
3. Follow existing patterns in the codebase
4. If none of that helps — **ask me, don't guess**
