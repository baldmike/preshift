<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const connected = ref(false)
let interval: ReturnType<typeof setInterval> | null = null

function checkConnection() {
  // Check if Pusher/Echo is connected via window.Echo
  if (window.Echo?.connector?.pusher?.connection?.state === 'connected') {
    connected.value = true
  } else {
    connected.value = false
  }
}

onMounted(() => {
  interval = setInterval(checkConnection, 3000)
  checkConnection()
})

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
