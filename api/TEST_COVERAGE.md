# Test Coverage Report

**Generated:** 2026-02-24
**Test Suite:** 303 tests, 900 assertions
**Status:** All passing

---

## Auth

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `POST /api/login` | `AuthTest.php` | `test_user_can_login_with_valid_credentials`, `test_login_fails_with_invalid_credentials` | Covered |
| `POST /api/login` | `SmokeTest.php` | `test_login_returns_token`, `test_login_fails_with_bad_credentials` | Covered |
| `POST /api/login` | `LocationUserTest.php` | `test_login_response_includes_locations` | Covered |
| `POST /api/logout` | `AuthTest.php` | `test_user_can_logout` | Covered |
| `POST /api/logout` | `SmokeTest.php` | `test_logout_revokes_token` | Covered |
| `GET /api/user` | `AuthTest.php` | `test_authenticated_user_can_get_profile`, `test_unauthenticated_cannot_access_user` | Covered |
| `GET /api/user` | `SmokeTest.php` | `test_get_user_returns_authenticated_user` | Covered |
| `GET /api/user` | `LocationUserTest.php` | `test_get_user_response_includes_locations` | Covered |
| `POST /api/change-password` | `AuthTest.php` | `test_user_can_change_password`, `test_change_password_fails_with_wrong_current` | Covered |
| `POST /api/change-password` | `ConfigAndPasswordTest.php` | `test_change_password_succeeds_with_correct_current_password`, `test_change_password_fails_with_wrong_current_password`, `test_change_password_fails_when_confirmation_does_not_match`, `test_change_password_fails_when_too_short` | Covered |
| `PUT /api/profile` | `AuthTest.php` | `test_user_can_update_profile_name` | Covered |
| `PUT /api/profile` | `ProfileTest.php` | `test_unauthenticated_cannot_update_profile`, `test_update_name`, `test_update_availability`, `test_ignores_role_email_and_location_id`, `test_returns_location_relationship` | Covered |

## PreShift

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/preshift` | `PreShiftTest.php` | `test_staff_can_access_preshift`, `test_preshift_returns_location_scoped_data`, `test_preshift_returns_role_filtered_announcements`, `test_unauthenticated_user_cannot_access_preshift` | Covered |
| `GET /api/preshift` | `SmokeTest.php` | `test_preshift_returns_combined_data` | Covered |

## 86'd Items

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/eighty-sixed` | `SmokeTest.php` | `test_list_active_eighty_sixed` | Covered |
| `POST /api/eighty-sixed` | `SmokeTest.php` | `test_create_eighty_sixed_item`, `test_staff_cannot_create_eighty_sixed` | Covered |
| `PATCH /api/eighty-sixed/{id}` | `EightySixedTest.php` | `test_manager_can_update_eighty_sixed_item`, `test_staff_cannot_update_eighty_sixed_item`, `test_manager_cannot_update_other_locations_item` | Covered |
| `PATCH /api/eighty-sixed/{id}/restore` | `SmokeTest.php` | `test_restore_eighty_sixed_item` | Covered |

## Specials

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/specials` | `SpecialTest.php` | `test_manager_can_list_specials` | Covered |
| `POST /api/specials` | `SpecialTest.php` | `test_manager_can_create_special`, `test_staff_cannot_create_special` | Covered |
| `POST /api/specials` | `SmokeTest.php` | `test_specials_crud` | Covered |
| `PATCH /api/specials/{id}` | `SpecialTest.php` | `test_manager_can_update_special`, `test_cross_location_manager_cannot_update` | Covered |
| `PATCH /api/specials/{id}` | `MissingCoverageTest.php` | `test_manager_cannot_update_special_at_other_location` | Covered |
| `PATCH /api/specials/{id}/decrement` | `SpecialTest.php` | `test_manager_can_decrement_special_quantity` | Covered |
| `PATCH /api/specials/{id}/decrement` | `MissingCoverageTest.php` | `test_manager_can_decrement_special_quantity`, `test_decrement_returns_422_when_quantity_is_null`, `test_decrement_returns_422_when_quantity_is_zero`, `test_staff_cannot_decrement_special`, `test_manager_cannot_decrement_special_at_other_location` | Covered |
| `DELETE /api/specials/{id}` | `SpecialTest.php` | `test_manager_can_delete_special` | Covered |

## Push Items

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/push-items` | `PushItemTest.php` | `test_manager_can_list_push_items` | Covered |
| `POST /api/push-items` | `PushItemTest.php` | `test_manager_can_create_push_item`, `test_staff_cannot_create_push_item` | Covered |
| `POST /api/push-items` | `SmokeTest.php` | `test_push_items_crud` | Covered |
| `PATCH /api/push-items/{id}` | `PushItemTest.php` | `test_manager_can_update_push_item`, `test_cross_location_manager_cannot_update` | Covered |
| `DELETE /api/push-items/{id}` | `PushItemTest.php` | `test_manager_can_delete_push_item`, `test_staff_cannot_delete_push_item` | Covered |

