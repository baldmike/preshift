<script setup lang="ts">
/**
 * SwapRequestCard.vue
 *
 * Displays a shift swap request as an amber-themed card, styled after
 * PushItemCard.  Shows who is requesting the swap, which shift is
 * being offered (date + template name), the current status, and an
 * optional reason.  When the status is "offered", the picker's name
 * is also displayed.
 *
 * Props:
 *   - swap: SwapRequest  -- expects `requester`, `schedule_entry`
 *     (with nested `shift_template`), and `picker` to be eagerly loaded.
 *
 * Status colour mapping (uses BadgePill palette):
 *   - pending   → yellow  (closest to amber in the design system)
 *   - offered   → blue    (someone offered to pick up the shift)
 *   - approved  → green   (manager approved the swap)
 *   - denied    → red     (manager denied the swap)
 *   - cancelled → gray    (requester cancelled)
 *
 * This component is read-only and does not emit any events.
 * Parent views are responsible for action buttons (offer, cancel, etc.).
 */
import { computed } from 'vue'
import type { SwapRequest } from '@/types'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{ swap: SwapRequest }>()

// ── Status → BadgePill colour mapping ──────────────────────────────────

/**
 * Map the swap request status to a BadgePill color.
 *   - "pending"   → yellow (awaiting someone to pick it up)
 *   - "offered"   → blue   (someone offered, awaiting manager approval)
 *   - "approved"  → green  (swap was approved)
 *   - "denied"    → red    (swap was denied)
 *   - "cancelled" → gray   (requester cancelled)
 */
const statusColor = computed(() => {
  const map: Record<SwapRequest['status'], 'yellow' | 'blue' | 'green' | 'red' | 'gray'> = {
    pending: 'yellow',
    offered: 'blue',
    approved: 'green',
    denied: 'red',
    cancelled: 'gray',
  }
  return map[props.swap.status]
})

// ── Computed display values ────────────────────────────────────────────

/**
 * Requester's display name, falling back to a generic label when the
 * relationship is not loaded from the API.
 */
const requesterName = computed(() => props.swap.requester?.name ?? 'Staff member')

/**
 * The shift template name associated with the schedule entry being
 * swapped. Falls back to "Shift" if relationships are missing.
 */
const shiftName = computed(() =>
  props.swap.schedule_entry?.shift_template?.name ?? 'Shift',
)

/**
 * The date of the shift being swapped, formatted as a short string
 * (e.g. "Mon, Feb 23").  Parses at noon to avoid timezone drift
 * on date-only strings.
 */
const shiftDate = computed(() => {
  const dateStr = props.swap.schedule_entry?.date
  if (!dateStr) return ''
  const d = new Date(dateStr + 'T12:00:00')
  return d.toLocaleDateString([], {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  })
})

/**
 * The name of the person who offered to pick up the shift.
 * Only relevant when status is "offered" (or later, if a picker
 * was assigned before approval/denial).
 */
const pickerName = computed(() => props.swap.picker?.name ?? null)
</script>

<template>
  <!-- Amber-themed card matching PushItemCard styling -->
  <div class="rounded-lg bg-amber-500/5 border border-amber-500/10 p-3">
    <div class="min-w-0">
      <!-- Top row: requester name + status badge -->
      <div class="flex items-center gap-1.5">
        <h4 class="font-semibold text-amber-300 text-sm truncate">{{ requesterName }}</h4>
        <BadgePill :label="swap.status" :color="statusColor" />
      </div>

      <!--
        Shift info: template name + date.
        Displayed as a secondary line beneath the requester name.
      -->
      <p class="text-xs text-amber-400/70 mt-0.5">
        {{ shiftName }}
        <span v-if="shiftDate" class="text-amber-500/50"> &middot; {{ shiftDate }}</span>
      </p>

      <!--
        Picker info: shown only when someone has offered to pick up
        the shift (picker relationship is loaded).
      -->
      <p v-if="pickerName" class="text-[10px] text-amber-500/60 mt-1">
        Offered by <span class="text-amber-400/80 font-medium">{{ pickerName }}</span>
      </p>

      <!-- Optional reason for the swap request -->
      <p v-if="swap.reason" class="text-[10px] text-amber-500/50 mt-1 italic line-clamp-2">
        {{ swap.reason }}
      </p>
    </div>
  </div>
</template>
