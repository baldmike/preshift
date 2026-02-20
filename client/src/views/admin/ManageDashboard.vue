<script setup lang="ts">
/**
 * ManageDashboard -- the management hub that links to all admin CRUD views.
 * Displayed as a grid of navigation cards, each showing the section name
 * and an active-item count (for countable sections like 86'd items, specials,
 * push items, and announcements). The "Locations" link is shown only to
 * users with the admin role. Counts are fetched from the /api/preshift
 * endpoint on mount.
 */
import { ref, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import type { PreShiftData } from '@/types'

// Used to conditionally show the admin-only "Locations" link
const { isAdmin } = useAuth()

// Holds the active-item counts displayed on each nav card badge
const counts = ref({
  eightySixed: 0,
  specials: 0,
  pushItems: 0,
  announcements: 0,
})

// Fetch current pre-shift data on mount to populate the counts.
// On failure, counts remain at 0 (silent degradation).
onMounted(async () => {
  try {
    const { data } = await api.get<PreShiftData>('/api/preshift')
    counts.value.eightySixed = data.eighty_sixed.length
    counts.value.specials = data.specials.length
    counts.value.pushItems = data.push_items.length
    counts.value.announcements = data.announcements.length
  } catch {
    // Counts stay at 0
  }
})

// Static navigation link definitions for the management grid.
// Each entry maps to a manage sub-route, with a label, color, and
// optional key into the `counts` object to display "X active".
const links = [
  { to: '/manage/86', label: '86\'d Items', icon: 'ban', key: 'eightySixed' as const, color: 'bg-red-500' },
  { to: '/manage/specials', label: 'Specials', icon: 'star', key: 'specials' as const, color: 'bg-blue-500' },
  { to: '/manage/push-items', label: 'Push Items', icon: 'trending', key: 'pushItems' as const, color: 'bg-amber-500' },
  { to: '/manage/announcements', label: 'Announcements', icon: 'megaphone', key: 'announcements' as const, color: 'bg-purple-500' },
  { to: '/manage/menu', label: 'Menu Items', icon: 'menu', key: null, color: 'bg-green-500' },
  { to: '/manage/users', label: 'Employees', icon: 'users', key: null, color: 'bg-indigo-500' },
  { to: '/manage/acknowledgments', label: 'Acknowledgments', icon: 'check', key: null, color: 'bg-teal-500' },
  { to: '/manage/schedule', label: 'Schedule Builder', icon: 'calendar', key: null, color: 'bg-cyan-500' },
  { to: '/manage/shift-drops', label: 'Shift Drops', icon: 'swap', key: null, color: 'bg-orange-500' },
  { to: '/manage/time-off', label: 'Manage Time Off', icon: 'clock', key: null, color: 'bg-rose-500' },
]
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-white">Management</h1>

      <div class="grid gap-4 sm:grid-cols-2">
        <router-link
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="bg-gray-800 rounded-lg shadow p-4 hover:bg-gray-700 transition-colors flex items-center gap-4"
        >
          <div :class="[link.color, 'w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg']">
            {{ link.label.charAt(0) }}
          </div>
          <div>
            <div class="font-semibold text-white">{{ link.label }}</div>
            <div v-if="link.key" class="text-sm text-gray-400">
              {{ counts[link.key] }} active
            </div>
          </div>
        </router-link>
      </div>

      <!-- Admin Only -->
      <div v-if="isAdmin" class="mt-6">
        <h2 class="text-lg font-semibold text-gray-400 mb-3">Admin</h2>
        <router-link
          to="/admin/locations"
          class="bg-gray-800 rounded-lg shadow p-4 hover:bg-gray-700 transition-colors flex items-center gap-4"
        >
          <div class="bg-gray-600 w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg">
            L
          </div>
          <div>
            <div class="font-semibold text-white">Locations</div>
            <div class="text-sm text-gray-400">Manage locations</div>
          </div>
        </router-link>
      </div>
    </div>
  </AppShell>
</template>
