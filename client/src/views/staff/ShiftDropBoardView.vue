<script setup lang="ts">
/**
 * ShiftDropBoardView.vue
 *
 * Staff-facing view for shift drops. Two sections:
 *   1. "Available Shifts" — open drops from other staff (same role) that
 *      the current user can volunteer to pick up.
 *   2. "My Drops" — the current user's own drops with cancel option.
 *
 * Route: /shift-drops (staff only)
 */
import { ref, computed, onMounted } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import { useAuth } from '@/composables/useAuth'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import ShiftDropCard from '@/components/ShiftDropCard.vue'
import type { ShiftDrop } from '@/types'

const { user } = useAuth()
const store = useScheduleStore()
const loading = ref(false)
const actingOn = ref<Set<number>>(new Set())

/** Open drops from OTHER staff that the current user could volunteer for. */
const availableDrops = computed(() =>
  store.shiftDrops.filter(
    (d) => d.status === 'open' && d.requested_by !== user.value?.id
  )
)

/** The current user's own drops, regardless of status. */
const myDrops = computed(() =>
  store.shiftDrops.filter(
    (d) => d.requested_by === user.value?.id
  )
)

function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

/** Check if the current user has already volunteered for a drop. */
function hasVolunteered(drop: ShiftDrop): boolean {
  return drop.volunteers?.some((v) => v.user_id === user.value?.id) ?? false
}

async function volunteer(dropId: number) {
  actingOn.value.add(dropId)
  try {
    const { data } = await api.post<ShiftDrop>(`/api/shift-drops/${dropId}/volunteer`)
    store.upsertShiftDrop(data)
    toast('Volunteered — waiting for manager to select', 'success')
  } catch {
    toast('Failed to volunteer', 'error')
  } finally {
    actingOn.value.delete(dropId)
  }
}

async function cancelDrop(dropId: number) {
  actingOn.value.add(dropId)
  try {
    const { data } = await api.post<ShiftDrop>(`/api/shift-drops/${dropId}/cancel`)
    store.upsertShiftDrop(data)
    toast('Drop cancelled', 'success')
  } catch {
    toast('Failed to cancel drop', 'error')
  } finally {
    actingOn.value.delete(dropId)
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await store.fetchShiftDrops()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <AppShell>
    <div class="space-y-6">

      <!-- Page Header -->
      <div>
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          <h1 class="text-xl font-bold text-white">Drop Board</h1>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">Pick up available shifts or manage your drops</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="flex flex-col items-center gap-3">
          <svg class="animate-spin h-8 w-8 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm text-gray-500">Loading shift drops...</span>
        </div>
      </div>

      <template v-else>

        <!-- Section 1: Available Shifts -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <header class="flex items-center gap-1.5 px-3 py-2.5 text-xs font-bold uppercase tracking-wide text-amber-300 border-b border-white/[0.06]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="flex-1">Available Shifts</span>
            <span
              v-if="availableDrops.length"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-amber-500/20 text-amber-400 text-[10px] font-bold"
            >
              {{ availableDrops.length }}
            </span>
          </header>

          <div class="p-3 space-y-3">
            <template v-if="availableDrops.length">
              <div v-for="drop in availableDrops" :key="drop.id">
                <ShiftDropCard :drop="drop" />
                <div class="mt-2 flex justify-end">
                  <button
                    v-if="!hasVolunteered(drop)"
                    @click="volunteer(drop.id)"
                    :disabled="actingOn.has(drop.id)"
                    class="px-3 py-1.5 rounded-md bg-amber-600 text-white text-xs font-semibold
                           hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-gray-950
                           disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <svg v-if="actingOn.has(drop.id)" class="animate-spin h-3.5 w-3.5 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ actingOn.has(drop.id) ? 'Volunteering...' : 'Pick It Up' }}
                  </button>
                  <span v-else class="text-xs text-green-400 font-medium">You volunteered</span>
                </div>
              </div>
            </template>
            <p v-else class="text-gray-600 text-xs text-center py-6">
              No available shifts to pick up right now
            </p>
          </div>
        </section>

        <!-- Section 2: My Drops -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <header class="flex items-center gap-1.5 px-3 py-2.5 text-xs font-bold uppercase tracking-wide text-blue-300 border-b border-white/[0.06]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="flex-1">My Drops</span>
            <span
              v-if="myDrops.length"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-blue-500/20 text-blue-400 text-[10px] font-bold"
            >
              {{ myDrops.length }}
            </span>
          </header>

          <div class="p-3 space-y-3">
            <template v-if="myDrops.length">
              <div v-for="drop in myDrops" :key="drop.id">
                <ShiftDropCard :drop="drop" />
                <div
                  v-if="drop.status === 'open'"
                  class="mt-2 flex justify-end"
                >
                  <button
                    @click="cancelDrop(drop.id)"
                    :disabled="actingOn.has(drop.id)"
                    class="px-3 py-1.5 rounded-md bg-red-500/20 text-red-400 text-xs font-semibold
                           hover:bg-red-500/30 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-gray-950
                           disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <svg v-if="actingOn.has(drop.id)" class="animate-spin h-3.5 w-3.5 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ actingOn.has(drop.id) ? 'Cancelling...' : 'Cancel' }}
                  </button>
                </div>
              </div>
            </template>
            <p v-else class="text-gray-600 text-xs text-center py-6">
              You haven't dropped any shifts
            </p>
          </div>
        </section>

      </template>
    </div>
  </AppShell>
</template>
