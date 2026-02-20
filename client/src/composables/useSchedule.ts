/**
 * composables/useSchedule.ts
 *
 * Provides helper functions and computed refs for the scheduling system.
 * Wraps common date/time formatting, shift label generation, and
 * convenience accessors for the schedule store.
 */

import { computed } from 'vue'
import { useScheduleStore } from '@/stores/schedule'

/**
 * Returns scheduling helpers and computed refs.
 *
 * @returns An object containing:
 *  - `nextShift`      : The authenticated user's next upcoming shift (or null).
 *  - `pendingSwaps`   : Count of swap requests in "pending" or "offered" status.
 *  - `pendingTimeOff` : Count of pending time-off requests.
 *  - `formatShiftTime`: Formats a HH:MM:SS time string into a short label.
 *  - `formatWeekLabel`: Formats a week_start date into a human-readable week range.
 */
export function useSchedule() {
  const store = useScheduleStore()

  /** The user's next upcoming shift, or null if none. */
  const nextShift = computed(() => {
    return store.myShifts.length > 0 ? store.myShifts[0] : null
  })

  /** Count of swap requests awaiting action (pending or offered). */
  const pendingSwaps = computed(() => {
    return store.swapRequests.filter(
      (s) => s.status === 'pending' || s.status === 'offered'
    ).length
  })

  /** Count of time-off requests still pending manager review. */
  const pendingTimeOff = computed(() => {
    return store.timeOffRequests.filter((r) => r.status === 'pending').length
  })

  /**
   * Formats a HH:MM or HH:MM:SS time string into a short label.
   * e.g. "16:00" → "4:00 PM", "10:30:00" → "10:30 AM"
   */
  function formatShiftTime(time: string): string {
    const [h, m] = time.split(':').map(Number)
    const period = h >= 12 ? 'PM' : 'AM'
    const hour = h % 12 || 12
    return `${hour}:${String(m).padStart(2, '0')} ${period}`
  }

  /**
   * Formats a week_start date string into a human-readable range.
   * e.g. "2026-02-23" → "Feb 23 – Mar 1"
   */
  function formatWeekLabel(weekStart: string): string {
    const start = new Date(weekStart + 'T00:00:00')
    const end = new Date(start)
    end.setDate(end.getDate() + 6)

    const opts: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' }
    return `${start.toLocaleDateString([], opts)} – ${end.toLocaleDateString([], opts)}`
  }

  return {
    nextShift,
    pendingSwaps,
    pendingTimeOff,
    formatShiftTime,
    formatWeekLabel,
  }
}
