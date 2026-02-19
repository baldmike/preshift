<script setup lang="ts">
/**
 * EightySixedBoard -- staff-facing view that lists all currently 86'd items.
 * All users can see the board, but only admins and managers see an inline
 * form to 86 a new item. Items are displayed as EightySixedCard components
 * in a responsive grid. The form posts to the API and optimistically adds
 * the new record to the local list on success.
 */
import { ref, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import EightySixedCard from '@/components/EightySixedCard.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import type { EightySixed } from '@/types'

// Role checks to conditionally show the "86 an Item" manager form
const { isAdmin, isManager } = useAuth()

// Reactive list of all currently 86'd items from the API
const items = ref<EightySixed[]>([])
// True while the initial item list is being fetched
const loading = ref(false)

// Form state for the manager-only "86 an Item" form
const itemName = ref('')   // Name of the item to 86
const reason = ref('')     // Optional reason why the item is unavailable
const submitting = ref(false) // True while the form POST is in-flight

/**
 * Fetches all currently 86'd items from GET /api/eighty-sixed
 * and populates the items array.
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
 * Handles the "86 It" form submission. Validates that itemName is non-empty,
 * POSTs to the API, then optimistically appends the returned record to the
 * local list and resets the form fields. Shows a toast on success or failure.
 */
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
    window.dispatchEvent(
      new CustomEvent('toast', { detail: { message: 'Item 86\'d', type: 'success' } })
    )
  } catch {
    window.dispatchEvent(
      new CustomEvent('toast', { detail: { message: 'Failed to add item', type: 'error' } })
    )
  } finally {
    submitting.value = false
  }
}

// Fetch items on component mount
onMounted(fetchItems)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-gray-900">86'd Board</h1>

      <!-- Manager Form -->
      <div v-if="isAdmin || isManager" class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold text-gray-900 mb-3">86 an Item</h3>
        <form @submit.prevent="addItem" class="flex flex-col sm:flex-row gap-3">
          <BaseInput v-model="itemName" placeholder="Item name" class="flex-1" />
          <BaseInput v-model="reason" placeholder="Reason (optional)" class="flex-1" />
          <BaseButton type="submit" variant="danger" :loading="submitting">
            86 It
          </BaseButton>
        </form>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <!-- Items Grid -->
      <div v-else-if="items.length" class="grid gap-3 sm:grid-cols-2">
        <EightySixedCard v-for="item in items" :key="item.id" :item="item" />
      </div>

      <div v-else class="text-center py-8 text-gray-500">
        No items are currently 86'd. Looking good!
      </div>
    </div>
  </AppShell>
</template>
