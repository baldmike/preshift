/**
 * stores/schedule.test.ts
 *
 * Unit tests for the useScheduleStore Pinia store.
 *
 * These tests verify:
 *  1. Initial state is clean (empty arrays, null refs, loading = false).
 *  2. Fetch actions (`fetchShiftTemplates`, `fetchSchedules`, `fetchSchedule`,
 *     `fetchMyShifts`, `fetchShiftDrops`, `fetchTimeOffRequests`) each call the
 *     correct API endpoint and populate their respective state refs.
 *  3. `fetchCurrentWeekSchedule()` finds the published schedule whose
 *     `week_start` matches the current Monday, fetches its full details, and
 *     sets `currentSchedule`.  Also covers the no-match and error cases.
 *  4. Realtime mutation methods (`onSchedulePublished`, `upsertShiftDrop`,
 *     `upsertTimeOffRequest`) correctly insert new records or update existing
 *     ones based on matching `id`.
 *  5. `onSchedulePublished()` triggers a re-fetch of `myShifts` so the
 *     staff member's dashboard stays current after a schedule is published.
 *
 * We mock `@/composables/useApi` so no real HTTP requests are made.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useScheduleStore } from '@/stores/schedule'
import type {
  ShiftTemplate,
  Schedule,
  ScheduleEntry,
  ShiftDrop,
  TimeOffRequest,
} from '@/types'

// ── Mock the API module ────────────────────────────────────────────────────
// Replace the default export of `@/composables/useApi` with an object whose
// `get` method is a Vitest spy.  The schedule store only uses `api.get()`,
// but we include `post` for completeness to match the project convention.
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
 * Creates a valid ShiftTemplate object with sensible defaults.
 * The `id` defaults to a random number so each call produces a unique
 * template unless explicitly overridden.
 */
