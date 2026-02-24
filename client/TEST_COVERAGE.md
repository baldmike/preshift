# Client Test Coverage

> Last updated: 2026-02-24
> Tests: 584 passed (73 files)

---

## Stores (6/6 -- 100%)

| Store | Test File | Tests | Status |
|-------|-----------|-------|--------|
| auth.ts | auth.test.ts | 12 | COVERED |
| preshift.ts | preshift.test.ts | 20 | COVERED |
| schedule.ts | schedule.test.ts | 38 | COVERED |
| notifications.ts | notifications.test.ts | 7 | COVERED |
| messages.ts | messages.test.ts | 11 | COVERED |
| location.ts | location.test.ts | 5 | COVERED |

### auth.ts -- `auth.test.ts` (12 tests)

- `returns false when no token and no user`
- `returns false when token exists but no user`
- `returns true when both token and user exist`
- `returns true only when user.role === "admin"`
- `returns true only when user.role === "manager"`
- `returns true for "server" and "bartender" roles`
- `returns false when no user is loaded`
- `returns true when user has is_superadmin = true`
- `returns false when user has is_superadmin = false`
- `returns user.location_id or null when no user`
- `calls POST /api/login, sets token and user, stores token in localStorage`
- `calls POST /api/logout, clears token and user, removes from localStorage`
- `clears state even if API call fails`
- `calls GET /api/user and sets user`
- `is initialised from localStorage on store creation`

### preshift.ts -- `preshift.test.ts` (20 tests)

- `has empty arrays and loading=false`
- `sets loading=true during request`
- `populates all state arrays from API response`
- `resets loading to false even on error`
- `pushes item to the eightySixed array`
- `filters out the item by id`
- `pushes item to the specials array`
- `replaces the matching item in the specials array`
- `does nothing if id not found`
- `filters out the special by id`
- `pushes item to the pushItems array`
- `replaces the matching item in the pushItems array`
- `filters out the push item by id`
- `addAnnouncement pushes, updateAnnouncement replaces, removeAnnouncement filters`
- `adds a new acknowledgment ref to the array`
- `does not add duplicate acknowledgment refs`

### schedule.ts -- `schedule.test.ts` (38 tests)

- `has empty arrays, null refs, and loading=false`
- `calls GET /api/shift-templates and populates shiftTemplates`
- `handles an empty response gracefully`
- `calls GET /api/schedules and populates schedules`
- `calls GET /api/schedules/:id and sets currentSchedule`
- `calls GET /api/my-shifts and populates myShifts`
- `finds and fetches the current week published schedule`
- `matches week_start with a datetime string (includes T component)`
- `sets currentSchedule to null when no matching schedule is found`
- `ignores draft schedules for the current week`
- `sets currentSchedule to null on API error`
- `clears a previously set currentSchedule when no match is found`
- `calls GET /api/shift-drops and populates shiftDrops`
- `calls GET /api/time-off-requests and populates timeOffRequests`
- `updates an existing schedule in the schedules array`
- `pushes a new schedule if id is not found in the array`
- `triggers fetchMyShifts after updating the schedules array`
- `merges incoming fields with existing schedule fields`
- `updates an existing shift drop by id`
- `pushes a new shift drop if id is not found`
- `inserts into an empty array` (upsertShiftDrop)
- `updates an existing time-off request by id`
- `pushes a new time-off request if id is not found`
- `inserts into an empty array` (upsertTimeOffRequest)
- `handles denied status correctly`
- `starts as an empty object` (ackSummaryMap)
- `calls GET /api/acknowledgments/summary and populates ackSummaryMap`
- `handles API error gracefully` (fetchAckSummary)
- `sets the percentage for a specific user`
- `preserves other users when updating one`

### notifications.ts -- `notifications.test.ts` (7 tests)

- `has correct initial state`
- `fetchNotifications populates state from API`
- `fetchNotifications sets loading flag`
- `markRead optimistically updates notification and decrements count`
- `markRead does not decrement below zero`
- `markAllRead marks all notifications and resets count`
- `pushNotification prepends and increments count`

### messages.ts -- `messages.test.ts` (11 tests)

- `has empty initial state`
- `fetchBoardMessages populates state`
- `createBoardMessage adds to state`
- `addBoardMessage inserts new top-level post`
- `addBoardMessage inserts reply into parent`
- `updateBoardMessageLocal replaces the correct post`
- `removeBoardMessage removes the correct post`
- `fetchConversations populates state`
- `fetchUnreadCount updates count`
- `pushDirectMessage increments unread for non-active conversation`

### location.ts -- `location.test.ts` (5 tests)

