# Backend Test Coverage

**Status:** 100% controller coverage — every controller, model, and middleware has test coverage.

**Last verified:** 2026-02-25
**Total:** 36 test files, 387 tests

## Feature Tests (32 files)

| Test File | Covers | Tests |
|---|---|---|
| `AcknowledgmentBroadcastTest` | AcknowledgmentController (broadcast events) | 2 |
| `AcknowledgmentSummaryTest` | AcknowledgmentController (summary endpoint) | 5 |
| `AnnouncementTest` | AnnouncementController | 7 |
| `AuthTest` | AuthController (login, logout, profile, password) | 8 |
| `BoardMessageTest` | BoardMessageController | 11 |
| `ConfigAndPasswordTest` | ConfigController, SetupController (reset), CheckSuperAdmin middleware | 15 |
| `ConversationTest` | ConversationController, DirectMessageController | 11 |
| `DirectMessageTest` | DirectMessageController (auth, validation, broadcast) | 7 |
| `EightySixedTest` | EightySixedController (update, role guard, cross-location) | 3 |
| `EventTest` | EventController | 7 |
| `ExampleTest` | Application smoke test | 1 |
| `LocationUserTest` | Location-user pivot, SwitchLocationController, needs_setup | 9 |
| `ManagerLogTest` | ManagerLogController (with snapshots) | 9 |
| `MenuAndLocationTest` | CategoryController, MenuItemController, LocationController, UserController | 19 |
| `MissingCoverageTest` | ScheduleController, ScheduleEntryController, ShiftDropController, SpecialController, ShiftTemplateController (cross-location guards) | 16 |
| `MultiLocationTest` | Multi-location data isolation across all content types | 12 |
| `NotificationTest` | NotificationController, notification dispatch for drops/time-off | 10 |
| `PreShiftTest` | PreShiftController (hero endpoint) | 4 |
| `ProfilePhotoTest` | AuthController (photo upload/delete) | 8 |
| `ProfileTest` | AuthController (profile update) | 5 |
| `PushItemTest` | PushItemController | 7 |
| `SchedulingTest` | ScheduleController, ScheduleEntryController, ShiftTemplateController, ShiftDropController, TimeOffRequestController | 33 |
| `SetupTest` | SetupController (first-time establishment setup) | 5 |
| `ShiftDropRoleTest` | ShiftDropController (role-filtered drops, multi-role support) | 11 |
| `SmokeTest` | End-to-end happy paths across all major features | 23 |
| `SpecialTest` | SpecialController (CRUD + decrement) | 7 |
| `SwitchLocationTest` | SwitchLocationController | 4 |
| `TimeOffAutomationTest` | TimeOffRequestController (advance notice), ScheduleEntryController (conflict check) | 8 |
| `TonightsScheduleTest` | ScheduleController (published schedule with entries) | 3 |
| `WeatherTest` | WeatherController (caching, coordinates, auth) | 6 |
| `OrganizationTest` | Organization layer (org-scoped locations, switch within/outside org, setup, access pending) | 10 |
| `FormRequestValidationTest` | All Form Requests (validation rules, 422 responses) | 26 |
| `PolicyAuthorizationTest` | All Policies (cross-location isolation, same-location pass) | 28 |

## Unit Tests (3 files)

| Test File | Covers | Tests |
|---|---|---|
| `ExampleTest` | Basic assertion | 1 |
| `MiddlewareTest` | CheckRole, EnsureLocationAccess | 8 |
| `ModelTest` | User, Location, Schedule, ScheduleEntry, ShiftDrop, ShiftDropVolunteer, EightySixed, Announcement, Special, TimeOffRequest (relationships, scopes, casts) | 38 |

## Controller → Test Mapping (26 controllers)

