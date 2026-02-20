<script setup lang="ts">
/**
 * MyScheduleView.vue
 *
 * Staff-facing view that displays the authenticated user's upcoming shifts.
 * Fetches shifts from the schedule store on mount and renders each as a
 * ShiftCard component. Includes loading and empty states following the same
 * patterns used throughout the codebase (DashboardView, EightySixedBoard, etc.).
 *
 * Route: /my-schedule (staff only)
 */
import { ref, onMounted } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import ShiftCard from '@/components/ShiftCard.vue'

// ── Store ──────────────────────────────────────────────────────────────
// The schedule store manages myShifts state and provides the fetch action.
const store = useScheduleStore()

// ── Local State ────────────────────────────────────────────────────────
// True while the initial shift data is being loaded from the API.
// Uses a local ref rather than store.loading so multiple views don't
// interfere with each other's loading states.
const loading = ref(false)

// ── Fetch ──────────────────────────────────────────────────────────────
/**
 * Loads the authenticated user's upcoming shifts into the store.
 * Wraps the store action with local loading state for the spinner.
 */
async function loadShifts() {
  loading.value = true
  try {
    await store.fetchMyShifts()
  } finally {
    loading.value = false
  }
}

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

async function giveUpShift(entryId: number) {
  try {
    await api.post('/api/shift-drops', { schedule_entry_id: entryId })
    toast('Shift dropped — waiting for a volunteer', 'success')
  } catch {
    toast('Failed to drop shift', 'error')
  }
}

// Fetch shifts as soon as the view mounts
onMounted(loadShifts)
</script>

<template>
  <AppShell>
    <div class="space-y-4">

      <!-- ── Page Header ──────────────────────────────────────────────── -->
      <div>
        <div class="flex items-center gap-2">
          <!-- Calendar icon (matches the icon style used in DashboardView headers) -->
          <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h1 class="text-xl font-bold text-white">My Schedule</h1>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">Your upcoming shifts</p>
      </div>

      <!-- ── Sub-navigation ────────────────────────────────────────────── -->
      <div class="flex gap-2">
        <router-link
          to="/shift-drops"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md bg-white/[0.06] text-gray-300 hover:bg-white/[0.1] hover:text-white transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          Drop Board
        </router-link>
        <router-link
          to="/time-off"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md bg-white/[0.06] text-gray-300 hover:bg-white/[0.1] hover:text-white transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Request Time Off
        </router-link>
      </div>

      <!-- ── Loading State ────────────────────────────────────────────── -->
      <!-- Animated spinner shown while fetching shifts from the API.
           Uses the exact same markup pattern as DashboardView and
           EightySixedBoard for visual consistency. -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="flex flex-col items-center gap-3">
          <svg class="animate-spin h-8 w-8 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm text-gray-500">Loading your shifts...</span>
        </div>
      </div>

      <!-- ── Shift List ───────────────────────────────────────────────── -->
      <!-- Renders each shift entry as a ShiftCard in a responsive grid.
           The grid uses gap-3 and sm:grid-cols-2 to match the layout
           pattern from EightySixedBoard's item grid. -->
      <div v-else-if="store.myShifts.length" class="grid gap-3 sm:grid-cols-2">
        <ShiftCard
          v-for="entry in store.myShifts"
          :key="entry.id"
          :entry="entry"
          @give-up="giveUpShift"
        />
      </div>

      <!-- ── Empty State ──────────────────────────────────────────────── -->
      <!-- Shown when the user has no upcoming shifts. Uses the same
           centered layout pattern as DashboardView's empty state. -->
      <div v-else class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-14 h-14 rounded-full bg-gray-800 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
        <p class="text-gray-400 font-medium">No upcoming shifts scheduled</p>
        <p class="text-gray-600 text-sm mt-1">Check back when the next schedule is published</p>
      </div>

    </div>
  </AppShell>
</template>
