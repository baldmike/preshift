# Current Sprint — Feb 20-21, 2026

## Goals
Ship v1.0.0: complete backend architecture refactor, full test coverage, comprehensive documentation

## Tasks
- [x] Add model factories for all 16 models (PR #6)
- [x] Extract validation into Form Request classes (PR #7)
- [x] Add API Resource classes to all controllers (PR #8)
- [x] Add Policy classes for model-level authorization (PR #10)
- [x] Add docblocks to Form Requests, Resources, and Policies (PR #12)
- [x] Add missing backend tests for uncovered controller actions (PR #13)
- [x] Update project docs: CLAUDE.md, spec, workflow (PR #9, #11)
- [x] Tag and release v1.0.0

## Notes
- All 4 refactor branches merged cleanly with zero behavior changes
- Backend tests: 141 → 157 (16 new tests for previously untested actions)
- Frontend tests: 148 (unchanged, all passing)
- Policies supplement existing middleware — admin always passes, non-admin checks location ownership

---

# Previous Sprints

## Sprint 1 — Feb 19-20, 2026
- [x] Add superadmin privilege, config page, and change password
- [x] Add employee availability grid with time slot selection
- [x] Replace swap requests with shift drop/pickup flow
- [x] Add comprehensive test suite and doc comments
- [x] UI tweaks: copyright, availability collapse, danger zone, schedule seeder

## Sprint 0 — Feb 18-19, 2026
- [x] Project scaffolding (Laravel 11 + Vue 3 SPA)
- [x] Auth system (Sanctum token-based)
- [x] 86'd board CRUD with real-time broadcasting
- [x] Specials CRUD with quantity tracking
- [x] Push items CRUD with priority levels
- [x] Announcements with role targeting and acknowledgments
- [x] Scheduling system: shift templates, weekly schedules, time-off requests
- [x] Menu items and categories CRUD
- [x] User management (admin/manager)
- [x] Pre-shift hero endpoint
- [x] Dark theme redesign and mobile-first UI
