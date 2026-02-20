<script setup lang="ts">
/**
 * ManageSpecials -- admin/manager CRUD view for daily specials.
 * Provides a toggleable create/edit form and a table of all specials.
 * Supports creating new specials (POST), editing existing ones (PATCH),
 * and deleting specials (DELETE). The form is reused for both create
 * and edit modes, distinguished by whether editingId is set.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import type { Special } from '@/types'

// Reactive list of specials fetched from the API
const specials = ref<Special[]>([])
// True while the initial specials list is being loaded
const loading = ref(false)
// True while the create/update API call is in-flight
const submitting = ref(false)

// Controls visibility of the create/edit form panel
const showForm = ref(false)
// When non-null, the form is in "edit" mode for the special with this ID
const editingId = ref<number | null>(null)
// Reactive form fields bound to the create/edit form inputs
const form = ref({
  title: '',
  description: '',
  type: '',       // Category of special (e.g. "food", "drink")
  starts_at: '',  // Start date (ISO date string)
  ends_at: '',    // End date (ISO date string)
})

// Clears all form fields, exits edit mode, and hides the form panel
function resetForm() {
  form.value = { title: '', description: '', type: '', starts_at: '', ends_at: '' }
  editingId.value = null
  showForm.value = false
}

// Populates the form with an existing special's data for editing
function editSpecial(special: Special) {
  editingId.value = special.id
  form.value = {
    title: special.title,
    description: special.description || '',
    type: special.type || '',
    starts_at: special.starts_at || '',
    ends_at: special.ends_at || '',
  }
  showForm.value = true
}

/**
 * Fetches all specials from GET /api/specials.
 */
async function fetchSpecials() {
  loading.value = true
  try {
    const { data } = await api.get<Special[]>('/api/specials')
    specials.value = data
  } finally {
    loading.value = false
  }
}

/**
 * Saves a special -- creates (POST) or updates (PATCH) based on editingId.
 * Defaults starts_at to today if not provided on create.
 * Updates the local list in-place on success and resets the form.
 */
async function saveSpecial() {
  if (!form.value.title.trim()) return
  submitting.value = true
  try {
    if (editingId.value) {
      const { data } = await api.patch(`/api/specials/${editingId.value}`, form.value)
      const idx = specials.value.findIndex((s) => s.id === editingId.value)
      if (idx !== -1) specials.value[idx] = data
      toast('Special updated', 'success')
    } else {
      const { data } = await api.post('/api/specials', {
        ...form.value,
        starts_at: form.value.starts_at || new Date().toISOString().split('T')[0],
      })
      specials.value.push(data)
      toast('Special created', 'success')
    }
    resetForm()
  } catch {
    toast('Failed to save special', 'error')
  } finally {
    submitting.value = false
  }
}

/**
 * Deletes a special after user confirmation via browser confirm dialog.
 * Removes the record from the local list on success.
 */
async function deleteSpecial(id: number) {
  if (!confirm('Delete this special?')) return
  try {
    await api.delete(`/api/specials/${id}`)
    specials.value = specials.value.filter((s) => s.id !== id)
    toast('Special deleted', 'success')
  } catch {
    toast('Failed to delete special', 'error')
  }
}

// Helper to dispatch a global toast notification via CustomEvent
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// Load specials on component mount
onMounted(fetchSpecials)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage Specials</h1>
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
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">Add Special</BaseButton>
        </div>
      </div>

      <!-- Form -->
      <div v-if="showForm" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ editingId ? 'Edit' : 'Create' }} Special</h3>
        <form @submit.prevent="saveSpecial" class="space-y-3">
          <BaseInput v-model="form.title" label="Title" placeholder="Special title" />
          <BaseInput v-model="form.description" label="Description" placeholder="Describe the special" />
          <div class="grid grid-cols-3 gap-3">
            <BaseInput v-model="form.type" label="Type" placeholder="e.g. food, drink" />
            <BaseInput v-model="form.starts_at" label="Starts" type="date" />
            <BaseInput v-model="form.ends_at" label="Ends" type="date" />
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
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="special in specials" :key="special.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ special.title }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ special.type || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ special.starts_at }}{{ special.ends_at ? ` - ${special.ends_at}` : '' }}
              </td>
              <td class="px-4 py-3 text-right space-x-2">
                <BaseButton size="sm" variant="secondary" @click="editSpecial(special)">Edit</BaseButton>
                <BaseButton size="sm" variant="danger" @click="deleteSpecial(special.id)">Delete</BaseButton>
              </td>
            </tr>
            <tr v-if="!specials.length">
              <td colspan="4" class="px-4 py-8 text-center text-gray-500">No specials</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
