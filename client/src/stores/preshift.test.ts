/**
 * stores/preshift.test.ts
 *
 * Unit tests for the usePreshiftStore Pinia store.
 *
 * These tests verify:
 *  1. Initial state is clean (empty arrays, loading = false).
 *  2. `fetchAll()` sets the `loading` flag correctly, populates all five
 *     state arrays from the API response, and resets `loading` even on error.
 *  3. Granular mutation methods (add/update/remove) for 86'd items, specials,
 *     push items, and announcements work correctly -- these are called by
 *     WebSocket event handlers to patch the store without a full re-fetch.
 *  4. `markAcknowledged()` tracks acknowledged items and prevents duplicates.
 *
 * We mock `@/composables/useApi` so no real HTTP requests are made.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { usePreshiftStore } from '@/stores/preshift'
import type {
  EightySixed,
  Special,
  PushItem,
  Announcement,
} from '@/types'

// ── Mock the API module ────────────────────────────────────────────────────
// Replace the default export of `@/composables/useApi` with an object whose
// `post` and `get` methods are Vitest spies.  This prevents real HTTP calls
// and allows us to control return values per test.
vi.mock('@/composables/useApi', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

// ── Helpers ────────────────────────────────────────────────────────────────
// Each helper creates a minimal valid object matching its TypeScript
// interface.  Tests pass partial overrides to customise only the fields
// relevant to the assertion, keeping the test body concise.

/**
 * Creates a valid EightySixed object with sensible defaults.
 * The `id` field is randomised so each call produces a unique item unless
 * explicitly overridden.
 */
