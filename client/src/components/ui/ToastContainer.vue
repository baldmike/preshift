<script setup lang="ts">
/**
 * ToastContainer -- global notification system rendered once inside AppShell.
 * Listens for custom 'toast' events dispatched on `window` from anywhere in
 * the application. Toasts auto-dismiss after 4 seconds and can also be
 * clicked to dismiss early. Uses Vue's <TransitionGroup> for slide-in/out
 * animations.
 *
 * Usage from any component:
 *   window.dispatchEvent(new CustomEvent('toast', {
 *     detail: { message: 'Saved!', type: 'success' }
 *   }))
 */
import { ref, onMounted, onUnmounted } from 'vue'

// Shape of a single toast notification
interface Toast {
  id: number
  message: string
  type: 'success' | 'error' | 'info'
}

// Reactive list of currently visible toasts
const toasts = ref<Toast[]>([])
// Auto-incrementing ID to uniquely key each toast for TransitionGroup
let nextId = 0

// Adds a toast to the stack and schedules its removal after 4 seconds
function addToast(message: string, type: Toast['type'] = 'info') {
  const id = nextId++
  toasts.value.push({ id, message, type })
  setTimeout(() => removeToast(id), 4000)
}

// Removes a toast by filtering it out of the reactive array
function removeToast(id: number) {
  toasts.value = toasts.value.filter((t) => t.id !== id)
}

// Handles the global custom 'toast' event dispatched on window.
// This allows any component (even non-Vue code) to trigger a toast
// without needing a direct reference to this component.
function handleToast(e: Event) {
  const detail = (e as CustomEvent).detail
  addToast(detail.message, detail.type || 'info')
}

// Register/unregister the global event listener on mount/unmount
onMounted(() => {
  window.addEventListener('toast', handleToast)
})

onUnmounted(() => {
  window.removeEventListener('toast', handleToast)
})

// Expose addToast so parent components with a template ref can call it directly
defineExpose({ addToast })
</script>

<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col gap-2">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'rounded-lg px-4 py-3 text-sm font-medium shadow-lg max-w-sm',
          {
            'bg-green-600 text-white': toast.type === 'success',
            'bg-red-600 text-white': toast.type === 'error',
            'bg-gray-800 text-white': toast.type === 'info',
          },
        ]"
        @click="removeToast(toast.id)"
      >
        {{ toast.message }}
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active {
  transition: all 0.3s ease-out;
}
.toast-leave-active {
  transition: all 0.2s ease-in;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(30px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
