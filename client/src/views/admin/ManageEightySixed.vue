<script setup lang="ts">
/**
 * ManageEightySixed -- admin/manager view for managing 86'd items.
 * Provides a toggleable create/edit form to 86 a new item (POST) or
 * update an existing one (PATCH), and a table listing all currently
 * 86'd items with Edit and Restore actions. When navigated to with
 * an item ID via the route param (e.g. /manage/86/5), the form opens
 * pre-populated with that item's data. Changes are reflected in the
 * local list immediately without a full refetch.
 */
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import type { EightySixed } from '@/types'

// Optional route prop: when present, the form opens in edit mode for this item
const props = defineProps<{ id?: string }>()

// Reactive list of currently 86'd items
const items = ref<EightySixed[]>([])
// True while the initial list is being fetched from the API
const loading = ref(false)
// True while the create/update API call is in-flight
const submitting = ref(false)

// Controls visibility of the create/edit form panel
const showForm = ref(false)
// When non-null, the form is in "edit" mode for the 86'd item with this ID
const editingId = ref<number | null>(null)

// Form fields for 86'ing or editing an item
const itemName = ref('')   // The menu item name to 86
const reason = ref('')     // Optional reason why the item is unavailable

/**
 * Clears all form fields, exits edit mode, and hides the form panel.
 */
function resetForm() {
  itemName.value = ''
  reason.value = ''
  editingId.value = null
  showForm.value = false
}

/**
 * Populates the form with an existing 86'd item's data for editing.
 * Opens the form panel and sets editingId.
 */
function editItem(item: EightySixed) {
  editingId.value = item.id
  itemName.value = item.item_name
  reason.value = item.reason || ''
  showForm.value = true
}

/**
 * Fetches all currently 86'd items from GET /api/eighty-sixed.
 */
async function fetchItems() {
  loading.value = true
  try {
    const { data } = await api.get<EightySixed[]>('/api/eighty-sixed')
    items.value = data
  } finally {
    loading.value = false
  }
}

/**
 * Saves an 86'd item -- creates (POST) or updates (PATCH) based on editingId.
 * Updates the local list in-place on success and resets the form.
 */
async function saveItem() {
  if (!itemName.value.trim()) return
  submitting.value = true
  try {
    const payload = {
      item_name: itemName.value,
      reason: reason.value || null,
    }
    if (editingId.value) {
      const { data } = await api.patch(`/api/eighty-sixed/${editingId.value}`, payload)
      const idx = items.value.findIndex((i) => i.id === editingId.value)
      if (idx !== -1) items.value[idx] = data
      toast('Item updated', 'success')
    } else {
      const { data } = await api.post('/api/eighty-sixed', payload)
      items.value.push(data)
      toast('Item 86\'d', 'success')
    }
    resetForm()
  } catch {
    toast('Failed to save item', 'error')
  } finally {
    submitting.value = false
  }
}

/**
 * Restores a previously 86'd item by PATCHing its restore endpoint.
 * Removes the item from the local list on success.
 */
async function restoreItem(id: number) {
  try {
    await api.patch(`/api/eighty-sixed/${id}/restore`)
    items.value = items.value.filter((i) => i.id !== id)
    toast('Item restored', 'success')
  } catch {
    toast('Failed to restore item', 'error')
  }
}

// Helper to dispatch a global toast notification via CustomEvent
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// Formats an ISO date string to a short, human-readable timestamp
function formatTime(dateStr: string) {
  return new Date(dateStr).toLocaleString([], {
    month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
  })
}

// Fetch 86'd items on mount; if an ID route param is present, open edit mode
onMounted(async () => {
  await fetchItems()
  if (props.id) {
    const target = items.value.find((i) => i.id === Number(props.id))
    if (target) editItem(target)
  }
})
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage 86'd Items</h1>
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
          <BaseButton v-if="!showForm" size="sm" @click="showForm = true">86 an Item</BaseButton>
        </div>
      </div>

      <!-- Create/Edit Form -->
      <div v-if="showForm" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">{{ editingId ? 'Edit Item' : '86 an Item' }}</h3>
        <form @submit.prevent="saveItem" class="flex flex-col sm:flex-row gap-3">
          <BaseInput v-model="itemName" placeholder="Item name" class="flex-1" />
          <BaseInput v-model="reason" placeholder="Reason (optional)" class="flex-1" />
          <div class="flex gap-2">
            <BaseButton type="submit" variant="danger" :loading="submitting">
              {{ editingId ? 'Update' : '86 It' }}
            </BaseButton>
            <BaseButton variant="secondary" @click="resetForm">Cancel</BaseButton>
          </div>
        </form>
      </div>

      <!-- List -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <div v-else class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">86'd By</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in items" :key="item.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.item_name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ item.reason || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ item.user?.name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ formatTime(item.created_at) }}</td>
              <td class="px-4 py-3 text-right space-x-2">
                <BaseButton size="sm" variant="secondary" @click="editItem(item)">
                  Edit
                </BaseButton>
                <BaseButton size="sm" variant="secondary" @click="restoreItem(item.id)">
                  Restore
                </BaseButton>
              </td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="5" class="px-4 py-8 text-center text-gray-500">No items currently 86'd</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
