<script setup lang="ts">
/**
 * ManageDashboard.vue
 *
 * Top-level management hub that provides icon-card links to every admin
 * section (86'd, Specials, Push Items, Announcements, Menu, Employees,
 * Acknowledgments, Schedule, Shift Drops, Time Off). Each card shows a
 * live count of active items fetched from the preshift endpoint. Admins
 * also see a separate "Locations" link.
 */
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
  { to: '/manage/86', label: "86'd Items", key: 'eightySixed' as const, iconBg: 'bg-red-500/15', iconText: 'text-red-400' },
  { to: '/manage/specials', label: 'Specials', key: 'specials' as const, iconBg: 'bg-blue-500/15', iconText: 'text-blue-400' },
  { to: '/manage/push-items', label: 'Push Items', key: 'pushItems' as const, iconBg: 'bg-amber-500/15', iconText: 'text-amber-400' },
  { to: '/manage/announcements', label: 'Announcements', key: 'announcements' as const, iconBg: 'bg-purple-500/15', iconText: 'text-purple-400' },
  { to: '/manage/menu', label: 'Menu', key: null, iconBg: 'bg-green-500/15', iconText: 'text-green-400' },
  { to: '/manage/users', label: 'Employees', key: null, iconBg: 'bg-indigo-500/15', iconText: 'text-indigo-400' },
  { to: '/manage/acknowledgments', label: "Ack's", key: null, iconBg: 'bg-teal-500/15', iconText: 'text-teal-400' },
  { to: '/manage/schedule', label: 'Schedule', key: null, iconBg: 'bg-cyan-500/15', iconText: 'text-cyan-400' },
  { to: '/manage/shift-drops', label: 'Shift Drops', key: null, iconBg: 'bg-orange-500/15', iconText: 'text-orange-400' },
  { to: '/manage/time-off', label: 'Time Off', key: null, iconBg: 'bg-rose-500/15', iconText: 'text-rose-400' },
]
</script>

<template>
  <AppShell>
    <div class="space-y-5">
      <h1 class="text-xl font-bold text-white">Management</h1>

      <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3">
        <router-link
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          class="group rounded-xl border border-white/[0.06] bg-white/[0.03] p-3 hover:bg-white/[0.06] hover:border-white/[0.12] transition-all"
        >
          <div class="w-9 h-9 rounded-lg flex items-center justify-center mb-2.5" :class="link.iconBg">
            <!-- 86'd -->
            <svg v-if="link.to === '/manage/86'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            <!-- Specials -->
            <svg v-else-if="link.to === '/manage/specials'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
            <!-- Push Items -->
            <svg v-else-if="link.to === '/manage/push-items'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            <!-- Announcements -->
            <svg v-else-if="link.to === '/manage/announcements'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
            <!-- Menu -->
            <svg v-else-if="link.to === '/manage/menu'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <!-- Employees -->
            <svg v-else-if="link.to === '/manage/users'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <!-- Acknowledgments -->
            <svg v-else-if="link.to === '/manage/acknowledgments'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <!-- Schedule -->
            <svg v-else-if="link.to === '/manage/schedule'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <!-- Shift Drops -->
            <svg v-else-if="link.to === '/manage/shift-drops'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <!-- Time Off -->
            <svg v-else-if="link.to === '/manage/time-off'" class="w-[18px] h-[18px]" :class="link.iconText" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>

          <div class="text-sm font-semibold text-gray-200 leading-tight">{{ link.label }}</div>
          <div v-if="link.key" class="text-[11px] text-gray-500 mt-0.5">
            {{ counts[link.key] }} active
          </div>
        </router-link>
      </div>

      <!-- Admin Only: Locations -->
      <div v-if="isAdmin">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2.5">Admin</h2>
        <router-link
          to="/admin/locations"
          class="group rounded-xl border border-white/[0.06] bg-white/[0.03] p-3 hover:bg-white/[0.06] hover:border-white/[0.12] transition-all flex items-center gap-3"
        >
          <div class="w-9 h-9 rounded-lg bg-gray-500/15 flex items-center justify-center">
            <svg class="w-[18px] h-[18px] text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div class="text-sm font-semibold text-gray-200">Locations</div>
        </router-link>
      </div>
    </div>
  </AppShell>
</template>
