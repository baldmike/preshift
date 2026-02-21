# PreShift — Reverb Events

> All events broadcast on private channel: `private-location.{location_id}`. Events include the FULL resource payload so the client can update Pinia stores directly without re-fetching.

---

## Pre-Shift Content Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `ItemEightySixed` | Item is 86'd | eighty_sixed record |
| `ItemRestored` | 86 is lifted | eighty_sixed record |
| `SpecialCreated` | New special added | special record |
| `SpecialUpdated` | Special modified | special record |
| `SpecialDeleted` | Special removed | special id |
| `PushItemCreated` | New push item | push_item record |
| `PushItemUpdated` | Push item modified | push_item record |
| `PushItemDeleted` | Push item removed | push_item id |
| `AnnouncementPosted` | New announcement | announcement record |
| `AnnouncementUpdated` | Announcement modified | announcement record |
| `AnnouncementDeleted` | Announcement removed | announcement id |

---

## Scheduling Events

| Event | Trigger | Payload |
|-------|---------|---------|
| `SchedulePublished` | Manager publishes a schedule | schedule record with entries |
| `ShiftDropRequested` | Staff requests to drop a shift | shift_drop record (manager sees) |
| `ShiftDropApproved` | Manager approves drop | shift_drop record (broadcasts to eligible same-role staff) |
| `ShiftDropDenied` | Manager denies drop request | shift_drop record (requester notified) |
| `ShiftDropVolunteer` | Someone volunteers to pick up | shift_drop_volunteer record (manager sees) |
| `ShiftDropFilled` | Manager selects volunteer, shift reassigned | shift_drop record (both parties notified) |
| `TimeOffResolved` | Manager approves/denies time-off | time_off_request record |
