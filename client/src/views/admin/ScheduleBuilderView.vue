<script setup lang="ts">
/**
 * ScheduleBuilderView -- manager's weekly schedule builder.
 *
 * This view provides a two-panel layout for building and publishing weekly
 * schedules. The left sidebar lists all existing schedule weeks (with status
 * badges and a "Create Week" form). The right main area shows the selected
 * schedule's grid, a publish/unpublish toggle, an inline form for adding
 * schedule entries, and a section of approved time-off warnings.
 *
 * Time slots are created automatically when adding entries — managers pick
 * start/end times from dropdowns and the system handles the rest.
 *
 * API endpoints used:
 *   GET    /api/shift-templates
 *   POST   /api/shift-templates
 *   GET    /api/schedules
 *   POST   /api/schedules
 *   GET    /api/schedules/:id
 *   POST   /api/schedules/:id/publish
 *   POST   /api/schedules/:id/unpublish
 *   POST   /api/schedule-entries
 *   DELETE /api/schedule-entries/:id
 *   GET    /api/time-off-requests
 *   GET    /api/users
 */
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import api from '@/composables/useApi'
import { useScheduleStore } from '@/stores/schedule'
import { useSchedule } from '@/composables/useSchedule'
import { useAuth } from '@/composables/useAuth'
import { useLocationChannel } from '@/composables/useReverb'
import AppShell from '@/components/layout/AppShell.vue'
import ScheduleGrid from '@/components/ScheduleGrid.vue'
import TimeOffBadge from '@/components/TimeOffBadge.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { Schedule, ShiftTemplate, TimeOffRequest, User } from '@/types'

// ── Composables ──────────────────────────────────────────────────────────────

const { formatWeekLabel } = useSchedule()
const scheduleStore = useScheduleStore()
const { locationId } = useAuth()

// ── State: schedules ─────────────────────────────────────────────────────────

// List of all weekly schedules fetched from the API
const schedules = ref<Schedule[]>([])
// True while the schedule list is being loaded
const loading = ref(false)
// The currently selected schedule (with entries eagerly loaded)
const activeSchedule = ref<Schedule | null>(null)
// True while a publish/unpublish action is in-flight
const publishing = ref(false)

// ── State: create-week form ──────────────────────────────────────────────────

// Controls visibility of the "Create Week" date input
const showCreateWeek = ref(false)
// The week_start date value for creating a new schedule
const newWeekStart = ref('')
// True while the create-schedule request is in-flight
const creatingWeek = ref(false)

// ── State: add-entry form ────────────────────────────────────────────────────

// Controls visibility of the add-entry modal/inline form
const showEntryForm = ref(false)
// True while the add-entry request is in-flight
const addingEntry = ref(false)
// Form fields for adding a new schedule entry
const entryForm = ref({
  start_time: '',
  date: '',
  user_id: null as number | null,
  role: 'server' as 'server' | 'bartender',
  notes: '',
})

// Time options for the start/end dropdowns (30-min increments, full day)
const timeOptions: { label: string; value: string }[] = []
for (let h = 0; h < 24; h++) {
  for (const m of [0, 30]) {
    const hh = String(h).padStart(2, '0')
    const mm = String(m).padStart(2, '0')
    const hour12 = h === 0 ? 12 : h > 12 ? h - 12 : h
    const ampm = h >= 12 ? 'PM' : 'AM'
    timeOptions.push({ label: `${hour12}:${mm} ${ampm}`, value: `${hh}:${mm}` })
  }
}

// Auto-set the role dropdown to match the selected user's role
watch(() => entryForm.value.user_id, (uid) => {
  if (!uid) return
  const u = users.value.find(u => u.id === uid)
  if (u && (u.role === 'server' || u.role === 'bartender')) {
    entryForm.value.role = u.role
  }
})

// ── State: shift templates (managed automatically behind the scenes) ────────

// List of all shift templates for this location (used for grid rows)
const shiftTemplates = ref<ShiftTemplate[]>([])

// ── State: users & time-off ─────────────────────────────────────────────────

// All staff users at this location (for the entry form's user dropdown)
const rawUsers = ref<User[]>([])
const roleOrder: Record<string, number> = { admin: 0, manager: 1, bartender: 2, server: 3 }

