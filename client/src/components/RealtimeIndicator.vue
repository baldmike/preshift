<script setup lang="ts">
/**
 * RealtimeIndicator -- small status dot shown in the TopBar that reflects
 * whether the browser has an active WebSocket connection to Laravel Reverb
 * (via Pusher/Echo). Polls the connection state every 3 seconds.
 * Displays a green "Live" label when connected and a gray "Offline" label
 * when disconnected, giving staff immediate feedback on realtime status.
 */
import { ref, onMounted, onUnmounted } from 'vue'

// Reactive flag that drives the dot color and label text in the template
const connected = ref(false)
// Handle for the polling interval so it can be cleared on unmount
let interval: ReturnType<typeof setInterval> | null = null

// Inspects the Pusher connection state exposed on window.Echo
// and updates the reactive `connected` flag accordingly
function checkConnection() {
  if (window.Echo?.connector?.pusher?.connection?.state === 'connected') {
    connected.value = true
  } else {
    connected.value = false
  }
}

// Start polling on mount and run an immediate check
onMounted(() => {
  interval = setInterval(checkConnection, 3000)
  checkConnection()
})

// Clear the polling interval to prevent memory leaks
onUnmounted(() => {
  if (interval) clearInterval(interval)
})
</script>

<template>
  <div class="flex items-center gap-1.5" :title="connected ? 'Connected' : 'Disconnected'">
    <span
      :class="[
        'inline-block w-2 h-2 rounded-full',
        connected ? 'bg-green-400' : 'bg-gray-500',
      ]"
    />
    <span class="text-xs text-gray-400">{{ connected ? 'Live' : 'Offline' }}</span>
  </div>
</template>
