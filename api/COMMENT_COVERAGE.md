# API Comment Coverage

> Last updated: 2026-02-24
> Files: 213 | Documented: 213 | Coverage: 100%

---

## Models (21/21 -- 100%)

| File | Documented |
|------|-----------|
| Acknowledgment.php | YES |
| Announcement.php | YES |
| BoardMessage.php | YES |
| Category.php | YES |
| Conversation.php | YES |
| DirectMessage.php | YES |
| EightySixed.php | YES |
| Event.php | YES |
| Location.php | YES |
| ManagerLog.php | YES |
| MenuItem.php | YES |
| PushItem.php | YES |
| Schedule.php | YES |
| ScheduleEntry.php | YES |
| Setting.php | YES |
| ShiftDrop.php | YES |
| ShiftDropVolunteer.php | YES |
| ShiftTemplate.php | YES |
| Special.php | YES |
| TimeOffRequest.php | YES |
| User.php | YES |

All models have class-level docblocks with property documentation, relationship docblocks, and scope docblocks with types.

---

## Controllers (26/26 -- 100%)

| File | Documented |
|------|-----------|
| AcknowledgmentController.php | YES |
| AnnouncementController.php | YES |
| AuthController.php | YES |
| BoardMessageController.php | YES |
| CategoryController.php | YES |
| ConfigController.php | YES |
| ConversationController.php | YES |
| DirectMessageController.php | YES |
| EightySixedController.php | YES |
| EventController.php | YES |
| LocationController.php | YES |
| ManagerLogController.php | YES |
| MenuItemController.php | YES |
| NotificationController.php | YES |
| PreShiftController.php | YES |
| PushItemController.php | YES |
| ScheduleController.php | YES |
| ScheduleEntryController.php | YES |
| SetupController.php | YES |
| ShiftDropController.php | YES |
| ShiftTemplateController.php | YES |
| SpecialController.php | YES |
| SwitchLocationController.php | YES |
| TimeOffRequestController.php | YES |
| UserController.php | YES |
| WeatherController.php | YES |

All controllers have class-level and method-level docblocks.

---

## Middleware (3/3 -- 100%)

| File | Documented |
|------|-----------|
| CheckRole.php | YES |
| EnsureLocationAccess.php | YES |
| CheckSuperAdmin.php | YES |

All middleware have class-level and method-level docblocks explaining authorization logic.

---

## Form Requests (31/31 -- 100%)

| File | Documented |
|------|-----------|
| ChangePasswordRequest.php | YES |
| InitialSetupRequest.php | YES |
| LoginRequest.php | YES |
| SetupLocationRequest.php | YES |
| StoreAcknowledgmentRequest.php | YES |
| StoreAnnouncementRequest.php | YES |
| StoreBoardMessageRequest.php | YES |
| StoreCategoryRequest.php | YES |
| StoreConversationRequest.php | YES |
| StoreDirectMessageRequest.php | YES |
| StoreEightySixedRequest.php | YES |
| StoreEventRequest.php | YES |
| StoreLocationRequest.php | YES |
| StoreManagerLogRequest.php | YES |
| StoreMenuItemRequest.php | YES |
| StorePushItemRequest.php | YES |
| StoreScheduleEntryRequest.php | YES |
| StoreScheduleRequest.php | YES |
| StoreShiftDropRequest.php | YES |
| StoreShiftTemplateRequest.php | YES |
| StoreSpecialRequest.php | YES |
| StoreTimeOffRequestRequest.php | YES |
| StoreUserRequest.php | YES |
| SwitchLocationRequest.php | YES |
| UpdateBoardMessageRequest.php | YES |
| UpdateManagerLogRequest.php | YES |
| UpdateMyAvailabilityRequest.php | YES |
| UpdateProfileRequest.php | YES |
| UpdateScheduleEntryRequest.php | YES |
| UpdateSettingsRequest.php | YES |
| UpdateUserRequest.php | YES |

All form requests have class-level docblocks documenting purpose and validation rules.

---

## API Resources (21/21 -- 100%)

| File | Documented |
|------|-----------|
| AcknowledgmentResource.php | YES |
| AnnouncementResource.php | YES |
| BoardMessageResource.php | YES |
| CategoryResource.php | YES |
| ConversationResource.php | YES |
| DirectMessageResource.php | YES |
| EightySixedResource.php | YES |
| EventResource.php | YES |
| LocationResource.php | YES |
| ManagerLogResource.php | YES |
| MenuItemResource.php | YES |
| PushItemResource.php | YES |
| ScheduleEntryResource.php | YES |
| ScheduleResource.php | YES |
| SettingResource.php | YES |
| ShiftDropResource.php | YES |
| ShiftDropVolunteerResource.php | YES |
| ShiftTemplateResource.php | YES |
| SpecialResource.php | YES |
| TimeOffRequestResource.php | YES |
| UserResource.php | YES |