/**
 * Set of user IDs already scheduled on the currently selected entry form date.
 * Used to filter the staff dropdown so a user can't be assigned twice on the same day.
 */
const scheduledUserIdsOnDate = computed<Set<number>>(() => {
  if (!activeSchedule.value?.entries || !entryForm.value.date) return new Set()
  const ids = activeSchedule.value.entries
    .filter((e: any) => e.date === entryForm.value.date)
    .map((e: any) => e.user_id)
  return new Set(ids)
})

/**
 * Sorted user list: filters out users already scheduled on the selected date,
 * then sorts available users first, then unavailable.
 * Within each group, sorted by role tier then name.
 */
const users = computed(() => {
  // Exclude users already scheduled on this date
  const list = rawUsers.value.filter(u => !scheduledUserIdsOnDate.value.has(u.id))
  const dayName = entryForm.value.date ? getDayName(entryForm.value.date) : ''
  const startTime = entryForm.value.start_time

  list.sort((a, b) => {
    // If we have a date + time, sort available before unavailable
    if (dayName && startTime) {
      const aAvail = isUserAvailable(a, dayName, startTime) ? 0 : 1
      const bAvail = isUserAvailable(b, dayName, startTime) ? 0 : 1
      if (aAvail !== bAvail) return aAvail - bAvail
    }
    // Then by role tier, then by name
    return (roleOrder[a.role] ?? 99) - (roleOrder[b.role] ?? 99) || a.name.localeCompare(b.name)
  })

  return list
})
// Approved time-off requests (shown as warnings below the grid)
const timeOffRequests = ref<TimeOffRequest[]>([])

// ── Fetch functions ──────────────────────────────────────────────────────────

/**
 * Fetches all weekly schedules from GET /api/schedules.
 * Sorts by week_start descending so the most recent week appears first.
 */
async function fetchSchedules() {
  loading.value = true
  try {
    const { data } = await api.get<Schedule[]>('/api/schedules')
    schedules.value = data
  } finally {
    loading.value = false
  }
}

/**
 * Fetches all shift templates from GET /api/shift-templates.
 */
async function fetchShiftTemplates() {
  try {
    const { data } = await api.get<ShiftTemplate[]>('/api/shift-templates')
    shiftTemplates.value = data
  } catch {
    toast('Failed to load shift templates', 'error')
  }
}

/**
 * Fetches all users from GET /api/users.
 * Provides the dropdown options for the add-entry form.
 */
async function fetchUsers() {
  try {
    const { data } = await api.get<User[]>('/api/users')
    rawUsers.value = data
  } catch {
    toast('Failed to load users', 'error')
  }
}

/**
 * Fetches all time-off requests from GET /api/time-off-requests.
 * Only approved requests are shown as schedule warnings.
 */
async function fetchTimeOff() {
  try {
    const { data } = await api.get<TimeOffRequest[]>('/api/time-off-requests')
    timeOffRequests.value = data
  } catch {
    // Silent failure -- time-off warnings are non-critical
  }
}

/**
 * Loads a single schedule by ID (with entries eagerly loaded) and sets
 * it as the active schedule for the main area.
 */
async function selectSchedule(id: number) {
  try {
    const { data } = await api.get<Schedule>(`/api/schedules/${id}`)
    activeSchedule.value = data
  } catch {
    toast('Failed to load schedule', 'error')
  }
}

// ── Schedule CRUD ────────────────────────────────────────────────────────────

/**
 * Creates a new weekly schedule with the provided week_start date.
 * Adds the new schedule to the local list and selects it.
 */
async function createWeek() {
  if (!newWeekStart.value) return
  creatingWeek.value = true
  try {
    const { data } = await api.post<Schedule>('/api/schedules', {
      week_start: newWeekStart.value,
    })
    schedules.value.unshift(data)
    // Immediately select the newly created schedule
    await selectSchedule(data.id)
    newWeekStart.value = ''
    showCreateWeek.value = false
    toast('Schedule week created', 'success')
  } catch {
    toast('Failed to create schedule week', 'error')
  } finally {
    creatingWeek.value = false
  }
}

/**
 * Publishes the currently active schedule so staff can see it.
 * POST /api/schedules/:id/publish
 */
