<script setup lang="ts">
/**
 * ManageShiftDrops -- manager view for reviewing shift drops.
 *
 * Three sections:
 *   1. "Open Drops" — open drops with volunteers, manager can select one.
 *   2. "Waiting for Volunteers" — open drops with no volunteers yet.
 *   3. "Resolved" — filled/cancelled, collapsed by default.
 */
import { ref, computed, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import ShiftDropCard from '@/components/ShiftDropCard.vue'
import type { ShiftDrop } from '@/types'

const shiftDrops = ref<ShiftDrop[]>([])
const loading = ref(false)
const processingId = ref<number | null>(null)
const showResolved = ref(false)

/** Open drops that have at least one volunteer — ready for manager selection. */
const withVolunteers = computed(() =>
  shiftDrops.value.filter(
    (d) => d.status === 'open' && (d.volunteers?.length ?? 0) > 0
  )
)

/** Open drops with no volunteers yet — informational. */
const waitingForVolunteers = computed(() =>
  shiftDrops.value.filter(
    (d) => d.status === 'open' && (d.volunteers?.length ?? 0) === 0
  )
)

/** Filled or cancelled drops. */
const resolved = computed(() =>
  shiftDrops.value.filter(
    (d) => d.status === 'filled' || d.status === 'cancelled'
  )
)

async function fetchDrops() {
  loading.value = true
  try {
    const { data } = await api.get<ShiftDrop[]>('/api/shift-drops')
    shiftDrops.value = data
  } finally {
    loading.value = false
  }
}

async function selectVolunteer(dropId: number, userId: number) {
  processingId.value = dropId
  try {
    const { data } = await api.post<ShiftDrop>(`/api/shift-drops/${dropId}/select/${userId}`)
    const idx = shiftDrops.value.findIndex((d) => d.id === dropId)
    if (idx !== -1) shiftDrops.value[idx] = data
    toast('Volunteer selected — shift reassigned', 'success')
  } catch {
    toast('Failed to select volunteer', 'error')
  } finally {
    processingId.value = null
  }
}

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

onMounted(fetchDrops)
</script>

<template>
  <AppShell>
    <div class="space-y-5">
      <!-- Page header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-white tracking-tight">Shift Drops</h1>
          <p class="text-xs text-gray-500 mt-0.5">Review and assign dropped shifts to volunteers</p>
        </div>
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

      <!-- Loading state -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-6 w-6 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <template v-else>
        <!-- SECTION 1: Open Drops with Volunteers -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <div class="flex items-center gap-2 px-4 py-2.5 border-b border-white/[0.06]">
            <h2 class="text-xs font-bold uppercase tracking-wider text-amber-400">Open Drops</h2>
            <span
              v-if="withVolunteers.length"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400"
            >{{ withVolunteers.length }}</span>
          </div>

          <div v-if="withVolunteers.length" class="divide-y divide-white/[0.04]">
            <div
              v-for="drop in withVolunteers"
              :key="drop.id"
              class="px-4 py-3 space-y-2"
            >
              <ShiftDropCard :drop="drop" />

              <!-- Volunteer list with select buttons -->
              <div class="space-y-1.5">
                <p class="text-[10px] text-gray-500 font-medium uppercase tracking-wide">Volunteers:</p>
                <div
                  v-for="vol in drop.volunteers"
                  :key="vol.id"
                  class="flex items-center justify-between"
                >
                  <span class="text-xs text-gray-300">{{ vol.user?.name ?? 'Staff' }}</span>
                  <button
                    :disabled="processingId === drop.id"
                    class="bg-green-500/25 text-green-300 hover:bg-green-500/35 px-3 py-1 text-xs font-semibold rounded-md disabled:opacity-50 transition-colors"
                    @click="selectVolunteer(drop.id, vol.user_id)"
                  >{{ processingId === drop.id ? 'Processing...' : 'Select' }}</button>
                </div>
              </div>
            </div>
          </div>

          <p v-else class="text-gray-600 text-xs text-center py-6">
            No drops with volunteers right now
          </p>
        </section>

        <!-- SECTION 2: Waiting for Volunteers -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <div class="flex items-center gap-2 px-4 py-2.5 border-b border-white/[0.06]">
            <h2 class="text-xs font-bold uppercase tracking-wider text-yellow-400">Waiting for Volunteers</h2>
            <span
              v-if="waitingForVolunteers.length"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-bold bg-yellow-500/20 text-yellow-400"
            >{{ waitingForVolunteers.length }}</span>
          </div>

          <div v-if="waitingForVolunteers.length" class="divide-y divide-white/[0.04]">
            <div
              v-for="drop in waitingForVolunteers"
              :key="drop.id"
              class="px-4 py-3"
            >
              <ShiftDropCard :drop="drop" />
              <p class="text-[10px] text-gray-500 mt-1">No volunteers yet</p>
            </div>
          </div>

          <p v-else class="text-gray-600 text-xs text-center py-6">
            No drops waiting for volunteers
          </p>
        </section>

        <!-- SECTION 3: Resolved (collapsed by default) -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <button
            class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-white/[0.02] transition-colors"
            @click="showResolved = !showResolved"
          >
            <div class="flex items-center gap-2">
              <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Resolved</h2>
              <span
                v-if="resolved.length"
                class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-bold bg-gray-500/20 text-gray-400"
              >{{ resolved.length }}</span>
            </div>
            <svg
              class="w-4 h-4 text-gray-500 transition-transform"
              :class="{ 'rotate-180': showResolved }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <div v-if="showResolved && resolved.length" class="border-t border-white/[0.06] divide-y divide-white/[0.04]">
            <div
              v-for="drop in resolved"
              :key="drop.id"
              class="px-4 py-3"
            >
              <ShiftDropCard :drop="drop" />
            </div>
          </div>

          <p
            v-if="showResolved && !resolved.length"
            class="text-gray-600 text-xs text-center py-6 border-t border-white/[0.06]"
          >
            No resolved drops
          </p>
        </section>
      </template>
    </div>
  </AppShell>
</template>
