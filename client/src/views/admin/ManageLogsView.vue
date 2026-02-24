<script setup lang="ts">
/**
 * ManageLogsView -- admin/manager CRUD view for daily operational logs.
 *
 * Managers create one log per day with freeform notes. On creation the
 * backend auto-snapshots weather, events, and scheduled staff. This view
 * provides a toggleable create/edit form (date picker + textarea) and
 * displays log entries sorted by date descending. Each entry shows the
 * weather summary, events list, schedule list, and manager notes with
 * edit/delete actions.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import type { ManagerLog } from '@/types'

// Reactive list of logs fetched from the API
const logs = ref<ManagerLog[]>([])
// True while the initial list is being loaded
const loading = ref(false)
// True while the create/update API call is in-flight
const submitting = ref(false)

// Controls visibility of the create/edit form panel
const showForm = ref(false)
// When non-null, the form is in "edit" mode for the log with this ID
const editingId = ref<number | null>(null)
// Reactive form fields bound to the create/edit form inputs
const form = ref({
  log_date: new Date().toISOString().split('T')[0],
  body: '',
})

// Clears all form fields, exits edit mode, and hides the form panel
function resetForm() {
  form.value = { log_date: new Date().toISOString().split('T')[0], body: '' }
  editingId.value = null
  showForm.value = false
}

// Populates the form with an existing log's body for editing.
// Date is not editable on update (snapshots are immutable).
function editLog(log: ManagerLog) {
  editingId.value = log.id
  form.value = {
    log_date: log.log_date.split('T')[0],
    body: log.body,
  }
  showForm.value = true
}

/**
 * Fetches all manager logs from GET /api/manager-logs.
 */
async function fetchLogs() {
  loading.value = true
  try {
    const { data } = await api.get<ManagerLog[]>('/api/manager-logs')
    logs.value = data
  } finally {
    loading.value = false
  }
}

/**
 * Saves a log -- creates (POST) or updates (PATCH) based on editingId.
 * On create, sends log_date and body; the backend auto-populates snapshots.
 * On update, only sends body (snapshots are immutable).
 */
async function saveLog() {
  if (!form.value.body.trim()) return
  submitting.value = true
  try {
    if (editingId.value) {
      const { data } = await api.patch(`/api/manager-logs/${editingId.value}`, {
        body: form.value.body,
      })
      const idx = logs.value.findIndex((l) => l.id === editingId.value)
      if (idx !== -1) logs.value[idx] = data
      toast('Log updated', 'success')
    } else {
      const { data } = await api.post('/api/manager-logs', {
        log_date: form.value.log_date,
        body: form.value.body,
      })
      logs.value.unshift(data)
      toast('Log created', 'success')
    }
    resetForm()
  } catch (err: any) {
    const message = err?.response?.data?.errors?.log_date?.[0] || 'Failed to save log'
    toast(message, 'error')
  } finally {
    submitting.value = false
  }
}

/**
 * Deletes a log after user confirmation.
 * Removes the record from the local list on success.
 */
async function deleteLog(id: number) {
  if (!confirm('Delete this log entry?')) return
  try {
    await api.delete(`/api/manager-logs/${id}`)
    logs.value = logs.value.filter((l) => l.id !== id)
    toast('Log deleted', 'success')
  } catch {
    toast('Failed to delete log', 'error')
  }
}

// Helper to dispatch a global toast notification via CustomEvent
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// Formats a date string (YYYY-MM-DD or full ISO) to a readable display format
function formatDate(dateStr: string) {
  const d = new Date(dateStr.split('T')[0] + 'T12:00:00')
  return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
}

// Formats a time string (HH:MM or HH:MM:SS) for display
function formatTime(timeStr: string | null) {
  if (!timeStr) return ''
  const [h, m] = timeStr.split(':')
  const hour = parseInt(h)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const display = hour > 12 ? hour - 12 : hour === 0 ? 12 : hour
  return `${display}:${m} ${ampm}`
}

