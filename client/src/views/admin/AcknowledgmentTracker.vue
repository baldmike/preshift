<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { User, PreShiftData } from '@/types'

interface AckStatus {
  user: User
  eightySixed: number[]
  specials: number[]
  pushItems: number[]
  announcements: number[]
}

const users = ref<User[]>([])
const preshift = ref<PreShiftData | null>(null)
const ackStatuses = ref<AckStatus[]>([])
const loading = ref(false)

const totalItems = computed(() => {
  if (!preshift.value) return 0
  return (
    preshift.value.eighty_sixed.length +
    preshift.value.specials.length +
    preshift.value.push_items.length +
    preshift.value.announcements.length
  )
})

async function fetchData() {
  loading.value = true
  try {
    const [usersRes, preshiftRes, ackRes] = await Promise.all([
      api.get<User[]>('/api/users'),
      api.get<PreShiftData>('/api/preshift'),
      api.get('/api/acknowledgments/status'),
    ])

    users.value = usersRes.data
    preshift.value = preshiftRes.data

    // For each user, we'd need their ack status
    // The ack status endpoint returns the current user's acks
    // In a real app, the manager endpoint would return all users' acks
    // For now, we show the user list with the data we have
    ackStatuses.value = []
  } finally {
    loading.value = false
  }
}

function getUserAckCount(user: User): string {
  // In a production app, the API would provide per-user acknowledgment counts
  return '-'
}

onMounted(fetchData)
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Acknowledgment Tracker</h1>
        <router-link to="/manage" class="text-sm text-indigo-600 hover:text-indigo-800">Back</router-link>
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

      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
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
            <tr v-for="user in users" :key="user.id">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ user.name }}</td>
              <td class="px-4 py-3">
                <BadgePill
                  :label="user.role"
                  :color="user.role === 'admin' ? 'red' : user.role === 'manager' ? 'yellow' : 'blue'"
                />
              </td>
              <td class="px-4 py-3 text-center text-sm text-gray-500">
                {{ getUserAckCount(user) }}
              </td>
            </tr>
            <tr v-if="!users.length">
              <td colspan="3" class="px-4 py-8 text-center text-gray-500">No staff members</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppShell>
</template>