All resources have class-level docblocks explaining model transformation and serialized fields.

---

## Policies (16/16 -- 100%)

| File | Documented |
|------|-----------|
| AnnouncementPolicy.php | YES |
| BoardMessagePolicy.php | YES |
| CategoryPolicy.php | YES |
| ConversationPolicy.php | YES |
| EightySixedPolicy.php | YES |
| EventPolicy.php | YES |
| LocationPolicy.php | YES |
| ManagerLogPolicy.php | YES |
| MenuItemPolicy.php | YES |
| PushItemPolicy.php | YES |
| SchedulePolicy.php | YES |
| ShiftDropPolicy.php | YES |
| ShiftTemplatePolicy.php | YES |
| SpecialPolicy.php | YES |
| TimeOffRequestPolicy.php | YES |
| UserPolicy.php | YES |

All policies have class-level and method-level docblocks for each authorization rule.

---

## Events (26/26 -- 100%)

| File | Documented |
|------|-----------|
| AcknowledgmentRecorded.php | YES |
| AnnouncementDeleted.php | YES |
| AnnouncementPosted.php | YES |
| AnnouncementUpdated.php | YES |
| BoardMessageDeleted.php | YES |
| BoardMessagePosted.php | YES |
| BoardMessageUpdated.php | YES |
| DirectMessageSent.php | YES |
| EventCreated.php | YES |
| EventDeleted.php | YES |
| EventUpdated.php | YES |
| ItemEightySixed.php | YES |
| ItemEightySixedUpdated.php | YES |
| ItemRestored.php | YES |
| PushItemCreated.php | YES |
| PushItemDeleted.php | YES |
| PushItemUpdated.php | YES |
| SchedulePublished.php | YES |
| ShiftDropFilled.php | YES |
| ShiftDropRequested.php | YES |
| ShiftDropVolunteered.php | YES |
| SpecialCreated.php | YES |
| SpecialDeleted.php | YES |
| SpecialLowStock.php | YES |
| SpecialUpdated.php | YES |
| TimeOffResolved.php | YES |

All events have comprehensive docblocks explaining dispatch trigger, broadcast channel, and payload.

---

## Migrations (34/34 -- 100%)

All 34 migration files have class-level docblocks explaining table purpose, key columns, and design decisions.

---

## Seeders (1/1 -- 100%)

| File | Documented |
|------|-----------|
| DatabaseSeeder.php | YES |

Comprehensive docblock plus inline section comments describing seeded data.

---

## Routes (4/4 -- 100%)

| File | Documented |
|------|-----------|
| api.php | YES |
| channels.php | YES |
| console.php | YES |
| web.php | YES |

All route files have file-level docblocks. `api.php` documents the 3-tier route organization.

---

## Tests (30/30 -- 100%)

All 30 test files have file-level docblocks listing test scenarios AND per-test method docblocks.

### Feature Tests (28)

| File | Documented |
|------|-----------|
| AcknowledgmentBroadcastTest.php | YES |
| AcknowledgmentSummaryTest.php | YES |
| AnnouncementTest.php | YES |
| AuthTest.php | YES |
| BoardMessageTest.php | YES |
| ConfigAndPasswordTest.php | YES |
| ConversationTest.php | YES |
| DirectMessageTest.php | YES |
| EightySixedTest.php | YES |
| EventTest.php | YES |
| LocationUserTest.php | YES |
| ManagerLogTest.php | YES |
| MenuAndLocationTest.php | YES |
| MissingCoverageTest.php | YES |
| MultiLocationTest.php | YES |
| NotificationTest.php | YES |
| PreShiftTest.php | YES |
| ProfileTest.php | YES |
| PushItemTest.php | YES |
| SchedulingTest.php | YES |
| SetupTest.php | YES |
| ShiftDropRoleTest.php | YES |
| SmokeTest.php | YES |
| SpecialTest.php | YES |
| SwitchLocationTest.php | YES |
| TimeOffAutomationTest.php | YES |
| TonightsScheduleTest.php | YES |
| WeatherTest.php | YES |

### Unit Tests (2)

| File | Documented |
|------|-----------|
| MiddlewareTest.php | YES |
| ModelTest.php | YES |

---

## Summary

| Category | Files | Documented | Coverage |
|----------|-------|------------|----------|
| Models | 21 | 21 | 100% |
| Controllers | 26 | 26 | 100% |
| Middleware | 3 | 3 | 100% |
| Form Requests | 31 | 31 | 100% |
| API Resources | 21 | 21 | 100% |
| Policies | 16 | 16 | 100% |
| Events | 26 | 26 | 100% |
| Migrations | 34 | 34 | 100% |
| Seeders | 1 | 1 | 100% |
| Routes | 4 | 4 | 100% |
| Tests | 30 | 30 | 100% |
| **Total** | **213** | **213** | **100%** |
