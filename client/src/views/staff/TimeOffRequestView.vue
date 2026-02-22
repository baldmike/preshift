<script setup lang="ts">
/**
 * TimeOffRequestView.vue
 *
 * Staff-facing view for submitting and viewing time-off requests.
 * The page has two sections:
 *
 *   1. A form to submit a new time-off request (start date, end date, reason).
 *      On successful submission the new request is prepended to the local list,
 *      the form is reset, and a toast notification is shown.
 *
 *   2. A list of the user's existing time-off requests, each displaying the
 *      date range, reason, and a color-coded status badge:
 *        - pending  → amber
 *        - approved → green
 *        - denied   → red
 *
 * Data flows through the Pinia schedule store (useScheduleStore). Requests
 * are fetched on mount; new submissions are POSTed directly via the api
 * composable and then pushed into the store's local state.
 *
 * Route: /time-off (staff only)
 */
import { ref, onMounted } from 'vue'
import { useScheduleStore } from '@/stores/schedule'
import api from '@/composables/useApi'
import AppShell from '@/components/layout/AppShell.vue'
import type { TimeOffRequest } from '@/types'

// ── Store ──────────────────────────────────────────────────────────────
const store = useScheduleStore()

// ── Local State ────────────────────────────────────────────────────────
// True while the initial list of time-off requests is being fetched
const loading = ref(false)

// True while a new request is being submitted to the API
const submitting = ref(false)

// Form model for the new time-off request.
// All fields start empty and are reset after successful submission.
const form = ref({
  start_date: '',
  end_date: '',
  reason: '',
})

// ── Helpers ────────────────────────────────────────────────────────────

/**
 * Dispatches a toast notification via the global CustomEvent pattern
 * used throughout the app (handled by ToastContainer).
 */
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

/**
 * Formats an ISO date string (e.g. "2026-03-15") into a short
 * human-readable label (e.g. "Mar 15, 2026").
 * Returns "N/A" for null/undefined values for defensive rendering.
 */