## Announcements

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/announcements` | `AnnouncementTest.php` | `test_manager_can_list_announcements` | Covered |
| `POST /api/announcements` | `AnnouncementTest.php` | `test_manager_can_create_announcement`, `test_staff_cannot_create_announcement`, `test_role_targeted_announcements_filter_correctly` | Covered |
| `POST /api/announcements` | `SmokeTest.php` | `test_announcements_crud`, `test_announcements_filtered_by_role` | Covered |
| `PATCH /api/announcements/{id}` | `AnnouncementTest.php` | `test_manager_can_update_announcement`, `test_cross_location_manager_cannot_update` | Covered |
| `DELETE /api/announcements/{id}` | `AnnouncementTest.php` | `test_manager_can_delete_announcement` | Covered |

## Events

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/events` | `EventTest.php` | `test_manager_can_list_events`, `test_staff_can_list_events`, `test_user_cannot_see_other_locations_events` | Covered |
| `POST /api/events` | `EventTest.php` | `test_manager_can_create_event`, `test_staff_cannot_create_events` | Covered |
| `PATCH /api/events/{id}` | `EventTest.php` | `test_manager_can_update_event` | Covered |
| `DELETE /api/events/{id}` | `EventTest.php` | `test_manager_can_delete_event` | Covered |

## Acknowledgments

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `POST /api/acknowledge` | `SmokeTest.php` | `test_acknowledge_item` | Covered |
| `POST /api/acknowledge` | `AcknowledgmentBroadcastTest.php` | `test_acknowledge_dispatches_broadcast_event`, `test_acknowledge_all_items_broadcasts_100_percent` | Covered |
| `GET /api/acknowledgments/status` | `SmokeTest.php` | `test_acknowledgment_status` | Covered |
| `GET /api/acknowledgments/summary` | `AcknowledgmentSummaryTest.php` | `test_server_cannot_access_summary`, `test_manager_can_access_summary`, `test_summary_shows_zero_for_unacked_user`, `test_summary_shows_100_for_fully_acked_user`, `test_summary_returns_zero_percentage_when_no_items_exist` | Covered |

## Menu Items

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/menu-items` | `MenuAndLocationTest.php` | `test_staff_can_list_menu_items`, `test_can_filter_menu_items_by_category_id` | Covered |
| `POST /api/menu-items` | `MenuAndLocationTest.php` | `test_manager_can_create_menu_item`, `test_staff_cannot_create_menu_items` | Covered |
| `PATCH /api/menu-items/{id}` | `MenuAndLocationTest.php` | `test_manager_can_update_menu_item` | Covered |
| `DELETE /api/menu-items/{id}` | `MenuAndLocationTest.php` | `test_manager_can_delete_menu_item` | Covered |

## Categories

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/categories` | `MenuAndLocationTest.php` | `test_manager_can_list_categories` | Covered |
| `POST /api/categories` | `MenuAndLocationTest.php` | `test_manager_can_create_category` | Covered |
| `PATCH /api/categories/{id}` | `MenuAndLocationTest.php` | `test_manager_can_update_category` | Covered |
| `DELETE /api/categories/{id}` | `MenuAndLocationTest.php` | `test_manager_can_delete_category` | Covered |

