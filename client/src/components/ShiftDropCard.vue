<script setup lang="ts">
/**
 * ShiftDropCard.vue
 *
 * Renders a compact, amber-themed card for a single shift drop request.
 * Shows the requester's name, a color-coded status badge (open/filled/
 * cancelled), shift name, date, time range, volunteer count, and an
 * optional reason.
 *
 * Props:
 *   - drop: ShiftDrop
 */
import { computed } from 'vue'
import type { ShiftDrop } from '@/types'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{ drop: ShiftDrop }>()

const statusColor = computed(() => {
  const map: Record<ShiftDrop['status'], 'yellow' | 'green' | 'gray'> = {
    open: 'yellow',
    filled: 'green',
    cancelled: 'gray',
  }
  return map[props.drop.status]
})

const requesterName = computed(() => props.drop.requester?.name ?? 'Staff member')

const shiftName = computed(() =>
  props.drop.schedule_entry?.shift_template?.name ?? 'Shift',
)

const shiftDate = computed(() => {
  const dateStr = props.drop.schedule_entry?.date
  if (!dateStr) return ''
  const d = new Date(dateStr + 'T12:00:00')
  return d.toLocaleDateString([], {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  })
})

function formatShiftTime(time: string): string {
  const [hourStr, minuteStr] = time.split(':')
  let hour = parseInt(hourStr, 10)
  const minute = minuteStr ?? '00'
  const ampm = hour >= 12 ? 'PM' : 'AM'
  if (hour === 0) hour = 12
  else if (hour > 12) hour -= 12
  return `${hour}:${minute} ${ampm}`
}

const timeRange = computed(() => {
  const st = props.drop.schedule_entry?.shift_template
  if (!st) return ''
  return formatShiftTime(st.start_time)
})

const volunteerCount = computed(() => props.drop.volunteers?.length ?? 0)
</script>

<template>
  <div class="rounded-lg bg-amber-500/5 border border-amber-500/10 p-3">
    <div class="min-w-0">
      <!-- Top row: requester name + status badge -->
      <div class="flex items-center gap-1.5">
        <h4 class="font-semibold text-amber-300 text-sm truncate">{{ requesterName }}</h4>
        <BadgePill :label="drop.status" :color="statusColor" />
      </div>

      <!-- Shift info -->
      <p class="text-xs text-amber-400/70 mt-0.5">
        {{ shiftName }}
        <span v-if="shiftDate" class="text-amber-500/50"> &middot; {{ shiftDate }}</span>
      </p>

      <!-- Time range -->
      <p v-if="timeRange" class="text-[10px] text-amber-500/60 mt-0.5">
        {{ timeRange }}
      </p>

      <!-- Volunteer count -->
      <p v-if="volunteerCount > 0" class="text-[10px] text-amber-500/60 mt-1">
        {{ volunteerCount }} volunteer{{ volunteerCount !== 1 ? 's' : '' }}
      </p>

      <!-- Optional reason -->
      <p v-if="drop.reason" class="text-[10px] text-amber-500/50 mt-1 italic line-clamp-2">
        {{ drop.reason }}
      </p>
    </div>
  </div>
</template>
