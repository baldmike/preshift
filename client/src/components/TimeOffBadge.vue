<script setup lang="ts">
/**
 * TimeOffBadge.vue
 *
 * A compact, inline badge that summarises a time-off request.
 * Designed to sit inside lists or grid cells without taking up much
 * vertical space -- unlike the full-card components used elsewhere.
 *
 * Displays:
 *   - User name (from the eagerly-loaded `user` relationship)
 *   - Date range formatted as "Feb 23 – Feb 25"
 *   - Status badge (pending → yellow, approved → green, denied → red)
 *
 * Props:
 *   - request: TimeOffRequest  -- expects the `user` relationship to
 *     be eagerly loaded for the name display.
 *
 * This component is read-only and does not emit any events.
 * Parent views are responsible for action buttons (approve, deny, etc.).
 */
import { computed } from 'vue'
import type { TimeOffRequest } from '@/types'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{ request: TimeOffRequest }>()

// ── Status → BadgePill colour mapping ──────────────────────────────────

/**
 * Map the time-off request status to a BadgePill color.
 *   - "pending"  → yellow (awaiting manager decision)
 *   - "approved" → green  (time off approved)
 *   - "denied"   → red    (time off denied)
 */
const statusColor = computed(() => {
  const map: Record<TimeOffRequest['status'], 'yellow' | 'green' | 'red'> = {
    pending: 'yellow',
    approved: 'green',
    denied: 'red',
  }
  return map[props.request.status]
})

// ── Computed display values ────────────────────────────────────────────

/**
 * The requesting user's display name.
 * Falls back to "Staff member" when the relationship is not loaded.
 */
const userName = computed(() => props.request.user?.name ?? 'Staff member')

/**
 * Formats a date-only ISO string ("2026-02-23") into a short label
 * (e.g. "Feb 23").  Parses at noon to avoid timezone drift on
 * date-only strings.
 */
function formatShortDate(dateStr: string): string {
  // Extract YYYY-MM-DD from possible ISO timestamp ("2026-02-23T00:00:00.000000Z")
  const ymd = dateStr.substring(0, 10)
  const d = new Date(ymd + 'T12:00:00')
  return d.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

/**
 * The formatted date range.
 * If start and end are the same day, only one date is shown.
 * Otherwise renders as "Feb 23 – Feb 25".
 */
const dateRange = computed(() => {
  const start = formatShortDate(props.request.start_date)
  const end = formatShortDate(props.request.end_date)
  return start === end ? start : `${start} – ${end}`
})
</script>

<template>
  <!--
    Compact inline container with minimal padding.
    Uses a subtle dark background with rounded corners to visually
    group the badge contents without dominating the layout.
    This is intentionally NOT a full-height card -- just an inline
    pill-shaped element for dense list or grid contexts.
  -->
  <span
    class="inline-flex items-center gap-1.5 rounded-full bg-white/[0.03] border border-white/[0.06] px-2.5 py-1 text-xs"
  >
    <!-- User name (truncated if too long to keep the badge compact) -->
    <span class="font-medium text-gray-300 truncate max-w-[120px]">{{ userName }}</span>

    <!-- Date range (e.g. "Feb 23 – Feb 25") -->
    <span class="text-gray-500">{{ dateRange }}</span>

    <!-- Status badge using the shared BadgePill component -->
    <BadgePill :label="request.status" :color="statusColor" />
  </span>
</template>
