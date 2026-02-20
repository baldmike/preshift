<script setup lang="ts">
/**
 * ManagePushItems -- admin/manager CRUD view for push items.
 * Push items are menu items that management wants staff to actively
 * promote or upsell. This view provides a toggleable create/edit form
 * with a priority selector (low/medium/high) and a table listing all
 * push items with Edit and Delete actions.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { PushItem } from '@/types'

// Reactive list of push items fetched from the API
const items = ref<PushItem[]>([])
// True while the initial list is being loaded
const loading = ref(false)
// True while the create/update API call is in-flight
const submitting = ref(false)

// Controls visibility of the create/edit form panel
const showForm = ref(false)
// When non-null, the form is in "edit" mode for the push item with this ID
const editingId = ref<number | null>(null)
// Reactive form fields bound to the create/edit form inputs
const form = ref({
  title: '',
  description: '',
  reason: '',           // Why this item should be pushed (e.g. overstock, high margin)
  priority: 'medium',   // Priority level: 'low', 'medium', or 'high'
})

// Clears all form fields, exits edit mode, and hides the form panel
function resetForm() {
  form.value = { title: '', description: '', reason: '', priority: 'medium' }
  editingId.value = null
  showForm.value = false
}

// Populates the form with an existing push item's data for editing
function editItem(item: PushItem) {
  editingId.value = item.id
  form.value = {
    title: item.title,
    description: item.description || '',
    reason: item.reason || '',
    priority: item.priority || 'medium',
  }
  showForm.value = true
}

/**
 * Fetches all push items from GET /api/push-items.
 */
async function fetchItems() {
  loading.value = true
  try {
    const { data } = await api.get<PushItem[]>('/api/push-items')
    items.value = data
  } finally {
    loading.value = false
  }
}

/**
 * Saves a push item -- creates (POST) or updates (PATCH) based on editingId.
 * Updates the local list in-place on success and resets the form.
 */
async function saveItem() {
  if (!form.value.title.trim()) return
  submitting.value = true
  try {
    if (editingId.value) {
      const { data } = await api.patch(`/api/push-items/${editingId.value}`, form.value)
      const idx = items.value.findIndex((i) => i.id === editingId.value)
      if (idx !== -1) items.value[idx] = data
      toast('Push item updated', 'success')
    } else {
      const { data } = await api.post('/api/push-items', form.value)
      items.value.push(data)
      toast('Push item created', 'success')
    }
    resetForm()
  } catch {
    toast('Failed to save push item', 'error')
  } finally {
    submitting.value = false
  }
}

/**
 * Deletes a push item after user confirmation.
 * Removes the record from the local list on success.
 */
async function deleteItem(id: number) {
  if (!confirm('Delete this push item?')) return
  try {
    await api.delete(`/api/push-items/${id}`)
    items.value = items.value.filter((i) => i.id !== id)
    toast('Push item deleted', 'success')
  } catch {
    toast('Failed to delete push item', 'error')
  }
}

// Helper to dispatch a global toast notification via CustomEvent
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// Maps priority levels to BadgePill color values for the table column
const priorityColor: Record<string, 'red' | 'yellow' | 'green' | 'gray'> = {
  high: 'red',
  medium: 'yellow',
  low: 'green',
}

// Fetch push items on component mount
onMounted(fetchItems)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage Push Items</h1>
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
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">Add Push Item</BaseButton>
        </div>
      </div>

      <!-- Form -->
      <div v-if="showForm" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ editingId ? 'Edit' : 'Create' }} Push Item</h3>
        <form @submit.prevent="saveItem" class="space-y-3">
          <BaseInput v-model="form.title" label="Title" placeholder="Push item title" />
          <BaseInput v-model="form.description" label="Description" placeholder="Describe the push item" />
          <BaseInput v-model="form.reason" label="Reason" placeholder="Why push this item?" />
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
            <select
              v-model="form.priority"
              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
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
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in items" :key="item.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.title }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ item.reason || '-' }}</td>
              <td class="px-4 py-3">
                <BadgePill v-if="item.priority" :label="item.priority" :color="priorityColor[item.priority] || 'gray'" />
              </td>
              <td class="px-4 py-3 text-right space-x-2">
                <BaseButton size="sm" variant="secondary" @click="editItem(item)">Edit</BaseButton>
                <BaseButton size="sm" variant="danger" @click="deleteItem(item.id)">Delete</BaseButton>
              </td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="4" class="px-4 py-8 text-center text-gray-500">No push items</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
