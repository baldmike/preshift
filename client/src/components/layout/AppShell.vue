<script setup lang="ts">
/**
 * AppShell.vue
 *
 * Top-level layout wrapper used by every page in the app.
 * Provides consistent structure: TopBar, scrollable main content area,
 * copyright footer, BottomNav, and a global ToastContainer for notifications.
 */
import TopBar from './TopBar.vue'
import BottomNav from './BottomNav.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'
</script>

<template>
  <div class="min-h-screen bg-gray-950 flex flex-col">
    <TopBar />
    <main class="flex-1 pb-24 px-4 py-4 max-w-4xl mx-auto w-full">
      <slot />
    </main>
    <!-- Copyright moved into BottomNav for persistent visibility -->
    <BottomNav />
    <ToastContainer />
  </div>
</template>