- `has empty locations array and null current`
- `sets locations from API response`
- `sets the current location`
- `replaces the previous current location when called again`
- `locations array is reactive (push updates length)`

---

## Composables (7/7 -- 100%)

| Composable | Test File | Tests | Status |
|------------|-----------|-------|--------|
| useSchedule.ts | useSchedule.test.ts | 20 | COVERED |
| useApi.ts | useApi.test.ts | 6 | COVERED |
| useAcknowledgments.ts | useAcknowledgments.test.ts | 6 | COVERED |
| useAuth.ts | useAuth.test.ts | 9 | COVERED |
| useMessages.ts | useMessages.test.ts | 10 | COVERED |
| useReverb.ts | useReverb.test.ts | 8 | COVERED |
| useOnboarding.ts | useOnboarding.test.ts | 12 | COVERED |

### useSchedule.ts -- `useSchedule.test.ts` (20 tests)

- `returns null when myShifts is empty`
- `returns the first shift when myShifts has entries`
- `computes correct Monday-Sunday when today is a Wednesday`
- `computes correct range when today is Monday`
- `computes correct range when today is Sunday`
- `handles month boundaries correctly`
- `handles year boundaries correctly`
- `returns an empty record when myShifts is empty`
- `filters shifts to only the current week`
- `groups multiple shifts on the same day`
- `formats morning time (HH:MM) correctly`
- `formats afternoon time (HH:MM:SS) correctly`
- `formats midnight (00:00) as 12:00 AM`
- `formats noon (12:00) as 12:00 PM`
- `formats 1 PM correctly`
- `preserves minutes with leading zero`
- `formats a week within the same month`
- `formats a week crossing month boundary`
- `returns 0 when no shift drops exist`
- `counts only open drops`
- `returns 0 when no time-off requests exist`
- `counts only pending time-off requests`

### useApi.ts -- `useApi.test.ts` (6 tests)

- `returns the same Axios instance from useApi() and the default export`
- `attaches Bearer token from localStorage on outgoing requests`
- `omits Authorization header when no token is stored`
- `passes successful responses through unchanged`
- `clears token and redirects to /login on 401 response`
- `rejects non-401 errors without side effects`

### useAcknowledgments.ts -- `useAcknowledgments.test.ts` (6 tests)

- `sends a POST to /api/acknowledge with type and id`
- `updates the local store after a successful API call`
- `propagates API errors to the caller`
- `returns false when no acknowledgments exist`
- `differentiates between types with the same id`
- `returns true for each acknowledged item in a mixed array`

### useAuth.ts -- `useAuth.test.ts` (9 tests)

- `returns safe defaults when no user is loaded`
- `returns true for isLoggedIn only when both token and user exist`
- `computes isAdmin correctly based on user role`
- `computes isManager correctly based on user role`
- `computes isStaff correctly for server and bartender roles`
- `computes isSuperAdmin from the user flag`
- `returns the user location_id or null`
- `returns true when user has more than one location membership`
- `returns true for admin users with no location memberships`

### useMessages.ts -- `useMessages.test.ts` (10 tests)

- `filters board messages to only pinned posts`
- `returns empty array when no posts are pinned`
- `filters board messages to only unpinned posts`
- `returns empty array when all posts are pinned`
- `returns empty arrays when board has no messages`
- `reflects the unread DM count from the store`
- `exposes boardMessages from the store`
- `exposes loading flags from the store`
- `exposes store actions as direct references`

### useReverb.ts -- `useReverb.test.ts` (8 tests)

- `creates an Echo instance with correct configuration`
- `caches Echo instances by location ID`
- `creates separate instances for different location IDs`
- `uses empty string for auth when no token exists`
- `useLocationChannel subscribes to private-location.{id}`
- `useUserChannel subscribes to private-user.{userId}`
- `disconnects all cached instances and resets the cache`
- `handles disconnectReverb gracefully when no instances exist`

### useOnboarding.ts -- `useOnboarding.test.ts` (12 tests)

- `starts inactive at step 0`
- `exposes the correct total steps and first current step`
- `activates the tour and resets to step 0`
- `advances to the next step`
- `finishes the tour and marks as seen on the last step`
- `deactivates the tour and persists seen flag`
- `handles skipTour with no user without throwing`
- `starts the tour for a superadmin who has not seen it`
- `does not start the tour for a non-superadmin user`
- `does not start the tour when user is null`
- `does not start the tour if the user has already seen it`
- `returns the correct step for each index`

---

## Components (30/30 -- 100%)