function makeEightySixed(overrides: Partial<EightySixed> = {}): EightySixed {
  return {
    id: Math.floor(Math.random() * 10000),
    location_id: 1,
    menu_item_id: null,
    item_name: '86d Item',
    reason: null,
    eighty_sixed_by: 1,
    restored_at: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/**
 * Creates a valid Special object with sensible defaults.
 * Includes required fields like `starts_at` and `created_by`.
 */
function makeSpecial(overrides: Partial<Special> = {}): Special {
  return {
    id: Math.floor(Math.random() * 10000),
    location_id: 1,
    menu_item_id: null,
    title: 'Test Special',
    description: null,
    type: null,
    starts_at: '2026-01-01T17:00:00Z',
    ends_at: null,
    is_active: true,
    quantity: null,
    created_by: 1,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/**
 * Creates a valid PushItem object with sensible defaults.
 * Includes required fields like `title`, `is_active`, and `created_by`.
 */
function makePushItem(overrides: Partial<PushItem> = {}): PushItem {
  return {
    id: Math.floor(Math.random() * 10000),
    location_id: 1,
    menu_item_id: null,
    title: 'Test Push Item',
    description: null,
    reason: null,
    priority: null,
    is_active: true,
    created_by: 1,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/**
 * Creates a valid Announcement object with sensible defaults.
 * Includes required fields like `title`, `posted_by`, and `expires_at`.
 */
function makeAnnouncement(overrides: Partial<Announcement> = {}): Announcement {
  return {
    id: Math.floor(Math.random() * 10000),
    location_id: 1,
    title: 'Test Announcement',
    body: null,
    priority: null,
    target_roles: null,
    posted_by: 1,
    expires_at: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

// ── Test suite ─────────────────────────────────────────────────────────────

describe('usePreshiftStore', () => {
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
     * When the store is first created, all entity arrays should be empty
     * and the `loading` flag should be false.  No data is fetched until
     * `fetchAll()` is explicitly called.
     */
    it('has empty arrays and loading=false', () => {
      const store = usePreshiftStore()

      // All entity arrays should start empty
      expect(store.eightySixed).toEqual([])
      expect(store.specials).toEqual([])
      expect(store.pushItems).toEqual([])
      expect(store.announcements).toEqual([])
      expect(store.acknowledgments).toEqual([])

      // Loading flag should be off by default
      expect(store.loading).toBe(false)
    })
  })

  // ── fetchAll() ──────────────────────────────────────────────────────────

  describe('fetchAll()', () => {
    /**
     * While the API request is in flight, `loading` should be true so
     * the UI can render a loading indicator.  We test this by inspecting
     * `loading` from inside a mock implementation that runs synchronously
     * before the promise resolves.
     */
    it('sets loading=true during request', async () => {
      const api = (await import('@/composables/useApi')).default

      // Track loading values observed during the API call
      let loadingDuringRequest = false

      // When `api.get` is called, check `loading` synchronously before
      // resolving the promise -- this simulates the "in-flight" state
      ;(api.get as ReturnType<typeof vi.fn>).mockImplementationOnce(() => {
        // At this point fetchAll() has already set loading = true
        const store = usePreshiftStore()
        loadingDuringRequest = store.loading
        return Promise.resolve({
          data: {
            eighty_sixed: [],
            specials: [],
            push_items: [],
            announcements: [],
            acknowledgments: [],
          },
        })
      })

      const store = usePreshiftStore()
      await store.fetchAll()

      // Verify loading was true while the request was in flight
      expect(loadingDuringRequest).toBe(true)

      // And it should be false again after the request completes
      expect(store.loading).toBe(false)
    })

    /**
     * After a successful `fetchAll()`, all five state arrays should be
     * populated from the corresponding fields in the `PreShiftData`
     * API response.
     */
    it('populates all state arrays from API response', async () => {
      const api = (await import('@/composables/useApi')).default

      // Prepare fake data for each entity type
      const fakeEightySixed = [makeEightySixed({ id: 1 }), makeEightySixed({ id: 2 })]
      const fakeSpecials = [makeSpecial({ id: 10 })]
      const fakePushItems = [makePushItem({ id: 20 }), makePushItem({ id: 21 })]
      const fakeAnnouncements = [makeAnnouncement({ id: 30 })]
      const fakeAcks = [{ type: 'announcement', id: 30 }]

      // Configure the mock to return the fake PreShiftData payload
      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: {
          eighty_sixed: fakeEightySixed,
          specials: fakeSpecials,
          push_items: fakePushItems,
          announcements: fakeAnnouncements,
          acknowledgments: fakeAcks,
        },
      })

      const store = usePreshiftStore()
      await store.fetchAll()

      // Verify the correct endpoint was called
      expect(api.get).toHaveBeenCalledWith('/api/preshift')

      // Verify all arrays were populated from the response
      expect(store.eightySixed).toEqual(fakeEightySixed)
      expect(store.specials).toEqual(fakeSpecials)
      expect(store.pushItems).toEqual(fakePushItems)
      expect(store.announcements).toEqual(fakeAnnouncements)
      expect(store.acknowledgments).toEqual(fakeAcks)
    })

    /**
     * If the API request fails (e.g. network error, server 500), the
     * `loading` flag must still be reset to false.  The store uses a
     * `finally` block to guarantee this.  We verify by rejecting the
     * mock and checking `loading` after the error.
     */
    it('resets loading to false even on error', async () => {
      const api = (await import('@/composables/useApi')).default

      // Make the API call reject with an error
      ;(api.get as ReturnType<typeof vi.fn>).mockRejectedValueOnce(new Error('Server error'))

      const store = usePreshiftStore()

      // fetchAll() will throw because the API rejects -- catch it
      try {
        await store.fetchAll()
      } catch {
        // Expected to throw -- we don't need to assert on the error itself
      }

      // Loading must be false even after the error
      expect(store.loading).toBe(false)
    })
  })

  // ── 86'd items mutations ────────────────────────────────────────────────

  describe('addEightySixed()', () => {
    /**
     * `addEightySixed()` is called when a `EightySixedCreated` WebSocket
     * event arrives.  It should push the new item onto the end of the
     * `eightySixed` array.
     */
    it('pushes item to the eightySixed array', () => {
      const store = usePreshiftStore()
      const item = makeEightySixed({ id: 100, item_name: 'Salmon' })

      // Array starts empty
      expect(store.eightySixed).toHaveLength(0)

      // Add the item
      store.addEightySixed(item)

      // Verify it was pushed
      expect(store.eightySixed).toHaveLength(1)
      expect(store.eightySixed[0].id).toBe(100)
      expect(store.eightySixed[0].item_name).toBe('Salmon')
    })
  })

  describe('removeEightySixed()', () => {
    /**
     * `removeEightySixed()` is called when a `EightySixedRestored` event
     * arrives.  It filters out the item with the matching id, leaving all
     * other items intact.
     */
    it('filters out the item by id', () => {
      const store = usePreshiftStore()

      // Pre-populate with three items
      const item1 = makeEightySixed({ id: 1 })
      const item2 = makeEightySixed({ id: 2 })
      const item3 = makeEightySixed({ id: 3 })
      store.eightySixed = [item1, item2, item3]

      // Remove the middle item
      store.removeEightySixed(2)

      // Only items 1 and 3 should remain
      expect(store.eightySixed).toHaveLength(2)
      expect(store.eightySixed.map((i) => i.id)).toEqual([1, 3])
    })
  })

  // ── Specials mutations ──────────────────────────────────────────────────

  describe('addSpecial()', () => {
    /**
     * `addSpecial()` is called when a `SpecialCreated` WebSocket event
     * arrives.  It appends the new special to the end of the array.
     */
    it('pushes item to the specials array', () => {
      const store = usePreshiftStore()
      const special = makeSpecial({ id: 50, title: 'Happy Hour IPA' })

      // Start empty
      expect(store.specials).toHaveLength(0)

      // Add the special
      store.addSpecial(special)

      // Verify it was appended
      expect(store.specials).toHaveLength(1)
      expect(store.specials[0].id).toBe(50)
      expect(store.specials[0].title).toBe('Happy Hour IPA')
    })
  })

  describe('updateSpecial()', () => {
    /**
     * `updateSpecial()` finds the existing special by `id` and replaces
     * it with the updated version.  The array order and length should
     * remain unchanged.
     */
    it('replaces the matching item in the specials array', () => {
      const store = usePreshiftStore()

      // Pre-populate with two specials
      const original = makeSpecial({ id: 10, title: 'Old Title' })
      const other = makeSpecial({ id: 11, title: 'Other Special' })
      store.specials = [original, other]

      // Update the first special with a new title
      const updated = makeSpecial({ id: 10, title: 'New Title' })
      store.updateSpecial(updated)

      // The first item should now have the updated title
      expect(store.specials).toHaveLength(2)
      expect(store.specials[0].title).toBe('New Title')
      // The second item should be untouched
      expect(store.specials[1].title).toBe('Other Special')
    })

    /**
     * If the id doesn't match any existing special, the array should
     * remain completely unchanged.  This is a defensive guard against
     * stale WebSocket events.
     */
    it('does nothing if id not found', () => {
      const store = usePreshiftStore()

      // Pre-populate with one special
      const existing = makeSpecial({ id: 10, title: 'Existing' })
      store.specials = [existing]

      // Try to update a non-existent id
      const phantom = makeSpecial({ id: 999, title: 'Phantom' })
      store.updateSpecial(phantom)

      // Array should be unchanged
      expect(store.specials).toHaveLength(1)
      expect(store.specials[0].title).toBe('Existing')
    })
  })

  describe('removeSpecial()', () => {
    /**
     * `removeSpecial()` filters out the special with the given id.
     * Other specials remain in the array in their original order.
     */
    it('filters out the special by id', () => {
      const store = usePreshiftStore()

      // Pre-populate with three specials
      store.specials = [
        makeSpecial({ id: 1 }),
        makeSpecial({ id: 2 }),
        makeSpecial({ id: 3 }),
      ]

      // Remove the first special
      store.removeSpecial(1)

      // Only ids 2 and 3 should remain
      expect(store.specials).toHaveLength(2)
      expect(store.specials.map((s) => s.id)).toEqual([2, 3])
    })
  })

  // ── Push items mutations ────────────────────────────────────────────────

  describe('addPushItem()', () => {
    /**
     * `addPushItem()` appends a new push item to the `pushItems` array.
     * Called when a `PushItemCreated` WebSocket event is received.
     */
    it('pushes item to the pushItems array', () => {
      const store = usePreshiftStore()
      const item = makePushItem({ id: 60, title: 'Upsell: Wine Pairing' })

      // Verify the array starts empty
      expect(store.pushItems).toHaveLength(0)

      // Add the push item
      store.addPushItem(item)

      // Verify it was appended
      expect(store.pushItems).toHaveLength(1)
      expect(store.pushItems[0].id).toBe(60)
      expect(store.pushItems[0].title).toBe('Upsell: Wine Pairing')
    })
  })

  describe('updatePushItem()', () => {
    /**
     * `updatePushItem()` finds the existing push item by `id` and
     * replaces it in-place with the updated version.
     */
    it('replaces the matching item in the pushItems array', () => {
      const store = usePreshiftStore()

      // Pre-populate with two push items
      store.pushItems = [
        makePushItem({ id: 20, title: 'Old Push' }),
        makePushItem({ id: 21, title: 'Other Push' }),
      ]

      // Update the first push item
      const updated = makePushItem({ id: 20, title: 'Updated Push' })
      store.updatePushItem(updated)

      // Verify the replacement happened
      expect(store.pushItems).toHaveLength(2)
      expect(store.pushItems[0].title).toBe('Updated Push')
      expect(store.pushItems[1].title).toBe('Other Push')
    })
  })

  describe('removePushItem()', () => {
    /**
     * `removePushItem()` filters out the push item with the given id.
     * Called when a `PushItemDeleted` WebSocket event is received.
     */
    it('filters out the push item by id', () => {
      const store = usePreshiftStore()

      // Pre-populate with two push items
      store.pushItems = [
        makePushItem({ id: 20 }),
        makePushItem({ id: 21 }),
      ]

      // Remove the second item
      store.removePushItem(21)

      // Only id 20 should remain
      expect(store.pushItems).toHaveLength(1)
      expect(store.pushItems[0].id).toBe(20)
    })
  })

  // ── Announcement mutations ──────────────────────────────────────────────

  describe('addAnnouncement(), updateAnnouncement(), removeAnnouncement()', () => {
    /**
     * These three methods mirror the same add/update/remove pattern used
     * for specials and push items.  We test them together here since they
     * share identical logic structures.
     */
    it('addAnnouncement pushes, updateAnnouncement replaces, removeAnnouncement filters', () => {
      const store = usePreshiftStore()

      // ── addAnnouncement ──
      // Start with an empty array, then add an announcement
      const ann1 = makeAnnouncement({ id: 100, title: 'Staff Meeting at 3pm' })
      store.addAnnouncement(ann1)
      expect(store.announcements).toHaveLength(1)
      expect(store.announcements[0].title).toBe('Staff Meeting at 3pm')

      // Add a second announcement
      const ann2 = makeAnnouncement({ id: 101, title: 'New Dress Code' })
      store.addAnnouncement(ann2)
      expect(store.announcements).toHaveLength(2)

      // ── updateAnnouncement ──
      // Update the first announcement's title
      const updatedAnn1 = makeAnnouncement({ id: 100, title: 'Staff Meeting MOVED to 4pm' })
      store.updateAnnouncement(updatedAnn1)

      // Verify the first item was replaced but the second remains unchanged
      expect(store.announcements).toHaveLength(2)
      expect(store.announcements[0].title).toBe('Staff Meeting MOVED to 4pm')
      expect(store.announcements[1].title).toBe('New Dress Code')

      // ── removeAnnouncement ──
      // Remove the second announcement by id
      store.removeAnnouncement(101)

      // Only the first announcement should remain
      expect(store.announcements).toHaveLength(1)
      expect(store.announcements[0].id).toBe(100)
    })
  })

  // ── markAcknowledged() ──────────────────────────────────────────────────

  describe('markAcknowledged()', () => {
    /**
     * `markAcknowledged()` records that the current user has acknowledged
     * an item.  It pushes a new `AcknowledgmentRef` object (with `type`
     * and `id` fields) into the `acknowledgments` array.
     */
    it('adds a new acknowledgment ref to the array', () => {
      const store = usePreshiftStore()

      // Initially no acknowledgments
      expect(store.acknowledgments).toHaveLength(0)

      // Mark an announcement as acknowledged
      store.markAcknowledged('announcement', 42)

      // Verify the ref was added
      expect(store.acknowledgments).toHaveLength(1)
      expect(store.acknowledgments[0]).toEqual({ type: 'announcement', id: 42 })

      // Mark a special as acknowledged -- different type
      store.markAcknowledged('special', 99)

      // Both refs should be present
      expect(store.acknowledgments).toHaveLength(2)
      expect(store.acknowledgments[1]).toEqual({ type: 'special', id: 99 })
    })

    /**
     * If the same type+id combination has already been acknowledged, calling
     * `markAcknowledged()` again should NOT add a duplicate entry.  The
     * store checks for existing matches before pushing.
     */
    it('does not add duplicate acknowledgment refs', () => {
      const store = usePreshiftStore()

      // Acknowledge an announcement
      store.markAcknowledged('announcement', 42)
      expect(store.acknowledgments).toHaveLength(1)

      // Try to acknowledge the same announcement again
      store.markAcknowledged('announcement', 42)

      // Should still be length 1 -- no duplicate added
      expect(store.acknowledgments).toHaveLength(1)

      // A different id for the same type should be allowed
      store.markAcknowledged('announcement', 43)
      expect(store.acknowledgments).toHaveLength(2)

      // Same id but different type should also be allowed
      store.markAcknowledged('special', 42)
      expect(store.acknowledgments).toHaveLength(3)
    })
  })
})
