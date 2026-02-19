<script setup lang="ts">
/**
 * AppShell -- the top-level layout wrapper used by every authenticated page.
 * Provides a consistent chrome: sticky TopBar, scrollable main content area,
 * fixed BottomNav for mobile navigation, and a global ToastContainer for
 * flash notifications. Views inject their content via the default <slot />.
 */
import TopBar from './TopBar.vue'
import BottomNav from './BottomNav.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex flex-col">
    <TopBar />
    <!-- pb-16 reserves space so content is never hidden behind the fixed BottomNav -->
    <main class="flex-1 pb-16 px-4 py-4 max-w-4xl mx-auto w-full">
      <slot />
    </main>
    <BottomNav />
    <ToastContainer />
  </div>
</template>