async function publishSchedule() {
  if (!activeSchedule.value) return
  publishing.value = true
  try {
    const { data } = await api.post<Schedule>(`/api/schedules/${activeSchedule.value.id}/publish`)
    activeSchedule.value = data
    // Also update the sidebar list entry
    const idx = schedules.value.findIndex((s) => s.id === data.id)
    if (idx !== -1) schedules.value[idx] = data
    toast('Schedule published', 'success')
  } catch {
    toast('Failed to publish schedule', 'error')
  } finally {
    publishing.value = false
  }
}

/**
 * Unpublishes the currently active schedule, reverting it to draft.
 * POST /api/schedules/:id/unpublish
 */
async function unpublishSchedule() {
  if (!activeSchedule.value) return
  publishing.value = true
  try {
    const { data } = await api.post<Schedule>(`/api/schedules/${activeSchedule.value.id}/unpublish`)
    activeSchedule.value = data
    // Also update the sidebar list entry
    const idx = schedules.value.findIndex((s) => s.id === data.id)
    if (idx !== -1) schedules.value[idx] = data
    toast('Schedule unpublished', 'success')
  } catch {
    toast('Failed to unpublish schedule', 'error')
  } finally {
    publishing.value = false
  }
}

// ── Schedule entry management ────────────────────────────────────────────────

/**
 * Called when the ScheduleGrid emits "add-entry". Pre-fills the entry form
 * with times from the shift template and the date, then shows the form.
 */
function handleAddEntry(payload: { shiftTemplateId: number; date: string }) {
  const tmpl = shiftTemplates.value.find(t => t.id === payload.shiftTemplateId)
  entryForm.value = {
    start_time: tmpl ? tmpl.start_time.substring(0, 5) : '',
    date: payload.date,
    user_id: null,
    role: 'server',
    notes: '',
  }
  showEntryForm.value = true
}

/**
 * Formats a "HH:MM" string into "H:MM AM/PM" for display.
 */
function formatTimeLabel(time: string): string {
  const [hStr, mStr] = time.split(':')
  let h = parseInt(hStr, 10)
  const ampm = h >= 12 ? 'PM' : 'AM'
  if (h === 0) h = 12
  else if (h > 12) h -= 12
  return `${h}:${mStr} ${ampm}`
}

/**
 * Saves a new schedule entry via POST /api/schedule-entries.
 * Finds or auto-creates a shift template matching the selected times,
 * then creates the entry. On success, re-fetches the active schedule.
 */
async function saveEntry() {
  if (!activeSchedule.value || !entryForm.value.user_id || !entryForm.value.start_time) return
  addingEntry.value = true
  try {
    // Find an existing template with matching start time
    let template = shiftTemplates.value.find(
      t => t.start_time.substring(0, 5) === entryForm.value.start_time
    )

    // If none found, auto-create one named after the start time
    if (!template) {
      const name = formatTimeLabel(entryForm.value.start_time)
      const { data } = await api.post<ShiftTemplate>('/api/shift-templates', {
        name,
        start_time: entryForm.value.start_time,
      })
      shiftTemplates.value.push(data)
      template = data
    }

    await api.post('/api/schedule-entries', {
      schedule_id: activeSchedule.value.id,
      user_id: entryForm.value.user_id,
      shift_template_id: template.id,
      date: entryForm.value.date,
      role: entryForm.value.role,
      notes: entryForm.value.notes || null,
    })
    // Refresh the active schedule to show the new entry in the grid
    await selectSchedule(activeSchedule.value.id)
    showEntryForm.value = false
    toast('Entry added', 'success')
  } catch {
    toast('Failed to add entry', 'error')
  } finally {
    addingEntry.value = false
  }
}

/**
 * Called when the ScheduleGrid emits "remove-entry". Deletes the entry
 * and re-fetches the active schedule to refresh the grid.
 */
async function handleRemoveEntry(entryId: number) {
  if (!activeSchedule.value) return
  if (!confirm('Remove this schedule entry?')) return
  try {
    await api.delete(`/api/schedule-entries/${entryId}`)
    // Refresh the active schedule to reflect the removal
    await selectSchedule(activeSchedule.value.id)
    toast('Entry removed', 'success')
  } catch {
    toast('Failed to remove entry', 'error')
  }
}

