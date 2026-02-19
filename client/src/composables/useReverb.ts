/**
 * composables/useReverb.ts
 *
 * Sets up Laravel Echo with the Pusher driver to connect to a Laravel Reverb
 * WebSocket server.  Reverb is Laravel's first-party WebSocket server that
 * is wire-compatible with the Pusher protocol, so we use the `pusher-js`
 * client library under the hood.
 *
 * Architecture:
 *  - One Echo instance is created per location (keyed by `locationId`) so
 *    that each location gets its own WebSocket connection and private channel.
 *  - Instances are cached in `echoInstances` to avoid creating duplicate
 *    connections when multiple components subscribe to the same location.
 *  - The Bearer token is read from localStorage at connection time and sent
 *    to `/broadcasting/auth` so Reverb can authorize private channel access.
 *
 * Environment variables used (set in `.env` or Vite config):
 *  - VITE_REVERB_APP_KEY  : Pusher-compatible app key
 *  - VITE_REVERB_HOST     : WebSocket server hostname (default: 127.0.0.1)
 *  - VITE_REVERB_PORT     : WebSocket server port (default: 8080)
 */

import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Augment the global Window interface so TypeScript knows about the Pusher
// and Echo properties that Laravel Echo expects to find on `window`.
declare global {
  interface Window {
    Pusher: typeof Pusher
    Echo: Echo<'pusher'>
  }
}

// Laravel Echo requires `window.Pusher` to be set before instantiation.
window.Pusher = Pusher

/**
 * Cache of Echo instances keyed by location ID.
 * This prevents creating multiple WebSocket connections for the same location
 * when several components call `useReverb()` with the same ID.
 */
let echoInstances: Record<number, Echo<'pusher'>> = {}

/**
 * Returns (or creates) a Laravel Echo instance for the given location.
 *
 * On first call for a given `locationId`, a new Echo/Pusher connection is
 * established using environment-driven configuration.  Subsequent calls
 * return the cached instance.
 *
 * @param locationId - The Location primary key to scope the connection to
 * @returns A Laravel Echo instance connected to the Reverb WebSocket server
 */
export function useReverb(locationId: number): Echo<'pusher'> {
  // Return the cached instance if one already exists for this location
  if (echoInstances[locationId]) {
    return echoInstances[locationId]
  }

  // Read the Bearer token for authenticating private channels
  const token = localStorage.getItem('preshift_token') || ''

  // Create a new Echo instance configured for the Pusher protocol over Reverb
  const echo = new Echo({
    broadcaster: 'pusher',                                              // Use the Pusher-compatible driver
    key: import.meta.env.VITE_REVERB_APP_KEY || 'preshift-key',        // Reverb app key
    wsHost: import.meta.env.VITE_REVERB_HOST || '127.0.0.1',           // WebSocket server host
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,                  // Non-TLS WebSocket port
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,                 // TLS WebSocket port (same in dev)
    forceTLS: false,                                                    // Disable forced TLS for local dev
    disableStats: true,                                                 // Do not send stats to Pusher
    cluster: 'mt1',                                                     // Required by Pusher SDK but unused by Reverb
    enabledTransports: ['ws', 'wss'],                                   // Allow both ws and wss transports
    authEndpoint: '/broadcasting/auth',                                 // Laravel endpoint that authorises private channel subscriptions
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,                               // Attach the JWT for channel auth
      },
    },
  })

  // Cache the instance so subsequent calls reuse it
  echoInstances[locationId] = echo
  return echo
}

/**
 * Convenience helper that returns a private channel scoped to a specific
 * location.  The channel name follows the convention `private-location.{id}`
 * which must match the Laravel `BroadcastServiceProvider` channel definition.
 *
 * @param locationId - The Location primary key
 * @returns A Pusher private channel instance for listening to location events
 */
export function useLocationChannel(locationId: number) {
  const echo = useReverb(locationId)
  // Subscribe to the private channel named "location.{id}"
  return echo.private(`location.${locationId}`)
}

/**
 * Disconnects all active Echo/WebSocket connections and clears the instance
 * cache.  Should be called on logout to clean up resources and stop
 * receiving realtime events.
 */
export function disconnectReverb() {
  Object.values(echoInstances).forEach((echo) => {
    echo.disconnect()
  })
  // Reset the cache so fresh connections are created on next login
  echoInstances = {}
}
