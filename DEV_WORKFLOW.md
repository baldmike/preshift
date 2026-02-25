# PreShift — Development Workflow

> This document defines the development practices for the PreShift project. Follow these conventions for all work on this codebase.

---

## Git Branching Strategy

```
main              ← always deployable, production-ready
  ├── feature/scheduling
  ├── feature/shift-drops
  ├── fix/86-board-restore-bug
  └── chore/update-dependencies
```

### Branch Rules

- **Never commit directly to `main`.**
- All work happens in feature/fix/chore branches off `main`.
- PRs merge feature branches → `main` via squash and merge.

### Branch Naming

Use prefixes to categorize work:

| Prefix | Use |
|--------|-----|
| `feature/` | New functionality (e.g. `feature/shift-drops`) |
| `fix/` | Bug fixes (e.g. `fix/86-restore-not-clearing`) |
| `chore/` | Maintenance, deps, config (e.g. `chore/update-tailwind`) |
| `refactor/` | Code restructuring, no behavior change (e.g. `refactor/preshift-endpoint`) |

Keep names short, lowercase, hyphenated. Describe what, not how.

### Creating a Branch

```bash
git checkout main
git pull origin main
git checkout -b feature/your-feature-name
```

### When You're Done

```bash
git push origin feature/your-feature-name
# Open PR on GitHub: feature branch → main
# Review the diff, squash and merge
```

---

## Commit Practices

### Rules

1. **Commit small.** Each commit should do one logical thing.
2. **Commit often.** Whenever something works — even partially — commit it.
3. **Write clear messages.** One line, present tense, describes what changed.

### Good Commits

```
add shift_drops migration and model
create ShiftDropController with store and approve methods
wire up ShiftDropBoardView to GET /api/shift-drops
fix 86 board not updating on Reverb event
add role guard to schedule publish endpoint
style shift card component for mobile
```

### Bad Commits

```
stuff
WIP
fixed it
updates
worked on scheduling
```

### Commit Frequently At These Points

- Migration created and tested
- Model + relationships defined
- Controller method working (test with Postman/browser)
- Vue component rendering correctly
- API wired to frontend and working end-to-end
- Bug fixed and verified
- Before any risky refactor (safety checkpoint)

---

## Pull Requests

### Every feature/fix gets a PR — no exceptions.

Even as a solo dev, PRs force you to:
- Review your own diff as a whole before merging
- Write down what changed and why
- Create a searchable history of decisions
- Catch things you missed while coding

### PR Template

When opening a PR on GitHub, use this format in the description:

```markdown
## What this does
Brief description of the feature or fix.

## Changes
- Added migration for shift_drops table
- Created ShiftDrop model with relationships
- Added API endpoints: POST /api/shift-drops, POST /api/shift-drops/{id}/approve
- Created ShiftDropBoardView.vue

## How to test
1. Log in as staff
2. Navigate to My Schedule
3. Click "Drop Shift" on any upcoming shift
4. Log in as manager — should see pending drop request

## Notes
Anything tricky, known limitations, or follow-up needed.
```

### PR Review Process (Solo)

1. Open the PR on GitHub
2. Go to the "Files changed" tab
3. Read through every file diff — take 10 minutes minimum
4. Look for: leftover console.logs, hardcoded values, missing error handling, auth checks
5. If you spot something, fix it and push before merging
6. Merge via "Squash and merge" to keep `main` history clean

---

## Sprint Planning

### Cadence: 1-week sprints (Monday to Friday)

### Monday: Plan the Sprint

1. Open `SPRINT.md` (or GitHub Issues / GitHub Projects)
2. Pick 3-5 tasks for the week
3. Each task should be completable in 1-2 days max
4. If a task feels bigger, break it down into subtasks
5. Prioritize: what unblocks other work? What's closest to done?

### During the Week

- Work on one task at a time
- Commit frequently, push daily
- If something takes longer than expected, note it — don't silently let it bleed into next week

### Friday: Review the Sprint

- What got done?
- What didn't? Why?
- Move incomplete items to next sprint or re-scope them
- Tag a release if a milestone was hit

### Sprint File Format

Keep a `SPRINT.md` in the project root:

