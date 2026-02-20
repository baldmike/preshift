<script setup lang="ts">
/**
 * ManageSwapRequests -- manager view for reviewing shift swap requests.
 *
 * This view fetches all swap requests from the API and organises them into
 * three sections based on their lifecycle status:
 *
 *   1. "Needs Action" — status "offered" (a staff member has volunteered to
 *      pick up the shift and the swap now requires manager approval or denial).
 *      Each card shows Approve and Deny action buttons.
 *
 *   2. "Pending" — status "pending" (the original requester posted the swap
 *      but no one has offered to take it yet). Informational only, no manager
 *      action buttons.
 *
 *   3. "Resolved" — status "approved", "denied", or "cancelled". This section
 *      is collapsed by default to reduce visual noise. Each card shows a
 *      status badge for quick reference.
 *
 * API endpoints used:
 *   GET  /api/swap-requests
 *   POST /api/swap-requests/:id/approve
 *   POST /api/swap-requests/:id/deny
 */
import { ref, computed, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import SwapRequestCard from '@/components/SwapRequestCard.vue'
import type { SwapRequest } from '@/types'

// ── State ────────────────────────────────────────────────────────────────────

// All swap requests fetched from the API
const swapRequests = ref<SwapRequest[]>([])
// True while the initial list is being loaded
const loading = ref(false)
// Tracks which swap request ID is currently being processed (approve/deny)
// so we can show a loading state on the specific card's buttons
const processingId = ref<number | null>(null)
// Controls visibility of the "Resolved" section (collapsed by default)
const showResolved = ref(false)

// ── Computed sections ────────────────────────────────────────────────────────

/**
 * Swap requests with status "offered" -- someone has volunteered to take
 * the shift and a manager needs to approve or deny the swap.
 */
const needsAction = computed(() =>
  swapRequests.value.filter((s) => s.status === 'offered')
)

/**
 * Swap requests with status "pending" -- the requester posted the swap
 * but no one has offered to pick it up yet. Informational only.
 */
const pending = computed(() =>
  swapRequests.value.filter((s) => s.status === 'pending')
)

/**
 * Swap requests that have reached a terminal state: approved, denied,
 * or cancelled. Hidden by default behind a collapsible toggle.
 */
const resolved = computed(() =>
  swapRequests.value.filter((s) =>
    s.status === 'approved' || s.status === 'denied' || s.status === 'cancelled'
  )
)

// ── Fetch ────────────────────────────────────────────────────────────────────

/**
 * Fetches all swap requests from GET /api/swap-requests.
 * Expects the API to return entries with eagerly-loaded relationships
 * (schedule_entry, shift_template, requester, picker, etc.).
 */
async function fetchSwapRequests() {
  loading.value = true
  try {
    const { data } = await api.get<SwapRequest[]>('/api/swap-requests')
    swapRequests.value = data
  } finally {
    loading.value = false
  }
}

// ── Actions ──────────────────────────────────────────────────────────────────

/**
 * Approves a swap request via POST /api/swap-requests/:id/approve.
 * Updates the local record in-place on success so the card moves from
 * "Needs Action" to "Resolved" without a full re-fetch.
 */
async function approveSwap(id: number) {
  processingId.value = id
  try {
    const { data } = await api.post<SwapRequest>(`/api/swap-requests/${id}/approve`)
    // Replace the swap request in the local list with the updated version
    const idx = swapRequests.value.findIndex((s) => s.id === id)
    if (idx !== -1) swapRequests.value[idx] = data
    toast('Swap request approved', 'success')
  } catch {
    toast('Failed to approve swap request', 'error')
  } finally {
    processingId.value = null
  }
}

/**
 * Denies a swap request via POST /api/swap-requests/:id/deny.
 * Updates the local record in-place on success.
 */
async function denySwap(id: number) {
  processingId.value = id
  try {
    const { data } = await api.post<SwapRequest>(`/api/swap-requests/${id}/deny`)
    // Replace the swap request in the local list with the updated version
    const idx = swapRequests.value.findIndex((s) => s.id === id)
    if (idx !== -1) swapRequests.value[idx] = data
    toast('Swap request denied', 'success')
  } catch {
    toast('Failed to deny swap request', 'error')
  } finally {
    processingId.value = null
  }
}

// ── Toast helper ─────────────────────────────────────────────────────────────

/** Dispatches a global toast notification via CustomEvent. */
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// ── Lifecycle ────────────────────────────────────────────────────────────────

// Fetch swap requests on component mount
onMounted(fetchSwapRequests)
</script>

<template>
  <AppShell>
    <div class="space-y-5">
      <!-- ── Page header ──────────────────────────────────────────────────── -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-white tracking-tight">Manage Swaps</h1>
          <p class="text-xs text-gray-500 mt-0.5">Review and approve shift swap requests</p>
        </div>
        <router-link
          to="/manage"
          class="text-xs text-gray-500 hover:text-gray-300 transition-colors"
        >Back</router-link>
      </div>

      <!-- ── Loading state ────────────────────────────────────────────────── -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-6 w-6 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <template v-else>
        <!-- ════════════════ SECTION 1: Needs Action ════════════════ -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <!-- Section header -->
          <div class="flex items-center gap-2 px-4 py-2.5 border-b border-white/[0.06]">
            <h2 class="text-xs font-bold uppercase tracking-wider text-amber-400">Needs Action</h2>
            <!-- Count badge -->
            <span
              v-if="needsAction.length"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-400"
            >{{ needsAction.length }}</span>
          </div>

          <!-- Needs-action swap cards with approve/deny buttons -->
          <div v-if="needsAction.length" class="divide-y divide-white/[0.04]">
            <div
              v-for="swap in needsAction"
              :key="swap.id"
              class="px-4 py-3 space-y-2"
            >
              <!-- SwapRequestCard component renders the swap details -->
              <SwapRequestCard :swap="swap" />

              <!-- Picker info: who offered to take the shift -->
              <p v-if="swap.picker" class="text-[10px] text-gray-500">
                Offered by: <span class="text-gray-300 font-medium">{{ swap.picker.name }}</span>
              </p>

              <!-- Action buttons -->
              <div class="flex gap-2">
                <button
                  :disabled="processingId === swap.id"
                  class="bg-green-500/25 text-green-300 hover:bg-green-500/35 px-3 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50 transition-colors"
                  @click="approveSwap(swap.id)"
                >{{ processingId === swap.id ? 'Processing...' : 'Approve' }}</button>
                <button
                  :disabled="processingId === swap.id"
                  class="bg-red-500/25 text-red-300 hover:bg-red-500/35 px-3 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50 transition-colors"
                  @click="denySwap(swap.id)"
                >Deny</button>
              </div>
            </div>
          </div>

          <!-- Empty state for needs-action -->
          <p v-else class="text-gray-600 text-xs text-center py-6">
            No swap requests need action right now
          </p>
        </section>

        <!-- ════════════════ SECTION 2: Pending ════════════════ -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <!-- Section header -->
          <div class="flex items-center gap-2 px-4 py-2.5 border-b border-white/[0.06]">
            <h2 class="text-xs font-bold uppercase tracking-wider text-yellow-400">Pending</h2>
            <span
              v-if="pending.length"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-bold bg-yellow-500/20 text-yellow-400"
            >{{ pending.length }}</span>
          </div>

          <!-- Pending swap cards (informational only, no action buttons) -->
          <div v-if="pending.length" class="divide-y divide-white/[0.04]">
            <div
              v-for="swap in pending"
              :key="swap.id"
              class="px-4 py-3"
            >
              <SwapRequestCard :swap="swap" />
              <!-- Target user info, if the requester requested a specific person -->
              <p v-if="swap.target_user" class="text-[10px] text-gray-500 mt-1">
                Requested to: <span class="text-gray-300 font-medium">{{ swap.target_user.name }}</span>
              </p>
              <p v-else class="text-[10px] text-gray-500 mt-1">
                Open to anyone
              </p>
            </div>
          </div>

          <!-- Empty state for pending -->
          <p v-else class="text-gray-600 text-xs text-center py-6">
            No pending swap requests
          </p>
        </section>

        <!-- ════════════════ SECTION 3: Resolved (collapsed by default) ════════════════ -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <!-- Collapsible section header -->
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

          <!-- Resolved swap cards (shown only when expanded) -->
          <div v-if="showResolved && resolved.length" class="border-t border-white/[0.06] divide-y divide-white/[0.04]">
            <div
              v-for="swap in resolved"
              :key="swap.id"
              class="px-4 py-3"
            >
              <SwapRequestCard :swap="swap" />
            </div>
          </div>

          <!-- Empty state (visible when expanded and no resolved swaps) -->
          <p
            v-if="showResolved && !resolved.length"
            class="text-gray-600 text-xs text-center py-6 border-t border-white/[0.06]"
          >
            No resolved swap requests
          </p>
        </section>
      </template>
    </div>
  </AppShell>
</template>
