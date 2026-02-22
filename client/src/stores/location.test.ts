/**
 * stores/location.test.ts
 *
 * Unit tests for the useLocationStore Pinia store.
 *
 * These tests verify:
 *  1. Initial state has empty `locations` and null `current`.
 *  2. `fetchLocations()` calls the correct API endpoint and populates the
 *     `locations` array from the response.
 *  3. `setCurrent()` sets the `current` ref to the provided Location object.
 *  4. The `locations` array is reactive -- pushing an item increases its
 *     length as expected.
 *  5. Calling `setCurrent()` with a different Location replaces the
 *     previously set value.
 *
 * We mock `@/composables/useApi` so no real HTTP requests are made.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useLocationStore } from '@/stores/location'
import type { Location } from '@/types'

// ── Mock the API module ────────────────────────────────────────────────────
// Replace the default export of `@/composables/useApi` with an object whose
// `post` and `get` methods are Vitest spies.  This prevents real HTTP calls
// and lets us control the resolved values for each test.
vi.mock('@/composables/useApi', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

// ── Helpers ────────────────────────────────────────────────────────────────

/**
 * Creates a valid Location object with sensible defaults.
 * Pass an `overrides` partial to customise individual fields.
 * This keeps each test focused on only the fields it cares about.
 */
function makeLocation(overrides: Partial<Location> = {}): Location {
  return {
    id: Math.floor(Math.random() * 10000),
    name: 'Test Location',
    address: '123 Main St',
    timezone: 'America/New_York',
    latitude: null,
    longitude: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

// ── Test suite ─────────────────────────────────────────────────────────────

describe('useLocationStore', () => {
  /**
   * Before every test we create a fresh Pinia instance and clear all mock
   * call history.  This ensures each test starts with a clean store and
   * no leftover spy state from previous tests.
   */
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  // ── Initial state ───────────────────────────────────────────────────────

  describe('initial state', () => {
    /**
     * When the store is created, `locations` should be an empty array and
     * `current` should be null.  No data is loaded until `fetchLocations()`
     * is explicitly called, and no location is selected until `setCurrent()`
     * is invoked.
     */
    it('has empty locations array and null current', () => {
      const store = useLocationStore()

      // The locations list should start empty
      expect(store.locations).toEqual([])

      // No location should be selected initially
      expect(store.current).toBeNull()
    })
  })

  // ── fetchLocations() ───────────────────────────────────────────────────

  describe('fetchLocations()', () => {
    /**
     * `fetchLocations()` should call `GET /api/locations` and populate
     * the `locations` array with the response data.  This endpoint
     * returns a flat array of Location objects.
     */
    it('sets locations from API response', async () => {
      const api = (await import('@/composables/useApi')).default

      // Prepare two fake locations that the API would return
      const fakeLocations = [
        makeLocation({ id: 1, name: 'Downtown Bistro' }),
        makeLocation({ id: 2, name: 'Uptown Lounge' }),
      ]

      // Configure the mock to resolve with the fake locations
      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: fakeLocations,
      })

      const store = useLocationStore()

      // Verify locations start empty before the fetch
      expect(store.locations).toHaveLength(0)

      // Fetch locations from the API
      await store.fetchLocations()

      // Verify the correct endpoint was called
      expect(api.get).toHaveBeenCalledWith('/api/locations')

      // Verify the store was populated with the API response
      expect(store.locations).toHaveLength(2)
      expect(store.locations[0].name).toBe('Downtown Bistro')
      expect(store.locations[1].name).toBe('Uptown Lounge')
    })
  })

  // ── setCurrent() ──────────────────────────────────────────────────────

  describe('setCurrent()', () => {
    /**
     * `setCurrent()` sets the `current` ref to the provided Location
     * object.  This is used by components to track which location the
     * user is currently viewing or managing, avoiding prop drilling.
     */
    it('sets the current location', () => {
      const store = useLocationStore()
      const location = makeLocation({ id: 5, name: 'Midtown Grill' })

      // Initially no location is selected
      expect(store.current).toBeNull()

      // Select a location
      store.setCurrent(location)

      // The current location should now be set
      expect(store.current).toEqual(location)
      expect(store.current!.id).toBe(5)
      expect(store.current!.name).toBe('Midtown Grill')
    })

    /**
     * Calling `setCurrent()` with a different location should fully
     * replace the previously selected location.  There is no merging or
     * stacking -- only one location can be "current" at a time.
     */
    it('replaces the previous current location when called again', () => {
      const store = useLocationStore()

      // Select the first location
      const first = makeLocation({ id: 1, name: 'First Location' })
      store.setCurrent(first)
      expect(store.current!.name).toBe('First Location')

      // Select a different location -- should replace, not merge
      const second = makeLocation({ id: 2, name: 'Second Location' })
      store.setCurrent(second)

      // Verify the current location is now the second one
      expect(store.current!.id).toBe(2)
      expect(store.current!.name).toBe('Second Location')

      // The first location should no longer be referenced
      expect(store.current!.id).not.toBe(1)
    })
  })

  // ── Reactivity ──────────────────────────────────────────────────────────

  describe('reactivity', () => {
    /**
     * The `locations` array is a Vue `ref`, so pushing items into it
     * should be reactive and the length should update accordingly.
     * This test verifies that direct mutations to the array work as
     * expected within the Pinia store context.
     */
    it('locations array is reactive (push updates length)', () => {
      const store = useLocationStore()

      // Start with zero locations
      expect(store.locations).toHaveLength(0)

      // Push a location directly onto the reactive array
      const loc1 = makeLocation({ id: 1, name: 'Location A' })
      store.locations.push(loc1)

      // Length should update reactively
      expect(store.locations).toHaveLength(1)
      expect(store.locations[0].name).toBe('Location A')

      // Push another location
      const loc2 = makeLocation({ id: 2, name: 'Location B' })
      store.locations.push(loc2)

      // Length should now be 2
      expect(store.locations).toHaveLength(2)
      expect(store.locations[1].name).toBe('Location B')
    })
  })
})
