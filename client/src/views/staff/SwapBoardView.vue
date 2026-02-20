<script setup lang="ts">
/**
 * SwapBoardView.vue
 *
 * Staff-facing view for managing shift swap requests. Divided into two sections:
 *
 *   1. "Open Swaps" -- pending swap requests from other staff that the current
 *      user can offer to cover. Each card has an "Offer to Cover" button that
 *      POSTs to the API and updates the swap's local state.
 *
 *   2. "My Swap Requests" -- the current user's own swap requests, with a
 *      "Cancel" button for any that are still pending or offered.
 *
 * Data flows through the Pinia schedule store (useScheduleStore). Swap
 * requests are fetched on mount and mutated locally after each API call to
 * avoid unnecessary re-fetches.
 *
 * Route: /swap-board (staff only)
 */
import { ref, computed, onMounted } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import { useAuth } from '@/composables/useAuth'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import SwapRequestCard from '@/components/SwapRequestCard.vue'
import type { SwapRequest } from '@/types'

// ── Auth ───────────────────────────────────────────────────────────────
// Get the current authenticated user so we can split swap requests into
// "mine" vs "open" based on the `requested_by` field.
const { user } = useAuth()

// ── Store ──────────────────────────────────────────────────────────────
const store = useScheduleStore()

// ── Local State ────────────────────────────────────────────────────────
// True while swap requests are being loaded from the API
const loading = ref(false)

// Tracks which swap request IDs are currently being acted on (offer or cancel)
// so we can disable their buttons and show a submitting state.
const actingOn = ref<Set<number>>(new Set())

// ── Computed Filters ───────────────────────────────────────────────────

/**
 * Open swap requests -- pending swaps from OTHER staff that the current
 * user could offer to cover. Excludes the user's own requests.
 */
const openSwaps = computed(() =>
  store.swapRequests.filter(
    (s) => s.status === 'pending' && s.requested_by !== user.value?.id
  )
)

/**
 * The current user's own swap requests, regardless of status.
 * Sorted with active statuses first for better UX.
 */
const mySwapRequests = computed(() =>
  store.swapRequests.filter(
    (s) => s.requested_by === user.value?.id
  )
)

// ── Actions ────────────────────────────────────────────────────────────

/**
 * Dispatches a toast notification via the global CustomEvent pattern
 * used throughout the app (handled by ToastContainer).
 */
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

/**
 * Offers to cover another staff member's swap request.
 * POSTs to the API and updates the local store with the response data
 * so the UI reflects the new "offered" status immediately.
 *
 * @param swapId - The swap request ID to offer coverage for
 */
async function offerToCover(swapId: number) {
  actingOn.value.add(swapId)
  try {
    const { data } = await api.post<SwapRequest>(`/api/swap-requests/${swapId}/offer`)
    // Update the swap request in the store's local array so the UI
    // reflects the status change without a full re-fetch.
    store.upsertSwapRequest(data)
    toast('Offer submitted — waiting for manager approval', 'success')
  } catch {
    toast('Failed to submit offer', 'error')
  } finally {
    actingOn.value.delete(swapId)
  }
}

/**
 * Cancels one of the current user's own swap requests.
 * POSTs to the cancel endpoint and updates local state with the
 * returned "cancelled" status.
 *
 * @param swapId - The swap request ID to cancel
 */
async function cancelSwap(swapId: number) {
  actingOn.value.add(swapId)
  try {
    const { data } = await api.post<SwapRequest>(`/api/swap-requests/${swapId}/cancel`)
    // Update the local store with the cancelled swap data
    store.upsertSwapRequest(data)
    toast('Swap request cancelled', 'success')
  } catch {
    toast('Failed to cancel request', 'error')
  } finally {
    actingOn.value.delete(swapId)
  }
}

// ── Mount ──────────────────────────────────────────────────────────────
/**
 * Fetches all visible swap requests on mount. The store action hits
 * GET /api/swap-requests which returns both open and own requests.
 */
