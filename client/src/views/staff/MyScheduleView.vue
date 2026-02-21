<script setup lang="ts">
/**
 * MyScheduleView -- staff view showing the full weekly schedule grid,
 * the employee's own upcoming shifts, and a self-service availability
 * grid where they can set which days/times they're available.
 */
import { ref, onMounted } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import { useAuthStore } from '@/stores/auth'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import ShiftCard from '@/components/ShiftCard.vue'
import ScheduleGrid from '@/components/ScheduleGrid.vue'
import AvailabilityGrid from '@/components/AvailabilityGrid.vue'

const store = useScheduleStore()
const authStore = useAuthStore()
const loading = ref(false)

// ── Availability state ──────────────────────────────────────────────────
const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const
const DAY_LABELS: Record<string, string> = {
  monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed', thursday: 'Thu',
  friday: 'Fri', saturday: 'Sat', sunday: 'Sun',
}

/** Build an empty availability map (no slots selected) */
function emptyAvailability(): Record<string, string[]> {
  return Object.fromEntries(DAYS.map(d => [d, []]))
}

const availability = ref<Record<string, string[]>>(emptyAvailability())
const savingAvailability = ref(false)
const editingAvailability = ref(false)

/** True when the user has any availability set */
function hasAvailability(): boolean {
  const a = authStore.user?.availability
  if (!a) return false
  return DAYS.some(d => (a[d] ?? []).length > 0)
}

/** Returns a short summary like "Mon, Tue (10:30), Wed (4:30)" */
function availabilitySummary(): string {
  const a = authStore.user?.availability
  if (!a) return 'Not set'
  const parts: string[] = []
  for (const d of DAYS) {
    const slots = a[d]
    if (!slots || slots.length === 0) continue
    if (slots.includes('open')) {
      parts.push(DAY_LABELS[d])
    } else {
      const slotLabels: Record<string, string> = { '10:30': '10:30', '16:30': '4:30', '18:00': '6:00', '19:00': '7:00' }
      const labels = slots.map(s => slotLabels[s] ?? s)
      parts.push(`${DAY_LABELS[d]} (${labels.join(', ')})`)
    }
  }
  if (parts.length === 0) return 'Not set'
  const allOpen = DAYS.every(d => a[d]?.includes('open'))
  if (allOpen) return 'Open all week'
  return parts.join(', ')
}

/** Load the current user's availability from their profile */
function loadAvailability() {
  const user = authStore.user
  if (user?.availability) {
    availability.value = { ...emptyAvailability(), ...user.availability }
  }
  // Start collapsed if availability already set
  editingAvailability.value = !hasAvailability()
}

/** Save availability via PUT /api/my-availability */
async function saveAvailability() {
  savingAvailability.value = true
  try {
    const { data } = await api.put('/api/my-availability', {
      availability: availability.value,
    })
    authStore.user = data
    editingAvailability.value = false
    toast('Availability saved', 'success')
  } catch {
    toast('Failed to save availability', 'error')
  } finally {
    savingAvailability.value = false
  }
}

// ── Schedule loading ────────────────────────────────────────────────────

async function loadData() {
  loading.value = true
  try {
    await Promise.all([
      store.fetchMyShifts(),
      store.fetchCurrentWeekSchedule(),
    ])
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

onMounted(() => {
  loadData()
  loadAvailability()
})
</script>

<template>
  <AppShell>
    <div class="space-y-4">

      <!-- Page Header -->
      <div>
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h1 class="text-xl font-bold text-white">Schedule</h1>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">This week's full schedule</p>
      </div>

      <!-- Sub-navigation: pill links to shift drop board and time-off request views -->
      <div class="flex gap-2">
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

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="flex flex-col items-center gap-3">
          <svg class="animate-spin h-8 w-8 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm text-gray-500">Loading schedule...</span>
        </div>
      </div>

      <template v-else>
        <!-- Full Schedule Grid -->
        <section v-if="store.currentSchedule">
          <ScheduleGrid
            :schedule="store.currentSchedule"
          />
        </section>
        <div v-else class="rounded-xl border border-white/[0.06] bg-white/[0.03] p-6 text-center">
          <p class="text-gray-400 font-medium">No published schedule this week</p>
          <p class="text-gray-600 text-sm mt-1">Check back when the next schedule is published</p>
        </div>

        <!-- My Shifts -->
        <div v-if="store.myShifts.length">
          <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wide mb-2">My Shifts</h2>
          <div class="grid gap-3 sm:grid-cols-2">
            <ShiftCard
              v-for="entry in store.myShifts"
              :key="entry.id"
              :entry="entry"
              @give-up="giveUpShift"
            />
          </div>
        </div>

        <!-- My Availability -->
        <section class="rounded-xl border border-white/[0.06] bg-white/[0.03] p-4 space-y-3">
          <!-- Collapsed: summary line + edit button -->
          <div v-if="!editingAvailability" class="flex items-center justify-between">
            <div>
              <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wide">My Availability</h2>
              <p class="text-xs text-gray-300 mt-1">{{ availabilitySummary() }}</p>
            </div>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 transition-colors"
              @click="editingAvailability = true"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Availability
            </button>
          </div>

          <!-- Expanded: full grid editor -->
          <template v-else>
            <div>
              <h2 class="text-sm font-bold text-gray-400 uppercase tracking-wide">My Availability</h2>
              <p class="text-[10px] text-gray-600 mt-0.5">Tap the times you can work each day. OPEN = available all day.</p>
            </div>
            <AvailabilityGrid
              v-model="availability"
              :saving="savingAvailability"
              @save="saveAvailability"
            />
          </template>
        </section>
      </template>

    </div>
  </AppShell>
</template>
