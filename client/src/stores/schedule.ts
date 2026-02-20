/**
 * stores/schedule.ts
 *
 * Pinia store for the scheduling system. Manages state for shift templates,
 * weekly schedules, schedule entries, shift drops, time-off requests, and
 * the authenticated staff member's upcoming shifts.
 */

import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/composables/useApi'
import type {
  ShiftTemplate,
  Schedule,
  ScheduleEntry,
  ShiftDrop,
  TimeOffRequest,
} from '@/types'

export const useScheduleStore = defineStore('schedule', () => {
  // ── State ──────────────────────────────────────────────────────────────

  const shiftTemplates = ref<ShiftTemplate[]>([])
  const schedules = ref<Schedule[]>([])
  const currentSchedule = ref<Schedule | null>(null)
  const myShifts = ref<ScheduleEntry[]>([])
  const shiftDrops = ref<ShiftDrop[]>([])
  const timeOffRequests = ref<TimeOffRequest[]>([])
  const loading = ref(false)

  // ── Fetch actions ──────────────────────────────────────────────────────

  async function fetchShiftTemplates() {
    const { data } = await api.get<ShiftTemplate[]>('/api/shift-templates')
    shiftTemplates.value = data
  }

  async function fetchSchedules() {
    const { data } = await api.get<Schedule[]>('/api/schedules')
    schedules.value = data
  }

  async function fetchSchedule(id: number) {
    const { data } = await api.get<Schedule>(`/api/schedules/${id}`)
    currentSchedule.value = data
  }

  async function fetchMyShifts() {
    const { data } = await api.get<ScheduleEntry[]>('/api/my-shifts')
    myShifts.value = data
  }

  async function fetchShiftDrops() {
    const { data } = await api.get<ShiftDrop[]>('/api/shift-drops')
    shiftDrops.value = data
  }

  async function fetchTimeOffRequests() {
    const { data } = await api.get<TimeOffRequest[]>('/api/time-off-requests')
    timeOffRequests.value = data
  }

  // ── Realtime mutations ─────────────────────────────────────────────────

  function onSchedulePublished(schedule: Schedule) {
    const idx = schedules.value.findIndex((s) => s.id === schedule.id)
    if (idx !== -1) {
      schedules.value[idx] = { ...schedules.value[idx], ...schedule }
    } else {
      schedules.value.push(schedule)
    }
    fetchMyShifts()
  }

  function upsertShiftDrop(drop: ShiftDrop) {
    const idx = shiftDrops.value.findIndex((d) => d.id === drop.id)
    if (idx !== -1) {
      shiftDrops.value[idx] = drop
    } else {
      shiftDrops.value.push(drop)
    }
  }

  function upsertTimeOffRequest(request: TimeOffRequest) {
    const idx = timeOffRequests.value.findIndex((r) => r.id === request.id)
    if (idx !== -1) {
      timeOffRequests.value[idx] = request
    } else {
      timeOffRequests.value.push(request)
    }
  }

  return {
    shiftTemplates,
    schedules,
    currentSchedule,
    myShifts,
    shiftDrops,
    timeOffRequests,
    loading,
    fetchShiftTemplates,
    fetchSchedules,
    fetchSchedule,
    fetchMyShifts,
    fetchShiftDrops,
    fetchTimeOffRequests,
    onSchedulePublished,
    upsertShiftDrop,
    upsertTimeOffRequest,
  }
})