onMounted(async () => {
  loading.value = true
  try {
    await store.fetchSwapRequests()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <AppShell>
    <div class="space-y-6">

      <!-- ── Page Header ──────────────────────────────────────────────── -->
      <div>
        <div class="flex items-center gap-2">
          <!-- Swap/arrows icon -->
          <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          <h1 class="text-xl font-bold text-white">Swap Board</h1>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">Pick up shifts or manage your swap requests</p>
      </div>

      <!-- ── Loading State ────────────────────────────────────────────── -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="flex flex-col items-center gap-3">
          <svg class="animate-spin h-8 w-8 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm text-gray-500">Loading swap requests...</span>
        </div>
      </div>

      <!-- ── Content (loaded) ─────────────────────────────────────────── -->
      <template v-else>

        <!-- ════════════════ Section 1: Open Swaps ════════════════ -->
        <!-- Swap requests from OTHER staff members with status "pending".
             Each shows a SwapRequestCard plus an "Offer to Cover" button
             so the current user can volunteer to pick up the shift. -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <!-- Section header (matches dm-header pattern from DailyManageView) -->
          <header class="flex items-center gap-1.5 px-3 py-2.5 text-xs font-bold uppercase tracking-wide text-amber-300 border-b border-white/[0.06]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="flex-1">Open Swaps</span>
            <!-- Count badge, shown only when there are open swaps -->
            <span
              v-if="openSwaps.length"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-amber-500/20 text-amber-400 text-[10px] font-bold"
            >
              {{ openSwaps.length }}
            </span>
          </header>

          <!-- Open swaps list -->
          <div class="p-3 space-y-3">
            <template v-if="openSwaps.length">
              <div v-for="swap in openSwaps" :key="swap.id">
                <!-- Render the swap details via SwapRequestCard -->
                <SwapRequestCard :swap="swap" />

                <!-- "Offer to Cover" action button sits below the card -->
                <div class="mt-2 flex justify-end">
                  <button
                    @click="offerToCover(swap.id)"
                    :disabled="actingOn.has(swap.id)"
                    class="px-3 py-1.5 rounded-md bg-amber-600 text-white text-xs font-semibold
                           hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-gray-950
                           disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <!-- Show spinner while the API call is in flight -->
                    <svg v-if="actingOn.has(swap.id)" class="animate-spin h-3.5 w-3.5 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ actingOn.has(swap.id) ? 'Offering...' : 'Offer to Cover' }}
                  </button>
                </div>
              </div>
            </template>

            <!-- Empty state when no open swaps are available -->
            <p v-else class="text-gray-600 text-xs text-center py-6">
              No open swap requests right now
            </p>
          </div>
        </section>

        <!-- ════════════════ Section 2: My Swap Requests ════════════════ -->
        <!-- The current user's own swap requests across all statuses.
             Active requests (pending/offered) show a "Cancel" button so
             the user can withdraw before a manager resolves them. -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <!-- Section header -->
          <header class="flex items-center gap-1.5 px-3 py-2.5 text-xs font-bold uppercase tracking-wide text-blue-300 border-b border-white/[0.06]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span class="flex-1">My Swap Requests</span>
            <span
              v-if="mySwapRequests.length"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-blue-500/20 text-blue-400 text-[10px] font-bold"
            >
              {{ mySwapRequests.length }}
            </span>
          </header>

          <!-- My requests list -->
          <div class="p-3 space-y-3">
            <template v-if="mySwapRequests.length">
              <div v-for="swap in mySwapRequests" :key="swap.id">
                <!-- Render the swap details via SwapRequestCard -->
                <SwapRequestCard :swap="swap" />

                <!-- "Cancel" button only for requests that can still be cancelled.
                     Once a swap is approved, denied, or already cancelled, no action
                     is available and we just display the card with its status badge. -->
                <div
                  v-if="swap.status === 'pending' || swap.status === 'offered'"
                  class="mt-2 flex justify-end"
                >
                  <button
                    @click="cancelSwap(swap.id)"
                    :disabled="actingOn.has(swap.id)"
                    class="px-3 py-1.5 rounded-md bg-red-500/20 text-red-400 text-xs font-semibold
                           hover:bg-red-500/30 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-gray-950
                           disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                  >
                    <svg v-if="actingOn.has(swap.id)" class="animate-spin h-3.5 w-3.5 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    {{ actingOn.has(swap.id) ? 'Cancelling...' : 'Cancel' }}
                  </button>
                </div>
              </div>
            </template>

            <!-- Empty state when the user has no swap requests -->
            <p v-else class="text-gray-600 text-xs text-center py-6">
              You haven't made any swap requests
            </p>
          </div>
        </section>

      </template>
    </div>
  </AppShell>
</template>
