<script setup lang="ts">
/**
 * ManageTimeOff -- manager view for reviewing and acting on time-off requests.
 *
 * This view fetches all time-off requests from the API and splits them into
 * two sections:
 *
 *   1. "Pending Requests" — requests with status "pending" that require a
 *      manager decision. Each row displays the staff member's name, the
 *      date range formatted as "Feb 23 – Feb 25", the reason (if provided),
 *      and Approve / Deny action buttons.
 *
 *   2. "Resolved" — requests with status "approved" or "denied". This section
 *      is collapsed by default to keep the view focused on actionable items.
 *      Each row shows a status badge (green for approved, red for denied).
 *
 * API endpoints used:
 *   GET  /api/time-off-requests
 *   POST /api/time-off-requests/:id/approve
 *   POST /api/time-off-requests/:id/deny
 */
import { ref, computed, onMounted } from 'vue'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import type { TimeOffRequest } from '@/types'

// ── State ────────────────────────────────────────────────────────────────────

// All time-off requests fetched from the API
const timeOffRequests = ref<TimeOffRequest[]>([])
// True while the initial list is being loaded
const loading = ref(false)
// Tracks which request ID is currently being processed (approve/deny)
// so we can show a loading state on the specific row's buttons
const processingId = ref<number | null>(null)
// Controls visibility of the "Resolved" section (collapsed by default)
const showResolved = ref(false)

// ── Computed sections ────────────────────────────────────────────────────────

/**
 * Time-off requests with status "pending" -- awaiting manager decision.
 */
const pendingRequests = computed(() =>
  timeOffRequests.value.filter((r) => r.status === 'pending')
)

/**
 * Time-off requests that have been approved or denied.
 * Hidden by default behind a collapsible toggle.
 */
const resolvedRequests = computed(() =>
  timeOffRequests.value.filter((r) =>
    r.status === 'approved' || r.status === 'denied'
  )
)

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Formats an ISO date string ("2026-02-23") into a short, human-readable
 * label (e.g. "Feb 23"). Parses at noon to avoid timezone drift on
 * date-only strings.
 */
