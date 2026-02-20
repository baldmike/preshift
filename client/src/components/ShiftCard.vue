<script setup lang="ts">
/**
 * ShiftCard.vue
 *
 * Displays a single schedule entry as a compact, blue-themed card.
 * Shows the date, shift template name, formatted time range, role badge,
 * and optional manager notes.
 *
 * Props:
 *   - entry: ScheduleEntry  -- the schedule entry to render; expects
 *     `shift_template` to be eagerly loaded so the template name and
 *     times are available.
 *
 * This component is read-only; it does not emit any events.
 */
import { computed } from 'vue'
import type { ScheduleEntry } from '@/types'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{ entry: ScheduleEntry }>()
const emit = defineEmits<{ 'give-up': [entryId: number] }>()

// ── Helpers ────────────────────────────────────────────────────────────

/**
 * Converts a 24-hour time string ("16:00:00" or "16:00") into a
 * 12-hour formatted string ("4:00 PM").
 * Handles edge cases: midnight → "12:00 AM", noon → "12:00 PM".
 */
function formatShiftTime(time: string): string {
  const [hourStr, minuteStr] = time.split(':')
  let hour = parseInt(hourStr, 10)
  const minute = minuteStr ?? '00'
  const ampm = hour >= 12 ? 'PM' : 'AM'
  if (hour === 0) hour = 12
  else if (hour > 12) hour -= 12
  return `${hour}:${minute} ${ampm}`
}

// ── Computed Values ────────────────────────────────────────────────────

/**
 * Format the entry date as a short human-readable string.
 * Example: "Mon, Feb 23"
 * Uses the entry's ISO date string, parsed as a local date (noon offset
 * avoids timezone drift issues with date-only strings).
 */
const formattedDate = computed(() => {
  const d = new Date(props.entry.date + 'T12:00:00')
  return d.toLocaleDateString([], {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  })
})

/**
 * The shift template name -- falls back to "Shift" if the relationship
 * was not loaded from the API.
 */
const shiftName = computed(() => props.entry.shift_template?.name ?? 'Shift')

/**
 * Formatted time range string (e.g. "10:30 AM – 3:00 PM").
 * Returns an empty string when the shift template relationship is
 * not loaded so the UI degrades gracefully.
 */
const timeRange = computed(() => {
  const st = props.entry.shift_template
  if (!st) return ''
  return `${formatShiftTime(st.start_time)} – ${formatShiftTime(st.end_time)}`
})

/**
 * Map the entry role to an existing BadgePill color.
 *   - "server"    → blue
 *   - "bartender" → green
 */
const roleColor = computed(() => {
  return props.entry.role === 'bartender' ? 'green' : 'blue'
})
</script>

<template>
  <!-- Blue-themed card matching SpecialCard styling -->
  <div class="rounded-lg bg-blue-500/5 border border-blue-500/10 p-3">
    <div class="min-w-0">
      <!-- Top row: shift name + role badge -->
      <div class="flex items-center gap-1.5">
        <h4 class="font-semibold text-blue-300 text-sm truncate">{{ shiftName }}</h4>
        <BadgePill :label="entry.role" :color="roleColor" />
      </div>

      <!-- Time range (e.g. "10:30 AM – 3:00 PM") -->
      <p v-if="timeRange" class="text-xs text-blue-400/70 mt-0.5">
        {{ timeRange }}
      </p>

      <!-- Footer: date + optional notes -->
      <div class="flex items-center gap-2 text-[10px] text-blue-500/60 mt-1.5">
        <span>{{ formattedDate }}</span>
      </div>

      <!-- Manager notes, if present -->
      <p v-if="entry.notes" class="text-[10px] text-blue-500/50 mt-1 italic line-clamp-2">
        {{ entry.notes }}
      </p>

      <!-- Give Up Shift button -->
      <button
        @click="emit('give-up', entry.id)"
        class="mt-2 text-[10px] font-medium text-red-400/70 hover:text-red-300 transition-colors"
      >Give Up Shift</button>
    </div>
  </div>
</template>