## Shift Templates

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/shift-templates` | `SchedulingTest.php` | `test_manager_can_list_shift_templates` | Covered |
| `POST /api/shift-templates` | `SchedulingTest.php` | `test_manager_can_create_shift_template` | Covered |
| `PATCH /api/shift-templates/{id}` | `SchedulingTest.php` | `test_manager_can_update_shift_template` | Covered |
| `DELETE /api/shift-templates/{id}` | `SchedulingTest.php` | `test_manager_can_delete_shift_template` | Covered |
| `DELETE /api/shift-templates/{id}` | `MissingCoverageTest.php` | `test_manager_cannot_delete_shift_template_at_other_location` | Covered |

## Schedules

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/schedules` | `SchedulingTest.php` | `test_manager_can_list_schedules`, `test_staff_cannot_create_schedules` | Covered |
| `GET /api/schedules` | `TonightsScheduleTest.php` | `test_staff_can_list_schedules_for_their_location` | Covered |
| `GET /api/schedules/{id}` | `SchedulingTest.php` | `test_manager_can_view_schedule_with_entries`, `test_schedule_view_returns_iso_timestamp_dates` | Covered |
| `GET /api/schedules/{id}` | `TonightsScheduleTest.php` | `test_staff_can_fetch_published_schedule_with_entries`, `test_schedule_entries_are_scoped_to_location` | Covered |
| `POST /api/schedules` | `SchedulingTest.php` | `test_manager_can_create_schedule` | Covered |
| `PATCH /api/schedules/{id}` | `MissingCoverageTest.php` | `test_manager_can_update_schedule`, `test_manager_cannot_update_schedule_at_other_location` | Covered |
| `POST /api/schedules/{id}/publish` | `SchedulingTest.php` | `test_manager_can_publish_schedule` | Covered |
| `POST /api/schedules/{id}/publish` | `MissingCoverageTest.php` | `test_manager_cannot_publish_schedule_at_other_location` | Covered |
| `POST /api/schedules/{id}/unpublish` | `SchedulingTest.php` | `test_manager_can_unpublish_schedule` | Covered |
| `GET /api/my-shifts` | `SchedulingTest.php` | `test_staff_can_view_their_shifts` | Covered |
| `PUT /api/my-availability` | `SmokeTest.php` | `test_staff_can_update_own_availability`, `test_availability_rejects_invalid_slots`, `test_manager_can_set_user_availability` | Covered |

## Schedule Entries

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `POST /api/schedule-entries` | `SchedulingTest.php` | `test_manager_can_create_schedule_entry`, `test_duplicate_user_date_entry_returns_422`, `test_same_user_on_different_dates_is_allowed`, `test_different_users_on_same_date_is_allowed`, `test_duplicate_entry_returns_custom_error_message`, `test_duplicate_constraint_applies_across_schedules`, `test_staff_cannot_create_schedule_entries` | Covered |
| `POST /api/schedule-entries` | `TimeOffAutomationTest.php` | `test_schedule_entry_rejected_when_user_has_approved_time_off`, `test_schedule_entry_allowed_when_user_has_no_time_off`, `test_schedule_entry_allowed_when_time_off_is_pending` | Covered |
| `PATCH /api/schedule-entries/{id}` | `MissingCoverageTest.php` | `test_manager_can_update_schedule_entry`, `test_manager_cannot_update_schedule_entry_at_other_location` | Covered |
| `DELETE /api/schedule-entries/{id}` | `SchedulingTest.php` | `test_manager_can_delete_schedule_entry` | Covered |

## Shift Drops

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/shift-drops` | `MissingCoverageTest.php` | `test_manager_can_list_all_shift_drops`, `test_staff_sees_own_drops_and_eligible_open_drops`, `test_staff_cannot_see_other_location_shift_drops` | Covered |
| `GET /api/shift-drops` | `ShiftDropRoleTest.php` | `test_staff_sees_only_matching_role_drops`, `test_multi_role_user_sees_drops_for_all_roles` | Covered |
| `POST /api/shift-drops` | `SchedulingTest.php` | `test_staff_can_drop_own_shift`, `test_staff_cannot_drop_another_users_shift` | Covered |
| `POST /api/shift-drops` | `ShiftDropRoleTest.php` | `test_drop_notification_sent_to_eligible_same_role_staff`, `test_drop_notification_not_sent_to_wrong_role_staff`, `test_drop_notification_body_includes_role`, `test_requester_excluded_from_drop_notification` | Covered |
| `POST /api/shift-drops` | `NotificationTest.php` | `test_shift_drop_store_sends_notification_to_managers` | Covered |
| `POST /api/shift-drops/{id}/volunteer` | `SchedulingTest.php` | `test_staff_can_volunteer_for_open_drop`, `test_staff_cannot_volunteer_for_own_drop`, `test_staff_cannot_volunteer_if_different_role` | Covered |
| `POST /api/shift-drops/{id}/volunteer` | `ShiftDropRoleTest.php` | `test_multi_role_user_can_volunteer_for_either_role`, `test_single_role_user_cannot_volunteer_for_wrong_role`, `test_volunteer_notification_body_includes_role` | Covered |
| `POST /api/shift-drops/{id}/volunteer` | `NotificationTest.php` | `test_shift_drop_volunteer_sends_notification_to_managers` | Covered |
| `POST /api/shift-drops/{id}/select/{user}` | `SchedulingTest.php` | `test_manager_can_select_volunteer` | Covered |
| `POST /api/shift-drops/{id}/select/{user}` | `MissingCoverageTest.php` | `test_staff_cannot_select_volunteer` | Covered |
| `POST /api/shift-drops/{id}/cancel` | `SchedulingTest.php` | `test_staff_can_cancel_own_drop`, `test_staff_cannot_cancel_another_users_drop` | Covered |

## Time Off Requests

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/time-off-requests` | `SchedulingTest.php` | `test_staff_see_own_requests_managers_see_all` | Covered |
| `POST /api/time-off-requests` | `SchedulingTest.php` | `test_staff_can_submit_time_off_request` | Covered |
| `POST /api/time-off-requests` | `TimeOffAutomationTest.php` | `test_time_off_rejected_when_start_date_less_than_n_days_away`, `test_time_off_accepted_when_start_date_exactly_n_days_away`, `test_advance_notice_respects_custom_setting_value`, `test_advance_notice_defaults_to_14_when_setting_not_configured` | Covered |
| `POST /api/time-off-requests` | `NotificationTest.php` | `test_time_off_store_sends_notification_to_managers` | Covered |
| `POST /api/time-off-requests/{id}/approve` | `SchedulingTest.php` | `test_manager_can_approve_time_off_request`, `test_cannot_approve_already_resolved_request` | Covered |
| `POST /api/time-off-requests/{id}/deny` | `SchedulingTest.php` | `test_manager_can_deny_time_off_request` | Covered |

