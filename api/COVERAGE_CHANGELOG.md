# Coverage Documentation Changelog

---

## 2026-02-24 — Multi-Location Audit & Full Reconciliation

### What Changed

Both `COMMENT_COVERAGE.md` and `TEST_COVERAGE.md` were audited against the actual codebase and corrected. Many entries in the previous docs referenced planned file names that were never created or were later consolidated under different names.

### COMMENT_COVERAGE.md Changes

#### Models: 22 → 21
- Removed `Notification.php` (does not exist on disk — notifications use Laravel's built-in system)

#### Controllers: 26/26 (corrected entries)
- Removed phantom entries: `DailyLogController`, `ProfileController`, `SettingController`, `MyShiftController`
- Added actual files: `ConfigController`, `ManagerLogController`, `SetupController`, `SwitchLocationController`

#### Form Requests: 31/31 (corrected entries)
- Removed 10 phantom entries that never existed on disk:
  - `UpdateAnnouncementRequest`, `UpdateCategoryRequest`, `UpdateEightySixedRequest`, `UpdateEventRequest`, `UpdateLocationRequest`, `UpdateMenuItemRequest`, `UpdatePushItemRequest`, `UpdateShiftTemplateRequest`, `UpdateSpecialRequest`, `StoreSettingRequest`
- Added 10 actual files:
  - `ChangePasswordRequest`, `StoreAcknowledgmentRequest`, `UpdateMyAvailabilityRequest`, `UpdateProfileRequest`, `UpdateScheduleEntryRequest`, `UpdateSettingsRequest`, `StoreConversationRequest`, `InitialSetupRequest`, `SetupLocationRequest`, `SwitchLocationRequest`

#### API Resources: 21/21 (corrected entries)
- Removed `NotificationResource.php` (does not exist)
- Added `ShiftDropVolunteerResource.php` (exists on disk)

#### Events: 26/26 (corrected entries)
- Removed phantom names: `EightySixedCreated`, `EightySixedDeleted`, `EightySixedUpdated`, `ScheduleUpdated`, `ShiftDropUpdated`, `TimeOffRequestCreated`, `TimeOffRequestUpdated`
- Added actual names: `ItemEightySixed`, `ItemRestored`, `ItemEightySixedUpdated`, `ShiftDropFilled`, `ShiftDropVolunteered`, `SpecialLowStock`, `TimeOffResolved`

#### Migrations: 52 → 34
- Previous count was inflated. Actual migration files on disk: 34.

#### Tests: 31 → 30 (corrected entries)
- Previous list had 16 phantom entries referencing planned test files that were consolidated into other files
- Replaced entire section with actual 28 feature test files + 2 unit test files
- Added `MultiLocationTest.php` (new, 12 tests, 63 assertions)

#### Total files: 233 → 213

### TEST_COVERAGE.md Changes

- Updated test suite totals: 303 tests / 900 assertions → **315 tests / 963 assertions**
- Added new **Multi-Location Data Isolation** section with all 12 `MultiLocationTest.php` test methods
- Added `MultiLocationTest.php` cross-location data isolation entries to the middleware section
- Added multi-location login flow and data isolation entries to the Multi-Location User Support section
- Updated summary: 32 → 33 test files, 89 → 101 endpoint coverage entries