function makeShiftTemplate(overrides: Partial<ShiftTemplate> = {}): ShiftTemplate {
  return {
    id: Math.floor(Math.random() * 10000),
    location_id: 1,
    name: 'Lunch',
    start_time: '10:30:00',
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/**
 * Creates a valid Schedule object with sensible defaults.
 * Defaults to a draft schedule for the week of 2026-01-05.
 */
function makeSchedule(overrides: Partial<Schedule> = {}): Schedule {
  return {
    id: Math.floor(Math.random() * 10000),
    location_id: 1,
    week_start: '2026-01-05',
    status: 'draft',
    published_at: null,
    published_by: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/**
 * Creates a valid ScheduleEntry object with sensible defaults.
 * Represents one staff member assigned to one shift on a specific date.
 */
function makeScheduleEntry(overrides: Partial<ScheduleEntry> = {}): ScheduleEntry {
  return {
    id: Math.floor(Math.random() * 10000),
    schedule_id: 1,
    user_id: 1,
    shift_template_id: 1,
    date: '2026-01-05',
    role: 'server',
    notes: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/**
 * Creates a valid ShiftDrop object with sensible defaults.
 * Defaults to an open shift drop with no volunteers yet.
 */
function makeShiftDrop(overrides: Partial<ShiftDrop> = {}): ShiftDrop {
  return {
    id: Math.floor(Math.random() * 10000),
    schedule_entry_id: 1,
    requested_by: 1,
    reason: null,
    status: 'open',
    filled_by: null,
    filled_at: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/**
 * Creates a valid TimeOffRequest object with sensible defaults.
 * Defaults to a pending request spanning a single day.
 */
function makeTimeOffRequest(overrides: Partial<TimeOffRequest> = {}): TimeOffRequest {
  return {
    id: Math.floor(Math.random() * 10000),
    user_id: 1,
    location_id: 1,
    start_date: '2026-02-01',
    end_date: '2026-02-01',
    reason: null,
    status: 'pending',
    resolved_by: null,
    resolved_at: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

// ── Test suite ─────────────────────────────────────────────────────────────

describe('useScheduleStore', () => {
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
     * When the store is first created, all entity arrays should be empty,
     * nullable refs should be null, and the `loading` flag should be false.
     * No data is fetched until an action is explicitly called.
     */
    it('has empty arrays, null refs, and loading=false', () => {
      const store = useScheduleStore()

      // All entity arrays should start empty
      expect(store.shiftTemplates).toEqual([])
      expect(store.schedules).toEqual([])
      expect(store.myShifts).toEqual([])
      expect(store.shiftDrops).toEqual([])
      expect(store.timeOffRequests).toEqual([])

      // Nullable refs should be null
      expect(store.currentSchedule).toBeNull()

      // Loading flag should be off by default
      expect(store.loading).toBe(false)
    })
  })

  // ── fetchShiftTemplates() ─────────────────────────────────────────────

  describe('fetchShiftTemplates()', () => {
    /**
     * `fetchShiftTemplates()` should call `GET /api/shift-templates` and
     * populate the `shiftTemplates` array from the API response data.
     */
    it('calls GET /api/shift-templates and populates shiftTemplates', async () => {
      const api = (await import('@/composables/useApi')).default

      const fakeTemplates = [
        makeShiftTemplate({ id: 1, name: 'Lunch' }),
        makeShiftTemplate({ id: 2, name: 'Dinner' }),
        makeShiftTemplate({ id: 3, name: 'Double' }),
      ]

      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: fakeTemplates,
      })

      const store = useScheduleStore()

      // Initially empty
      expect(store.shiftTemplates).toEqual([])

      await store.fetchShiftTemplates()

      // Verify the correct endpoint was called
      expect(api.get).toHaveBeenCalledWith('/api/shift-templates')

      // Verify the state was populated
      expect(store.shiftTemplates).toEqual(fakeTemplates)
      expect(store.shiftTemplates).toHaveLength(3)
      expect(store.shiftTemplates[0].name).toBe('Lunch')
      expect(store.shiftTemplates[2].name).toBe('Double')
    })

    /**
     * If the API returns an empty array, the store should reflect that
     * without error.
     */
    it('handles an empty response gracefully', async () => {
      const api = (await import('@/composables/useApi')).default

      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: [],
      })

      const store = useScheduleStore()
      await store.fetchShiftTemplates()

      expect(store.shiftTemplates).toEqual([])
    })
  })

  // ── fetchSchedules() ──────────────────────────────────────────────────

  describe('fetchSchedules()', () => {
    /**
     * `fetchSchedules()` should call `GET /api/schedules` and populate
     * the `schedules` array with the full list of schedules.
     */
    it('calls GET /api/schedules and populates schedules', async () => {
      const api = (await import('@/composables/useApi')).default

      const fakeSchedules = [
        makeSchedule({ id: 1, week_start: '2026-01-05', status: 'published' }),
        makeSchedule({ id: 2, week_start: '2026-01-12', status: 'draft' }),
      ]

      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: fakeSchedules,
      })

      const store = useScheduleStore()
      await store.fetchSchedules()

      expect(api.get).toHaveBeenCalledWith('/api/schedules')
      expect(store.schedules).toEqual(fakeSchedules)
      expect(store.schedules).toHaveLength(2)
    })
  })

  // ── fetchSchedule() ───────────────────────────────────────────────────

  describe('fetchSchedule()', () => {
    /**
     * `fetchSchedule(id)` should call `GET /api/schedules/:id` and set
     * `currentSchedule` to the returned schedule object (including its
     * eagerly-loaded entries).
     */
    it('calls GET /api/schedules/:id and sets currentSchedule', async () => {
      const api = (await import('@/composables/useApi')).default

      const fakeSchedule = makeSchedule({
        id: 42,
        status: 'published',
        entries: [
          makeScheduleEntry({ id: 100, schedule_id: 42 }),
          makeScheduleEntry({ id: 101, schedule_id: 42 }),
        ],
      })

      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: fakeSchedule,
      })

      const store = useScheduleStore()

      // Initially null
      expect(store.currentSchedule).toBeNull()

      await store.fetchSchedule(42)

      // Verify the correct endpoint with the id was called
      expect(api.get).toHaveBeenCalledWith('/api/schedules/42')

      // Verify currentSchedule was set
      expect(store.currentSchedule).toEqual(fakeSchedule)
      expect(store.currentSchedule!.id).toBe(42)
      expect(store.currentSchedule!.entries).toHaveLength(2)
    })
  })

  // ── fetchMyShifts() ───────────────────────────────────────────────────

  describe('fetchMyShifts()', () => {
    /**
     * `fetchMyShifts()` should call `GET /api/my-shifts` and populate
     * the `myShifts` array with the authenticated user's upcoming shifts.
     */
    it('calls GET /api/my-shifts and populates myShifts', async () => {
      const api = (await import('@/composables/useApi')).default

      const fakeShifts = [
        makeScheduleEntry({ id: 1, date: '2026-02-20', role: 'server' }),
        makeScheduleEntry({ id: 2, date: '2026-02-21', role: 'bartender' }),
      ]

      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: fakeShifts,
      })

      const store = useScheduleStore()
      await store.fetchMyShifts()

      expect(api.get).toHaveBeenCalledWith('/api/my-shifts')
      expect(store.myShifts).toEqual(fakeShifts)
      expect(store.myShifts).toHaveLength(2)
      expect(store.myShifts[0].role).toBe('server')
      expect(store.myShifts[1].role).toBe('bartender')
    })
  })

  // ── fetchCurrentWeekSchedule() ────────────────────────────────────────

  describe('fetchCurrentWeekSchedule()', () => {
    /**
     * Helper to compute the ISO date string for Monday of the current week.
     * This mirrors the logic in the store so our test fixtures match.
     */
    function getCurrentMonday(): string {
      const today = new Date()
      const jsDay = today.getDay()
      const offset = (jsDay + 6) % 7
      const monday = new Date(today)
      monday.setDate(today.getDate() - offset)
      return `${monday.getFullYear()}-${String(monday.getMonth() + 1).padStart(2, '0')}-${String(monday.getDate()).padStart(2, '0')}`
    }

    /**
     * When a published schedule exists for the current week, the store
     * should first fetch all schedules, find the matching one, then fetch
     * its full details and set `currentSchedule`.
     */
    it('finds and fetches the current week published schedule', async () => {
      const api = (await import('@/composables/useApi')).default
      const mondayISO = getCurrentMonday()

      // First call: GET /api/schedules returns a list that includes
      // a published schedule for the current week's Monday
      const matchingSchedule = makeSchedule({
        id: 77,
        week_start: mondayISO,
        status: 'published',
      })
      const otherSchedule = makeSchedule({
        id: 78,
        week_start: '2025-01-06',
        status: 'published',
      })

      ;(api.get as ReturnType<typeof vi.fn>)
        // First call: list all schedules
        .mockResolvedValueOnce({
          data: [matchingSchedule, otherSchedule],
        })
        // Second call: fetch the matched schedule's full details
        .mockResolvedValueOnce({
          data: {
            ...matchingSchedule,
            entries: [makeScheduleEntry({ schedule_id: 77 })],
          },
        })

      const store = useScheduleStore()
      await store.fetchCurrentWeekSchedule()

      // First call should be the list endpoint
      expect(api.get).toHaveBeenCalledWith('/api/schedules')
      // Second call should fetch the specific schedule
      expect(api.get).toHaveBeenCalledWith('/api/schedules/77')

      // currentSchedule should be the fully-loaded schedule
      expect(store.currentSchedule).not.toBeNull()
      expect(store.currentSchedule!.id).toBe(77)
      expect(store.currentSchedule!.entries).toHaveLength(1)
    })

    /**
     * When `week_start` includes a time component (e.g. from a DateTime
     * column), the store splits on 'T' to compare only the date part.
     * This test ensures that matching works correctly with ISO datetime
     * strings.
     */
    it('matches week_start with a datetime string (includes T component)', async () => {
      const api = (await import('@/composables/useApi')).default
      const mondayISO = getCurrentMonday()

      // The schedule has a full datetime string, not just a date
      const schedule = makeSchedule({
        id: 99,
        week_start: `${mondayISO}T00:00:00.000000Z`,
        status: 'published',
      })

      ;(api.get as ReturnType<typeof vi.fn>)
        .mockResolvedValueOnce({ data: [schedule] })
        .mockResolvedValueOnce({ data: { ...schedule, entries: [] } })

      const store = useScheduleStore()
      await store.fetchCurrentWeekSchedule()

      // Should still match after splitting on 'T'
      expect(store.currentSchedule).not.toBeNull()
      expect(store.currentSchedule!.id).toBe(99)
    })

    /**
     * When no published schedule exists for the current week (e.g. only
     * drafts or schedules for other weeks), `currentSchedule` should be
     * set to null and no second API call should be made.
     */
    it('sets currentSchedule to null when no matching schedule is found', async () => {
      const api = (await import('@/composables/useApi')).default

      // Return schedules that do NOT match the current week
      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: [
          makeSchedule({ id: 1, week_start: '2025-06-01', status: 'published' }),
          makeSchedule({ id: 2, week_start: '2025-06-08', status: 'draft' }),
        ],
      })

      const store = useScheduleStore()
      await store.fetchCurrentWeekSchedule()

      // currentSchedule should remain null
      expect(store.currentSchedule).toBeNull()

      // Only one API call should have been made (the list call)
      expect(api.get).toHaveBeenCalledTimes(1)
    })

    /**
     * A draft schedule for the current week should NOT be matched --
     * only published schedules are eligible.
     */
    it('ignores draft schedules for the current week', async () => {
      const api = (await import('@/composables/useApi')).default
      const mondayISO = getCurrentMonday()

      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: [
          makeSchedule({ id: 5, week_start: mondayISO, status: 'draft' }),
        ],
      })

      const store = useScheduleStore()
      await store.fetchCurrentWeekSchedule()

      // Draft schedule should not be selected
      expect(store.currentSchedule).toBeNull()
      expect(api.get).toHaveBeenCalledTimes(1)
    })

    /**
     * If the API request fails (network error, server 500), the store
     * should catch the error gracefully and set `currentSchedule` to null
     * rather than letting the exception propagate.
     */
    it('sets currentSchedule to null on API error', async () => {
      const api = (await import('@/composables/useApi')).default

      ;(api.get as ReturnType<typeof vi.fn>).mockRejectedValueOnce(
        new Error('Network Error')
      )

      const store = useScheduleStore()

      // Should NOT throw
      await store.fetchCurrentWeekSchedule()

      // currentSchedule should be null after the error
      expect(store.currentSchedule).toBeNull()
    })

    /**
     * When `currentSchedule` was previously set and the new fetch finds
     * no matching schedule, it should be reset to null.
     */
    it('clears a previously set currentSchedule when no match is found', async () => {
      const api = (await import('@/composables/useApi')).default

      const store = useScheduleStore()

      // Pre-populate with an existing schedule
      store.currentSchedule = makeSchedule({ id: 10, status: 'published' })
      expect(store.currentSchedule).not.toBeNull()

      // Return an empty list from the API
      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: [],
      })

      await store.fetchCurrentWeekSchedule()

      // Should have been cleared
      expect(store.currentSchedule).toBeNull()
    })
  })

  // ── fetchShiftDrops() ─────────────────────────────────────────────────

  describe('fetchShiftDrops()', () => {
    /**
     * `fetchShiftDrops()` should call `GET /api/shift-drops` and populate
     * the `shiftDrops` array.
     */
    it('calls GET /api/shift-drops and populates shiftDrops', async () => {
      const api = (await import('@/composables/useApi')).default

      const fakeDrops = [
        makeShiftDrop({ id: 1, status: 'open' }),
        makeShiftDrop({ id: 2, status: 'filled', filled_by: 3 }),
      ]

      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: fakeDrops,
      })

      const store = useScheduleStore()
      await store.fetchShiftDrops()

      expect(api.get).toHaveBeenCalledWith('/api/shift-drops')
      expect(store.shiftDrops).toEqual(fakeDrops)
      expect(store.shiftDrops).toHaveLength(2)
      expect(store.shiftDrops[0].status).toBe('open')
      expect(store.shiftDrops[1].filled_by).toBe(3)
    })
  })

  // ── fetchTimeOffRequests() ────────────────────────────────────────────

  describe('fetchTimeOffRequests()', () => {
    /**
     * `fetchTimeOffRequests()` should call `GET /api/time-off-requests`
     * and populate the `timeOffRequests` array.
     */
    it('calls GET /api/time-off-requests and populates timeOffRequests', async () => {
      const api = (await import('@/composables/useApi')).default

      const fakeRequests = [
        makeTimeOffRequest({ id: 1, status: 'pending' }),
        makeTimeOffRequest({ id: 2, status: 'approved', resolved_by: 5 }),
        makeTimeOffRequest({ id: 3, status: 'denied', resolved_by: 5 }),
      ]

      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: fakeRequests,
      })

      const store = useScheduleStore()
      await store.fetchTimeOffRequests()

      expect(api.get).toHaveBeenCalledWith('/api/time-off-requests')
      expect(store.timeOffRequests).toEqual(fakeRequests)
      expect(store.timeOffRequests).toHaveLength(3)
    })
  })

  // ── onSchedulePublished() ─────────────────────────────────────────────

  describe('onSchedulePublished()', () => {
    /**
     * When a `SchedulePublished` WebSocket event arrives for a schedule
     * that already exists in the `schedules` array, it should be updated
     * in place (merged) rather than duplicated.
     */
    it('updates an existing schedule in the schedules array', async () => {
      const api = (await import('@/composables/useApi')).default

      // Mock the fetchMyShifts call that onSchedulePublished triggers
      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: [],
      })

      const store = useScheduleStore()

      // Pre-populate with a draft schedule
      const existing = makeSchedule({ id: 10, status: 'draft', week_start: '2026-02-16' })
      store.schedules = [existing]

      // Simulate a published event for the same schedule
      const published = makeSchedule({
        id: 10,
        status: 'published',
        week_start: '2026-02-16',
        published_at: '2026-02-15T18:00:00Z',
        published_by: 5,
      })

      store.onSchedulePublished(published)

      // The array should still have exactly one schedule
      expect(store.schedules).toHaveLength(1)

      // It should be updated to published status
      expect(store.schedules[0].status).toBe('published')
      expect(store.schedules[0].published_at).toBe('2026-02-15T18:00:00Z')
      expect(store.schedules[0].published_by).toBe(5)
    })

    /**
     * When a `SchedulePublished` event arrives for a schedule that is NOT
     * already in the `schedules` array (e.g. it was created by another
     * manager), it should be pushed onto the end of the array.
     */
    it('pushes a new schedule if id is not found in the array', async () => {
      const api = (await import('@/composables/useApi')).default

      // Mock the fetchMyShifts call
      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: [],
      })

      const store = useScheduleStore()

      // Pre-populate with one existing schedule
      const existing = makeSchedule({ id: 10, status: 'published' })
      store.schedules = [existing]

      // A completely new published schedule arrives
      const newSchedule = makeSchedule({ id: 20, status: 'published' })
      store.onSchedulePublished(newSchedule)

      // Both schedules should now be in the array
      expect(store.schedules).toHaveLength(2)
      expect(store.schedules[0].id).toBe(10)
      expect(store.schedules[1].id).toBe(20)
    })

    /**
     * `onSchedulePublished()` should always trigger a re-fetch of
     * `myShifts` so the staff member's upcoming shifts are updated
     * immediately after a schedule is published.
     */
    it('triggers fetchMyShifts after updating the schedules array', async () => {
      const api = (await import('@/composables/useApi')).default

      const fakeMyShifts = [
        makeScheduleEntry({ id: 500, date: '2026-02-20' }),
      ]

      // Mock the fetchMyShifts call
      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: fakeMyShifts,
      })

      const store = useScheduleStore()

      const published = makeSchedule({ id: 30, status: 'published' })
      store.onSchedulePublished(published)

      // Verify that GET /api/my-shifts was called
      expect(api.get).toHaveBeenCalledWith('/api/my-shifts')
    })

    /**
     * When updating an existing schedule, the merged result should retain
     * original fields that are NOT present in the incoming event payload.
     * The spread `{ ...schedules[idx], ...schedule }` preserves existing
     * fields while overwriting those provided by the event.
     */
    it('merges incoming fields with existing schedule fields', async () => {
      const api = (await import('@/composables/useApi')).default

      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: [],
      })

      const store = useScheduleStore()

      // Existing schedule has entries_count from a prior list fetch
      const existing = makeSchedule({
        id: 15,
        status: 'draft',
        entries_count: 12,
      })
      store.schedules = [existing]

      // The published event does NOT include entries_count
      const published: Schedule = {
        id: 15,
        location_id: 1,
        week_start: '2026-02-16',
        status: 'published',
        published_at: '2026-02-15T20:00:00Z',
        published_by: 3,
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-02-15T20:00:00Z',
      }

      store.onSchedulePublished(published)

      // entries_count from the original should be preserved
      expect(store.schedules[0].entries_count).toBe(12)
      // And the new fields should be applied
      expect(store.schedules[0].status).toBe('published')
      expect(store.schedules[0].published_by).toBe(3)
    })
  })

  // ── upsertShiftDrop() ─────────────────────────────────────────────────

  describe('upsertShiftDrop()', () => {
    /**
     * When a shift drop with a matching id already exists in the array,
     * `upsertShiftDrop()` should replace it in-place with the updated
     * version, preserving the array length and order.
     */
    it('updates an existing shift drop by id', () => {
      const store = useScheduleStore()

      // Pre-populate with two shift drops
      const drop1 = makeShiftDrop({ id: 1, status: 'open', reason: 'Sick' })
      const drop2 = makeShiftDrop({ id: 2, status: 'open' })
      store.shiftDrops = [drop1, drop2]

      // Update the first drop -- it has been filled
      const updatedDrop = makeShiftDrop({
        id: 1,
        status: 'filled',
        filled_by: 7,
        filled_at: '2026-02-19T14:00:00Z',
        reason: 'Sick',
      })
      store.upsertShiftDrop(updatedDrop)

      // Array length should be unchanged
      expect(store.shiftDrops).toHaveLength(2)

      // The first drop should be updated
      expect(store.shiftDrops[0].status).toBe('filled')
      expect(store.shiftDrops[0].filled_by).toBe(7)
      expect(store.shiftDrops[0].filled_at).toBe('2026-02-19T14:00:00Z')

      // The second drop should be untouched
      expect(store.shiftDrops[1].id).toBe(2)
      expect(store.shiftDrops[1].status).toBe('open')
    })

    /**
     * When the shift drop id does NOT exist in the current array,
     * `upsertShiftDrop()` should push the new drop onto the end.
     */
    it('pushes a new shift drop if id is not found', () => {
      const store = useScheduleStore()

      // Start with one existing drop
      const existing = makeShiftDrop({ id: 1, status: 'open' })
      store.shiftDrops = [existing]

      // Insert a brand-new shift drop
      const newDrop = makeShiftDrop({ id: 50, status: 'open', reason: 'Personal' })
      store.upsertShiftDrop(newDrop)

      // Both should be in the array
      expect(store.shiftDrops).toHaveLength(2)
      expect(store.shiftDrops[0].id).toBe(1)
      expect(store.shiftDrops[1].id).toBe(50)
      expect(store.shiftDrops[1].reason).toBe('Personal')
    })

    /**
     * When the array is initially empty, `upsertShiftDrop()` should
     * add the drop as the first element.
     */
    it('inserts into an empty array', () => {
      const store = useScheduleStore()

      expect(store.shiftDrops).toHaveLength(0)

      const drop = makeShiftDrop({ id: 99, status: 'open' })
      store.upsertShiftDrop(drop)

      expect(store.shiftDrops).toHaveLength(1)
      expect(store.shiftDrops[0].id).toBe(99)
    })
  })

  // ── upsertTimeOffRequest() ────────────────────────────────────────────

  describe('upsertTimeOffRequest()', () => {
    /**
     * When a time-off request with a matching id exists in the array,
     * `upsertTimeOffRequest()` should replace it with the updated version.
     */
    it('updates an existing time-off request by id', () => {
      const store = useScheduleStore()

      // Pre-populate with two requests
      const req1 = makeTimeOffRequest({ id: 1, status: 'pending' })
      const req2 = makeTimeOffRequest({ id: 2, status: 'pending' })
      store.timeOffRequests = [req1, req2]

      // The first request gets approved
      const approved = makeTimeOffRequest({
        id: 1,
        status: 'approved',
        resolved_by: 5,
        resolved_at: '2026-02-18T10:00:00Z',
      })
      store.upsertTimeOffRequest(approved)

      // Length unchanged
      expect(store.timeOffRequests).toHaveLength(2)

      // First request should be updated
      expect(store.timeOffRequests[0].status).toBe('approved')
      expect(store.timeOffRequests[0].resolved_by).toBe(5)
      expect(store.timeOffRequests[0].resolved_at).toBe('2026-02-18T10:00:00Z')

      // Second request should be untouched
      expect(store.timeOffRequests[1].id).toBe(2)
      expect(store.timeOffRequests[1].status).toBe('pending')
    })

    /**
     * When the time-off request id does NOT exist in the array,
     * `upsertTimeOffRequest()` should push the new request onto the end.
     */
    it('pushes a new time-off request if id is not found', () => {
      const store = useScheduleStore()

      // Start with one existing request
      const existing = makeTimeOffRequest({ id: 1, status: 'approved' })
      store.timeOffRequests = [existing]

      // A new pending request arrives
      const newReq = makeTimeOffRequest({
        id: 75,
        status: 'pending',
        reason: 'Vacation',
        start_date: '2026-03-01',
        end_date: '2026-03-05',
      })
      store.upsertTimeOffRequest(newReq)

      // Both should be present
      expect(store.timeOffRequests).toHaveLength(2)
      expect(store.timeOffRequests[0].id).toBe(1)
      expect(store.timeOffRequests[1].id).toBe(75)
      expect(store.timeOffRequests[1].reason).toBe('Vacation')
    })

    /**
     * When the array is initially empty, `upsertTimeOffRequest()` should
     * add the request as the first element.
     */
    it('inserts into an empty array', () => {
      const store = useScheduleStore()

      expect(store.timeOffRequests).toHaveLength(0)

      const req = makeTimeOffRequest({ id: 200, status: 'pending' })
      store.upsertTimeOffRequest(req)

      expect(store.timeOffRequests).toHaveLength(1)
      expect(store.timeOffRequests[0].id).toBe(200)
    })

    /**
     * A denied time-off request should also be upsertable. This verifies
     * that the `status: 'denied'` variant is handled correctly.
     */
    it('handles denied status correctly', () => {
      const store = useScheduleStore()

      const pending = makeTimeOffRequest({ id: 5, status: 'pending' })
      store.timeOffRequests = [pending]

      const denied = makeTimeOffRequest({
        id: 5,
        status: 'denied',
        resolved_by: 3,
        resolved_at: '2026-02-19T09:00:00Z',
      })
      store.upsertTimeOffRequest(denied)

      expect(store.timeOffRequests).toHaveLength(1)
      expect(store.timeOffRequests[0].status).toBe('denied')
      expect(store.timeOffRequests[0].resolved_by).toBe(3)
    })
  })
})