| Component | Test File | Tests | Status |
|-----------|-----------|-------|--------|
| AcknowledgeButton.vue | AcknowledgeButton.test.ts | 4 | COVERED |
| AnnouncementCard.vue | AnnouncementCard.test.ts | 8 | COVERED |
| AppTour.vue | AppTour.test.ts | 7 | COVERED |
| AvailabilityGrid.vue | AvailabilityGrid.test.ts | 13 | COVERED |
| EightySixedCard.vue | EightySixedCard.test.ts | 8 | COVERED |
| EmployeeProfileModal.vue | EmployeeProfileModal.test.ts | 9 | COVERED |
| PushItemCard.vue | PushItemCard.test.ts | 8 | COVERED |
| RealtimeIndicator.vue | RealtimeIndicator.test.ts | 5 | COVERED |
| ScheduleGrid.vue | ScheduleGrid.test.ts | 13 | COVERED |
| ShiftCard.vue | ShiftCard.test.ts | 5 | COVERED |
| ShiftDropCard.vue | ShiftDropCard.test.ts | 5 | COVERED |
| SpecialCard.vue | SpecialCard.test.ts | 8 | COVERED |
| TileDetailModal.vue | TileDetailModal.test.ts | 15 | COVERED |
| TimeOffBadge.vue | TimeOffBadge.test.ts | 3 | COVERED |
| layout/AppShell.vue | AppShell.test.ts | 6 | COVERED |
| layout/BottomNav.vue | BottomNav.test.ts | 4 | COVERED |
| layout/NotificationBell.vue | NotificationBell.test.ts | 6 | COVERED |
| layout/TopBar.vue | TopBar.test.ts | 8 | COVERED |
| messages/BoardPostCard.vue | BoardPostCard.test.ts | 6 | COVERED |
| messages/BoardTab.vue | BoardTab.test.ts | 11 | COVERED |
| messages/ConversationListItem.vue | ConversationListItem.test.ts | 12 | COVERED |
| messages/ConversationThread.vue | ConversationThread.test.ts | 10 | COVERED |
| messages/DirectTab.vue | DirectTab.test.ts | 14 | COVERED |
| messages/MessageComposer.vue | MessageComposer.test.ts | 4 | COVERED |
| ui/BadgePill.vue | BadgePill.test.ts | 5 | COVERED |
| ui/BaseButton.vue | BaseButton.test.ts | 9 | COVERED |
| ui/BaseCard.vue | BaseCard.test.ts | 5 | COVERED |
| ui/BaseInput.vue | BaseInput.test.ts | 10 | COVERED |
| ui/BaseModal.vue | BaseModal.test.ts | 7 | COVERED |
| ui/ToastContainer.vue | ToastContainer.test.ts | 8 | COVERED |

### AcknowledgeButton.vue -- `AcknowledgeButton.test.ts` (4 tests)

- `shows HEARD button when not acknowledged`
- `shows checkmark when acknowledged`
- `calls acknowledge on click`
- `shows loading spinner during API call`

### AnnouncementCard.vue -- `AnnouncementCard.test.ts` (8 tests)

- `renders title as the heading`
- `shows priority badge via BadgePill`
- `shows body text when provided`
- `shows poster name`
- `shows expiration date when expires_at is set`
- `shows AcknowledgeButton for staff users`
- `shows Edit link instead of AcknowledgeButton for managers`
- `shows Edit link instead of AcknowledgeButton for admins`

### AppTour.vue -- `AppTour.test.ts` (7 tests)

- `does not render the overlay when tour is inactive`
- `renders the overlay when tour is active with a step`
- `displays the step title and description`
- `shows the correct step counter`
- `calls skipTour when the skip button is clicked`
- `calls nextStep when the Next button is clicked`
- `shows Done instead of Next on the last step`

### AvailabilityGrid.vue -- `AvailabilityGrid.test.ts` (13 tests)

- `renders 7 day column labels`
- `renders master + 5 per day + save buttons`
- `clicking 10:30 AM adds the slot to the day`
- `clicking OPEN replaces time slots with open`
- `clicking a slot while OPEN is active deselects OPEN`
- `clicking an active slot deselects it`
- `save button emits save event`
- `clicking OPEN while active clears the day`
- `master Open Availability sets all days to open`
- `master Open Availability clears all when already all-open`
- `readonly hides the master Open Availability button`
- `readonly hides the save button`
- `readonly adds pointer-events-none to the grid`

### EightySixedCard.vue -- `EightySixedCard.test.ts` (8 tests)

- `renders item_name as the heading`
- `shows reason when provided`
- `hides reason when null`
- `shows user name when user relationship is loaded`
- `renders AcknowledgeButton`
- `shows AcknowledgeButton for staff users`
- `shows Edit link instead of AcknowledgeButton for managers`
- `shows Edit link instead of AcknowledgeButton for admins`

