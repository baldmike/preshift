<?php

/**
 * Broadcast Channel Authorization
 *
 * This file defines the authorization callbacks for private and presence
 * WebSocket channels. When a client attempts to subscribe to a private channel
 * (e.g., via Laravel Echo), Laravel sends an auth request that runs through
 * these callbacks. The callback must return `true` (or a truthy value) for the
 * subscription to be allowed, or `false` to deny it.
 *
 * All broadcast events in this application use private channels, meaning every
 * subscription attempt is verified here before the client receives any data.
 */

use Illuminate\Support\Facades\Broadcast;

/**
 * Private user channel: "App.Models.User.{id}"
 *
 * Authorizes a user to listen on their own personal channel. This is the
 * default Laravel convention for per-user notifications (e.g., Whisper events
 * or direct messages). The callback simply checks that the authenticated
 * user's ID matches the channel's {id} parameter, preventing users from
 * eavesdropping on other users' private channels.
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Private location channel: "location.{locationId}"
 *
 * This is the primary channel used by all broadcast events in this app
 * (ItemEightySixed, ItemRestored, SpecialCreated/Updated/Deleted,
 * PushItemCreated/Updated/Deleted, AnnouncementPosted/Updated/Deleted).
 *
 * Authorization logic:
 *   - Admins can subscribe to ANY location channel (they oversee all locations).
 *   - Non-admin users (managers, servers, bartenders) can only subscribe to
 *     the channel matching their own location_id.
 *
 * This ensures that staff at Location A never receive real-time events from
 * Location B, maintaining data isolation between venues.
 */
Broadcast::channel('location.{locationId}', function ($user, $locationId) {
    return $user->isAdmin() || (int) $user->location_id === (int) $locationId;
});
