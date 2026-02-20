/**
 * stores/schedule.ts
 *
 * Pinia store for the scheduling system. Manages state for shift templates,
 * weekly schedules, schedule entries, swap requests, time-off requests, and
 * the authenticated staff member's upcoming shifts.
 *
 * Data flow:
 *  1. Each section has a dedicated fetch action that hits the corresponding
 *     API endpoint.
 *  2. Realtime updates arrive via Reverb WebSocket events (schedule.published,
 *     swap.requested, swap.offered, swap.resolved, time-off.resolved).
 *  3. Granular mutations let WebSocket handlers patch the store without a
 *     full re-fetch.
 */

import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/composables/useApi'
import type {
  ShiftTemplate,
  Schedule,
  ScheduleEntry,
  SwapRequest,
  TimeOffRequest,
} from '@/types'

export const useScheduleStore = defineStore('schedule', () => {
  // ── State ──────────────────────────────────────────────────────────────

  /** Reusable shift definitions for the location */
  const shiftTemplates = ref<ShiftTemplate[]>([])

  /** Weekly schedules (current + upcoming) */
  const schedules = ref<Schedule[]>([])

  /** The currently selected/viewed schedule with full entries */
  const currentSchedule = ref<Schedule | null>(null)

  /** The authenticated staff member's upcoming shifts */
  const myShifts = ref<ScheduleEntry[]>([])

  /** All swap requests visible to the current user */
  const swapRequests = ref<SwapRequest[]>([])

  /** All time-off requests visible to the current user */
  const timeOffRequests = ref<TimeOffRequest[]>([])

  /** True while any fetch is in progress */
  const loading = ref(false)

  // ── Fetch actions ──────────────────────────────────────────────────────

  /** Fetch all shift templates for the location. */
  async function fetchShiftTemplates() {
    const { data } = await api.get<ShiftTemplate[]>('/api/shift-templates')
    shiftTemplates.value = data
  }

  /** Fetch the list of schedules (current + upcoming weeks). */
  async function fetchSchedules() {
    const { data } = await api.get<Schedule[]>('/api/schedules')
    schedules.value = data
  }

  /** Fetch a single schedule with all its entries and relations. */
  async function fetchSchedule(id: number) {
    const { data } = await api.get<Schedule>(`/api/schedules/${id}`)
    currentSchedule.value = data
  }

  /** Fetch the authenticated user's upcoming shifts. */
  async function fetchMyShifts() {
    const { data } = await api.get<ScheduleEntry[]>('/api/my-shifts')
    myShifts.value = data
  }

  /** Fetch swap requests visible to the current user. */
  async function fetchSwapRequests() {
    const { data } = await api.get<SwapRequest[]>('/api/swap-requests')
    swapRequests.value = data
  }

  /** Fetch time-off requests visible to the current user. */
  async function fetchTimeOffRequests() {
    const { data } = await api.get<TimeOffRequest[]>('/api/time-off-requests')
    timeOffRequests.value = data
  }

  // ── Realtime mutations ─────────────────────────────────────────────────

  /**
   * Handle a SchedulePublished event — update the schedule in the local list
   * or add it if it's new, and refresh my shifts.
   */
  function onSchedulePublished(schedule: Schedule) {
    const idx = schedules.value.findIndex((s) => s.id === schedule.id)
    if (idx !== -1) {
      schedules.value[idx] = { ...schedules.value[idx], ...schedule }
    } else {
      schedules.value.push(schedule)
    }
    // Refresh my shifts since the published schedule may contain new assignments
    fetchMyShifts()
  }

  /**
   * Handle swap request events — update or add the swap request in local state.
   */
  function upsertSwapRequest(swap: SwapRequest) {
    const idx = swapRequests.value.findIndex((s) => s.id === swap.id)
    if (idx !== -1) {
      swapRequests.value[idx] = swap
    } else {
      swapRequests.value.push(swap)
    }
  }

  /**
   * Handle time-off resolved events — update the request in local state.
   */
  function upsertTimeOffRequest(request: TimeOffRequest) {
    const idx = timeOffRequests.value.findIndex((r) => r.id === request.id)
    if (idx !== -1) {
      timeOffRequests.value[idx] = request
    } else {
      timeOffRequests.value.push(request)
    }
  }

  // ── Public API ─────────────────────────────────────────────────────────
  return {
    // State
    shiftTemplates,
    schedules,
    currentSchedule,
    myShifts,
    swapRequests,
    timeOffRequests,
    loading,
    // Fetch actions
    fetchShiftTemplates,
    fetchSchedules,
    fetchSchedule,
    fetchMyShifts,
    fetchSwapRequests,
    fetchTimeOffRequests,
    // Realtime mutations
    onSchedulePublished,
    upsertSwapRequest,
    upsertTimeOffRequest,
  }
})