### EmployeeProfileModal.vue -- `EmployeeProfileModal.test.ts` (9 tests)

- `renders employee name, email, phone, and role badge`
- `renders phone as a tel: link`
- `clicking email copies to clipboard and dispatches toast`
- `shows "Not set" when phone is null`
- `shows availability grid when user has availability`
- `shows "Not set" for availability when null`
- `message button navigates to direct messages and emits close`
- `close button emits close event`
- `does not render modal content when user is null`

### PushItemCard.vue -- `PushItemCard.test.ts` (8 tests)

- `renders title as the heading`
- `shows priority badge via BadgePill`
- `shows description when provided`
- `shows reason when provided`
- `hides description when null`
- `shows AcknowledgeButton for staff users`
- `shows Edit link instead of AcknowledgeButton for managers`
- `shows Edit link instead of AcknowledgeButton for admins`

### RealtimeIndicator.vue -- `RealtimeIndicator.test.ts` (5 tests)

- `displays Offline with gray dot when Echo is not available`
- `displays Live with green dot when Echo is connected`
- `displays Offline when Echo state is not connected`
- `updates state after polling interval`
- `sets title attribute based on connection status`

### ScheduleGrid.vue -- `ScheduleGrid.test.ts` (13 tests)

- `renders 7 column headers (Mon through Sun)`
- `column headers include formatted dates`
- `renders a row for each time slot`
- `row headers show start time instead of template name`
- `shows "+" button when no entries exist for a cell`
- `emits 'add-entry' when '+' is clicked`
- `handles ISO datetime format from API without NaN`
- `derives time slot rows from entries when shiftTemplates prop is omitted`
- `shows assigned staff with role badges`
- `renders red ring on entries for unacknowledged users`
- `does not render red ring when ackMap is not provided`
- `emits 'view-profile' when clicking a staff name with ackMap`
- `staff names are not clickable without ackMap`

### ShiftCard.vue -- `ShiftCard.test.ts` (5 tests)

- `renders start time as the heading`
- `shows "Shift" fallback when shift_template is not loaded`
- `shows "Give Up Shift" button`
- `emits 'give-up' event when button is clicked`
- `renders formatted date`

### ShiftDropCard.vue -- `ShiftDropCard.test.ts` (5 tests)

- `renders requester name from drop.requester.name`
- `shows status badge with correct color for each status`
- `shows volunteer count when volunteers exist`
- `shows reason when provided`
- `hides reason when null`

### SpecialCard.vue -- `SpecialCard.test.ts` (8 tests)

- `renders title as the heading`
- `shows type badge via BadgePill`
- `shows description when provided`
- `shows date range from starts_at and ends_at`
- `shows quantity when present`
- `shows AcknowledgeButton for staff users`
- `shows Edit link instead of AcknowledgeButton for managers`
- `shows Edit link instead of AcknowledgeButton for admins`

### TileDetailModal.vue -- `TileDetailModal.test.ts` (15 tests)

- `does not render when tileType is null`
- `renders correct title for eightySixed`
- `renders correct title for specials`
- `renders correct title for pushItems`
- `renders correct title for announcements`
- `displays full content without truncation for eightySixed`
- `displays full content without truncation for announcements`
- `shows AcknowledgeButtons for staff users`
- `shows edit and delete buttons for managers without AcknowledgeButton`
- `shows edit and delete buttons for admins without AcknowledgeButton`
- `shows empty state for eightySixed`
- `shows empty state for specials`
- `shows empty state for pushItems`
- `shows empty state for announcements`
- `close button emits close event`

### TimeOffBadge.vue -- `TimeOffBadge.test.ts` (3 tests)

- `renders the user name`
- `shows date range`
- `applies correct styling for approved status`

### layout/AppShell.vue -- `AppShell.test.ts` (6 tests)

- `renders default slot content in the main area`
- `renders the TopBar component`
- `renders the BottomNav component`
- `renders the ToastContainer component`
- `renders the AppTour component`
- `applies expected layout classes to the root container`

### layout/BottomNav.vue -- `BottomNav.test.ts` (4 tests)

- `shows Manage link for managers`
- `hides Manage link for regular staff`
- `does not show Config link in bottom nav`
- `shows Manage link for admins`

### layout/NotificationBell.vue -- `NotificationBell.test.ts` (6 tests)

- `renders the bell icon`
- `shows unread badge when there are unread notifications`
- `hides unread badge when count is zero`
- `shows 9+ for counts above 9`
- `toggles dropdown on click`
- `displays notification rows in dropdown`