function formatDate(dateStr: string | null): string {
  if (!dateStr) return 'N/A'
  // Extract YYYY-MM-DD from possible ISO timestamp ("2026-02-23T00:00:00.000000Z")
  const ymd = dateStr.substring(0, 10)
  const d = new Date(ymd + 'T12:00:00')
  return d.toLocaleDateString([], {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

// ── Actions ────────────────────────────────────────────────────────────

/**
 * Submits a new time-off request to the API.
 * On success:
 *   - Pushes the returned TimeOffRequest into the store so it appears
 *     in the list immediately without a re-fetch.
 *   - Resets the form fields.
 *   - Shows a success toast.
 * On failure:
 *   - Shows an error toast.
 */
async function submitRequest() {
  // Basic client-side guard -- both dates are required
  if (!form.value.start_date || !form.value.end_date) return

  submitting.value = true
  try {
    const { data } = await api.post<TimeOffRequest>('/api/time-off-requests', {
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      reason: form.value.reason || null,
    })

    // Push the new request into the store's local state so the list
    // updates immediately. upsertTimeOffRequest handles both insert
    // and update semantics.
    store.upsertTimeOffRequest(data)

    // Reset the form for the next entry
    form.value = { start_date: '', end_date: '', reason: '' }

    toast('Time-off request submitted', 'success')
  } catch {
    toast('Failed to submit request', 'error')
  } finally {
    submitting.value = false
  }
}

// ── Mount ──────────────────────────────────────────────────────────────
/**
 * Fetches the current user's time-off requests on mount.
 * The store action hits GET /api/time-off-requests.
 */
onMounted(async () => {
  loading.value = true
  try {
    await store.fetchTimeOffRequests()
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
          <!-- Clock/calendar icon for time off -->
          <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h1 class="text-xl font-bold text-white">Time Off</h1>
        </div>
        <p class="text-xs text-gray-500 mt-0.5">Request and manage your time off</p>
      </div>

      <!-- Sub-navigation: pill links to schedule-related views -->
      <div class="flex gap-2">
        <router-link
          to="/tonights-schedule"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-teal-500/20 text-teal-300 hover:bg-teal-500/30 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Tonight's Schedule
        </router-link>
        <router-link
          to="/shift-drops"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 transition-colors"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          Drop Board
        </router-link>
      </div>

      <!-- ── New Request Form ─────────────────────────────────────────── -->
      <!-- Styled to match the form pattern from EightySixedBoard and
           DailyManageView: rounded container with subtle glass background,
           dm-input-style fields, and a themed submit button. -->
      <form
        @submit.prevent="submitRequest"
        class="rounded-lg bg-white/[0.03] border border-white/[0.06] p-4 space-y-3"
      >
        <h2 class="text-sm font-semibold text-gray-300">New Request</h2>

        <!-- Date range inputs (side by side on larger screens) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <!-- Start date -->
          <div>
            <label class="block text-xs text-gray-500 mb-1">Start Date</label>
            <input
              v-model="form.start_date"
              type="date"
              required
              class="w-full rounded-md bg-white/5 border border-white/10 px-3 py-2 text-sm text-gray-200
                     placeholder-gray-600 focus:outline-none focus:border-emerald-500/50 focus:ring-1
                     focus:ring-emerald-500/25 transition-colors"
            />
          </div>

          <!-- End date -->
          <div>
            <label class="block text-xs text-gray-500 mb-1">End Date</label>
            <input
              v-model="form.end_date"
              type="date"
              required
              class="w-full rounded-md bg-white/5 border border-white/10 px-3 py-2 text-sm text-gray-200
                     placeholder-gray-600 focus:outline-none focus:border-emerald-500/50 focus:ring-1
                     focus:ring-emerald-500/25 transition-colors"
            />
          </div>
        </div>

        <!-- Reason (optional text input) -->
        <div>
          <label class="block text-xs text-gray-500 mb-1">Reason (optional)</label>
          <input
            v-model="form.reason"
            type="text"
            placeholder="e.g. Family vacation, doctor's appointment"
            class="w-full rounded-md bg-white/5 border border-white/10 px-3 py-2 text-sm text-gray-200
                   placeholder-gray-600 focus:outline-none focus:border-emerald-500/50 focus:ring-1
                   focus:ring-emerald-500/25 transition-colors"
          />
        </div>

        <!-- Submit button -->
        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="submitting"
            class="px-4 py-2 rounded-md bg-emerald-600 text-white text-sm font-semibold
                   hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500
                   focus:ring-offset-2 focus:ring-offset-gray-950
                   disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <!-- Spinner while submitting -->
            <svg v-if="submitting" class="animate-spin h-4 w-4 inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            {{ submitting ? 'Submitting...' : 'Submit Request' }}
          </button>
        </div>
      </form>

      <!-- ── Loading State ────────────────────────────────────────────── -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="flex flex-col items-center gap-3">
          <svg class="animate-spin h-8 w-8 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <span class="text-sm text-gray-500">Loading requests...</span>
        </div>
      </div>

      <!-- ── Existing Requests List ───────────────────────────────────── -->
      <!-- Each request shows a date range, optional reason, and a
           color-coded status badge. Styled as individual cards within
           a section container matching DailyManageView's pattern. -->
      <section v-else-if="store.timeOffRequests.length" class="rounded-xl bg-white/[0.03] border border-white/[0.06] overflow-hidden">
        <!-- Section header -->
        <header class="flex items-center gap-1.5 px-3 py-2.5 text-xs font-bold uppercase tracking-wide text-emerald-300 border-b border-white/[0.06]">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <span class="flex-1">Your Requests</span>
          <span
            class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold"
          >
            {{ store.timeOffRequests.length }}
          </span>
        </header>

        <!-- Request cards -->
        <div class="p-3 space-y-2">
          <div
            v-for="request in store.timeOffRequests"
            :key="request.id"
            class="flex items-center gap-3 rounded-lg bg-white/[0.02] border border-white/[0.04] p-3
                   hover:bg-white/[0.04] transition-colors"
          >
            <!-- Request info (date range + reason) -->
            <div class="flex-1 min-w-0">
              <!-- Date range displayed prominently -->
              <p class="text-sm font-medium text-gray-200">
                {{ formatDate(request.start_date) }} &mdash; {{ formatDate(request.end_date) }}
              </p>
              <!-- Optional reason line -->
              <p class="text-xs text-gray-500 mt-0.5 truncate">
                {{ request.reason || 'No reason provided' }}
              </p>
            </div>

            <!-- Status badge: color-coded to quickly convey the decision.
                 Uses inline Tailwind classes that match the dm-badge color
                 palette from DailyManageView for consistency. -->
            <span
              :class="[
                'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide flex-shrink-0',
                {
                  'bg-amber-500/20 text-amber-400':   request.status === 'pending',
                  'bg-green-500/20 text-green-400':    request.status === 'approved',
                  'bg-red-500/20 text-red-400':        request.status === 'denied',
                },
              ]"
            >
              {{ request.status }}
            </span>
          </div>
        </div>
      </section>

      <!-- ── Empty State (no existing requests, not loading) ──────────── -->
      <div v-else class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-14 h-14 rounded-full bg-gray-800 flex items-center justify-center mb-4">
          <svg class="w-7 h-7 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-gray-400 font-medium">No time-off requests yet</p>
        <p class="text-gray-600 text-sm mt-1">Use the form above to submit your first request</p>
      </div>

    </div>
  </AppShell>
</template>