| Controller | Test File(s) |
|---|---|
| `AcknowledgmentController` | AcknowledgmentBroadcastTest, AcknowledgmentSummaryTest, PreShiftTest, SmokeTest |
| `AnnouncementController` | AnnouncementTest, PreShiftTest, SmokeTest, MultiLocationTest |
| `AuthController` | AuthTest, ProfileTest, ProfilePhotoTest, SmokeTest, LocationUserTest |
| `BoardMessageController` | BoardMessageTest |
| `CategoryController` | MenuAndLocationTest |
| `ConfigController` | ConfigAndPasswordTest, TimeOffAutomationTest |
| `ConversationController` | ConversationTest |
| `DirectMessageController` | DirectMessageTest, ConversationTest |
| `EightySixedController` | EightySixedTest, PreShiftTest, SmokeTest, MultiLocationTest |
| `EventController` | EventTest |
| `LocationController` | MenuAndLocationTest |
| `ManagerLogController` | ManagerLogTest |
| `MenuItemController` | MenuAndLocationTest, MultiLocationTest |
| `NotificationController` | NotificationTest |
| `PreShiftController` | PreShiftTest, SmokeTest, MultiLocationTest |
| `PushItemController` | PushItemTest, PreShiftTest, SmokeTest, MultiLocationTest |
| `ScheduleController` | SchedulingTest, MissingCoverageTest, TonightsScheduleTest |
| `ScheduleEntryController` | SchedulingTest, MissingCoverageTest, TimeOffAutomationTest |
| `SetupController` | SetupTest, ConfigAndPasswordTest |
| `ShiftDropController` | SchedulingTest, ShiftDropRoleTest, MissingCoverageTest |
| `ShiftTemplateController` | SchedulingTest, MissingCoverageTest |
| `SpecialController` | SpecialTest, PreShiftTest, SmokeTest, MissingCoverageTest, MultiLocationTest |
| `SwitchLocationController` | SwitchLocationTest, MultiLocationTest |
| `TimeOffRequestController` | SchedulingTest, TimeOffAutomationTest |
| `UserController` | MenuAndLocationTest, SmokeTest |
| `WeatherController` | WeatherTest |

## Model → Test Mapping (21 models)

| Model | Unit Tests | Feature Tests |
|---|---|---|
| `Acknowledgment` | — | AcknowledgmentBroadcastTest, AcknowledgmentSummaryTest, SmokeTest |
| `Announcement` | ModelTest (scopes, casts, relationships) | AnnouncementTest, PreShiftTest, SmokeTest |
| `BoardMessage` | — | BoardMessageTest |
| `Category` | — | MenuAndLocationTest |
| `Conversation` | — | ConversationTest |
| `DirectMessage` | — | DirectMessageTest, ConversationTest |
| `EightySixed` | ModelTest (scopes, relationships) | EightySixedTest, SmokeTest |
| `Event` | — | EventTest |
| `Location` | ModelTest (relationships) | MenuAndLocationTest, MultiLocationTest |
| `ManagerLog` | — | ManagerLogTest |
| `MenuItem` | — | MenuAndLocationTest |
| `Organization` | — | OrganizationTest |
| `PushItem` | — | PushItemTest, SmokeTest |
| `Schedule` | ModelTest (relationships, casts) | SchedulingTest, TonightsScheduleTest |
| `ScheduleEntry` | ModelTest (relationships, casts) | SchedulingTest |
| `Setting` | — | ConfigAndPasswordTest |
| `ShiftDrop` | ModelTest (relationships, casts) | SchedulingTest, ShiftDropRoleTest |
| `ShiftDropVolunteer` | ModelTest (relationships, casts) | SchedulingTest, ShiftDropRoleTest |
| `ShiftTemplate` | — | SchedulingTest |
| `Special` | ModelTest (scopes, relationships, casts) | SpecialTest, SmokeTest |
| `TimeOffRequest` | ModelTest (scopes, relationships, casts) | SchedulingTest, TimeOffAutomationTest |
| `User` | ModelTest (roles, relationships, casts) | AuthTest, SmokeTest, MultiLocationTest |

## Middleware → Test Mapping (3 middleware)

| Middleware | Test File(s) |
|---|---|
| `CheckRole` | MiddlewareTest (4 tests), plus implicitly in all role-guarded Feature tests |
| `CheckSuperAdmin` | ConfigAndPasswordTest (2 tests) |
| `EnsureLocationAccess` | MiddlewareTest (4 tests), plus implicitly in all location-scoped Feature tests |

## Rules

- Every new controller **must** have corresponding Feature tests (happy path + auth/role checks)
- Test files use descriptive names matching their primary controller: `ShiftDropController` → `ShiftDropRoleTest`, `SchedulingTest`
- Test files must include a file-level docblock listing what the tests verify
- Each `test_` method must have a docblock describing what it checks and why
- Use `RefreshDatabase` trait in all Feature tests
- Use factories and manual seeding — never depend on the DatabaseSeeder
- Test role restrictions: verify staff can't access manager routes
- Test cross-location isolation: verify location A can't see location B data
