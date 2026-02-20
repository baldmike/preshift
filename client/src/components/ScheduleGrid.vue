<script setup lang="ts">
/**
 * ScheduleGrid.vue
 *
 * Renders a weekly schedule as a 7-column (Mon–Sun) grid.
 * Each row corresponds to a ShiftTemplate; each cell shows the staff
 * assigned to that shift + day combination.
 *
 * Props:
 *   - schedule: Schedule          -- the weekly schedule with its entries
 *   - shiftTemplates: ShiftTemplate[] -- all shift templates for the location
 *
 * Emits:
 *   - add-entry:    { shiftTemplateId: number, date: string }
 *                   Fired when the "+" button in an empty cell is clicked.
 *   - remove-entry: number
 *                   Fired with the entry id when the "x" button is clicked.
 *
 * The grid calculates the seven dates of the week from schedule.week_start
 * (which is always a Monday) and cross-references schedule.entries to
 * populate each cell.
 */
import { computed } from 'vue'
import type { Schedule, ShiftTemplate, ScheduleEntry } from '@/types'
import BadgePill from '@/components/ui/BadgePill.vue'

const props = defineProps<{
  schedule: Schedule
  shiftTemplates: ShiftTemplate[]
}>()

const emit = defineEmits<{
  'add-entry': [payload: { shiftTemplateId: number; date: string }]
  'remove-entry': [entryId: number]
}>()

// ── Day abbreviations used in column headers ───────────────────────────

const dayAbbreviations = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

// ── Computed Values ────────────────────────────────────────────────────

/**
 * Generates an array of 7 ISO date strings starting from
 * schedule.week_start (Monday) through the following Sunday.
 * We parse at noon to avoid any timezone-offset drift that can
 * occur when parsing date-only strings.
 */
const weekDates = computed<string[]>(() => {
  const dates: string[] = []
  const base = new Date(props.schedule.week_start + 'T12:00:00')
  for (let i = 0; i < 7; i++) {
    const d = new Date(base)
    d.setDate(base.getDate() + i)
    // Produce "YYYY-MM-DD" to match the entry date format
    const yyyy = d.getFullYear()
    const mm = String(d.getMonth() + 1).padStart(2, '0')
    const dd = String(d.getDate()).padStart(2, '0')
    dates.push(`${yyyy}-${mm}-${dd}`)
  }
  return dates
})

/**
 * Column header labels combining the day abbreviation with a short
 * date (e.g. "Mon 2/23").
 */
const columnHeaders = computed(() => {
  return weekDates.value.map((iso, i) => {
    const d = new Date(iso + 'T12:00:00')
    const month = d.getMonth() + 1
    const day = d.getDate()
    return `${dayAbbreviations[i]} ${month}/${day}`
  })
})

/**
 * Returns all schedule entries that match a given shift template + date.
 * Used by each cell to determine which staff members are assigned.
 */
function entriesFor(shiftTemplateId: number, date: string): ScheduleEntry[] {
  if (!props.schedule.entries) return []
  return props.schedule.entries.filter(
    (e) => e.shift_template_id === shiftTemplateId && e.date === date,
  )
}

/**
 * Map the entry role to a BadgePill color.
 */
function roleColor(role: string): 'blue' | 'green' {
  return role === 'bartender' ? 'green' : 'blue'
}

/**
 * Format a shift template's time range for the row header.
 * Converts "16:00:00" → "4:00 PM".
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
</script>

<template>
  <!--
    Outer wrapper: horizontal scroll for narrow viewports,
    dark background consistent with the app's dark theme.
  -->
  <div class="overflow-x-auto rounded-lg border border-white/[0.06] bg-[rgba(255,255,255,0.03)]">
    <table class="w-full min-w-[640px] text-xs">
      <!-- ── Column headers: day abbreviation + date ───────────────── -->
      <thead>
        <tr>
          <!-- Top-left corner: "Shift" label for the row-header column -->
          <th
            class="sticky left-0 z-10 bg-[rgba(255,255,255,0.03)] border-b border-r border-white/[0.06] px-2 py-1.5 text-left text-[10px] uppercase tracking-wider text-gray-500"
          >
            Shift
          </th>
          <th
            v-for="(header, idx) in columnHeaders"
            :key="idx"
            class="border-b border-white/[0.06] px-2 py-1.5 text-center text-[10px] uppercase tracking-wider text-gray-500"
          >
            {{ header }}
          </th>
        </tr>
      </thead>

      <!-- ── One row per shift template ────────────────────────────── -->
      <tbody>
        <tr
          v-for="template in shiftTemplates"
          :key="template.id"
          class="group"
        >
          <!--
            Row header: shift template name and time range.
            Sticky on the left so it remains visible when scrolling.
          -->
          <td
            class="sticky left-0 z-10 bg-[rgba(255,255,255,0.03)] border-b border-r border-white/[0.06] px-2 py-1.5 whitespace-nowrap"
          >
            <div class="font-medium text-gray-300">{{ template.name }}</div>
            <div class="text-[10px] text-gray-500">
              {{ formatShiftTime(template.start_time) }} – {{ formatShiftTime(template.end_time) }}
            </div>
          </td>

          <!-- One cell per day of the week -->
          <td
            v-for="date in weekDates"
            :key="date"
            class="border-b border-white/[0.06] px-1.5 py-1 align-top min-w-[90px]"
          >
            <!--
              If entries exist for this shift+day: render each
              assigned staff member with a role badge and remove button.
            -->
            <div
              v-for="entry in entriesFor(template.id, date)"
              :key="entry.id"
              class="flex items-center gap-1 rounded bg-white/[0.03] px-1 py-0.5 mb-0.5 group/entry"
            >
              <!-- User name + role badge -->
              <span class="truncate text-gray-300 flex-1">
                {{ entry.user?.name ?? 'Staff' }}
              </span>
              <BadgePill :label="entry.role" :color="roleColor(entry.role)" />

              <!-- Remove button (visible on hover) -->
              <button
                class="ml-0.5 text-red-400/60 hover:text-red-400 opacity-0 group-hover/entry:opacity-100 transition-opacity text-[10px] leading-none"
                title="Remove entry"
                @click="emit('remove-entry', entry.id)"
              >
                &times;
              </button>
            </div>

            <!--
              If no entries exist: show an "add" button so a manager
              can assign someone to this slot.
            -->
            <button
              v-if="entriesFor(template.id, date).length === 0"
              class="flex items-center justify-center w-full rounded border border-dashed border-white/[0.08] text-gray-600 hover:text-gray-400 hover:border-white/[0.15] transition-colors py-1"
              title="Add entry"
              @click="emit('add-entry', { shiftTemplateId: template.id, date })"
            >
              +
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
