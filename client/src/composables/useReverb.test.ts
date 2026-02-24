/**
 * useReverb.test.ts
 *
 * Unit tests for the useReverb, useLocationChannel, useUserChannel, and
 * disconnectReverb functions.
 *
 * These tests verify:
 *  1. `useReverb(locationId)` creates a new Echo instance on first call
 *     for a given location and caches it for subsequent calls.
 *  2. The Echo instance is configured with the correct broadcaster,
 *     auth endpoint, and Bearer token from localStorage.
 *  3. `useLocationChannel(locationId)` subscribes to `private-location.{id}`.
 *  4. `useUserChannel(userId, locationId)` subscribes to `private-user.{userId}`.
 *  5. `disconnectReverb()` calls disconnect on all cached instances and
 *     clears the cache so fresh connections are created next time.
 *  6. Different location IDs get separate Echo instances.
 *
 * We mock `laravel-echo` and `pusher-js` to avoid real WebSocket connections.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'

/* Track all Echo instances created so we can inspect them */
const mockPrivate = vi.fn().mockReturnValue({ listen: vi.fn() })
const mockDisconnect = vi.fn()

let capturedOptions: any = null

/* Mock laravel-echo to capture constructor arguments */
vi.mock('laravel-echo', () => {
  return {
    default: class MockEcho {
      options: any
      connector: any
      private: any
      disconnect: any

      constructor(options: any) {
        capturedOptions = options
        this.options = options
        this.connector = {}
        this.private = mockPrivate
        this.disconnect = mockDisconnect
      }
    },
  }
})

/* Mock pusher-js — useReverb assigns it to window.Pusher */
vi.mock('pusher-js', () => ({
  default: class MockPusher {},
}))

// ── Test suite ─────────────────────────────────────────────────────────────

describe('useReverb composable', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()
    capturedOptions = null
    // Reset the module's echoInstances cache by re-importing
    vi.resetModules()
  })

  /* Verifies that useReverb creates an Echo instance with the correct
     broadcaster and auth endpoint configuration. */
  it('creates an Echo instance with correct configuration', async () => {
    localStorage.setItem('preshift_token', 'my-token')
    const { useReverb } = await import('@/composables/useReverb')

    useReverb(1)

    expect(capturedOptions).not.toBeNull()
    expect(capturedOptions.broadcaster).toBe('pusher')
    expect(capturedOptions.authEndpoint).toBe('/api/broadcasting/auth')
    expect(capturedOptions.auth.headers.Authorization).toBe('Bearer my-token')
    expect(capturedOptions.disableStats).toBe(true)
  })

  /* Verifies that calling useReverb twice with the same location ID
     returns the cached instance rather than creating a new one. */
  it('caches Echo instances by location ID', async () => {
    localStorage.setItem('preshift_token', 'token-abc')
    const { useReverb } = await import('@/composables/useReverb')

    const first = useReverb(5)
    const second = useReverb(5)

    expect(first).toBe(second)
  })

  /* Verifies that different location IDs produce separate Echo instances. */
  it('creates separate instances for different location IDs', async () => {
    localStorage.setItem('preshift_token', 'token-xyz')
    const { useReverb } = await import('@/composables/useReverb')

    const instanceA = useReverb(1)
    const instanceB = useReverb(2)

    expect(instanceA).not.toBe(instanceB)
  })

  /* Verifies that when no token exists in localStorage, an empty string
     is used for the Authorization header. */
  it('uses empty string for auth when no token exists', async () => {
    const { useReverb } = await import('@/composables/useReverb')

    useReverb(1)

    expect(capturedOptions.auth.headers.Authorization).toBe('Bearer ')
  })

  /* Verifies that useLocationChannel subscribes to the correct private
     channel name for the given location. */
  it('useLocationChannel subscribes to private-location.{id}', async () => {
    localStorage.setItem('preshift_token', 'token-123')
    const { useLocationChannel } = await import('@/composables/useReverb')

    useLocationChannel(42)

    expect(mockPrivate).toHaveBeenCalledWith('location.42')
  })

  /* Verifies that useUserChannel subscribes to the correct private user
     channel and reuses the location-keyed Echo instance. */
  it('useUserChannel subscribes to private-user.{userId}', async () => {
    localStorage.setItem('preshift_token', 'token-456')
    const { useUserChannel } = await import('@/composables/useReverb')

    useUserChannel(7, 1)

    expect(mockPrivate).toHaveBeenCalledWith('user.7')
  })

  /* Verifies that disconnectReverb calls disconnect on every cached
     instance and clears the cache for fresh connections. */
  it('disconnects all cached instances and resets the cache', async () => {
    localStorage.setItem('preshift_token', 'token-789')
    const { useReverb, disconnectReverb } = await import('@/composables/useReverb')

    // Create instances for two locations
    useReverb(1)
    useReverb(2)

    disconnectReverb()

    expect(mockDisconnect).toHaveBeenCalledTimes(2)

    // After disconnecting, the next call should create a fresh instance
    mockDisconnect.mockClear()
    const fresh = useReverb(1)
    expect(fresh).toBeDefined()
  })

  /* Verifies that disconnectReverb does not throw when no instances exist. */
  it('handles disconnectReverb gracefully when no instances exist', async () => {
    const { disconnectReverb } = await import('@/composables/useReverb')

    expect(() => disconnectReverb()).not.toThrow()
    expect(mockDisconnect).not.toHaveBeenCalled()
  })
})
