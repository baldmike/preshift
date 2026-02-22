<script setup lang="ts">
/**
 * BaseModal.vue
 *
 * A reusable slot-based modal wrapper following the app's dark theme.
 * Renders a backdrop overlay and a centered content card with fade + scale
 * transitions. Clicking the backdrop emits `close`.
 *
 * Props:
 *   - open: boolean  — controls visibility
 *
 * Emits:
 *   - close  — fired when the backdrop is clicked
 */
defineProps<{
  open: boolean
}>()

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
      <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center p-4">
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
            class="relative z-50 w-full max-w-md rounded-xl bg-gray-950 border border-white/[0.06] shadow-xl overflow-y-auto max-h-[90vh]"
          >
            <slot />
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