```markdown
# Current Sprint — Feb 20-27, 2026

## Goals
Ship scheduling system backend + basic frontend

## Tasks
- [x] Shift templates CRUD (backend)
- [x] Shift templates management view (frontend)
- [ ] Schedule builder — create/view weekly schedule
- [ ] Schedule publish/unpublish flow
- [ ] Shift drop request flow (backend)

## Notes
- Spent extra time on shift template time validation
- Need to revisit schedule_entries unique constraints

---

# Previous Sprints

## Sprint 0 — Feb 13-20, 2026
- [x] Project scaffolding (Laravel + Vue)
- [x] Auth system (Sanctum)
- [x] 86'd board CRUD
- [x] Specials CRUD
- [x] Push items CRUD
- [x] Announcements with acknowledgments
```

---

## Testing

### Philosophy

You don't need 100% coverage. Test the things that would hurt most if they broke.

### What to Test

| Priority | What | Why |
|----------|------|-----|
| **High** | Auth (login, logout, role guards) | Broken auth = broken app |
| **High** | 86'd board (create, restore) | Most-used feature during live service |
| **High** | Shift drop flow (request, approve, volunteer, select) | Complex state machine, easy to break |
| **Medium** | Specials/push items CRUD | Standard CRUD, less likely to break |
| **Medium** | Schedule publish/unpublish | State change with side effects |
| **Low** | Acknowledgments | Simple polymorphic, unlikely to break |

### Running Tests

```bash
# Run all tests
php artisan test

# Run a specific test file
php artisan test --filter=ShiftDropTest

# Run before every PR merge
php artisan test && echo "✅ Safe to merge"
```

### Creating Tests

```bash
# Feature test (HTTP/API tests — use these most)
php artisan make:test ShiftDropTest

# Unit test (isolated logic)
php artisan make:test ShiftDropStatusTest --unit
```

### Test Structure

Each test file covers one feature area. Each test method covers one scenario:

```php
// tests/Feature/ShiftDropTest.php

public function test_staff_can_request_to_drop_their_shift()
public function test_staff_cannot_drop_someone_elses_shift()
public function test_manager_can_approve_drop_request()
public function test_approved_drop_broadcasts_to_location()
public function test_volunteer_must_be_same_role()
public function test_manager_can_select_volunteer()
```

---

## Release Tagging

Tag releases when you hit milestones. Use semantic versioning:

```bash
git tag -a v0.1.0 -m "Core pre-shift features: 86 board, specials, push items, announcements"
git push origin v0.1.0
```

| Version | Milestone |
|---------|-----------|
| `v0.1.0` | Core pre-shift features working (86, specials, push, announcements) |
| `v0.2.0` | Scheduling system complete (templates, builder, publish) |
| `v0.3.0` | Shift drops + time-off requests |
| `v0.4.0` | Manager staff view toggle + acknowledgment tracking |
| `v1.0.0` | Full v1 feature-complete, ready for real-world use |

---

## Environment & Config

### .env

- Never commit `.env` — it's in `.gitignore`
- Always keep `.env.example` updated with every new variable you add
- When adding a new env variable, update `.env.example` in the same commit

### CLAUDE.md

Keep a `CLAUDE.md` in the project root. It is read automatically by AI coding assistants. Include:

- Project overview and current state
- Tech stack and conventions
- Architecture notes and constraints
- Things that should NOT be done (e.g. "do not modify existing migrations")

Update it as the project evolves.

---

## Daily Development Flow — Cheat Sheet

```
Morning:
  1. git checkout main && git pull
  2. Check SPRINT.md — what's today's task?
  3. git checkout -b feature/todays-task

While coding:
  4. Commit after each working piece
  5. Push at least once during the day

When feature is done:
  6. Run tests: php artisan test
  7. Push branch: git push origin feature/todays-task
  8. Open PR on GitHub → main
  9. Review your own diff (10 min minimum)
  10. Squash and merge

End of week:
  11. Update SPRINT.md
  12. If milestone hit: tag a release on main
```

### Run Daily Sprints

Each day, treat your work session as a mini-sprint:

1. **Pick 1-3 focused tasks** from the current sprint backlog
2. **Time-box each task** — if it's taking longer than expected, commit what you have and reassess
3. **Ship something every day** — even a small fix or incremental progress counts
4. **End-of-day checkpoint** — push all work, note where you left off, update SPRINT.md if needed

---

## Tools

| Tool | Purpose |
|------|---------|
| **GitHub** | Repos, PRs, Issues, Projects |
| **Chrome DevTools** | Device emulation, network debugging |
| **Postman or Thunder Client** | API testing during development |
| **AI Assistant** | Scaffolding, implementation, debugging |
| **Laravel Telescope** | Local debugging (requests, queries, events) — install in dev only |
| **Vue DevTools** | Browser extension for inspecting Vue components + Pinia state |
