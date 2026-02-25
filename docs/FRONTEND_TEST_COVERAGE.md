# Frontend Test Coverage

**Status:** 100% file coverage — every component, view, composable, and store has a corresponding test file.

**Last verified:** 2026-02-24
**Total:** 74 test files, 620 tests

## Components (24 files)

| Source File | Test File | Tests |
|---|---|---|
| `components/AcknowledgeButton.vue` | `AcknowledgeButton.test.ts` | 4 |
| `components/AnnouncementCard.vue` | `AnnouncementCard.test.ts` | 8 |
| `components/AppTour.vue` | `AppTour.test.ts` | 7 |
| `components/AvailabilityGrid.vue` | `AvailabilityGrid.test.ts` | 13 |
| `components/EightySixedCard.vue` | `EightySixedCard.test.ts` | 8 |
| `components/EmployeeProfileModal.vue` | `EmployeeProfileModal.test.ts` | 9 |
| `components/PushItemCard.vue` | `PushItemCard.test.ts` | 8 |
| `components/RealtimeIndicator.vue` | `RealtimeIndicator.test.ts` | 5 |
| `components/ScheduleGrid.vue` | `ScheduleGrid.test.ts` | 13 |
| `components/ShiftCard.vue` | `ShiftCard.test.ts` | 5 |
| `components/ShiftDropCard.vue` | `ShiftDropCard.test.ts` | 5 |
| `components/SpecialCard.vue` | `SpecialCard.test.ts` | 8 |
| `components/TileDetailModal.vue` | `TileDetailModal.test.ts` | 15 |
| `components/TimeOffBadge.vue` | `TimeOffBadge.test.ts` | 3 |
| `components/layout/AppShell.vue` | `layout/AppShell.test.ts` | 6 |
| `components/layout/BottomNav.vue` | `layout/BottomNav.test.ts` | 4 |
| `components/layout/NotificationBell.vue` | `layout/NotificationBell.test.ts` | 6 |
| `components/layout/TopBar.vue` | `layout/TopBar.test.ts` | 8 |
| `components/messages/BoardPostCard.vue` | `messages/BoardPostCard.test.ts` | 6 |
| `components/messages/BoardTab.vue` | `messages/BoardTab.test.ts` | 11 |
| `components/messages/ConversationListItem.vue` | `messages/ConversationListItem.test.ts` | 12 |
| `components/messages/ConversationThread.vue` | `messages/ConversationThread.test.ts` | 10 |
| `components/messages/DirectTab.vue` | `messages/DirectTab.test.ts` | 13 |
| `components/messages/MessageComposer.vue` | `messages/MessageComposer.test.ts` | 4 |

## UI Components (6 files)

| Source File | Test File | Tests |
|---|---|---|
| `components/ui/BadgePill.vue` | `ui/BadgePill.test.ts` | 5 |
| `components/ui/BaseButton.vue` | `ui/BaseButton.test.ts` | 9 |
| `components/ui/BaseCard.vue` | `ui/BaseCard.test.ts` | 5 |
| `components/ui/BaseInput.vue` | `ui/BaseInput.test.ts` | 10 |
| `components/ui/BaseModal.vue` | `ui/BaseModal.test.ts` | 7 |
| `components/ui/ToastContainer.vue` | `ui/ToastContainer.test.ts` | 8 |
| `components/ui/UserAvatar.vue` | `ui/UserAvatar.test.ts` | 10 |

## Views (22 files)

