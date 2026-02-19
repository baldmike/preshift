<script setup lang="ts">
import { ref } from 'vue'
import { useAcknowledgments } from '@/composables/useAcknowledgments'

const props = defineProps<{
  type: string
  id: number
  acknowledged: boolean
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
  <div>
    <span v-if="acknowledged" class="inline-flex items-center gap-1 text-green-600 text-sm font-medium">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd"
          d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
          clip-rule="evenodd" />
      </svg>
      Acknowledged
    </span>
    <button
      v-else
      @click="handleAcknowledge"
      :disabled="loading"
      class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-3 py-1.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition-colors disabled:opacity-50"
    >
      <svg v-if="loading" class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      Acknowledge
    </button>
  </div>
</template>