### layout/TopBar.vue -- `TopBar.test.ts` (8 tests)

- `displays the establishment name in the header`
- `renders user initials on the avatar button`
- `opens dropdown menu when avatar button is clicked`
- `shows user name and email in the dropdown`
- `shows Log Out button in the dropdown`
- `shows Change Password button in the dropdown`
- `shows NotificationBell for managers but not for staff`
- `displays date and time information`

### messages/BoardPostCard.vue -- `BoardPostCard.test.ts` (6 tests)

- `renders author name and body`
- `shows pin icon when pinned`
- `hides pin icon when not pinned`
- `shows managers-only badge`
- `shows edit/delete for post author`
- `hides edit/delete for non-author staff`

### messages/BoardTab.vue -- `BoardTab.test.ts` (11 tests)

- `fetches board messages and subscribes to realtime events on mount`
- `shows loading state while board messages are loading`
- `shows empty state when there are no posts`
- `renders pinned and regular posts`
- `shows visibility toggle for managers`
- `shows visibility toggle for admins`
- `hides visibility toggle for regular staff`
- `calls createBoardMessage when a post is submitted`
- `includes managers visibility when toggle is checked`
- `calls deleteBoardMessage on delete event`
- `calls togglePin on pin event`

### messages/ConversationListItem.vue -- `ConversationListItem.test.ts` (12 tests)

- `renders the other participant name`
- `shows Unknown when other participant is not found`
- `displays a role badge for the other participant`
- `truncates long message previews at 40 characters`
- `shows short messages without truncation`
- `shows "No messages yet" when latest_message is null`
- `shows unread dot when unread_count > 0`
- `hides unread dot when unread_count is 0`
- `emits select with conversation id on click`
- `applies active styling when active prop is true`
- `applies hover styling when active prop is false`
- `shows the avatar initial of the other participant`

### messages/ConversationThread.vue -- `ConversationThread.test.ts` (10 tests)

- `fetches messages for the given conversationId on mount`
- `shows loading state when dmLoading is true`
- `shows empty state when there are no messages`
- `aligns messages from other users to the left`
- `aligns messages from the current user to the right`
- `renders message body text`
- `applies correct bubble styling based on sender`
- `calls sendMessage when the composer submits`
- `renders the MessageComposer at the bottom`
- `refetches messages when conversationId changes`

### messages/DirectTab.vue -- `DirectTab.test.ts` (14 tests)

- `fetches conversations on mount`
- `shows loading state while conversations are loading`
- `shows empty state when there are no conversations`
- `renders a ConversationListItem for each conversation`
- `shows the New Message button`
- `opens user picker and fetches users on New Message click`
- `filters users in the picker by search input`
- `creates a conversation when a user is selected from the picker`
- `shows placeholder when no conversation is selected`
- `shows ConversationThread when a conversation is active`
- `subscribes to realtime DM events on mount`
- `auto-opens a conversation when initialUserId is provided`
- `closes the user picker without selecting a user`

### messages/MessageComposer.vue -- `MessageComposer.test.ts` (4 tests)

- `emits submit with trimmed body and clears input`
- `disables Send when input is empty`
- `disables Send when loading is true`
- `shows character count near maxLength`

### ui/BadgePill.vue -- `BadgePill.test.ts` (5 tests)

- `renders the label text correctly`
- `applies correct CSS classes for 'blue' color`
- `applies correct CSS classes for 'green' color`
- `applies correct CSS classes for 'yellow' color`
- `renders without errors when all props provided`

### ui/BaseButton.vue -- `BaseButton.test.ts` (9 tests)

- `renders default slot content inside the button`
- `applies primary variant classes by default`
- `applies secondary variant classes`
- `applies danger variant classes`
- `applies small size classes`
- `applies large size classes`
- `disables the button when loading is true`
- `renders spinner SVG when loading is true`
- `does not render spinner SVG when loading is false`

### ui/BaseCard.vue -- `BaseCard.test.ts` (5 tests)

- `renders default slot content inside the card`
- `renders h3 heading when title prop is provided`
- `does not render h3 heading when title prop is omitted`
- `applies base styling classes to the card container`
- `applies correct styling classes to the title heading`

### ui/BaseInput.vue -- `BaseInput.test.ts` (10 tests)

- `renders label when label prop is provided`
- `does not render label when label prop is omitted`
- `sets input value from modelValue prop`
- `emits update:modelValue when input value changes`
- `applies placeholder text to the input`
- `displays error message when error prop is set`
- `does not display error message when error prop is omitted`
- `applies red border classes when error is present`
- `defaults to type text when type prop is omitted`
- `applies custom input type when specified`

