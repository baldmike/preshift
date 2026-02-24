# Client Comment Coverage

> Last updated: 2026-02-24
> Files: 129 | Documented: 129 | Coverage: 100%

---

## Stores (6/6 -- 100%)

| File | Documented |
|------|-----------|
| auth.ts | YES |
| location.ts | YES |
| messages.ts | YES |
| notifications.ts | YES |
| preshift.ts | YES |
| schedule.ts | YES |

All stores have file-level block comments describing state shape, getters, and actions.

---

## Composables (7/7 -- 100%)

| File | Documented |
|------|-----------|
| useAcknowledgments.ts | YES |
| useApi.ts | YES |
| useAuth.ts | YES |
| useMessages.ts | YES |
| useOnboarding.ts | YES |
| useReverb.ts | YES |
| useSchedule.ts | YES |

All composables have file-level block comments describing purpose, exports, and usage patterns.

---

## Router (1/1 -- 100%)

| File | Documented |
|------|-----------|
| router/index.ts | YES |

Comprehensive file-level block comment describing route structure, meta fields, and navigation guard logic.

---

## Types (1/1 -- 100%)

| File | Documented |
|------|-----------|
| types/index.ts | YES |

File-level block comment explains purpose and notes that interfaces map to Laravel models.

---

## Entry Point (1/1 -- 100%)

| File | Documented |
|------|-----------|
| main.ts | YES |

File-level block comment explaining the application bootstrap sequence.

---

## Components (30/30 -- 100%)

### Root Components (14/14)

| File | Documented |
|------|-----------|
| AcknowledgeButton.vue | YES |
| AnnouncementCard.vue | YES |
| AppTour.vue | YES |
| AvailabilityGrid.vue | YES |
| EightySixedCard.vue | YES |
| EmployeeProfileModal.vue | YES |
| PushItemCard.vue | YES |
| RealtimeIndicator.vue | YES |
| ScheduleGrid.vue | YES |
| ShiftCard.vue | YES |
| ShiftDropCard.vue | YES |
| SpecialCard.vue | YES |
| TileDetailModal.vue | YES |
| TimeOffBadge.vue | YES |

### UI Components (6/6)

| File | Documented |
|------|-----------|
| ui/BadgePill.vue | YES |
| ui/BaseButton.vue | YES |
| ui/BaseCard.vue | YES |
| ui/BaseInput.vue | YES |
| ui/BaseModal.vue | YES |
| ui/ToastContainer.vue | YES |

### Layout Components (4/4)

| File | Documented |
|------|-----------|
| layout/AppShell.vue | YES |
| layout/BottomNav.vue | YES |
| layout/NotificationBell.vue | YES |
| layout/TopBar.vue | YES |

### Message Components (6/6)

| File | Documented |
|------|-----------|
| messages/BoardPostCard.vue | YES |
| messages/BoardTab.vue | YES |
| messages/ConversationListItem.vue | YES |
| messages/ConversationThread.vue | YES |
| messages/DirectTab.vue | YES |
| messages/MessageComposer.vue | YES |

All components have block comments at the top of `<script setup>` describing purpose and props.

---

## Views (27/27 -- 100%)

### Admin Views (15/15)

| File | Documented |
|------|-----------|
| admin/AcknowledgmentTracker.vue | YES |
| admin/ConfigView.vue | YES |
| admin/DailyManageView.vue | YES |
| admin/ManageAnnouncements.vue | YES |
| admin/ManageDashboard.vue | YES |
| admin/ManageEightySixed.vue | YES |
| admin/ManageLocations.vue | YES |
| admin/ManageLogsView.vue | YES |
| admin/ManageMenuItems.vue | YES |
| admin/ManagePushItems.vue | YES |
| admin/ManageShiftDrops.vue | YES |
| admin/ManageSpecials.vue | YES |
| admin/ManageTimeOff.vue | YES |
| admin/ManageUsers.vue | YES |
| admin/ScheduleBuilderView.vue | YES |

### Auth Views (3/3)

| File | Documented |
|------|-----------|
| auth/LocationPickerView.vue | YES |
| auth/LoginView.vue | YES |
| auth/SetupView.vue | YES |

### Staff Views (9/9)

| File | Documented |
|------|-----------|
| staff/DashboardView.vue | YES |
| staff/EightySixedBoard.vue | YES |
| staff/MessagesView.vue | YES |
| staff/MyScheduleView.vue | YES |
| staff/ProfileView.vue | YES |
| staff/ShiftDropBoardView.vue | YES |
| staff/SpecialsView.vue | YES |
| staff/TimeOffRequestView.vue | YES |
| staff/TonightsScheduleView.vue | YES |

All views have block comments at the top of `<script setup>` describing functionality.

