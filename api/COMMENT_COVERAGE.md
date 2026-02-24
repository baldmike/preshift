# API Comment Coverage

> Last updated: 2026-02-24
> Files: 233 | Documented: 233 | Coverage: 100%

---

## Models (22/22 -- 100%)

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
| Notification.php | YES |

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
| ConversationController.php | YES |
| DailyLogController.php | YES |
| DirectMessageController.php | YES |
| EightySixedController.php | YES |
| EventController.php | YES |
| LocationController.php | YES |
| MenuItemController.php | YES |
| NotificationController.php | YES |
| PreShiftController.php | YES |
| ProfileController.php | YES |
| PushItemController.php | YES |
| ScheduleController.php | YES |
| ScheduleEntryController.php | YES |
| SettingController.php | YES |
| ShiftDropController.php | YES |
| ShiftTemplateController.php | YES |
| SpecialController.php | YES |
| TimeOffRequestController.php | YES |
| UserController.php | YES |
| WeatherController.php | YES |
| MyShiftController.php | YES |

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
| LoginRequest.php | YES |
| StoreAnnouncementRequest.php | YES |
| UpdateAnnouncementRequest.php | YES |
| StoreBoardMessageRequest.php | YES |
| UpdateBoardMessageRequest.php | YES |
| StoreCategoryRequest.php | YES |
| UpdateCategoryRequest.php | YES |
| StoreDirectMessageRequest.php | YES |
| StoreEightySixedRequest.php | YES |
| UpdateEightySixedRequest.php | YES |
| StoreEventRequest.php | YES |
| UpdateEventRequest.php | YES |
| StoreLocationRequest.php | YES |
| UpdateLocationRequest.php | YES |
| StoreManagerLogRequest.php | YES |
| UpdateManagerLogRequest.php | YES |
| StoreMenuItemRequest.php | YES |
| UpdateMenuItemRequest.php | YES |
| StorePushItemRequest.php | YES |
| UpdatePushItemRequest.php | YES |
| StoreScheduleRequest.php | YES |
| StoreScheduleEntryRequest.php | YES |
| StoreSettingRequest.php | YES |
| StoreShiftDropRequest.php | YES |
| StoreShiftTemplateRequest.php | YES |
| UpdateShiftTemplateRequest.php | YES |
| StoreSpecialRequest.php | YES |
| UpdateSpecialRequest.php | YES |
| StoreTimeOffRequestRequest.php | YES |
| StoreUserRequest.php | YES |
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
| NotificationResource.php | YES |
| PushItemResource.php | YES |
| ScheduleEntryResource.php | YES |
| ScheduleResource.php | YES |
| ShiftDropResource.php | YES |
| ShiftTemplateResource.php | YES |
| SpecialResource.php | YES |
| TimeOffRequestResource.php | YES |
| UserResource.php | YES |
| SettingResource.php | YES |

All resources have class-level docblocks explaining model transformation and serialized fields.

---

## Policies (16/16 -- 100%)

| File | Documented |
|------|-----------|
| AcknowledgmentPolicy.php | YES |
| AnnouncementPolicy.php | YES |
| BoardMessagePolicy.php | YES |
| CategoryPolicy.php | YES |
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
| EightySixedCreated.php | YES |
| EightySixedDeleted.php | YES |
| EightySixedUpdated.php | YES |
| EventCreated.php | YES |
| EventDeleted.php | YES |
| EventUpdated.php | YES |
| PushItemCreated.php | YES |
| PushItemDeleted.php | YES |
| PushItemUpdated.php | YES |
| SchedulePublished.php | YES |
| ScheduleUpdated.php | YES |
| ShiftDropRequested.php | YES |
| ShiftDropUpdated.php | YES |
| SpecialCreated.php | YES |
| SpecialDeleted.php | YES |
| SpecialUpdated.php | YES |
| TimeOffRequestCreated.php | YES |
| TimeOffRequestUpdated.php | YES |

All events have comprehensive docblocks explaining dispatch trigger, broadcast channel, and payload.

---

## Migrations (52/52 -- 100%)

All 52 migration files have class-level docblocks explaining table purpose, key columns, and design decisions.

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

## Tests (31/31 -- 100%)

All 31 test files have file-level docblocks listing test scenarios AND per-test method docblocks.

| File | Documented |
|------|-----------|
| AcknowledgmentTest.php | YES |
| AnnouncementTest.php | YES |
| AuthTest.php | YES |
| BoardMessageTest.php | YES |
| CategoryTest.php | YES |
| CheckRoleMiddlewareTest.php | YES |
| ConversationTest.php | YES |
| DailyLogTest.php | YES |
| DirectMessageTest.php | YES |
| EightySixedTest.php | YES |
| EnsureLocationAccessTest.php | YES |
| EventTest.php | YES |
| LocationTest.php | YES |
| ManagerLogModelTest.php | YES |
| MenuItemTest.php | YES |
| MyShiftTest.php | YES |
| NotificationTest.php | YES |
| PreShiftTest.php | YES |
| ProfileTest.php | YES |
| PushItemTest.php | YES |
| ScheduleEntryTest.php | YES |
| ScheduleModelTest.php | YES |
| SchedulingTest.php | YES |
| SettingTest.php | YES |
| ShiftDropTest.php | YES |
| ShiftTemplateTest.php | YES |
| SpecialTest.php | YES |
| TimeOffRequestTest.php | YES |
| UserModelTest.php | YES |
| UserTest.php | YES |
| WeatherTest.php | YES |

---

## Summary

| Category | Files | Documented | Coverage |
|----------|-------|------------|----------|
| Models | 22 | 22 | 100% |
| Controllers | 26 | 26 | 100% |
| Middleware | 3 | 3 | 100% |
| Form Requests | 31 | 31 | 100% |
| API Resources | 21 | 21 | 100% |
| Policies | 16 | 16 | 100% |
| Events | 26 | 26 | 100% |
| Migrations | 52 | 52 | 100% |
| Seeders | 1 | 1 | 100% |
| Routes | 4 | 4 | 100% |
| Tests | 31 | 31 | 100% |
| **Total** | **233** | **233** | **100%** |
