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
 * A collapsible "Shift Templates" panel at the bottom lets managers CRUD
 * the reusable shift definitions (e.g. "Lunch 10:30–3:00") that are
 * referenced when building the grid.
 *
 * API endpoints used:
 *   GET    /api/shift-templates
 *   POST   /api/shift-templates
 *   PATCH  /api/shift-templates/:id
 *   DELETE /api/shift-templates/:id
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
import { ref, computed, onMounted } from 'vue'
import api from '@/composables/useApi'
import { useSchedule } from '@/composables/useSchedule'
import AppShell from '@/components/layout/AppShell.vue'
import ScheduleGrid from '@/components/ScheduleGrid.vue'
import TimeOffBadge from '@/components/TimeOffBadge.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { Schedule, ShiftTemplate, TimeOffRequest, User } from '@/types'

// ── Composables ──────────────────────────────────────────────────────────────

const { formatShiftTime, formatWeekLabel } = useSchedule()

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
  shift_template_id: null as number | null,
  date: '',
  user_id: null as number | null,
  role: 'server' as 'server' | 'bartender',
  notes: '',
})

// ── State: shift templates ───────────────────────────────────────────────────

// List of all shift templates for this location
const shiftTemplates = ref<ShiftTemplate[]>([])
// Controls visibility of the shift templates management section
const showTemplates = ref(false)
// Controls visibility of the shift template create/edit form
const showTemplateForm = ref(false)
// When non-null, the template form is in "edit" mode for this template ID
const editingTemplateId = ref<number | null>(null)
// True while a template create/update request is in-flight
const submittingTemplate = ref(false)
// Template form fields
const templateForm = ref({ name: '', start_time: '', end_time: '' })

// ── State: users & time-off ─────────────────────────────────────────────────

// All staff users at this location (for the entry form's user dropdown)
const users = ref<User[]>([])
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
    users.value = data
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
 * with the shift template ID and date, then shows the form.
 */
function handleAddEntry(payload: { shiftTemplateId: number; date: string }) {
  entryForm.value = {
    shift_template_id: payload.shiftTemplateId,
    date: payload.date,
    user_id: null,
    role: 'server',
    notes: '',
  }
  showEntryForm.value = true
}

/**
 * Saves a new schedule entry via POST /api/schedule-entries.
 * On success, re-fetches the active schedule to refresh the grid.
 */
