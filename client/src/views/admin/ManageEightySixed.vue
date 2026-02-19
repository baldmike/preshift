<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import type { EightySixed } from '@/types'

const items = ref<EightySixed[]>([])
const loading = ref(false)

const itemName = ref('')
const reason = ref('')
const submitting = ref(false)

async function fetchItems() {
  loading.value = true
  try {
    const { data } = await api.get<EightySixed[]>('/api/eighty-sixed')
    items.value = data
  } finally {
    loading.value = false
  }
}

async function addItem() {
  if (!itemName.value.trim()) return
  submitting.value = true
  try {
    const { data } = await api.post('/api/eighty-sixed', {
      item_name: itemName.value,
      reason: reason.value || null,
    })
    items.value.push(data)
    itemName.value = ''
    reason.value = ''
    toast('Item 86\'d', 'success')
  } catch {
    toast('Failed to add item', 'error')
  } finally {
    submitting.value = false
  }
}

async function restoreItem(id: number) {
  try {
    await api.patch(`/api/eighty-sixed/${id}/restore`)
    items.value = items.value.filter((i) => i.id !== id)
    toast('Item restored', 'success')
  } catch {
    toast('Failed to restore item', 'error')
  }
}

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

function formatTime(dateStr: string) {
  return new Date(dateStr).toLocaleString([], {
    month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
  })
}

onMounted(fetchItems)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Manage 86'd Items</h1>
        <router-link to="/manage" class="text-sm text-indigo-600 hover:text-indigo-800">Back</router-link>
      </div>

      <!-- Create Form -->
      <div class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">86 an Item</h3>
        <form @submit.prevent="addItem" class="flex flex-col sm:flex-row gap-3">
          <BaseInput v-model="itemName" placeholder="Item name" class="flex-1" />
          <BaseInput v-model="reason" placeholder="Reason (optional)" class="flex-1" />
          <BaseButton type="submit" variant="danger" :loading="submitting">
            86 It
          </BaseButton>
        </form>
      </div>

      <!-- List -->
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
              <td class="px-4 py-3 text-right">
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