// ── Computed helpers ─────────────────────────────────────────────────────────

/**
 * Filters time-off requests to only show approved ones that overlap
 * the currently selected schedule's week. Used as warnings below the grid.
 */
const approvedTimeOff = computed(() => {
  if (!activeSchedule.value) return []
  return timeOffRequests.value.filter((r) => r.status === 'approved')
})

// ── Toast helper ─────────────────────────────────────────────────────────────

// ── Availability helpers ──────────────────────────────────────────────────────

const DAY_NAMES = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as const
const DAY_LABELS: Record<string, string> = {
  sunday: 'Sundays', monday: 'Mondays', tuesday: 'Tuesdays', wednesday: 'Wednesdays',
  thursday: 'Thursdays', friday: 'Fridays', saturday: 'Saturdays',
}

/** Returns the lowercase day name for a given ISO date string */
function getDayName(dateStr: string): string {
  const date = new Date(dateStr + 'T00:00:00')
  return DAY_NAMES[date.getDay()]
}

/** Convert "HH:MM" to total minutes for range comparison */
function timeToMinutes(time: string): number {
  const [h, m] = time.split(':').map(Number)
  return h * 60 + m
}

/**
 * Checks whether a user is available for a specific day + start time.
 * Availability slots:
 *   "open"  → available any time
 *   "10:30" → available 10:30 AM – 6:00 PM (630–1080 min)
 *   "16:30" → available 4:30 PM – close (990+ min)
 *
 * If the user has no availability set (null), they are treated as not yet
 * configured — we show them but mark as "not set".
 */
function isUserAvailable(user: User, dayName: string, startTime: string): boolean {
  if (!user.availability) return true // Not set yet — show them
  const slots = user.availability[dayName] ?? []
  if (slots.length === 0) return false // Day has no availability
  if (slots.includes('open')) return true // Open = any time

  const startMin = timeToMinutes(startTime)
  // "10:30" covers shifts starting 10:30 AM (630) to before 6:00 PM (1080)
  if (slots.includes('10:30') && startMin >= 630 && startMin < 1080) return true
  // "16:30" covers shifts starting 4:30 PM (990) onwards
  if (slots.includes('16:30') && startMin >= 990) return true

  return false
}

/** Returns the availability indicator for the staff dropdown */
function availabilityIndicator(user: User): string {
  if (!entryForm.value.date || !entryForm.value.start_time) return ''
  if (!user.availability) return ' (not set)'
  const dayName = getDayName(entryForm.value.date)
  return isUserAvailable(user, dayName, entryForm.value.start_time) ? '' : ' (unavailable)'
}

/** Computed warning message when an unavailable user is selected */
const availabilityWarning = computed(() => {
  if (!entryForm.value.user_id || !entryForm.value.date || !entryForm.value.start_time) return ''
  const user = users.value.find(u => u.id === entryForm.value.user_id)
  if (!user) return ''
  const dayName = getDayName(entryForm.value.date)
  if (isUserAvailable(user, dayName, entryForm.value.start_time)) return ''
  return `${user.name} is not available on ${DAY_LABELS[dayName]} at this time`
})

/** Dispatches a global toast notification via CustomEvent. */
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// ── Lifecycle ────────────────────────────────────────────────────────────────

let ackChannel: ReturnType<typeof useLocationChannel> | null = null

// Fetch all required data on component mount
onMounted(() => {
  fetchSchedules()
  fetchShiftTemplates()
  fetchUsers()
  fetchTimeOff()
  scheduleStore.fetchAckSummary()

  if (locationId.value) {
    ackChannel = useLocationChannel(locationId.value)
    ackChannel.listen('.acknowledgment.recorded', (e: any) => {
      scheduleStore.updateUserAckPercentage(e.user_id, e.percentage)
    })
  }
})

onUnmounted(() => {
  if (ackChannel) {
    ackChannel.stopListening('.acknowledgment.recorded')
  }
})
</script>