async function saveEntry() {
  if (!activeSchedule.value || !entryForm.value.user_id || !entryForm.value.shift_template_id) return
  addingEntry.value = true
  try {
    await api.post('/api/schedule-entries', {
      schedule_id: activeSchedule.value.id,
      user_id: entryForm.value.user_id,
      shift_template_id: entryForm.value.shift_template_id,
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

// ── Shift template CRUD ──────────────────────────────────────────────────────

/** Resets the template form fields, exits edit mode, and hides the form. */
function resetTemplateForm() {
  templateForm.value = { name: '', start_time: '', end_time: '' }
  editingTemplateId.value = null
  showTemplateForm.value = false
}

/** Populates the template form with an existing template's data for editing. */
function editTemplate(t: ShiftTemplate) {
  editingTemplateId.value = t.id
  templateForm.value = {
    name: t.name,
    // Strip seconds if present (e.g. "16:00:00" → "16:00") for time inputs
    start_time: t.start_time.substring(0, 5),
    end_time: t.end_time.substring(0, 5),
  }
  showTemplateForm.value = true
}

/**
 * Saves a shift template -- creates (POST) or updates (PATCH) based on
 * editingTemplateId. Updates the local list in-place on success.
 */
async function saveTemplate() {
  if (!templateForm.value.name.trim() || !templateForm.value.start_time || !templateForm.value.end_time) return
  submittingTemplate.value = true
  try {
    if (editingTemplateId.value) {
      const { data } = await api.patch<ShiftTemplate>(
        `/api/shift-templates/${editingTemplateId.value}`,
        templateForm.value,
      )
      const idx = shiftTemplates.value.findIndex((t) => t.id === editingTemplateId.value)
      if (idx !== -1) shiftTemplates.value[idx] = data
      toast('Template updated', 'success')
    } else {
      const { data } = await api.post<ShiftTemplate>('/api/shift-templates', templateForm.value)
      shiftTemplates.value.push(data)
      toast('Template created', 'success')
    }
    resetTemplateForm()
  } catch {
    toast('Failed to save template', 'error')
  } finally {
    submittingTemplate.value = false
  }
}

/**
 * Deletes a shift template after user confirmation.
 * Removes the record from the local list on success.
 */
async function deleteTemplate(id: number) {
  if (!confirm('Delete this shift template?')) return
  try {
    await api.delete(`/api/shift-templates/${id}`)
    shiftTemplates.value = shiftTemplates.value.filter((t) => t.id !== id)
    toast('Template deleted', 'success')
  } catch {
    toast('Failed to delete template', 'error')
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

/**
 * Returns the name of the shift template that matches the given ID.
 * Used in the entry form to display which shift slot is being assigned.
 */
function templateName(id: number | null): string {
  if (!id) return 'Unknown'
  return shiftTemplates.value.find((t) => t.id === id)?.name ?? 'Unknown'
}

// ── Toast helper ─────────────────────────────────────────────────────────────

/** Dispatches a global toast notification via CustomEvent. */
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// ── Lifecycle ────────────────────────────────────────────────────────────────

// Fetch all required data on component mount
onMounted(() => {
  fetchSchedules()
  fetchShiftTemplates()
  fetchUsers()
  fetchTimeOff()
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
          to="/manage"
          class="text-xs text-gray-500 hover:text-gray-300 transition-colors"
        >Back</router-link>
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
              @add-entry="handleAddEntry"
              @remove-entry="handleRemoveEntry"
            />

            <!-- ── Add entry inline form (shown when triggered from grid) ── -->
            <div
              v-if="showEntryForm"
              class="rounded-xl bg-white/[0.03] border border-white/[0.06] p-4"
            >
              <h3 class="text-sm font-semibold text-white mb-3">
                Add Entry — {{ templateName(entryForm.shift_template_id) }} on {{ entryForm.date }}
              </h3>
              <form @submit.prevent="saveEntry" class="space-y-3">
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
                      <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
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

      <!-- ════════════════ SHIFT TEMPLATES management ════════════════ -->
      <div class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
        <!-- Collapsible header -->
        <button
          class="w-full flex items-center justify-between px-4 py-3 hover:bg-white/[0.02] transition-colors"
          @click="showTemplates = !showTemplates"
        >
          <h2 class="text-xs font-bold uppercase tracking-wider text-gray-400">Shift Templates</h2>
          <svg
            class="w-4 h-4 text-gray-500 transition-transform"
            :class="{ 'rotate-180': showTemplates }"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div v-if="showTemplates" class="border-t border-white/[0.06]">
          <!-- Add template button -->
          <div class="px-4 py-2 border-b border-white/[0.06] flex justify-end">
            <button
              v-if="!showTemplateForm"
              class="bg-blue-500/25 text-blue-300 hover:bg-blue-500/35 px-3 py-1.5 text-xs font-semibold rounded-md"
              @click="showTemplateForm = true"
            >Add Template</button>
          </div>

          <!-- Template create/edit form -->
          <div v-if="showTemplateForm" class="px-4 py-3 bg-black/20 border-b border-white/[0.06]">
            <h3 class="text-sm font-semibold text-white mb-3">
              {{ editingTemplateId ? 'Edit' : 'Create' }} Shift Template
            </h3>
            <form @submit.prevent="saveTemplate" class="space-y-3">
              <div>
                <label class="block text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-1">Name</label>
                <input
                  v-model="templateForm.name"
                  placeholder="e.g. Lunch, Dinner, Double"
                  required
                  class="w-full px-3 py-2 text-sm text-gray-200 bg-white/5 border border-white/10 rounded-md outline-none focus:border-white/25"
                />
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-1">Start Time</label>
                  <input
                    v-model="templateForm.start_time"
                    type="time"
                    required
                    class="w-full px-3 py-2 text-sm text-gray-200 bg-white/5 border border-white/10 rounded-md outline-none focus:border-white/25"
                  />
                </div>
                <div>
                  <label class="block text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-1">End Time</label>
                  <input
                    v-model="templateForm.end_time"
                    type="time"
                    required
                    class="w-full px-3 py-2 text-sm text-gray-200 bg-white/5 border border-white/10 rounded-md outline-none focus:border-white/25"
                  />
                </div>
              </div>
              <div class="flex gap-2">
                <button
                  type="submit"
                  :disabled="submittingTemplate"
                  class="bg-blue-500/25 text-blue-300 hover:bg-blue-500/35 px-3 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50"
                >{{ submittingTemplate ? 'Saving...' : (editingTemplateId ? 'Update' : 'Create') }}</button>
                <button
                  type="button"
                  class="bg-transparent text-gray-400 hover:bg-white/5 px-3 py-1.5 text-xs font-semibold rounded-md"
                  @click="resetTemplateForm"
                >Cancel</button>
              </div>
            </form>
          </div>

          <!-- Template list -->
          <div class="divide-y divide-white/[0.04]">
            <div
              v-for="t in shiftTemplates"
              :key="t.id"
              class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/[0.04] transition-colors"
            >
              <!-- Template info -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-200">{{ t.name }}</p>
                <p class="text-[10px] text-gray-500">
                  {{ formatShiftTime(t.start_time) }} – {{ formatShiftTime(t.end_time) }}
                </p>
              </div>
              <!-- Edit / Delete actions -->
              <button
                class="text-blue-400 hover:text-blue-300 p-1 rounded hover:bg-white/[0.06] transition-colors"
                @click="editTemplate(t)"
                title="Edit"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                class="text-red-400 hover:text-red-300 p-1 rounded hover:bg-white/[0.06] transition-colors"
                @click="deleteTemplate(t.id)"
                title="Delete"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
            <!-- Empty state -->
            <p v-if="!shiftTemplates.length" class="text-gray-600 text-xs text-center py-6">
              No shift templates yet
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>
