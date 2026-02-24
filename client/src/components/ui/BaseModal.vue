<script setup lang="ts">
/**
 * BaseModal.vue
 *
 * A reusable slot-based modal wrapper following the app's dark theme.
 * Renders a backdrop overlay and a centered content card with fade + scale
 * transitions. Clicking the backdrop emits `close`.
 *
 * Props:
 *   - open: boolean          — controls visibility
 *   - size?: 'md' | 'lg'     — max-width of the content card (default 'md')
 *
 * Emits:
 *   - close  — fired when the backdrop is clicked
 */
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  open: boolean
  size?: 'md' | 'lg'
}>(), {
  size: 'md',
})

/** Maps the size prop to a Tailwind max-width utility class */
const sizeClass = computed(() => props.size === 'lg' ? 'max-w-lg' : 'max-w-md')

defineEmits<{
  close: []
}>()
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/50"
          @click="$emit('close')"
        />

        <!-- Content card -->
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
          appear
        >
          <div
            class="relative z-[70] w-full rounded-xl bg-gray-950 border border-white/[0.06] shadow-xl overflow-y-auto max-h-[90vh]"
            :class="sizeClass"
          >
            <slot />
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