### ui/BaseModal.vue -- `BaseModal.test.ts` (7 tests)

- `renders modal content when open is true`
- `does not render modal content when open is false`
- `renders default slot content inside the content card`
- `emits close event when backdrop is clicked`
- `applies max-w-md class for default size`
- `applies max-w-lg class for large size`
- `content card has expected styling classes`

### ui/ToastContainer.vue -- `ToastContainer.test.ts` (8 tests)

- `renders with no toasts by default`
- `displays a toast when addToast is called`
- `applies green background class for success toasts`
- `applies red background class for error toasts`
- `applies gray background class for info toasts`
- `removes a toast when it is clicked`
- `adds a toast in response to a window toast event`
- `displays multiple toasts simultaneously`

---

## Views (27/27 -- 100%)

| View | Test File | Tests | Status |
|------|-----------|-------|--------|
| staff/DashboardView.vue | DashboardView.test.ts | 3 | COVERED |
| admin/ScheduleBuilderView.vue | ScheduleBuilderView.test.ts | 5 | COVERED |
| admin/AcknowledgmentTracker.vue | AcknowledgmentTracker.test.ts | 6 | COVERED |
| admin/ConfigView.vue | ConfigView.test.ts | 7 | COVERED |
| admin/DailyManageView.vue | DailyManageView.test.ts | 7 | COVERED |
| admin/ManageAnnouncements.vue | ManageAnnouncements.test.ts | 7 | COVERED |
| admin/ManageDashboard.vue | DailyManageView.test.ts | 7 | COVERED |
| admin/ManageEightySixed.vue | ManageEightySixed.test.ts | 7 | COVERED |
| admin/ManageLocations.vue | ManageLocations.test.ts | 6 | COVERED |
| admin/ManageLogsView.vue | ManageLogsView.test.ts | 6 | COVERED |
| admin/ManageMenuItems.vue | ManageMenuItems.test.ts | 9 | COVERED |
| admin/ManagePushItems.vue | ManagePushItems.test.ts | 7 | COVERED |
| admin/ManageShiftDrops.vue | ManageShiftDrops.test.ts | 6 | COVERED |
| admin/ManageSpecials.vue | ManageSpecials.test.ts | 7 | COVERED |
| admin/ManageTimeOff.vue | ManageTimeOff.test.ts | 6 | COVERED |
| admin/ManageUsers.vue | ManageUsers.test.ts | 9 | COVERED |
| auth/LocationPickerView.vue | LocationPickerView.test.ts | 6 | COVERED |
| auth/LoginView.vue | LoginView.test.ts | 8 | COVERED |
| auth/SetupView.vue | SetupView.test.ts | 6 | COVERED |
| staff/EightySixedBoard.vue | EightySixedBoard.test.ts | 6 | COVERED |
| staff/MessagesView.vue | MessagesView.test.ts | 6 | COVERED |
| staff/MyScheduleView.vue | MyScheduleView.test.ts | 6 | COVERED |
| staff/ProfileView.vue | ProfileView.test.ts | 8 | COVERED |
| staff/ShiftDropBoardView.vue | ShiftDropBoardView.test.ts | 7 | COVERED |
| staff/SpecialsView.vue | SpecialsView.test.ts | 5 | COVERED |
| staff/TimeOffRequestView.vue | TimeOffRequestView.test.ts | 9 | COVERED |
| staff/TonightsScheduleView.vue | TonightsScheduleView.test.ts | 7 | COVERED |

### staff/DashboardView.vue -- `DashboardView.test.ts` (3 tests)

- `renders schedule nav pills when no published schedule exists`
- `pill links display correct labels`
- `renders Messages section link`

### admin/ScheduleBuilderView.vue -- `ScheduleBuilderView.test.ts` (5 tests)

- `hides users already scheduled on the selected date`
- `shows users when form date differs from their scheduled date`
- `shows all users when schedule has no entries`
- `filters out multiple users scheduled on the same date`
- `only filters users for the specific selected date`

### admin/AcknowledgmentTracker.vue -- `AcknowledgmentTracker.test.ts` (6 tests)

- `renders without crashing and shows the page heading`
- `displays a navigation link back to /manage/daily`
- `hides the loading spinner after data is fetched`
- `displays summary cards with category item counts`
- `renders per-user acknowledgment rows with names and fractions`
- `shows empty-state message when there are no staff members`

### admin/ConfigView.vue -- `ConfigView.test.ts` (7 tests)

- `renders without crashing and shows the page heading`
- `displays a navigation link back to /manage/daily`
- `shows the SUPERADMIN badge`
- `shows the Initial Setup section when setup is not complete`
- `shows the Establishment Name section when setup is complete`
- `shows the Danger Zone when setup is complete`
- `shows the Time Off Policy section when setup is complete`