---

## Test Files (56/56 -- 100%)

All test files have file-level block comments listing what the tests verify AND per-`it()` block comments explaining each test.

### Store Tests (6/6)

| File | Documented |
|------|-----------|
| stores/auth.test.ts | YES |
| stores/location.test.ts | YES |
| stores/messages.test.ts | YES |
| stores/notifications.test.ts | YES |
| stores/preshift.test.ts | YES |
| stores/schedule.test.ts | YES |

### Composable Tests (7/7)

| File | Documented |
|------|-----------|
| composables/useAcknowledgments.test.ts | YES |
| composables/useApi.test.ts | YES |
| composables/useAuth.test.ts | YES |
| composables/useMessages.test.ts | YES |
| composables/useOnboarding.test.ts | YES |
| composables/useReverb.test.ts | YES |
| composables/useSchedule.test.ts | YES |

### Router Tests (1/1)

| File | Documented |
|------|-----------|
| router/index.test.ts | YES |

### Component Tests (30/30)

| File | Documented |
|------|-----------|
| components/AcknowledgeButton.test.ts | YES |
| components/AnnouncementCard.test.ts | YES |
| components/AppTour.test.ts | YES |
| components/AvailabilityGrid.test.ts | YES |
| components/EightySixedCard.test.ts | YES |
| components/EmployeeProfileModal.test.ts | YES |
| components/PushItemCard.test.ts | YES |
| components/RealtimeIndicator.test.ts | YES |
| components/ScheduleGrid.test.ts | YES |
| components/ShiftCard.test.ts | YES |
| components/ShiftDropCard.test.ts | YES |
| components/SpecialCard.test.ts | YES |
| components/TileDetailModal.test.ts | YES |
| components/TimeOffBadge.test.ts | YES |
| components/layout/AppShell.test.ts | YES |
| components/layout/BottomNav.test.ts | YES |
| components/layout/NotificationBell.test.ts | YES |
| components/layout/TopBar.test.ts | YES |
| components/messages/BoardPostCard.test.ts | YES |
| components/messages/BoardTab.test.ts | YES |
| components/messages/ConversationListItem.test.ts | YES |
| components/messages/ConversationThread.test.ts | YES |
| components/messages/DirectTab.test.ts | YES |
| components/messages/MessageComposer.test.ts | YES |
| components/ui/BadgePill.test.ts | YES |
| components/ui/BaseButton.test.ts | YES |
| components/ui/BaseCard.test.ts | YES |
| components/ui/BaseInput.test.ts | YES |
| components/ui/BaseModal.test.ts | YES |
| components/ui/ToastContainer.test.ts | YES |

### View Tests (12/12)

| File | Documented |
|------|-----------|
| views/admin/AcknowledgmentTracker.test.ts | YES |
| views/admin/ConfigView.test.ts | YES |
| views/admin/DailyManageView.test.ts | YES |
| views/admin/ManageAnnouncements.test.ts | YES |
| views/admin/ManageEightySixed.test.ts | YES |
| views/admin/ManageLocations.test.ts | YES |
| views/admin/ManageLogsView.test.ts | YES |
| views/admin/ManageMenuItems.test.ts | YES |
| views/admin/ManagePushItems.test.ts | YES |
| views/admin/ManageShiftDrops.test.ts | YES |
| views/admin/ManageSpecials.test.ts | YES |
| views/admin/ManageTimeOff.test.ts | YES |
| views/admin/ManageUsers.test.ts | YES |
| views/admin/ScheduleBuilderView.test.ts | YES |
| views/auth/LocationPickerView.test.ts | YES |
| views/auth/LoginView.test.ts | YES |
| views/auth/SetupView.test.ts | YES |
| views/staff/DashboardView.test.ts | YES |
| views/staff/EightySixedBoard.test.ts | YES |
| views/staff/MessagesView.test.ts | YES |
| views/staff/MyScheduleView.test.ts | YES |
| views/staff/ProfileView.test.ts | YES |
| views/staff/ShiftDropBoardView.test.ts | YES |
| views/staff/SpecialsView.test.ts | YES |
| views/staff/TimeOffRequestView.test.ts | YES |
| views/staff/TonightsScheduleView.test.ts | YES |

---

## Summary

| Category | Files | Documented | Coverage |
|----------|-------|------------|----------|
| Stores | 6 | 6 | 100% |
| Composables | 7 | 7 | 100% |
| Router | 1 | 1 | 100% |
| Types | 1 | 1 | 100% |
| Entry Point | 1 | 1 | 100% |
| Components | 30 | 30 | 100% |
| Views | 27 | 27 | 100% |
| Test Files | 56 | 56 | 100% |
| **Total** | **129** | **129** | **100%** |