<template>
  <AppShell>
    <div class="space-y-5">
      <!-- ── Page header ──────────────────────────────────────────────────── -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-white tracking-tight">Schedule Builder</h1>
          <p class="text-xs text-gray-500 mt-0.5">Build and publish weekly schedules</p>
        </div>
        <router-link
              to="/manage/daily"
              class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Corner!
            </router-link>
      </div>

      <!-- ── Two-panel layout: sidebar + main ──────────────────────────── -->
      <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-4">

        <!-- ════════════════ LEFT SIDEBAR: schedule list ════════════════ -->
        <div class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <!-- Sidebar header with "Create Week" toggle -->
          <div class="flex items-center justify-between px-3 py-2.5 border-b border-white/[0.06]">
            <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">Weeks</h2>
            <button
              class="flex items-center justify-center w-6 h-6 rounded-md bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-gray-200 transition-colors"
              @click="showCreateWeek = !showCreateWeek"
              :title="showCreateWeek ? 'Close' : 'Create week'"
            >
              <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-45': showCreateWeek }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </div>

          <!-- Create Week inline form -->
          <div v-if="showCreateWeek" class="px-3 py-2.5 bg-black/20 border-b border-white/[0.06]">
            <form @submit.prevent="createWeek" class="space-y-2">
              <label class="block text-[10px] font-medium text-gray-500 uppercase tracking-wide">Week Start (Monday)</label>
              <input
                v-model="newWeekStart"
                type="date"
                required
                class="w-full px-3 py-2 text-sm text-gray-200 bg-white/5 border border-white/10 rounded-md outline-none focus:border-white/25"
              />
              <div class="flex gap-2">
                <button
                  type="submit"
                  :disabled="creatingWeek"
                  class="bg-blue-500/25 text-blue-300 hover:bg-blue-500/35 px-3 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50"
                >{{ creatingWeek ? 'Creating...' : 'Create' }}</button>
                <button
                  type="button"
                  class="bg-transparent text-gray-400 hover:bg-white/5 px-3 py-1.5 text-xs font-semibold rounded-md"
                  @click="showCreateWeek = false; newWeekStart = ''"
                >Cancel</button>
              </div>
            </form>
          </div>

          <!-- Schedule list -->
          <div class="max-h-[45vh] overflow-y-auto">
            <!-- Loading state -->
            <div v-if="loading" class="flex justify-center py-6">
              <svg class="animate-spin h-5 w-5 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
            </div>

            <!-- Empty state -->
            <p v-else-if="!schedules.length" class="text-gray-600 text-xs text-center py-6">
              No schedules yet
            </p>

            <!-- Schedule week rows -->
            <button
              v-else
              v-for="schedule in schedules"
              :key="schedule.id"
              class="w-full flex items-center gap-2 px-3 py-2.5 text-left hover:bg-white/[0.04] transition-colors border-b border-white/[0.04] last:border-b-0"
              :class="{
                'bg-white/[0.06]': activeSchedule?.id === schedule.id,
              }"
              @click="selectSchedule(schedule.id)"
            >
              <!-- Week label (e.g. "Feb 23 – Mar 1") -->
              <span class="text-sm text-gray-300 flex-1 truncate">
                {{ formatWeekLabel(schedule.week_start) }}
              </span>
              <!-- Status badge (draft = amber, published = green) -->
              <BadgePill
                :label="schedule.status"
                :color="schedule.status === 'published' ? 'green' : 'yellow'"
              />
            </button>
          </div>
        </div>

        <!-- ════════════════ RIGHT MAIN AREA ════════════════ -->
        <div class="space-y-4">
          <!-- No schedule selected state -->
          <div
            v-if="!activeSchedule"
            class="rounded-xl bg-white/[0.03] border border-white/[0.06] p-8 text-center"
          >
            <p class="text-gray-500 text-sm">Select a week from the sidebar to begin editing</p>
          </div>

          <!-- Active schedule content -->
          <template v-else>
            <!-- Schedule header: week label, status, publish/unpublish -->
            <div class="rounded-xl bg-white/[0.03] border border-white/[0.06] px-4 py-3 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <h2 class="text-base font-bold text-white">
                  {{ formatWeekLabel(activeSchedule.week_start) }}
                </h2>
                <BadgePill
                  :label="activeSchedule.status"
                  :color="activeSchedule.status === 'published' ? 'green' : 'yellow'"
                />
              </div>
              <div>
                <!-- Publish button (shown when draft) -->
                <button
                  v-if="activeSchedule.status === 'draft'"
                  :disabled="publishing"
                  class="bg-green-500/25 text-green-300 hover:bg-green-500/35 px-3 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50 transition-colors"
                  @click="publishSchedule"
                >{{ publishing ? 'Publishing...' : 'Publish' }}</button>
                <!-- Unpublish button (shown when published) -->
                <button
                  v-else
                  :disabled="publishing"
                  class="bg-amber-500/25 text-amber-300 hover:bg-amber-500/35 px-3 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50 transition-colors"
                  @click="unpublishSchedule"
                >{{ publishing ? 'Unpublishing...' : 'Unpublish' }}</button>
              </div>
            </div>

            <!-- Schedule grid component -->
            <ScheduleGrid
              :schedule="activeSchedule"
              :shift-templates="shiftTemplates"
              :ack-map="scheduleStore.ackSummaryMap"
              @add-entry="handleAddEntry"
              @remove-entry="handleRemoveEntry"
            />

            <!-- ── Add entry inline form (shown when triggered from grid) ── -->
            <div
              v-if="showEntryForm"
              class="rounded-xl bg-white/[0.03] border border-white/[0.06] p-4"
            >
              <h3 class="text-sm font-semibold text-white mb-3">
                Add Entry — {{ entryForm.date }}
              </h3>
              <form @submit.prevent="saveEntry" class="space-y-3">
                <!-- Start Time -->
                <div>
                  <label class="block text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-1">Start Time</label>
                  <select
                    v-model="entryForm.start_time"
                    required
                    class="w-full px-3 py-2 text-sm text-gray-200 bg-white/5 border border-white/10 rounded-md outline-none focus:border-white/25"
                  >
                    <option value="" disabled>Select time...</option>
                    <option v-for="opt in timeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <!-- User selector -->
                  <div>
                    <label class="block text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-1">Staff Member</label>
                    <select
                      v-model.number="entryForm.user_id"
                      required
                      class="w-full px-3 py-2 text-sm text-gray-200 bg-white/5 border border-white/10 rounded-md outline-none focus:border-white/25"
                    >
                      <option :value="null" disabled>Select staff...</option>
                      <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role }}){{ availabilityIndicator(u) }}</option>
                    </select>
                  </div>
                  <!-- Role selector -->
                  <div>
                    <label class="block text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-1">Role</label>
                    <select
                      v-model="entryForm.role"
                      class="w-full px-3 py-2 text-sm text-gray-200 bg-white/5 border border-white/10 rounded-md outline-none focus:border-white/25"
                    >
                      <option value="server">Server</option>
                      <option value="bartender">Bartender</option>
                    </select>
                  </div>
                </div>
                <!-- Availability warning -->
                <p v-if="availabilityWarning" class="text-amber-400 text-sm flex items-center gap-1">
                  <span>⚠</span> {{ availabilityWarning }}
                </p>
                <!-- Notes (optional) -->
                <div>
                  <label class="block text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-1">Notes (optional)</label>
                  <input
                    v-model="entryForm.notes"
                    placeholder="e.g. training, section A"
                    class="w-full px-3 py-2 text-sm text-gray-200 bg-white/5 border border-white/10 rounded-md outline-none focus:border-white/25"
                  />
                </div>
                <div class="flex gap-2">
                  <button
                    type="submit"
                    :disabled="addingEntry"
                    class="bg-blue-500/25 text-blue-300 hover:bg-blue-500/35 px-3 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50"
                  >{{ addingEntry ? 'Adding...' : 'Add Entry' }}</button>
                  <button
                    type="button"
                    class="bg-transparent text-gray-400 hover:bg-white/5 px-3 py-1.5 text-xs font-semibold rounded-md"
                    @click="showEntryForm = false"
                  >Cancel</button>
                </div>
              </form>
            </div>

            <!-- ── Approved time-off warnings ──────────────────────────── -->
            <div
              v-if="approvedTimeOff.length"
              class="rounded-xl bg-white/[0.03] border border-white/[0.06] p-4"
            >
              <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 mb-3 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                Approved Time Off
              </h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <TimeOffBadge
                  v-for="req in approvedTimeOff"
                  :key="req.id"
                  :request="req"
                />
              </div>
            </div>
          </template>
        </div>
      </div>

    </div>
  </AppShell>
</template>