### admin/DailyManageView.vue -- `DailyManageView.test.ts` (7 tests)

- `renders without crashing`
- `displays the "Daily Management" heading`
- `shows all quick-nav management links`
- `renders all four section headings`
- `shows empty state messages when no data exists`
- `shows the "Corner!" back link`
- `renders add buttons for each section`

### admin/ManageAnnouncements.vue -- `ManageAnnouncements.test.ts` (7 tests)

- `renders without crashing`
- `displays the "Manage Announcements" heading`
- `shows the "Corner!" back link`
- `shows the "Add Announcement" create button`
- `shows empty state when no announcements exist`
- `renders the table header columns`
- `renders announcement data in the table`

### admin/ManageEightySixed.vue -- `ManageEightySixed.test.ts` (7 tests)

- `renders without crashing`
- `displays the "Manage 86'd Items" heading`
- `shows the "Corner!" back link`
- `shows the "86 an Item" create button`
- `shows empty state when no items are 86'd`
- `renders the table header columns`
- `renders 86'd item data in the table`

### admin/ManageLocations.vue -- `ManageLocations.test.ts` (6 tests)

- `renders without crashing and shows the page heading`
- `displays a navigation link back to /manage/daily`
- `hides the loading spinner after data is fetched`
- `shows empty-state message when no locations exist`
- `renders locations in the table with name and address`
- `displays table column headers for Name, Address, Timezone, Coordinates, Actions`

### admin/ManageLogsView.vue -- `ManageLogsView.test.ts` (6 tests)

- `renders without crashing and shows the page heading`
- `displays a navigation link back to /manage/daily`
- `hides the loading spinner after data is fetched`
- `shows empty-state message when no log entries exist`
- `renders log entries with their notes body and creator`
- `shows the "New Log Entry" button when the form is hidden`

### admin/ManageMenuItems.vue -- `ManageMenuItems.test.ts` (9 tests)

- `renders without crashing`
- `displays the "Manage Menu Items" heading`
- `shows the "Corner!" back link`
- `shows the "Add Item" create button`
- `shows empty state when no menu items exist`
- `renders the table header columns`
- `renders menu item data in the table`
- `fetches both menu items and categories on mount`

### admin/ManagePushItems.vue -- `ManagePushItems.test.ts` (7 tests)

- `renders without crashing`
- `displays the "Manage Push Items" heading`
- `shows the "Corner!" back link`
- `shows the "Add Push Item" create button`
- `shows empty state when no push items exist`
- `renders the table header columns`
- `renders push item data in the table`

### admin/ManageShiftDrops.vue -- `ManageShiftDrops.test.ts` (6 tests)

- `renders without crashing and shows the page heading`
- `displays a navigation link back to /manage/daily`
- `hides the loading spinner after data is fetched`
- `shows empty-state messages when no drops exist`
- `renders open drops with volunteers in the Open Drops section`
- `renders open drops without volunteers in the Waiting section`

### admin/ManageSpecials.vue -- `ManageSpecials.test.ts` (7 tests)

- `renders without crashing`
- `displays the "Manage Specials" heading`
- `shows the "Corner!" back link`
- `shows the "Add Special" create button`
- `shows empty state when no specials exist`
- `renders the table header columns`
- `renders special data in the table`

### admin/ManageTimeOff.vue -- `ManageTimeOff.test.ts` (6 tests)

- `renders without crashing and shows the page heading`
- `displays a navigation link back to /manage/daily`
- `hides the loading spinner after data is fetched`
- `shows empty-state message when no pending requests exist`
- `renders pending requests with staff name and action buttons`
- `shows the Resolved section header with count badge`

### admin/ManageUsers.vue -- `ManageUsers.test.ts` (9 tests)

- `renders without crashing`
- `displays the "Employees" heading`
- `shows the "Corner!" back link`
- `shows the "Add Employee" create button`
- `shows empty state when no employees exist`
- `renders the table header columns`
- `renders employee data in the table`
- `fetches users on mount`

### auth/LocationPickerView.vue -- `LocationPickerView.test.ts` (6 tests)

- `renders a button for each location in the store`
- `displays role badges on each location button`
- `calls switchLocation and redirects staff to /dashboard`
- `redirects managers to /manage/daily after picking a location`
- `displays error banner when switchLocation fails`
- `disables all buttons while a location switch is in progress`

### auth/LoginView.vue -- `LoginView.test.ts` (8 tests)

