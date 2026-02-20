/**
 * useSchedule.test.ts
 *
 * Unit tests for the useSchedule composable.
 *
 * These tests verify:
 *  1. `nextShift` — returns the first shift or null when empty.
 *  2. `currentWeekRange` — correctly computes Monday/Sunday for any day of
 *     the week, including edge cases (Sunday, Monday, mid-week).
 *  3. `currentWeekShifts` — filters myShifts to the current Mon–Sun range
 *     and groups them by ISO date string.
 *  4. `formatShiftTime` — converts HH:MM and HH:MM:SS strings to 12-hour labels.
 *  5. `formatWeekLabel` — formats a week-start date into a readable range.
 *  6. `pendingDrops` / `pendingTimeOff` — count only relevant statuses.
 *
 * We mock the Pinia schedule store so tests are fully isolated from the API.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useScheduleStore } from '@/stores/schedule'
import { useSchedule } from '@/composables/useSchedule'
import type { ScheduleEntry, ShiftDrop, TimeOffRequest } from '@/types'

// ── Helpers ────────────────────────────────────────────────────────────────

function makeEntry(overrides: Partial<ScheduleEntry> & { date: string }): ScheduleEntry {
  return {
    id: Math.floor(Math.random() * 10000),
    schedule_id: 1,
    user_id: 1,
    shift_template_id: 1,
    role: 'server' as const,
    notes: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

function makeShiftDrop(status: ShiftDrop['status']): ShiftDrop {
  return {
    id: Math.floor(Math.random() * 10000),
    schedule_entry_id: 1,
    requested_by: 1,
    reason: null,
    status,
    filled_by: null,
    filled_at: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  }
}

function makeTimeOff(status: TimeOffRequest['status']): TimeOffRequest {
  return {
    id: Math.floor(Math.random() * 10000),
    user_id: 1,
    location_id: 1,
    start_date: '2026-02-20',
    end_date: '2026-02-20',
    reason: 'Test',
    status,
    resolved_by: null,
    resolved_at: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  }
}

function toISO(d: Date): string {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

// ── Test suite ─────────────────────────────────────────────────────────────

describe('useSchedule composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  // ── nextShift ──────────────────────────────────────────────────────────

  describe('nextShift', () => {
    it('returns null when myShifts is empty', () => {
      const { nextShift } = useSchedule()
      expect(nextShift.value).toBeNull()
    })

    it('returns the first shift when myShifts has entries', () => {
      const store = useScheduleStore()
      const entry = makeEntry({ date: '2026-02-20', id: 42 })
      store.myShifts = [entry, makeEntry({ date: '2026-02-21' })]

      const { nextShift } = useSchedule()
      expect(nextShift.value).toEqual(entry)
      expect(nextShift.value!.id).toBe(42)
    })
  })

  // ── currentWeekRange ───────────────────────────────────────────────────

  describe('currentWeekRange', () => {
    let realDate: typeof Date

    beforeEach(() => {
      realDate = globalThis.Date
    })

    afterEach(() => {
      globalThis.Date = realDate
    })

    function freezeDate(isoDate: string) {
      const frozen = new realDate(isoDate + 'T12:00:00')
      // @ts-expect-error — overriding Date for test purposes
      globalThis.Date = class extends realDate {
        constructor(...args: any[]) {
          if (args.length === 0) {
            super(frozen.getTime())
          } else {
            // @ts-expect-error — spreading args into Date constructor
            super(...args)
          }
        }
      }
      globalThis.Date.now = () => frozen.getTime()
    }

    it('computes correct Monday–Sunday when today is a Wednesday', () => {
      freezeDate('2026-02-18')
      const { currentWeekRange } = useSchedule()
      expect(currentWeekRange.value.monday).toBe('2026-02-16')
      expect(currentWeekRange.value.sunday).toBe('2026-02-22')
    })

    it('computes correct range when today is Monday', () => {
      freezeDate('2026-02-16')
      const { currentWeekRange } = useSchedule()
      expect(currentWeekRange.value.monday).toBe('2026-02-16')
      expect(currentWeekRange.value.sunday).toBe('2026-02-22')
    })

    it('computes correct range when today is Sunday', () => {
      freezeDate('2026-02-22')
      const { currentWeekRange } = useSchedule()
      expect(currentWeekRange.value.monday).toBe('2026-02-16')
      expect(currentWeekRange.value.sunday).toBe('2026-02-22')
    })

    it('handles month boundaries correctly', () => {
      freezeDate('2026-03-01')
      const { currentWeekRange } = useSchedule()
      expect(currentWeekRange.value.monday).toBe('2026-02-23')
      expect(currentWeekRange.value.sunday).toBe('2026-03-01')
    })

    it('handles year boundaries correctly', () => {
      freezeDate('2026-01-01')
      const { currentWeekRange } = useSchedule()
      expect(currentWeekRange.value.monday).toBe('2025-12-29')
      expect(currentWeekRange.value.sunday).toBe('2026-01-04')
    })
  })

  // ── currentWeekShifts ──────────────────────────────────────────────────

  describe('currentWeekShifts', () => {
    let realDate: typeof Date

    beforeEach(() => {
      realDate = globalThis.Date
    })

    afterEach(() => {
      globalThis.Date = realDate
    })

    function freezeDate(isoDate: string) {
      const frozen = new realDate(isoDate + 'T12:00:00')
      // @ts-expect-error — overriding Date for test purposes
      globalThis.Date = class extends realDate {
        constructor(...args: any[]) {
          if (args.length === 0) {
            super(frozen.getTime())
          } else {
            // @ts-expect-error — spreading args into Date constructor
            super(...args)
          }
        }
      }
      globalThis.Date.now = () => frozen.getTime()
    }

    it('returns an empty record when myShifts is empty', () => {
      freezeDate('2026-02-18')
      const { currentWeekShifts } = useSchedule()
      expect(currentWeekShifts.value).toEqual({})
    })

    it('filters shifts to only the current week', () => {
      freezeDate('2026-02-18')

      const store = useScheduleStore()
      store.myShifts = [
        makeEntry({ date: '2026-02-15', id: 1 }),
        makeEntry({ date: '2026-02-16', id: 2 }),
        makeEntry({ date: '2026-02-18', id: 3 }),
        makeEntry({ date: '2026-02-22', id: 4 }),
        makeEntry({ date: '2026-02-23', id: 5 }),
      ]

      const { currentWeekShifts } = useSchedule()
      const result = currentWeekShifts.value

      expect(Object.keys(result)).toHaveLength(3)
      expect(result['2026-02-16']).toHaveLength(1)
      expect(result['2026-02-16'][0].id).toBe(2)
      expect(result['2026-02-18']).toHaveLength(1)
      expect(result['2026-02-22']).toHaveLength(1)

      expect(result['2026-02-15']).toBeUndefined()
      expect(result['2026-02-23']).toBeUndefined()
    })

    it('groups multiple shifts on the same day', () => {
      freezeDate('2026-02-18')

      const store = useScheduleStore()
      store.myShifts = [
        makeEntry({ date: '2026-02-18', id: 10 }),
        makeEntry({ date: '2026-02-18', id: 11 }),
        makeEntry({ date: '2026-02-19', id: 12 }),
      ]

      const { currentWeekShifts } = useSchedule()
      const result = currentWeekShifts.value

      expect(result['2026-02-18']).toHaveLength(2)
      expect(result['2026-02-18'].map((e) => e.id)).toEqual([10, 11])
      expect(result['2026-02-19']).toHaveLength(1)
    })
  })

  // ── formatShiftTime ────────────────────────────────────────────────────

  describe('formatShiftTime', () => {
    it('formats morning time (HH:MM) correctly', () => {
      const { formatShiftTime } = useSchedule()
      expect(formatShiftTime('10:30')).toBe('10:30 AM')
    })

    it('formats afternoon time (HH:MM:SS) correctly', () => {
      const { formatShiftTime } = useSchedule()
      expect(formatShiftTime('16:00:00')).toBe('4:00 PM')
    })

    it('formats midnight (00:00) as 12:00 AM', () => {
      const { formatShiftTime } = useSchedule()
      expect(formatShiftTime('00:00')).toBe('12:00 AM')
    })

    it('formats noon (12:00) as 12:00 PM', () => {
      const { formatShiftTime } = useSchedule()
      expect(formatShiftTime('12:00')).toBe('12:00 PM')
    })

    it('formats 1 PM correctly', () => {
      const { formatShiftTime } = useSchedule()
      expect(formatShiftTime('13:00')).toBe('1:00 PM')
    })

    it('preserves minutes with leading zero', () => {
      const { formatShiftTime } = useSchedule()
      expect(formatShiftTime('09:05:00')).toBe('9:05 AM')
    })
  })

  // ── formatWeekLabel ────────────────────────────────────────────────────

  describe('formatWeekLabel', () => {
    it('formats a week within the same month', () => {
      const { formatWeekLabel } = useSchedule()
      const label = formatWeekLabel('2026-02-16')
      expect(label).toContain('Feb')
      expect(label).toContain('16')
      expect(label).toContain('22')
    })

    it('formats a week crossing month boundary', () => {
      const { formatWeekLabel } = useSchedule()
      const label = formatWeekLabel('2026-02-23')
      expect(label).toContain('Feb')
      expect(label).toContain('23')
      expect(label).toContain('Mar')
      expect(label).toContain('1')
    })
  })

  // ── pendingDrops ────────────────────────────────────────────────────────

  describe('pendingDrops', () => {
    it('returns 0 when no shift drops exist', () => {
      const { pendingDrops } = useSchedule()
      expect(pendingDrops.value).toBe(0)
    })

    it('counts only open drops', () => {
      const store = useScheduleStore()
      store.shiftDrops = [
        makeShiftDrop('open'),
        makeShiftDrop('open'),
        makeShiftDrop('filled'),     // should NOT be counted
        makeShiftDrop('cancelled'),  // should NOT be counted
        makeShiftDrop('open'),
      ]

      const { pendingDrops } = useSchedule()
      expect(pendingDrops.value).toBe(3)
    })
  })

  // ── pendingTimeOff ─────────────────────────────────────────────────────

  describe('pendingTimeOff', () => {
    it('returns 0 when no time-off requests exist', () => {
      const { pendingTimeOff } = useSchedule()
      expect(pendingTimeOff.value).toBe(0)
    })

    it('counts only pending time-off requests', () => {
      const store = useScheduleStore()
      store.timeOffRequests = [
        makeTimeOff('pending'),
        makeTimeOff('approved'),
        makeTimeOff('pending'),
        makeTimeOff('denied'),
      ]

      const { pendingTimeOff } = useSchedule()
      expect(pendingTimeOff.value).toBe(2)
    })
  })
})