| Source File | Test File | Tests |
|---|---|---|
| `views/admin/AcknowledgmentTracker.vue` | `AcknowledgmentTracker.test.ts` | 6 |
| `views/admin/ConfigView.vue` | `ConfigView.test.ts` | 7 |
| `views/admin/DailyManageView.vue` | `DailyManageView.test.ts` | 7 |
| `views/admin/ManageAnnouncements.vue` | `ManageAnnouncements.test.ts` | 10 |
| `views/admin/ManageEightySixed.vue` | `ManageEightySixed.test.ts` | 10 |
| `views/admin/ManageLocations.vue` | `ManageLocations.test.ts` | 6 |
| `views/admin/ManageLogsView.vue` | `ManageLogsView.test.ts` | 6 |
| `views/admin/ManageMenuItems.vue` | `ManageMenuItems.test.ts` | 11 |
| `views/admin/ManagePushItems.vue` | `ManagePushItems.test.ts` | 10 |
| `views/admin/ManageShiftDrops.vue` | `ManageShiftDrops.test.ts` | 8 |
| `views/admin/ManageSpecials.vue` | `ManageSpecials.test.ts` | 10 |
| `views/admin/ManageTimeOff.vue` | `ManageTimeOff.test.ts` | 9 |
| `views/admin/ManageUsers.vue` | `ManageUsers.test.ts` | 11 |
| `views/admin/ScheduleBuilderView.vue` | `ScheduleBuilderView.test.ts` | 5 |
| `views/auth/LoginView.vue` | `LoginView.test.ts` | 8 |
| `views/auth/LocationPickerView.vue` | `LocationPickerView.test.ts` | 6 |
| `views/auth/SetupView.vue` | `SetupView.test.ts` | 6 |
| `views/staff/DashboardView.vue` | `DashboardView.test.ts` | 3 |
| `views/staff/EightySixedBoard.vue` | `EightySixedBoard.test.ts` | 6 |
| `views/staff/MessagesView.vue` | `MessagesView.test.ts` | 5 |
| `views/staff/MyScheduleView.vue` | `MyScheduleView.test.ts` + `__tests__/MyScheduleView.spec.ts` | 13 |
| `views/staff/ProfileView.vue` | `ProfileView.test.ts` | 9 |
| `views/staff/ShiftDropBoardView.vue` | `ShiftDropBoardView.test.ts` | 8 |
| `views/staff/SpecialsView.vue` | `SpecialsView.test.ts` | 5 |
| `views/staff/TimeOffRequestView.vue` | `TimeOffRequestView.test.ts` | 10 |
| `views/staff/TonightsScheduleView.vue` | `TonightsScheduleView.test.ts` + `__tests__/TonightsScheduleView.spec.ts` | 10 |
| `views/staff/WeatherWidget.vue` | `__tests__/WeatherWidget.spec.ts` | 3 |

## Composables (7 files)

| Source File | Test File | Tests |
|---|---|---|
| `composables/useAcknowledgments.ts` | `useAcknowledgments.test.ts` | 6 |
| `composables/useApi.ts` | `useApi.test.ts` | 6 |
| `composables/useAuth.ts` | `useAuth.test.ts` | 9 |
| `composables/useMessages.ts` | `useMessages.test.ts` | 9 |
| `composables/useOnboarding.ts` | `useOnboarding.test.ts` | 12 |
| `composables/useReverb.ts` | `useReverb.test.ts` | 8 |
| `composables/useSchedule.ts` | `useSchedule.test.ts` | 22 |

## Stores (6 files)

| Source File | Test File | Tests |
|---|---|---|
| `stores/auth.ts` | `auth.test.ts` | 15 |
| `stores/location.ts` | `location.test.ts` | 5 |
| `stores/messages.ts` | `messages.test.ts` | 10 |
| `stores/notifications.ts` | `notifications.test.ts` | 7 |
| `stores/preshift.ts` | `preshift.test.ts` | 16 |
| `stores/schedule.ts` | `schedule.test.ts` | 30 |

## Router (1 file)

| Source File | Test File | Tests |
|---|---|---|
| `router/index.ts` | `router/index.test.ts` | 12 |

## Rules

- Every new Vue component, view, composable, or store **must** have a corresponding test file
- Test files live alongside source files (same directory) with `.test.ts` extension
- Test files must include a file-level block comment listing what the tests verify
- Each `it()` must have a block comment describing what it checks and why
- When adding a field to the `User` type (or any shared type), update **all** test fixtures that construct that type