## Board Messages

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/board-messages` | `BoardMessageTest.php` | `test_staff_can_list_board_messages`, `test_staff_cannot_see_managers_only_posts`, `test_user_cannot_see_other_locations_posts` | Covered |
| `POST /api/board-messages` | `BoardMessageTest.php` | `test_staff_can_create_post`, `test_staff_can_create_reply` | Covered |
| `PATCH /api/board-messages/{id}` | `BoardMessageTest.php` | `test_author_can_edit_own_post`, `test_manager_can_edit_any_post`, `test_staff_cannot_edit_other_users_post` | Covered |
| `DELETE /api/board-messages/{id}` | `BoardMessageTest.php` | `test_author_can_delete_own_post` | Covered |
| `POST /api/board-messages/{id}/pin` | `BoardMessageTest.php` | `test_manager_can_pin_post`, `test_staff_cannot_pin_post` | Covered |

## Conversations

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/conversations` | `ConversationTest.php` | `test_user_can_list_conversations` | Covered |
| `POST /api/conversations` | `ConversationTest.php` | `test_find_or_create_returns_existing_conversation`, `test_find_or_create_creates_new_conversation`, `test_cannot_create_conversation_with_different_location_user`, `test_cannot_create_conversation_with_self` | Covered |
| `GET /api/conversations/unread-count` | `ConversationTest.php` | `test_unread_count_returns_correct_count` | Covered |
| `GET /api/conversations/unread-count` | `DirectMessageTest.php` | `test_unread_count_ignores_own_messages`, `test_unread_count_zero_after_reading` | Covered |
| `GET /api/conversations/{id}/messages` | `ConversationTest.php` | `test_participant_can_fetch_messages`, `test_non_participant_cannot_access_conversation`, `test_last_read_at_updates_when_fetching_messages` | Covered |
| `POST /api/conversations/{id}/messages` | `ConversationTest.php` | `test_participant_can_send_message` | Covered |
| `POST /api/conversations/{id}/messages` | `DirectMessageTest.php` | `test_unauthenticated_cannot_access_messages`, `test_non_participant_cannot_send_message`, `test_message_body_is_required`, `test_message_body_max_length`, `test_sending_message_broadcasts_event` | Covered |

## Users

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/users` | `ConversationTest.php` | `test_staff_can_list_location_users_for_dm_picker` | Covered |
| `GET /api/users` | `MenuAndLocationTest.php` | `test_manager_can_list_users_at_their_location` | Covered |
| `POST /api/users` | `MenuAndLocationTest.php` | `test_manager_can_create_user`, `test_auto_assigns_location_id_when_not_provided` | Covered |
| `POST /api/users` | `SmokeTest.php` | `test_manager_can_create_user`, `test_staff_cannot_manage_users` | Covered |
| `POST /api/users` | `ShiftDropRoleTest.php` | `test_manager_can_set_multi_role_via_user_update` | Covered |
| `PATCH /api/users/{id}` | `MenuAndLocationTest.php` | `test_manager_can_update_user` | Covered |
| `PATCH /api/users/{id}` | `SmokeTest.php` | `test_manager_can_update_user` | Covered |
| `DELETE /api/users/{id}` | `MenuAndLocationTest.php` | `test_manager_can_delete_user` | Covered |

## Manager Logs

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/manager-logs` | `ManagerLogTest.php` | `test_manager_can_list_logs`, `test_staff_cannot_access_manager_logs`, `test_cross_location_isolation` | Covered |
| `POST /api/manager-logs` | `ManagerLogTest.php` | `test_manager_can_create_log_with_snapshots`, `test_staff_cannot_create_manager_logs`, `test_duplicate_date_per_location_returns_422`, `test_snapshots_auto_populated_when_data_exists` | Covered |
| `PATCH /api/manager-logs/{id}` | `ManagerLogTest.php` | `test_manager_can_update_log_body` | Covered |
| `DELETE /api/manager-logs/{id}` | `ManagerLogTest.php` | `test_manager_can_delete_log` | Covered |

