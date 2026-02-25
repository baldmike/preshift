<script setup lang="ts">
/**
 * AvailabilityGrid.vue
 *
 * A weekly availability selector for staff members. Displays a 7-column
 * grid (Mon–Sun) where each day has three toggleable options:
 *   - 10:30 AM  (available for opening shift: 10:30 AM – 6:00 PM)
 *   - 4:30 PM   (available for evening shift: 4:30 PM – close)
 *   - OPEN       (master button: open availability for the entire day)
 *
 * Selecting OPEN overrides the individual time slots — it means the
 * employee is available for any shift that day. Selecting a time slot
 * while OPEN is active deselects OPEN and keeps only the specific slot.
 *
 * Props:
 *   - modelValue: Record<string, string[]>  — day-to-slots map
 *
 * Emits:
 *   - update:modelValue: Record<string, string[]>
 */
import { computed } from 'vue'

const props = defineProps<{
  modelValue: Record<string, string[]>
  saving?: boolean
  readonly?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: Record<string, string[]>]
  'save': []
}>()

const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as const
const DAY_LABELS: Record<string, string> = {
  monday: 'Mon', tuesday: 'Tue', wednesday: 'Wed', thursday: 'Thu',
  friday: 'Fri', saturday: 'Sat', sunday: 'Sun',
}

/** The slot options available per day */
const SLOTS = [
  { value: '10:30', label: '10:30 AM' },
  { value: '16:30', label: '4:30 PM' },
  { value: '18:00', label: '6:00 PM' },
  { value: '19:00', label: '7:00 PM' },
] as const

/** Check if a specific slot is active for a given day */
function isActive(day: string, slot: string): boolean {
  const slots = props.modelValue[day] ?? []
  return slots.includes(slot)
}

/** Check if OPEN is active for a given day */
function isOpen(day: string): boolean {
  const slots = props.modelValue[day] ?? []
  return slots.includes('open')
}

/** Toggle a time slot (10:30 or 16:30) for a day */
function toggleSlot(day: string, slot: string) {
  const current = [...(props.modelValue[day] ?? [])]
  const idx = current.indexOf(slot)

  if (idx >= 0) {
    // Deselect this slot
    current.splice(idx, 1)
  } else {
    // Select this slot; deselect OPEN since we're picking specific times
    const openIdx = current.indexOf('open')
    if (openIdx >= 0) current.splice(openIdx, 1)
    current.push(slot)
  }

  emit('update:modelValue', { ...props.modelValue, [day]: current })
}

/** Toggle OPEN for a day — overrides specific time slots */
function toggleOpen(day: string) {
  const current = props.modelValue[day] ?? []
  if (current.includes('open')) {
    // Deselect OPEN
    emit('update:modelValue', { ...props.modelValue, [day]: [] })
  } else {
    // Select OPEN — replaces any specific slots
    emit('update:modelValue', { ...props.modelValue, [day]: ['open'] })
  }
}

/** True when every day is set to 'open' */
const allOpen = computed(() => {
  return DAYS.every(d => (props.modelValue[d] ?? []).includes('open'))
})

/** Set every day to ['open'] (or clear all if already all-open) */
function toggleAllOpen() {
  if (allOpen.value) {
    // Deselect everything
    emit('update:modelValue', Object.fromEntries(DAYS.map(d => [d, []])))
  } else {
    // Set every day to open
    emit('update:modelValue', Object.fromEntries(DAYS.map(d => [d, ['open']])))
  }
}
</script>

<template>
  <div class="space-y-3">
    <!-- Master OPEN button — sets every day to open availability -->
    <button
      v-if="!readonly"
      type="button"
      class="w-full py-2 text-xs font-bold rounded-md border transition-colors uppercase tracking-wide"
      :class="allOpen
        ? 'bg-emerald-500/25 border-emerald-400/40 text-emerald-300'
        : 'bg-white/[0.03] border-white/[0.08] text-gray-500 hover:border-white/[0.15] hover:text-gray-300'
      "
      @click="toggleAllOpen"
    >
      Open Availability
    </button>

    <!-- Grid header -->
    <div class="overflow-x-auto">
    <div class="grid grid-cols-7 gap-1.5 text-center min-w-[320px]">
      <div
        v-for="day in DAYS"
        :key="day"
        class="text-[10px] font-bold uppercase tracking-wider text-gray-500"
      >
        {{ DAY_LABELS[day] }}
      </div>
    </div>

    <!-- Grid body: one column per day -->
    <div :class="['grid grid-cols-7 gap-1.5 min-w-[320px]', { 'pointer-events-none': readonly }]">
      <div
        v-for="day in DAYS"
        :key="day"
        class="flex flex-col gap-1"
      >
        <!-- Time slot buttons (10:30 AM, 4:30 PM) -->
        <button
          v-for="slot in SLOTS"
          :key="slot.value"
          type="button"
          class="px-1 py-2.5 text-[10px] font-semibold rounded-md border transition-colors leading-tight"
          :class="isActive(day, slot.value)
            ? 'bg-blue-500/25 border-blue-400/40 text-blue-300'
            : 'bg-white/[0.03] border-white/[0.08] text-gray-500 hover:border-white/[0.15] hover:text-gray-300'
          "
          @click="toggleSlot(day, slot.value)"
        >
          {{ slot.label }}
        </button>

        <!-- OPEN master button -->
        <button
          type="button"
          class="px-1 py-1.5 text-[10px] font-bold rounded-md border transition-colors leading-tight uppercase"
          :class="isOpen(day)
            ? 'bg-emerald-500/25 border-emerald-400/40 text-emerald-300'
            : 'bg-white/[0.03] border-white/[0.08] text-gray-500 hover:border-white/[0.15] hover:text-gray-300'
          "
          @click="toggleOpen(day)"
        >
          Open
        </button>
      </div>
    </div>
    </div>

    <!-- Save button -->
    <div v-if="!readonly" class="flex justify-end pt-1">
      <button
        type="button"
        class="bg-blue-500/25 text-blue-300 hover:bg-blue-500/35 px-4 py-1.5 text-xs font-semibold rounded-md disabled:opacity-50 transition-colors"
        :disabled="saving"
        @click="emit('save')"
      >
        {{ saving ? 'Saving...' : 'Save Availability' }}
      </button>
    </div>
  </div>
</template>
