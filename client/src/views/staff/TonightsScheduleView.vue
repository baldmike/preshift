<script setup lang="ts">
/**
 * TonightsScheduleView.vue
 *
 * Full-page view showing all staff working today at the user's location,
 * grouped by shift template time slot. Uses the same data from
 * currentSchedule.entries that the dashboard's "Today" section uses,
 * but shows ALL staff (not just the current user's shifts for non-managers).
 *
 * Route: /tonights-schedule (any authenticated user)
 */
import { onMounted, onUnmounted, computed, ref } from 'vue'
import type { User } from '@/types'
import { useScheduleStore } from '@/stores/schedule'
import { useAuth } from '@/composables/useAuth'
import { useSchedule } from '@/composables/useSchedule'
import { useLocationChannel } from '@/composables/useReverb'
import AppShell from '@/components/layout/AppShell.vue'
import EmployeeProfileModal from '@/components/EmployeeProfileModal.vue'

const scheduleStore = useScheduleStore()
const { formatShiftTime } = useSchedule()
const { user, locationId } = useAuth()
const loading = ref(false)
const selectedUser = ref<User | null>(null)

const canManage = computed(() => {
  const role = user.value?.role
  return role === 'admin' || role === 'manager'
})

/** Today's date as "YYYY-MM-DD" for filtering schedule entries */
const todayISO = computed(() => {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
})

/** Human-readable label for today (e.g. "Friday, February 20") */
const todayLabel = computed(() => {
  return new Date().toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })
})

/** All schedule entries for today — shows everyone working, not filtered by user */
const todayEntries = computed(() => {
  const schedule = scheduleStore.currentSchedule
  if (!schedule?.entries) return []
  return schedule.entries.filter(e => e.date.split('T')[0] === todayISO.value)
})

/**
 * Today's entries grouped by shift template time slot.
 * Each group has a `template` (with start_time for the heading)
 * and an `entries` array of staff assigned to that slot.
 */
const todayByShift = computed(() => {
  const groups: Record<number, { template: any; entries: any[] }> = {}
  for (const entry of todayEntries.value) {
    const tid = entry.shift_template_id
    if (!groups[tid]) {
      groups[tid] = {
        template: entry.shift_template || scheduleStore.shiftTemplates.find(t => t.id === tid),
        entries: [],
      }
    }
    groups[tid].entries.push(entry)
  }
  return Object.values(groups)
})

let channel: ReturnType<typeof useLocationChannel> | null = null

onMounted(async () => {
  loading.value = true
  try {
    await scheduleStore.fetchCurrentWeekSchedule()
  } finally {
    loading.value = false
  }

  if (canManage.value) {
    scheduleStore.fetchAckSummary()
  }

  if (locationId.value) {
    channel = useLocationChannel(locationId.value)
    channel.listen('.acknowledgment.recorded', (e: any) => {
      scheduleStore.updateUserAckPercentage(e.user_id, e.percentage)
    })
  }
})

onUnmounted(() => {
  if (channel) {
    channel.stopListening('.acknowledgment.recorded')
  }
})
</script>

<template>
  <AppShell>
    <div class="space-y-4">

      <!-- Page Header -->
      <div>
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <h1 class="text-xl font-bold text-white">Tonight's Schedule</h1>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">{{ todayLabel }} — everyone working today</p>
      </div>

      <!-- Sub-navigation -->
      <div class="flex flex-wrap gap-2">
        <router-link
          to="/shift-drops"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          Drop Board
        </router-link>
        <router-link
          to="/time-off"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Time Off
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="flex flex-col items-center gap-3">
          <svg class="animate-spin h-8 w-8 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm text-gray-500">Loading schedule...</span>
        </div>
      </div>

      <template v-else>
        <!-- Grouped shift entries -->
        <div v-if="todayByShift.length" class="space-y-3">
          <section
            v-for="group in todayByShift"
            :key="group.template?.id"
            class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden"
          >
            <!-- Shift time heading -->
            <header class="flex items-center gap-1.5 px-3 py-2.5 text-xs font-bold uppercase tracking-wide text-emerald-300 border-b border-white/[0.06]">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span v-if="group.template">{{ formatShiftTime(group.template.start_time) }}</span>
              <span v-else>Shift</span>
              <span
                class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold"
              >
                {{ group.entries.length }}
              </span>
            </header>

            <!-- Staff list -->
            <div class="p-3">
              <div class="flex flex-wrap gap-2">
                <component
                  :is="canManage && entry.user ? 'button' : 'div'"
                  v-for="entry in group.entries"
                  :key="entry.id"
                  :type="canManage && entry.user ? 'button' : undefined"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs"
                  :class="[
                    entry.role === 'bartender'
                      ? 'bg-green-500/15 text-green-300 border border-green-500/20'
                      : 'bg-blue-500/15 text-blue-300 border border-blue-500/20',
                    canManage && entry.user_id in scheduleStore.ackSummaryMap && scheduleStore.ackSummaryMap[entry.user_id] < 100
                      ? 'ring-1 ring-red-500/60'
                      : '',
                    canManage && entry.user ? 'hover:brightness-125 transition-all cursor-pointer' : '',
                  ]"
                  :title="canManage && entry.user_id in scheduleStore.ackSummaryMap && scheduleStore.ackSummaryMap[entry.user_id] < 100
                    ? 'Has not acknowledged all pre-shift items'
                    : undefined"
                  @click="canManage && entry.user ? selectedUser = entry.user : undefined"
                >
                  <span class="font-medium">{{ entry.user?.name || 'Staff' }}</span>
                  <span class="text-[10px] opacity-60 uppercase">{{ entry.role }}</span>
                </component>
              </div>
            </div>
          </section>
        </div>

        <!-- Empty state -->
        <div v-else class="flex flex-col items-center justify-center py-16 text-center">
          <div class="w-14 h-14 rounded-full bg-gray-800 flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <p class="text-gray-400 font-medium">No shifts scheduled today</p>
          <p class="text-gray-600 text-sm mt-1">Check the full schedule for upcoming shifts</p>
        </div>
      </template>

    </div>

    <EmployeeProfileModal :user="selectedUser" @close="selectedUser = null" />
  </AppShell>
</template>
