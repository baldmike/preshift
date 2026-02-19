<script setup lang="ts">
/**
 * BaseCard -- simple card container with an optional title heading.
 * Provides a white background, rounded corners, and shadow.
 * Card body content is injected via the default <slot />.
 */

// Props:
// - title: Optional heading rendered as an <h3> above the slot content
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