// Fetch logs on component mount
onMounted(fetchLogs)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manager Log</h1>
        <div class="flex gap-2">
          <router-link
            to="/manage/daily"
            class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Corner!
          </router-link>
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">New Log Entry</BaseButton>
        </div>
      </div>

      <!-- Create/Edit Form -->
      <div v-if="showForm" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ editingId ? 'Edit Log' : 'New Log Entry' }}</h3>
        <form @submit.prevent="saveLog" class="space-y-3">
          <div v-if="!editingId">
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input
              v-model="form.log_date"
              type="date"
              required
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea
              v-model="form.body"
              rows="4"
              required
              placeholder="How was the shift? Any observations, notes, or follow-ups..."
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <p v-if="!editingId" class="text-xs text-gray-500">
            Weather, events, and schedule will be auto-captured when you save.
          </p>
          <div class="flex gap-2">
            <BaseButton type="submit" :loading="submitting">{{ editingId ? 'Update' : 'Create' }}</BaseButton>
            <BaseButton variant="secondary" @click="resetForm">Cancel</BaseButton>
          </div>
        </form>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <!-- Log Entries -->
      <template v-else>
        <div v-if="!logs.length && !showForm" class="text-center py-12">
          <p class="text-gray-500 text-sm">No log entries yet. Create one to get started.</p>
        </div>

        <div v-for="log in logs" :key="log.id" class="bg-white rounded-lg shadow overflow-hidden">
          <!-- Date Header -->
          <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <h3 class="font-semibold text-gray-900 text-sm">{{ formatDate(log.log_date) }}</h3>
            </div>
            <div class="flex items-center gap-2">
              <span v-if="log.creator" class="text-xs text-gray-500">by {{ log.creator.name }}</span>
              <BaseButton size="sm" variant="secondary" @click="editLog(log)">Edit</BaseButton>
              <BaseButton size="sm" variant="danger" @click="deleteLog(log.id)">Delete</BaseButton>
            </div>
          </div>

          <div class="p-4 space-y-4">
            <!-- Weather Snapshot -->
            <div v-if="log.weather_snapshot" class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg">
              <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
              </svg>
              <div class="text-sm">
                <p class="font-medium text-blue-900">
                  {{ log.weather_snapshot.current.temperature }}&deg;F &mdash; {{ log.weather_snapshot.current.description }}
                </p>
                <p class="text-blue-700 text-xs mt-0.5">
                  High {{ log.weather_snapshot.today.high }}&deg; / Low {{ log.weather_snapshot.today.low }}&deg;
                  &middot; Feels like {{ log.weather_snapshot.current.feels_like }}&deg;
                  &middot; Wind {{ log.weather_snapshot.current.wind_speed }} mph
                </p>
              </div>
            </div>

            <!-- Events Snapshot -->
            <div v-if="log.events_snapshot && log.events_snapshot.length > 0">
              <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Events</h4>
              <ul class="space-y-1">
                <li v-for="event in log.events_snapshot" :key="event.id" class="flex items-center gap-2 text-sm text-gray-700">
                  <svg class="w-3.5 h-3.5 text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                  </svg>
                  <span class="font-medium">{{ event.title }}</span>
                  <span v-if="event.event_time" class="text-gray-400 text-xs">{{ formatTime(event.event_time) }}</span>
                </li>
              </ul>
            </div>

            <!-- Schedule Snapshot -->
            <div v-if="log.schedule_snapshot && log.schedule_snapshot.length > 0">
              <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Scheduled Staff</h4>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5">
                <div
                  v-for="entry in log.schedule_snapshot"
                  :key="entry.id"
                  class="flex items-center gap-1.5 text-xs bg-gray-50 rounded px-2 py-1.5"
                >
                  <span class="font-medium text-gray-800">{{ entry.user_name }}</span>
                  <span class="text-gray-400">&middot;</span>
                  <span class="text-gray-500 capitalize">{{ entry.role }}</span>
                  <span v-if="entry.shift_name" class="text-gray-400">({{ entry.shift_name }})</span>
                </div>
              </div>
            </div>

            <!-- Manager Notes -->
            <div>
              <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Notes</h4>
              <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ log.body }}</p>
            </div>
          </div>
        </div>
      </template>
    </div>
  </AppShell>
</template>
