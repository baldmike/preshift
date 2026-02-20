/**
 * composables/useSchedule.ts
 *
 * Provides helper functions and computed refs for the scheduling system.
 * Wraps common date/time formatting, shift label generation, and
 * convenience accessors for the schedule store.
 */

import { computed } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import type { ScheduleEntry } from '@/types'

export function useSchedule() {
  const store = useScheduleStore()

  const nextShift = computed(() => {
    return store.myShifts.length > 0 ? store.myShifts[0] : null
  })

  const currentWeekRange = computed(() => {
    const today = new Date()
    const jsDay = today.getDay()
    const offset = (jsDay + 6) % 7

    const monday = new Date(today)
    monday.setDate(today.getDate() - offset)

    const sunday = new Date(monday)
    sunday.setDate(monday.getDate() + 6)

    const toISO = (d: Date) =>
      `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`

    return { monday: toISO(monday), sunday: toISO(sunday) }
  })

  const currentWeekShifts = computed<Record<string, ScheduleEntry[]>>(() => {
    const { monday, sunday } = currentWeekRange.value
    const result: Record<string, ScheduleEntry[]> = {}

    for (const entry of store.myShifts) {
      const d = entry.date.split('T')[0]
      if (d >= monday && d <= sunday) {
        if (!result[d]) {
          result[d] = []
        }
        result[d].push(entry)
      }
    }

    return result
  })

  /** Count of shift drops with status "open". */
  const pendingDrops = computed(() => {
    return store.shiftDrops.filter((d) => d.status === 'open').length
  })

  /** Count of time-off requests still pending manager review. */
  const pendingTimeOff = computed(() => {
    return store.timeOffRequests.filter((r) => r.status === 'pending').length
  })

  function formatShiftTime(time: string): string {
    const [h, m] = time.split(':').map(Number)
    const period = h >= 12 ? 'PM' : 'AM'
    const hour = h % 12 || 12
    return `${hour}:${String(m).padStart(2, '0')} ${period}`
  }

  function formatWeekLabel(weekStart: string): string {
    const start = new Date(weekStart.split('T')[0] + 'T00:00:00')
    const end = new Date(start)
    end.setDate(end.getDate() + 6)

    const opts: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric' }
    return `${start.toLocaleDateString([], opts)} – ${end.toLocaleDateString([], opts)}`
  }

  return {
    nextShift,
    currentWeekShifts,
    currentWeekRange,
    pendingDrops,
    pendingTimeOff,
    formatShiftTime,
    formatWeekLabel,
  }
}
