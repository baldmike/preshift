<script setup lang="ts">
/**
 * AcknowledgeButton.vue
 *
 * A button that lets staff acknowledge (mark as seen) a pre-shift item such
 * as an 86'd item, special, push item, or announcement. Shows a "HEARD"
 * button in its unacknowledged state and a green checkmark once confirmed.
 * Handles the API call with a loading spinner and emits a toast on failure.
 *
 * Props:
 *   - type: string           -- acknowledgment category (e.g. 'eighty_sixed')
 *   - id: number             -- ID of the item being acknowledged
 *   - acknowledged: boolean  -- whether the item is already acknowledged
 *   - size?: 'sm' | 'md'     -- controls button dimensions and label visibility
 */
import { ref } from 'vue'
import { useAcknowledgments } from '@/composables/useAcknowledgments'

const props = defineProps<{
  type: string
  id: number
  acknowledged: boolean
  size?: 'sm' | 'md'
}>()

const { acknowledge } = useAcknowledgments()
const loading = ref(false)

async function handleAcknowledge() {
  loading.value = true
  try {
    await acknowledge(props.type, props.id)
  } catch {
    window.dispatchEvent(
      new CustomEvent('toast', { detail: { message: 'Failed to acknowledge', type: 'error' } })
    )
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="shrink-0">
    <!-- Acknowledged state -->
    <span
      v-if="acknowledged"
      class="inline-flex items-center text-emerald-400"
      :class="size === 'sm' ? 'gap-0.5' : 'gap-1 text-sm font-medium'"
    >
      <svg :class="size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4'" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clip-rule="evenodd" />
      </svg>
      <span v-if="size !== 'sm'" class="text-xs">Done</span>
    </span>

    <!-- Acknowledge button -->
    <button
      v-else
      @click="handleAcknowledge"
      :disabled="loading"
      :class="[
        'inline-flex items-center gap-1 rounded-md font-medium transition-colors disabled:opacity-50',
        size === 'sm'
          ? 'bg-white/5 border border-white/10 px-2 py-1 text-[10px] text-gray-400 hover:bg-white/10 hover:text-white'
          : 'bg-indigo-50 px-3 py-1.5 text-sm text-indigo-700 hover:bg-indigo-100',
      ]"
    >
      <svg v-if="loading" class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      HEARD
    </button>
  </div>
</template>
