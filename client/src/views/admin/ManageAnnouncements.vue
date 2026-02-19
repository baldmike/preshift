<script setup lang="ts">
/**
 * ManageAnnouncements -- admin/manager CRUD view for announcements.
 * Announcements are general messages posted by management for staff
 * to read before their shift (e.g. policy changes, event info).
 * This view provides a toggleable create/edit form with a priority
 * selector (low/normal/high/urgent), optional expiration date, and
 * a textarea for the announcement body. A table lists all current
 * announcements with Edit and Delete actions.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { Announcement } from '@/types'

// Reactive list of announcements fetched from the API
const announcements = ref<Announcement[]>([])
// True while the initial list is being loaded
const loading = ref(false)
// True while the create/update API call is in-flight
const submitting = ref(false)

// Controls visibility of the create/edit form panel
const showForm = ref(false)
// When non-null, the form is in "edit" mode for the announcement with this ID
const editingId = ref<number | null>(null)
// Reactive form fields bound to the create/edit form inputs
const form = ref({
  title: '',
  body: '',               // The full announcement text
  priority: 'normal',     // Priority level: 'low', 'normal', 'high', or 'urgent'
  expires_at: '',          // Optional expiration date (ISO date string)
})

// Clears all form fields, exits edit mode, and hides the form panel
function resetForm() {
  form.value = { title: '', body: '', priority: 'normal', expires_at: '' }
  editingId.value = null
  showForm.value = false
}

// Populates the form with an existing announcement's data for editing.
// Strips the time portion from expires_at for the date input.
function editAnnouncement(a: Announcement) {
  editingId.value = a.id
  form.value = {
    title: a.title,
    body: a.body || '',
    priority: a.priority || 'normal',
    expires_at: a.expires_at ? a.expires_at.split('T')[0] : '',
  }
  showForm.value = true
}

/**
 * Fetches all announcements from GET /api/announcements.
 */
async function fetchAnnouncements() {
  loading.value = true
  try {
    const { data } = await api.get<Announcement[]>('/api/announcements')
    announcements.value = data
  } finally {
    loading.value = false
  }
}

/**
 * Saves an announcement -- creates (POST) or updates (PATCH) based on editingId.
 * Converts empty expires_at to null before sending to the API.
 * Updates the local list in-place on success and resets the form.
 */
async function saveAnnouncement() {
  if (!form.value.title.trim()) return
  submitting.value = true
  try {
    const payload = {
      ...form.value,
      expires_at: form.value.expires_at || null,
    }
    if (editingId.value) {
      const { data } = await api.patch(`/api/announcements/${editingId.value}`, payload)
      const idx = announcements.value.findIndex((a) => a.id === editingId.value)
      if (idx !== -1) announcements.value[idx] = data
      toast('Announcement updated', 'success')
    } else {
      const { data } = await api.post('/api/announcements', payload)
      announcements.value.push(data)
      toast('Announcement posted', 'success')
    }
    resetForm()
  } catch {
    toast('Failed to save announcement', 'error')
  } finally {
    submitting.value = false
  }
}

/**
 * Deletes an announcement after user confirmation.
 * Removes the record from the local list on success.
 */
async function deleteAnnouncement(id: number) {
  if (!confirm('Delete this announcement?')) return
  try {
    await api.delete(`/api/announcements/${id}`)
    announcements.value = announcements.value.filter((a) => a.id !== id)
    toast('Announcement deleted', 'success')
  } catch {
    toast('Failed to delete announcement', 'error')
  }
}

// Helper to dispatch a global toast notification via CustomEvent
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// Maps announcement priority levels to BadgePill colors for the table column
const priorityColor: Record<string, 'red' | 'yellow' | 'blue' | 'gray'> = {
  urgent: 'red',
  high: 'yellow',
  normal: 'blue',
  low: 'gray',
}

// Fetch announcements on component mount
onMounted(fetchAnnouncements)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage Announcements</h1>
        <div class="flex gap-2">
          <router-link to="/manage" class="text-sm text-indigo-600 hover:text-indigo-800">Back</router-link>
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">Add Announcement</BaseButton>
        </div>
      </div>

      <!-- Form -->
      <div v-if="showForm" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ editingId ? 'Edit' : 'Post' }} Announcement</h3>
        <form @submit.prevent="saveAnnouncement" class="space-y-3">
          <BaseInput v-model="form.title" label="Title" placeholder="Announcement title" />
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Body</label>
            <textarea
              v-model="form.body"
              rows="3"
              placeholder="Announcement details..."
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
              <select
                v-model="form.priority"
                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="low">Low</option>
                <option value="normal">Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <BaseInput v-model="form.expires_at" label="Expires" type="date" />
          </div>
          <div class="flex gap-2">
            <BaseButton type="submit" :loading="submitting">{{ editingId ? 'Update' : 'Post' }}</BaseButton>
            <BaseButton variant="secondary" @click="resetForm">Cancel</BaseButton>
          </div>
        </form>
      </div>

      <!-- Table -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="a in announcements" :key="a.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ a.title }}</td>
              <td class="px-4 py-3">
                <BadgePill v-if="a.priority" :label="a.priority" :color="priorityColor[a.priority] || 'gray'" />
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ a.expires_at ? a.expires_at.split('T')[0] : 'Never' }}</td>
              <td class="px-4 py-3 text-right space-x-2">
                <BaseButton size="sm" variant="secondary" @click="editAnnouncement(a)">Edit</BaseButton>
                <BaseButton size="sm" variant="danger" @click="deleteAnnouncement(a.id)">Delete</BaseButton>
              </td>
            </tr>
            <tr v-if="!announcements.length">
              <td colspan="4" class="px-4 py-8 text-center text-gray-500">No announcements</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
