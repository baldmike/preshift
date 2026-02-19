<script setup lang="ts">
defineProps<{
  title?: string
}>()
</script>

<template>
  <div class="bg-white rounded-lg shadow p-4">
    <h3 v-if="title" class="text-lg font-semibold text-gray-900 mb-3">{{ title }}</h3>
    <slot />
  </div>
</template>
