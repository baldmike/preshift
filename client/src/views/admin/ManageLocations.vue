<script setup lang="ts">
/**
 * ManageLocations -- admin-only CRUD view for restaurant locations.
 * Each location represents a physical venue. Locations are used to
 * scope pre-shift data (86'd items, specials, etc.) and realtime
 * WebSocket channels. This view provides a toggleable create/edit
 * form with name, address, and timezone fields, plus a table with
 * an Edit action. No delete action is provided since removing a
 * location would cascade-affect all related data.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import type { Location } from '@/types'

// Reactive list of locations fetched from the API
const locations = ref<Location[]>([])
// True while the initial list is being loaded
const loading = ref(false)
// True while the create/update API call is in-flight
const submitting = ref(false)

// Controls visibility of the create/edit form panel
const showForm = ref(false)
// When non-null, the form is in "edit" mode for the location with this ID
const editingId = ref<number | null>(null)
// Reactive form fields bound to the create/edit form inputs
const form = ref({
  name: '',
  address: '',
  timezone: 'America/New_York',  // Defaults to Eastern time; user can override
  latitude: '',
  longitude: '',
})

// Clears all form fields, exits edit mode, and hides the form panel
function resetForm() {
  form.value = { name: '', address: '', timezone: 'America/New_York', latitude: '', longitude: '' }
  editingId.value = null
  showForm.value = false
}

// Populates the form with an existing location's data for editing
function editLocation(loc: Location) {
  editingId.value = loc.id
  form.value = {
    name: loc.name,
    address: loc.address || '',
    timezone: loc.timezone || 'America/New_York',
    latitude: loc.latitude != null ? String(loc.latitude) : '',
    longitude: loc.longitude != null ? String(loc.longitude) : '',
  }
  showForm.value = true
}

/**
 * Fetches all locations from GET /api/locations.
 */
async function fetchLocations() {
  loading.value = true
  try {
    const { data } = await api.get<Location[]>('/api/locations')
    locations.value = data
  } finally {
    loading.value = false
  }
}

/**
 * Saves a location -- creates (POST) or updates (PATCH) based on editingId.
 * Updates the local list in-place on success and resets the form.
 */
async function saveLocation() {
  if (!form.value.name.trim()) return
  submitting.value = true
  const payload = {
    ...form.value,
    latitude: form.value.latitude !== '' ? Number(form.value.latitude) : null,
    longitude: form.value.longitude !== '' ? Number(form.value.longitude) : null,
  }
  try {
    if (editingId.value) {
      const { data } = await api.patch(`/api/locations/${editingId.value}`, payload)
      const idx = locations.value.findIndex((l) => l.id === editingId.value)
      if (idx !== -1) locations.value[idx] = data
      toast('Location updated', 'success')
    } else {
      const { data } = await api.post('/api/locations', payload)
      locations.value.push(data)
      toast('Location created', 'success')
    }
    resetForm()
  } catch {
    toast('Failed to save location', 'error')
  } finally {
    submitting.value = false
  }
}

// Helper to dispatch a global toast notification via CustomEvent
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// Fetch locations on component mount
onMounted(fetchLocations)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage Locations</h1>
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
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">Add Location</BaseButton>
        </div>
      </div>

      <!-- Form -->
      <div v-if="showForm" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ editingId ? 'Edit' : 'Create' }} Location</h3>
        <form @submit.prevent="saveLocation" class="space-y-3">
          <BaseInput v-model="form.name" label="Name" placeholder="Location name" />
          <BaseInput v-model="form.address" label="Address" placeholder="Full address" />
          <BaseInput v-model="form.timezone" label="Timezone" placeholder="America/New_York" />
          <div class="grid grid-cols-2 gap-3">
            <BaseInput v-model="form.latitude" label="Latitude" placeholder="e.g. 40.7128" />
            <BaseInput v-model="form.longitude" label="Longitude" placeholder="e.g. -74.0060" />
          </div>
          <div class="flex gap-2">
            <BaseButton type="submit" :loading="submitting">{{ editingId ? 'Update' : 'Create' }}</BaseButton>
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
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timezone</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coordinates</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="loc in locations" :key="loc.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ loc.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ loc.address || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ loc.timezone || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">
                <template v-if="loc.latitude != null && loc.longitude != null">{{ loc.latitude }}, {{ loc.longitude }}</template>
                <template v-else>-</template>
              </td>
              <td class="px-4 py-3 text-right">
                <BaseButton size="sm" variant="secondary" @click="editLocation(loc)">Edit</BaseButton>
              </td>
            </tr>
            <tr v-if="!locations.length">
              <td colspan="5" class="px-4 py-8 text-center text-gray-500">No locations</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
