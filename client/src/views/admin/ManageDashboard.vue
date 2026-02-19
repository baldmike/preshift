<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import type { PreShiftData } from '@/types'

const { isAdmin } = useAuth()

const counts = ref({
  eightySixed: 0,
  specials: 0,
  pushItems: 0,
  announcements: 0,
})

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

const links = [
  { to: '/manage/86', label: '86\'d Items', icon: 'ban', key: 'eightySixed' as const, color: 'bg-red-500' },
  { to: '/manage/specials', label: 'Specials', icon: 'star', key: 'specials' as const, color: 'bg-blue-500' },
  { to: '/manage/push-items', label: 'Push Items', icon: 'trending', key: 'pushItems' as const, color: 'bg-amber-500' },
  { to: '/manage/announcements', label: 'Announcements', icon: 'megaphone', key: 'announcements' as const, color: 'bg-purple-500' },
  { to: '/manage/menu', label: 'Menu Items', icon: 'menu', key: null, color: 'bg-green-500' },
  { to: '/manage/users', label: 'Users', icon: 'users', key: null, color: 'bg-indigo-500' },
  { to: '/manage/acknowledgments', label: 'Acknowledgments', icon: 'check', key: null, color: 'bg-teal-500' },
]
</script>

<template>
  <AppShell>
    <div class="space-y-6">
      <h1 class="text-2xl font-bold text-gray-900">Management</h1>

      <div class="grid gap-4 sm:grid-cols-2">
        <router-link
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow flex items-center gap-4"
        >
          <div :class="[link.color, 'w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg']">
            {{ link.label.charAt(0) }}
          </div>
          <div>
            <div class="font-semibold text-gray-900">{{ link.label }}</div>
            <div v-if="link.key" class="text-sm text-gray-500">
              {{ counts[link.key] }} active
            </div>
          </div>
        </router-link>
      </div>

      <!-- Admin Only -->
      <div v-if="isAdmin" class="mt-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-3">Admin</h2>
        <router-link
          to="/admin/locations"
          class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow flex items-center gap-4"
        >
          <div class="bg-gray-700 w-12 h-12 rounded-lg flex items-center justify-center text-white font-bold text-lg">
            L
          </div>
          <div>
            <div class="font-semibold text-gray-900">Locations</div>
            <div class="text-sm text-gray-500">Manage locations</div>
          </div>
        </router-link>
      </div>
    </div>
  </AppShell>
</template>