function formatDate(dateStr: string): string {
  const d = new Date(dateStr + 'T12:00:00')
  return d.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

/**
 * Returns a formatted date range string.
 * If start and end are the same day, renders just one date (e.g. "Feb 23").
 * Otherwise renders as "Feb 23 – Feb 25".
 */
function dateRange(req: TimeOffRequest): string {
  const start = formatDate(req.start_date)
  const end = formatDate(req.end_date)
  return start === end ? start : `${start} – ${end}`
}

/**
 * Maps a time-off request status to a BadgePill color.
 *   - "pending"  → yellow
 *   - "approved" → green
 *   - "denied"   → red
 */
function statusColor(status: TimeOffRequest['status']): 'yellow' | 'green' | 'red' {
  const map: Record<TimeOffRequest['status'], 'yellow' | 'green' | 'red'> = {
    pending: 'yellow',
    approved: 'green',
    denied: 'red',
  }
  return map[status]
}

// ── Fetch ────────────────────────────────────────────────────────────────────

/**
 * Fetches all time-off requests from GET /api/time-off-requests.
 * Expects the API to return entries with eagerly-loaded user relationship.
 */
async function fetchTimeOffRequests() {
  loading.value = true
  try {
    const { data } = await api.get<TimeOffRequest[]>('/api/time-off-requests')
    timeOffRequests.value = data
  } finally {
    loading.value = false
  }
}

// ── Actions ──────────────────────────────────────────────────────────────────

/**
 * Approves a time-off request via POST /api/time-off-requests/:id/approve.
 * Updates the local record in-place on success so the row moves from
 * "Pending" to "Resolved" without a full re-fetch.
 */
async function approveRequest(id: number) {
  processingId.value = id
  try {
    const { data } = await api.post<TimeOffRequest>(`/api/time-off-requests/${id}/approve`)
    // Replace the time-off request in the local list with the updated version
    const idx = timeOffRequests.value.findIndex((r) => r.id === id)
    if (idx !== -1) timeOffRequests.value[idx] = data
    toast('Time-off request approved', 'success')
  } catch {
    toast('Failed to approve time-off request', 'error')
  } finally {
    processingId.value = null
  }
}

/**
 * Denies a time-off request via POST /api/time-off-requests/:id/deny.
 * Updates the local record in-place on success.
 */
async function denyRequest(id: number) {
  processingId.value = id
  try {
    const { data } = await api.post<TimeOffRequest>(`/api/time-off-requests/${id}/deny`)
    // Replace the time-off request in the local list with the updated version
    const idx = timeOffRequests.value.findIndex((r) => r.id === id)
    if (idx !== -1) timeOffRequests.value[idx] = data
    toast('Time-off request denied', 'success')
  } catch {
    toast('Failed to deny time-off request', 'error')
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

// Fetch time-off requests on component mount
onMounted(fetchTimeOffRequests)
</script>

<template>
  <AppShell>
    <div class="space-y-5">
      <!-- ── Page header ──────────────────────────────────────────────────── -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-white tracking-tight">Manage Time Off</h1>
          <p class="text-xs text-gray-500 mt-0.5">Review time-off requests</p>
        </div>
        <router-link
          to="/manage"
          class="text-xs text-gray-500 hover:text-gray-300 transition-colors"
        >Back</router-link>
      </div>

      <!-- ── Loading state ────────────────────────────────────────────────── -->
      <div v-if="loading" class="flex justify-center py-8">
        <svg class="animate-spin h-6 w-6 text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
      </div>

      <template v-else>
        <!-- ════════════════ SECTION 1: Pending Requests ════════════════ -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <!-- Section header -->
          <div class="flex items-center gap-2 px-4 py-2.5 border-b border-white/[0.06]">
            <h2 class="text-xs font-bold uppercase tracking-wider text-purple-400">Pending Requests</h2>
            <!-- Count badge -->
            <span
              v-if="pendingRequests.length"
              class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-400"
            >{{ pendingRequests.length }}</span>
          </div>

          <!-- Pending request rows with approve/deny buttons -->
          <div v-if="pendingRequests.length" class="divide-y divide-white/[0.04]">
            <div
              v-for="req in pendingRequests"
              :key="req.id"
              class="px-4 py-3 flex items-start gap-4"
            >
              <!-- Request details -->
              <div class="flex-1 min-w-0">
                <!-- Staff member name -->
                <p class="text-sm font-medium text-gray-200">
                  {{ req.user?.name ?? 'Unknown' }}
                </p>
                <!-- Date range (e.g. "Feb 23 – Feb 25") -->
                <p class="text-xs text-gray-400 mt-0.5">
                  {{ dateRange(req) }}
                </p>
                <!-- Reason, if present -->
                <p v-if="req.reason" class="text-[10px] text-gray-500 mt-1 italic line-clamp-2">
                  {{ req.reason }}
                </p>
              </div>

              <!-- Action buttons -->
              <div class="flex gap-2 flex-shrink-0 pt-0.5">
                <button
                  :disabled="processingId === req.id"
                  class="bg-green-500/25 text-green-300 hover:bg-green-500/35 px-3 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50 transition-colors"
                  @click="approveRequest(req.id)"
                >{{ processingId === req.id ? 'Processing...' : 'Approve' }}</button>
                <button
                  :disabled="processingId === req.id"
                  class="bg-red-500/25 text-red-300 hover:bg-red-500/35 px-3 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50 transition-colors"
                  @click="denyRequest(req.id)"
                >Deny</button>
              </div>
            </div>
          </div>

          <!-- Empty state for pending requests -->
          <p v-else class="text-gray-600 text-xs text-center py-6">
            No pending time-off requests
          </p>
        </section>

        <!-- ════════════════ SECTION 2: Resolved (collapsed by default) ════════════════ -->
        <section class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
          <!-- Collapsible section header -->
          <button
            class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-white/[0.02] transition-colors"
            @click="showResolved = !showResolved"
          >
            <div class="flex items-center gap-2">
              <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Resolved</h2>
              <span
                v-if="resolvedRequests.length"
                class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full text-[10px] font-bold bg-gray-500/20 text-gray-400"
              >{{ resolvedRequests.length }}</span>
            </div>
            <svg
              class="w-4 h-4 text-gray-500 transition-transform"
              :class="{ 'rotate-180': showResolved }"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Resolved request rows (shown only when expanded) -->
          <div v-if="showResolved && resolvedRequests.length" class="border-t border-white/[0.06] divide-y divide-white/[0.04]">
            <div
              v-for="req in resolvedRequests"
              :key="req.id"
              class="px-4 py-3 flex items-center gap-4"
            >
              <!-- Request details -->
              <div class="flex-1 min-w-0">
                <!-- Staff member name -->
                <p class="text-sm font-medium text-gray-200">
                  {{ req.user?.name ?? 'Unknown' }}
                </p>
                <!-- Date range -->
                <p class="text-xs text-gray-400 mt-0.5">
                  {{ dateRange(req) }}
                </p>
                <!-- Reason, if present -->
                <p v-if="req.reason" class="text-[10px] text-gray-500 mt-1 italic line-clamp-2">
                  {{ req.reason }}
                </p>
              </div>

              <!-- Status badge -->
              <BadgePill :label="req.status" :color="statusColor(req.status)" />
            </div>
          </div>

          <!-- Empty state (visible when expanded and no resolved requests) -->
          <p
            v-if="showResolved && !resolvedRequests.length"
            class="text-gray-600 text-xs text-center py-6 border-t border-white/[0.06]"
          >
            No resolved time-off requests
          </p>
        </section>
      </template>
    </div>
  </AppShell>
</template>
