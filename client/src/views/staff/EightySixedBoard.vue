<script setup lang="ts">
/**
 * EightySixedBoard.vue
 *
 * Full-page view listing all currently 86'd items. Staff see the read-only
 * card list, while admins and managers also get an inline form to 86 new
 * items on the fly. Fetches the item list from GET /api/eighty-sixed on
 * mount and displays an empty-state message when nothing is 86'd.
 */
import { ref, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import EightySixedCard from '@/components/EightySixedCard.vue'
import type { EightySixed } from '@/types'

const { isAdmin, isManager } = useAuth()

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

onMounted(fetchItems)
</script>

<template>
  <AppShell>
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
          </svg>
          <h1 class="text-xl font-bold text-white">86'd Board</h1>
          <span
            v-if="items.length"
            class="ml-1 inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500/20 text-red-400 text-xs font-bold"
          >
            {{ items.length }}
          </span>
        </div>
        <router-link
          to="/dashboard"
          class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold whitespace-nowrap bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-white transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Corner!
        </router-link>
      </div>

      <!-- Manager Form -->
      <form
        v-if="isAdmin || isManager"
        @submit.prevent="addItem"
        class="rounded-lg bg-white/[0.03] border border-white/[0.06] p-3"
      >
        <div class="flex gap-2">
          <input
            v-model="itemName"
            placeholder="Item name"
            required
            class="flex-1 min-w-0 rounded-md bg-white/5 border border-white/10 px-3 py-2 text-sm text-gray-200 placeholder-gray-600 focus:outline-none focus:border-red-500/50 focus:ring-1 focus:ring-red-500/25 transition-colors"
          />
          <input
            v-model="reason"
            placeholder="Reason"
            class="flex-1 min-w-0 rounded-md bg-white/5 border border-white/10 px-3 py-2 text-sm text-gray-200 placeholder-gray-600 focus:outline-none focus:border-red-500/50 focus:ring-1 focus:ring-red-500/25 transition-colors hidden sm:block"
          />
          <button
            type="submit"
            :disabled="submitting"
            class="shrink-0 px-4 py-2 rounded-md bg-red-600 text-white text-sm font-semibold
                   hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-gray-950
                   disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <svg v-if="submitting" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <span v-else>86 It</span>
          </button>
        </div>
        <!-- Reason on mobile (below) -->
        <input
          v-model="reason"
          placeholder="Reason (optional)"
          class="mt-2 w-full rounded-md bg-white/5 border border-white/10 px-3 py-2 text-sm text-gray-200 placeholder-gray-600 focus:outline-none focus:border-red-500/50 focus:ring-1 focus:ring-red-500/25 transition-colors sm:hidden"
        />
      </form>

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="flex flex-col items-center gap-3">
          <svg class="animate-spin h-8 w-8 text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm text-gray-500">Loading 86'd items...</span>
        </div>
      </div>

      <!-- Items -->
      <div v-else-if="items.length" class="grid gap-3 sm:grid-cols-2">
        <EightySixedCard v-for="item in items" :key="item.id" :item="item" />
      </div>

      <!-- Empty state -->
      <div v-else class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-14 h-14 rounded-full bg-emerald-500/10 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p class="text-gray-400 font-medium">All clear</p>
        <p class="text-gray-600 text-sm mt-1">Nothing is 86'd right now</p>
      </div>
    </div>
  </AppShell>
</template>