- `renders the login form with email, password, and submit button`
- `binds email and password inputs via v-model`
- `redirects staff to /dashboard on successful login`
- `redirects manager to /manage/daily on successful login`
- `redirects to /setup when user needs initial setup`
- `redirects to /pick-location when user has multiple locations`
- `displays error message on failed login`
- `disables the submit button and shows spinner while loading`

### auth/SetupView.vue -- `SetupView.test.ts` (6 tests)

- `renders name, city, state inputs and a submit button`
- `binds name, city, and state inputs via v-model`
- `posts to /api/setup and redirects to /manage/daily on success`
- `displays error banner when setup fails`
- `displays default error message when server provides no message`
- `disables submit button and shows spinner while loading`

### staff/EightySixedBoard.vue -- `EightySixedBoard.test.ts` (6 tests)

- `renders the page heading`
- `renders the Corner! back link to /dashboard`
- `shows empty state when no items exist`
- `renders item cards when data is returned`
- `shows loading text while fetching`
- `hides the add-item form for staff users`

### staff/MessagesView.vue -- `MessagesView.test.ts` (6 tests)

- `renders the page heading`
- `renders the Corner! back link to /dashboard`
- `renders both Board and Direct tab buttons`
- `defaults to the Board tab when no query param is set`
- `renders the Direct tab when query param tab=direct`

### staff/MyScheduleView.vue -- `MyScheduleView.test.ts` (6 tests)

- `renders the page heading`
- `renders schedule sub-navigation pill links`
- `pill links display correct labels`
- `renders the My Availability section heading`
- `shows empty schedule message when no schedule is published`
- `shows loading text while fetching`

### staff/ProfileView.vue -- `ProfileView.test.ts` (8 tests)

- `renders the page heading`
- `renders the Corner! back link to /dashboard`
- `displays the user name`
- `displays the user email`
- `renders profile field labels`
- `displays the location name`
- `renders the My Availability section heading`
- `renders the page subtitle`

### staff/ShiftDropBoardView.vue -- `ShiftDropBoardView.test.ts` (7 tests)

- `renders the page heading`
- `renders the page subtitle`
- `renders sub-navigation pill links`
- `pill links display correct labels`
- `renders both section headers`
- `shows loading text while fetching`
- `shows empty state messages when no drops exist`

### staff/SpecialsView.vue -- `SpecialsView.test.ts` (5 tests)

- `renders the page heading`
- `renders the Corner! back link to /dashboard`
- `shows empty state when no specials exist`
- `renders special cards when data is returned`
- `shows loading spinner while fetching`

### staff/TimeOffRequestView.vue -- `TimeOffRequestView.test.ts` (9 tests)

- `renders the page heading`
- `renders the page subtitle`
- `renders sub-navigation pill links`
- `pill links display correct labels`
- `renders the New Request form heading`
- `renders the request form with date inputs and submit button`
- `renders date field labels`
- `shows empty state when no requests exist`
- `shows loading text while fetching`

### staff/TonightsScheduleView.vue -- `TonightsScheduleView.test.ts` (7 tests)

- `renders the page heading`
- `renders the subtitle with today context`
- `renders sub-navigation pill links`
- `pill links display correct labels`
- `shows empty state when no shifts are scheduled today`
- `shows loading text while fetching`

---

## Router (1/1 -- 100%)

| File | Test File | Tests | Status |
|------|-----------|-------|--------|
| router/index.ts | index.test.ts | 12 | COVERED |

### router/index.ts -- `index.test.ts` (12 tests)

- `defines /login as a public route without requiresAuth`
- `defines staff routes with requiresAuth but no roles restriction`
- `defines management routes with roles restricted to admin and manager`
- `defines /admin/locations with roles restricted to admin only`
- `defines /pick-location and /setup as auth-required without roles`
- `redirects unauthenticated users to /login for protected routes`
- `allows authenticated users to reach protected routes`
- `calls fetchUser when token exists but user is null`
- `redirects to /login when fetchUser fails with an expired token`
- `redirects unauthorized roles to /dashboard for role-restricted routes`
- `allows admin users to access management routes`
- `allows navigation to /login without a token`

---

## Summary

| Category | Total Files | Tested | Untested | Coverage |
|----------|-------------|--------|----------|----------|
| Stores | 6 | 6 | 0 | 100% |
| Composables | 7 | 7 | 0 | 100% |
| Components | 30 | 30 | 0 | 100% |
| Views | 27 | 27 | 0 | 100% |
| Router | 1 | 1 | 0 | 100% |
| **Total** | **71** | **71** | **0** | **100%** |

| Metric | Value |
|--------|-------|
| Test files | 73 |
| Total test cases | 584 |
| All passing | Yes |
