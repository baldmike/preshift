# PreShift

## Project Overview

PreShift is a digital pre-shift meeting replacement for restaurants and bars. Managers post daily operational updates (86'd items, specials, push items, announcements) and staff check in before their shift to see everything they need. Includes a full scheduling system with shift drops and time-off requests.

**Live at:** https://preshift86.com

## Tech Stack

- **Frontend:** Vue 3 SPA (Vite + Vue Router + Pinia + Tailwind CSS)
- **Backend:** Laravel 11 API
- **Database:** MySQL
- **Auth:** Laravel Sanctum (token-based SPA authentication)
- **Realtime:** Laravel Reverb (WebSockets)
- **Deployment:** DigitalOcean (Ubuntu 24.04, Nginx, Supervisor)

## Project Structure

```
/api        ← Laravel 11 API
/client     ← Vue 3 SPA
```

## Roles

| Role | Access |
|------|--------|
| admin | Full access across all locations |
| manager | CRUD all content, scheduling, approve drops/time-off — scoped to their location |
| server | View pre-shift content, view schedule, request drops/time-off — scoped to their location |
| bartender | Same as server with bar-specific content visibility |

## Key Architecture Decisions

- `/api/preshift` is the hero endpoint — returns everything staff needs in one call, filtered by role + location
- Polymorphic acknowledgments — one table tracks reads on any content type
- Menu items are optional references — managers can 86 a menu item OR type freeform text
- Reverb channels are scoped per location: `private-location.{id}`
- Scheduling uses shift templates (reusable shift types defined per location)
- Shift changes use a drop/pickup model, not a swap model — staff drops a shift, manager approves, eligible staff volunteer, manager picks one
- "Today" scoping only — no AM/PM shift splits

## Development Rules

- **DO NOT EVER COMMIT DIRECTLY TO MAIN** — always use a branch + PR, no exceptions
- Always work in feature branches off `main`: `feature/`, `fix/`, `chore/`, `refactor/`
- There is NO `develop` branch — `main` is the only base branch
- Never include "Co-Authored-By" lines, Claude Code references, or any AI attribution in commit messages, PR descriptions, or code comments
- Write small, focused commits with clear present-tense messages
- Do not modify existing migrations — always create new migrations for schema changes
- Do not remove or refactor existing working code unless explicitly asked
- Follow existing code patterns, naming conventions, and file organization in the codebase
- All API endpoints must check role authorization (use middleware or policy)
- All data must be scoped to the user's location (never leak cross-location data)
- When creating new features, also create a corresponding Feature test in `/api/tests/Feature/`
- Run `php artisan test` before considering any feature complete

## API Conventions

- RESTful resource routes: `GET /api/resource`, `POST /api/resource`, `PATCH /api/resource/{id}`, `DELETE /api/resource/{id}`
- Action routes use POST: `POST /api/resource/{id}/approve`, `POST /api/resource/{id}/publish`
- All responses return JSON
- Auth via Sanctum token — every request must include bearer token except `/api/login`
- Validation in Form Request classes, not controllers
- Use API Resources for response formatting

## Frontend Conventions

- Vue 3 Composition API with `<script setup>` — no Options API
- Pinia for state management
- Composables for reusable logic (`useAuth`, `usePreshift`, `useSchedule`, etc.)
- Tailwind CSS for styling — no custom CSS files unless absolutely necessary
- Axios for API calls, configured with Sanctum token in a shared `api.js` service
- Vue Router with role-based navigation guards
- `main.css` must keep `-webkit-text-size-adjust: 100%` and `text-size-adjust: 100%` on `html, body` — prevents mobile WebKit zoom inflation

## Reverb Conventions

- All events broadcast on `private-location.{location_id}`
- Frontend listens via composable (`useReverb`) and updates Pinia stores on event
- Events should include the full resource payload so the frontend can update state without re-fetching

## Testing Conventions

- Feature tests for all API endpoints (happy path + auth/role checks at minimum)
- Test files mirror controller names: `ShiftDropController` → `ShiftDropTest`
- Use Laravel factories for test data
- Test role restrictions: verify staff can't access manager routes, location A can't see location B data
- Frontend: 100% file coverage — every component, view, composable, and store has a test file (see `docs/FRONTEND_TEST_COVERAGE.md`)
- When adding a field to the `User` type (or any shared type), update **all** test fixtures that construct that type

## Commenting Conventions

- Every entry must be commented — PHP gets docblocks, JS/TS gets block comments
- Vue components: include a block comment at the top of `<script setup>` describing the component, its purpose, and its props
- Test files: include a file-level block comment listing what the tests verify, and a block comment above each `it()` describing what the test checks and why
- Composables, stores, and utility files: include a block comment at the top describing the module's purpose and exports

## Release Workflow

When the user says "branch, tag, pr, deploy it" (or any subset), follow this exact flow:

1. **Branch** — Create a branch off `main` using the appropriate prefix (`feature/`, `fix/`, `chore/`, `refactor/`)
2. **Commit** — Stage only the changed files and commit with a clear present-tense message (no AI attribution)
3. **Tag** — Bump the minor version: check `git tag --sort=-v:refname | head -1` and increment (e.g. `v1.25.0` → `v1.26.0`)
4. **Push** — Push the branch with `-u` and the tag
5. **PR** — Create a PR with `gh pr create` using a short title and a summary + test plan body
6. **STOP** — Do NOT merge. The user will review and merge the PR themselves. Wait for "clean up" or "deploy it".
7. **Deploy** (only when user says "deploy it") — Run `ssh preshift 'cd /var/www/preshift && bash deploy/deploy.sh'`

If the user says "reseed production" after deploy:
```bash
ssh preshift 'cd /var/www/preshift/api && php artisan migrate:fresh --seed --force'
ssh preshift 'sudo supervisorctl restart preshift-reverb'
```

If the user says "clean up" without other context: ensure everything is pushed, run tests, verify all PRs are merged, switch to main, pull, and prune/delete all merged local branches.

## Deployment

Production server is accessible via `ssh preshift`. Deploy script lives at `deploy/deploy.sh`.

```bash
# Standard deploy (pull, install, migrate, build, restart services):
ssh preshift 'cd /var/www/preshift && bash deploy/deploy.sh'

# Full reseed (wipes all data — use only when seeder changes need deploying):
ssh preshift 'cd /var/www/preshift/api && php artisan migrate:fresh --seed --force'

# Restart Reverb after reseed (cache table gets dropped):
ssh preshift 'sudo supervisorctl restart preshift-reverb'
```

## Do NOT

- Do not install packages without being asked
- Do not modify `.env` — only update `.env.example`
- Do not change the database schema by editing existing migrations
- Do not add PWA/service worker functionality
- Do not build inventory/stock tracking features (deferred to v2)
- Do not add kitchen/cook or barback roles (deferred to v2)
- Do not use Options API in Vue components
- Do not remove `text-size-adjust` properties from `main.css` — they fix a mobile viewport zoom bug
- Do not merge PRs — only the user merges PRs, never Claude
