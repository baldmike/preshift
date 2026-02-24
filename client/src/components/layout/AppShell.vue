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
import { onMounted, watch } from 'vue'
import TopBar from './TopBar.vue'
import BottomNav from './BottomNav.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'
import AppTour from '@/components/AppTour.vue'
import { useAuth } from '@/composables/useAuth'
import { useReverb, disconnectReverb } from '@/composables/useReverb'
import { useOnboarding } from '@/composables/useOnboarding'

const { user, locationId } = useAuth()
const { checkAndStart } = useOnboarding()

onMounted(() => {
  if (user.value?.location_id) {
    useReverb(user.value.location_id)
  }

  /* Delay tour check to let the page finish rendering */
  setTimeout(() => checkAndStart(), 1000)
})

/**
 * Re-initialize the Reverb WebSocket connection when the user's active
 * location changes (e.g. after switching establishments). Disconnects
 * the old channel and subscribes to the new location's private channel.
 */
watch(locationId, (newId, oldId) => {
  if (newId && newId !== oldId) {
    disconnectReverb()
    useReverb(newId)
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
    <AppTour />
  </div>
</template>
