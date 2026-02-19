<script setup lang="ts">
import TopBar from './TopBar.vue'
import BottomNav from './BottomNav.vue'
import ToastContainer from '@/components/ui/ToastContainer.vue'
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex flex-col">
    <TopBar />
    <main class="flex-1 pb-16 px-4 py-4 max-w-4xl mx-auto w-full">
      <slot />
    </main>
    <BottomNav />
    <ToastContainer />
  </div>
</template>
