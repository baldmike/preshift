<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { PushItem } from '@/types'

const items = ref<PushItem[]>([])
const loading = ref(false)
const submitting = ref(false)

const showForm = ref(false)
const editingId = ref<number | null>(null)
const form = ref({
  title: '',
  description: '',
  reason: '',
  priority: 'medium',
})

function resetForm() {
  form.value = { title: '', description: '', reason: '', priority: 'medium' }
  editingId.value = null
  showForm.value = false
}

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

async function fetchItems() {
  loading.value = true
  try {
    const { data } = await api.get<PushItem[]>('/api/push-items')
    items.value = data
  } finally {
    loading.value = false
  }
}

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

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

const priorityColor: Record<string, 'red' | 'yellow' | 'green' | 'gray'> = {
  high: 'red',
  medium: 'yellow',
  low: 'green',
}

onMounted(fetchItems)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage Push Items</h1>
        <div class="flex gap-2">
          <router-link to="/manage" class="text-sm text-indigo-600 hover:text-indigo-800">Back</router-link>
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