## Weather

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/weather` | `WeatherTest.php` | `test_returns_404_when_location_has_no_coordinates`, `test_returns_weather_data_when_coordinates_are_set`, `test_response_is_cached_for_30_minutes`, `test_requires_authentication`, `test_weather_is_scoped_to_users_location`, `test_manager_can_access_weather` | Covered |

## Notifications

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/notifications` | `NotificationTest.php` | `test_manager_can_list_notifications`, `test_staff_cannot_access_notification_endpoints` | Covered |
| `POST /api/notifications/{id}/read` | `NotificationTest.php` | `test_manager_can_mark_notification_read` | Covered |
| `POST /api/notifications/read-all` | `NotificationTest.php` | `test_manager_can_mark_all_notifications_read` | Covered |

## Config

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/config/settings` | `ConfigAndPasswordTest.php` | `test_get_settings_returns_all_settings` | Covered |
| `PUT /api/config/settings` | `ConfigAndPasswordTest.php` | `test_update_settings_saves_establishment_name`, `test_update_settings_validates_establishment_name` | Covered |
| `PUT /api/config/settings` | `TimeOffAutomationTest.php` | `test_superadmin_can_update_time_off_advance_days_setting` | Covered |
| `POST /api/config/initial-setup` | `ConfigAndPasswordTest.php` | `test_initial_setup_blocked_for_non_superadmin`, `test_initial_setup_validates_required_fields`, `test_initial_setup_wipes_data_and_creates_account`, `test_initial_setup_without_city_state_skips_geocoding` | Covered |
| `POST /api/config/reset` | `ConfigAndPasswordTest.php` | `test_full_reset_blocked_for_non_superadmin`, `test_full_reset_truncates_data_and_recreates_superadmin` | Covered |

## Setup

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `POST /api/setup` | `SetupTest.php` | `test_new_admin_can_create_first_establishment`, `test_non_admin_cannot_use_setup`, `test_admin_with_existing_location_cannot_setup_again`, `test_setup_requires_name_city_state`, `test_unauthenticated_cannot_setup` | Covered |

## Switch Location

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `POST /api/switch-location` | `SwitchLocationTest.php` | `test_user_can_switch_to_another_location`, `test_user_cannot_switch_to_location_without_membership`, `test_switch_to_nonexistent_location_fails_validation`, `test_unauthenticated_cannot_switch_location` | Covered |
| `POST /api/switch-location` | `LocationUserTest.php` | `test_switch_location_updates_user_record`, `test_switch_location_fails_without_membership` | Covered |

## Locations

| Endpoint | Test File | Test Methods | Status |
|----------|-----------|--------------|--------|
| `GET /api/locations` | `MenuAndLocationTest.php` | `test_admin_can_list_all_locations`, `test_manager_cannot_access_location_endpoints` | Covered |
| `POST /api/locations` | `MenuAndLocationTest.php` | `test_admin_can_create_location` | Covered |
| `PATCH /api/locations/{id}` | `MenuAndLocationTest.php` | `test_admin_can_update_location` | Covered |
| `GET /api/locations` | `SmokeTest.php` | `test_non_admin_cannot_manage_locations` | Covered |

---

## Middleware

### `auth:sanctum`

| Middleware | Test File | Test Methods | Status |
|-----------|-----------|--------------|--------|
| Unauthenticated rejected | `AuthTest.php` | `test_unauthenticated_cannot_access_user` | Covered |
| Unauthenticated rejected | `PreShiftTest.php` | `test_unauthenticated_user_cannot_access_preshift` | Covered |
| Unauthenticated rejected | `WeatherTest.php` | `test_requires_authentication` | Covered |
| Unauthenticated rejected | `DirectMessageTest.php` | `test_unauthenticated_cannot_access_messages` | Covered |
| Unauthenticated rejected | `ProfileTest.php` | `test_unauthenticated_cannot_update_profile` | Covered |
| Unauthenticated rejected | `SwitchLocationTest.php` | `test_unauthenticated_cannot_switch_location` | Covered |
| Unauthenticated rejected | `SetupTest.php` | `test_unauthenticated_cannot_setup` | Covered |

### `role` (CheckRole middleware)

| Middleware | Test File | Test Methods | Status |
|-----------|-----------|--------------|--------|
| Role allows matching | `MiddlewareTest.php` | `test_check_role_allows_user_with_matching_role` | Covered |
| Role rejects non-matching | `MiddlewareTest.php` | `test_check_role_rejects_user_with_non_matching_role` | Covered |
| Role allows any of multiple | `MiddlewareTest.php` | `test_check_role_allows_user_when_role_matches_any_of_multiple` | Covered |
| Role rejects unauthenticated | `MiddlewareTest.php` | `test_check_role_rejects_unauthenticated_request` | Covered |
| Staff blocked from manager routes | `SchedulingTest.php` | `test_staff_cannot_create_schedules`, `test_staff_cannot_create_schedule_entries` | Covered |
| Staff blocked from manager routes | `SpecialTest.php` | `test_staff_cannot_create_special` | Covered |
| Staff blocked from manager routes | `PushItemTest.php` | `test_staff_cannot_create_push_item`, `test_staff_cannot_delete_push_item` | Covered |
| Staff blocked from manager routes | `AnnouncementTest.php` | `test_staff_cannot_create_announcement` | Covered |
| Staff blocked from manager routes | `EventTest.php` | `test_staff_cannot_create_events` | Covered |
| Staff blocked from manager routes | `NotificationTest.php` | `test_staff_cannot_access_notification_endpoints` | Covered |
| Staff blocked from manager routes | `ManagerLogTest.php` | `test_staff_cannot_access_manager_logs`, `test_staff_cannot_create_manager_logs` | Covered |
| Staff blocked from admin routes | `SmokeTest.php` | `test_non_admin_cannot_manage_locations`, `test_staff_cannot_manage_users` | Covered |
| Staff blocked from admin routes | `MenuAndLocationTest.php` | `test_manager_cannot_access_location_endpoints` | Covered |
| Staff blocked from select volunteer | `MissingCoverageTest.php` | `test_staff_cannot_select_volunteer` | Covered |

### `location` (EnsureLocationAccess middleware)

| Middleware | Test File | Test Methods | Status |
|-----------|-----------|--------------|--------|
| Allows admin without location_id | `MiddlewareTest.php` | `test_location_middleware_allows_admin_without_location_id` | Covered |
| Rejects non-admin without location_id | `MiddlewareTest.php` | `test_location_middleware_rejects_non_admin_without_location_id` | Covered |
| Allows non-admin with location_id | `MiddlewareTest.php` | `test_location_middleware_allows_non_admin_with_location_id` | Covered |
| Rejects unauthenticated | `MiddlewareTest.php` | `test_location_middleware_rejects_unauthenticated_request` | Covered |
| Cross-location data isolation | `SmokeTest.php` | `test_cannot_see_other_locations_data` | Covered |
| Cross-location data isolation | `PreShiftTest.php` | `test_preshift_returns_location_scoped_data` | Covered |
| Cross-location data isolation | `EightySixedTest.php` | `test_manager_cannot_update_other_locations_item` | Covered |
| Cross-location data isolation | `SpecialTest.php` | `test_cross_location_manager_cannot_update` | Covered |
| Cross-location data isolation | `PushItemTest.php` | `test_cross_location_manager_cannot_update` | Covered |
| Cross-location data isolation | `AnnouncementTest.php` | `test_cross_location_manager_cannot_update` | Covered |
| Cross-location data isolation | `EventTest.php` | `test_user_cannot_see_other_locations_events` | Covered |
| Cross-location data isolation | `BoardMessageTest.php` | `test_user_cannot_see_other_locations_posts` | Covered |
| Cross-location data isolation | `ManagerLogTest.php` | `test_cross_location_isolation` | Covered |
| Cross-location data isolation | `WeatherTest.php` | `test_weather_is_scoped_to_users_location` | Covered |
| Cross-location data isolation | `ConversationTest.php` | `test_cannot_create_conversation_with_different_location_user` | Covered |

### `superadmin` (SuperAdminMiddleware)

| Middleware | Test File | Test Methods | Status |
|-----------|-----------|--------------|--------|
| Blocks non-superadmin | `ConfigAndPasswordTest.php` | `test_superadmin_middleware_blocks_non_superadmin`, `test_initial_setup_blocked_for_non_superadmin`, `test_full_reset_blocked_for_non_superadmin` | Covered |
| Allows superadmin | `ConfigAndPasswordTest.php` | `test_superadmin_middleware_allows_superadmin` | Covered |

---

## Broadcasting / Events

| Event | Test File | Test Methods | Status |
|-------|-----------|--------------|--------|
| Acknowledgment broadcast | `AcknowledgmentBroadcastTest.php` | `test_acknowledge_dispatches_broadcast_event`, `test_acknowledge_all_items_broadcasts_100_percent` | Covered |
| DirectMessage broadcast | `DirectMessageTest.php` | `test_sending_message_broadcasts_event` | Covered |
| ShiftDrop notification dispatch | `NotificationTest.php` | `test_shift_drop_store_sends_notification_to_managers`, `test_shift_drop_volunteer_sends_notification_to_managers` | Covered |
| ShiftDrop notification (role-scoped) | `ShiftDropRoleTest.php` | `test_drop_notification_sent_to_eligible_same_role_staff`, `test_drop_notification_not_sent_to_wrong_role_staff`, `test_drop_notification_body_includes_role`, `test_volunteer_notification_body_includes_role`, `test_multi_role_staff_receives_notification_via_roles_json`, `test_requester_excluded_from_drop_notification` | Covered |
| TimeOff notification dispatch | `NotificationTest.php` | `test_time_off_store_sends_notification_to_managers` | Covered |
| Notification not sent to staff | `NotificationTest.php` | `test_notifications_not_sent_to_staff` | Covered |
| Email alerts conditional dispatch | `NotificationTest.php` | `test_email_sent_when_email_alerts_enabled`, `test_email_not_sent_when_email_alerts_disabled` | Covered |

---

## Policies

| Policy | Tested Via | Status |
|--------|-----------|--------|
| `AnnouncementPolicy` | `AnnouncementTest.php` (cross-location update blocked) | Covered |
| `BoardMessagePolicy` | `BoardMessageTest.php` (author edit, manager edit, staff blocked, delete) | Covered |
| `CategoryPolicy` | `MenuAndLocationTest.php` (manager CRUD) | Covered |
| `ConversationPolicy` | `ConversationTest.php` (non-participant blocked, location check) | Covered |
| `EightySixedPolicy` | `EightySixedTest.php` (staff blocked, cross-location blocked) | Covered |
| `EventPolicy` | `EventTest.php` (staff blocked from create, cross-location blocked) | Covered |
| `ManagerLogPolicy` | `ManagerLogTest.php` (staff blocked, cross-location isolation) | Covered |
| `MenuItemPolicy` | `MenuAndLocationTest.php` (staff blocked from create) | Covered |
| `PushItemPolicy` | `PushItemTest.php` (staff blocked, cross-location blocked) | Covered |
| `SchedulePolicy` | `SchedulingTest.php` (staff blocked), `MissingCoverageTest.php` (cross-location blocked) | Covered |
| `ScheduleEntryPolicy` | `SchedulingTest.php` (staff blocked), `MissingCoverageTest.php` (cross-location blocked) | Covered |
| `ShiftDropPolicy` | `SchedulingTest.php` (own drop only, volunteer rules), `ShiftDropRoleTest.php` (role filtering) | Covered |
| `ShiftTemplatePolicy` | `SchedulingTest.php` (manager CRUD), `MissingCoverageTest.php` (cross-location blocked) | Covered |
| `SpecialPolicy` | `SpecialTest.php` (staff blocked, cross-location blocked) | Covered |
| `TimeOffRequestPolicy` | `SchedulingTest.php` (approve/deny, already resolved), `TimeOffAutomationTest.php` (advance notice) | Covered |
| `UserPolicy` | `MenuAndLocationTest.php` (manager CRUD), `SmokeTest.php` (staff blocked) | Covered |

---

## Unit Tests

| Test File | Test Methods | Description |
|-----------|--------------|-------------|
| `MiddlewareTest.php` | `test_check_role_allows_user_with_matching_role`, `test_check_role_rejects_user_with_non_matching_role`, `test_check_role_allows_user_when_role_matches_any_of_multiple`, `test_check_role_rejects_unauthenticated_request`, `test_location_middleware_rejects_unauthenticated_request`, `test_location_middleware_allows_admin_without_location_id`, `test_location_middleware_rejects_non_admin_without_location_id`, `test_location_middleware_allows_non_admin_with_location_id` | CheckRole and EnsureLocationAccess middleware unit tests |
| `ModelTest.php` | 30 tests covering all model relationships, casts, fillable, hidden attributes, and scopes | All Eloquent model unit tests |

---

## Multi-Location User Support

| Feature | Test File | Test Methods | Status |
|---------|-----------|--------------|--------|
| User-locations pivot with role | `LocationUserTest.php` | `test_user_locations_relationship_returns_pivot_with_role`, `test_location_members_relationship_returns_pivot_with_role` | Covered |
| Switch location updates user | `LocationUserTest.php` | `test_switch_location_updates_user_record` | Covered |
| Switch fails without membership | `LocationUserTest.php` | `test_switch_location_fails_without_membership` | Covered |
| Needs setup detection | `LocationUserTest.php` | `test_needs_setup_true_for_admin_without_memberships`, `test_needs_setup_false_for_admin_with_memberships`, `test_needs_setup_false_for_non_admin` | Covered |
| Login includes locations | `LocationUserTest.php` | `test_login_response_includes_locations` | Covered |
| GET /api/user includes locations | `LocationUserTest.php` | `test_get_user_response_includes_locations` | Covered |
| Multi-role via user update | `ShiftDropRoleTest.php` | `test_manager_can_set_multi_role_via_user_update` | Covered |
| Multi-role drop visibility | `ShiftDropRoleTest.php` | `test_multi_role_user_sees_drops_for_all_roles`, `test_multi_role_user_can_volunteer_for_either_role` | Covered |

---

## Summary

| Domain | Endpoints | Test Files | Status |
|--------|-----------|------------|--------|
| Auth | 6 | `AuthTest.php`, `SmokeTest.php`, `LocationUserTest.php`, `ConfigAndPasswordTest.php`, `ProfileTest.php` | All Covered |
| PreShift | 1 | `PreShiftTest.php`, `SmokeTest.php` | All Covered |
| 86'd Items | 4 | `EightySixedTest.php`, `SmokeTest.php` | All Covered |
| Specials | 5 | `SpecialTest.php`, `SmokeTest.php`, `MissingCoverageTest.php` | All Covered |
| Push Items | 4 | `PushItemTest.php`, `SmokeTest.php` | All Covered |
| Announcements | 4 | `AnnouncementTest.php`, `SmokeTest.php` | All Covered |
| Events | 4 | `EventTest.php` | All Covered |
| Acknowledgments | 3 | `SmokeTest.php`, `AcknowledgmentSummaryTest.php`, `AcknowledgmentBroadcastTest.php` | All Covered |
| Menu Items | 4 | `MenuAndLocationTest.php` | All Covered |
| Categories | 4 | `MenuAndLocationTest.php` | All Covered |
| Shift Templates | 4 | `SchedulingTest.php`, `MissingCoverageTest.php` | All Covered |
| Schedules | 7 | `SchedulingTest.php`, `MissingCoverageTest.php`, `TonightsScheduleTest.php` | All Covered |
| Schedule Entries | 3 | `SchedulingTest.php`, `MissingCoverageTest.php`, `TimeOffAutomationTest.php` | All Covered |
| Shift Drops | 5 | `SchedulingTest.php`, `ShiftDropRoleTest.php`, `MissingCoverageTest.php`, `NotificationTest.php` | All Covered |
| Time Off Requests | 4 | `SchedulingTest.php`, `TimeOffAutomationTest.php`, `NotificationTest.php` | All Covered |
| Board Messages | 5 | `BoardMessageTest.php` | All Covered |
| Conversations & DMs | 5 | `ConversationTest.php`, `DirectMessageTest.php` | All Covered |
| Users | 4 | `MenuAndLocationTest.php`, `SmokeTest.php`, `ConversationTest.php`, `ShiftDropRoleTest.php` | All Covered |
| Manager Logs | 4 | `ManagerLogTest.php` | All Covered |
| Weather | 1 | `WeatherTest.php` | All Covered |
| Notifications | 3 | `NotificationTest.php` | All Covered |
| Config | 4 | `ConfigAndPasswordTest.php`, `TimeOffAutomationTest.php` | All Covered |
| Setup | 1 | `SetupTest.php` | All Covered |
| Switch Location | 1 | `SwitchLocationTest.php`, `LocationUserTest.php` | All Covered |
| Locations | 3 | `MenuAndLocationTest.php`, `SmokeTest.php` | All Covered |
| My Availability | 1 | `SmokeTest.php` | All Covered |
| **TOTAL** | **89** | **32 test files** | **All Covered** |

**Test Suite Totals:** 303 tests, 900 assertions -- all passing
