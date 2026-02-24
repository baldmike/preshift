<script setup lang="ts">
/**
 * AppShell.vue
 *
 * Top-level layout wrapper used by every page in the app.
 * Provides consistent structure: TopBar, scrollable main content area,
 * copyright footer, BottomNav, and a global ToastContainer for notifications.
 *
 * Initializes the Echo/Reverb WebSocket connection on mount so the
 * RealtimeIndicator in the TopBar can reflect connection state immediately.
 */
import { onMounted } from 'vue'
import TopBar from './TopBar.vue'
import BottomNav from './BottomNav.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'
import { useAuth } from '@/composables/useAuth'
import { useReverb } from '@/composables/useReverb'

const { user } = useAuth()

onMounted(() => {
  if (user.value?.location_id) {
    useReverb(user.value.location_id)
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-950 flex flex-col overflow-x-hidden">
    <TopBar />
    <main class="flex-1 pb-24 px-3 sm:px-4 py-4 max-w-4xl mx-auto w-full">
      <slot />
    </main>
    <!-- Copyright moved into BottomNav for persistent visibility -->
    <BottomNav />
    <ToastContainer />
  </div>
</template>
