<script setup lang="ts">
/**
 * AcknowledgmentTracker -- admin/manager view that shows which staff members
 * have acknowledged the current pre-shift information. Displays a summary
 * grid of total active items across all categories (86'd, specials, push
 * items, announcements) and a table of all users with their acknowledgment
 * progress. Uses the /api/acknowledgments/summary endpoint for per-user data.
 */
import { ref, onMounted, computed } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { PreShiftData } from '@/types'

interface AckSummaryUser {
  user_id: number
  user_name: string
  role: string
  total_items: number
  acknowledged_count: number
  percentage: number
}

interface AckSummary {
  total_items: number
  users: AckSummaryUser[]
}

// Current pre-shift data used to calculate totals for the summary grid
const preshift = ref<PreShiftData | null>(null)
// Per-user acknowledgment summary from the API
const summary = ref<AckSummary | null>(null)
// True while the initial data is being loaded
const loading = ref(false)

// Computed total of all active pre-shift items across all categories.
const totalItems = computed(() => {
  if (!preshift.value) return 0
  return (
    preshift.value.eighty_sixed.length +
    preshift.value.specials.length +
    preshift.value.push_items.length +
    preshift.value.announcements.length
  )
})

/**
 * Fetches acknowledgment summary and pre-shift data in parallel.
 */
async function fetchData() {
  loading.value = true
  try {
    const [summaryRes, preshiftRes] = await Promise.all([
      api.get<AckSummary>('/api/acknowledgments/summary'),
      api.get<PreShiftData>('/api/preshift'),
    ])

    summary.value = summaryRes.data
    preshift.value = preshiftRes.data
  } finally {
    loading.value = false
  }
}

/**
 * Returns color class for percentage badge.
 */
function percentageColor(pct: number): 'green' | 'yellow' | 'red' {
  if (pct === 100) return 'green'
  if (pct > 0) return 'yellow'
  return 'red'
}

// Fetch all data on component mount
onMounted(fetchData)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Acknowledgment Tracker</h1>
        <router-link
              to="/manage/daily"
              class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              Corner!
            </router-link>
      </div>

      <!-- Summary -->
      <div v-if="preshift" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-red-600">{{ preshift.eighty_sixed.length }}</div>
          <div class="text-xs text-gray-500 mt-1">86'd Items</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-blue-600">{{ preshift.specials.length }}</div>
          <div class="text-xs text-gray-500 mt-1">Specials</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-amber-600">{{ preshift.push_items.length }}</div>
          <div class="text-xs text-gray-500 mt-1">Push Items</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 text-center">
          <div class="text-2xl font-bold text-purple-600">{{ preshift.announcements.length }}</div>
          <div class="text-xs text-gray-500 mt-1">Announcements</div>
        </div>
      </div>

      <!-- Users Table -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <div v-else-if="summary" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff Member</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
                Acknowledged ({{ totalItems }} items)
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="user in summary.users" :key="user.user_id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ user.user_name }}</td>
              <td class="px-4 py-3">
                <BadgePill
                  :label="user.role"
                  :color="user.role === 'admin' ? 'red' : user.role === 'manager' ? 'yellow' : 'blue'"
                />
              </td>
              <td class="px-4 py-3 text-center text-sm">
                <span class="text-gray-700 font-medium">{{ user.acknowledged_count }} / {{ user.total_items }}</span>
                <BadgePill
                  class="ml-2"
                  :label="`${user.percentage}%`"
                  :color="percentageColor(user.percentage)"
                />
              </td>
            </tr>
            <tr v-if="!summary.users.length">
              <td colspan="3" class="px-4 py-8 text-center text-gray-500">No staff members</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
