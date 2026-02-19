<script setup lang="ts">
/**
 * BadgePill -- small colored pill/badge used to display status labels,
 * priority levels, roles, and category tags throughout the app.
 * Defaults to gray when no color prop is supplied.
 */

// Props:
// - color: Background/text color theme -- 'green', 'yellow', 'red', 'blue', or 'gray' (default)
// - label: The text displayed inside the pill (required)
defineProps<{
  color?: 'green' | 'yellow' | 'red' | 'blue' | 'gray'
  label: string
}>()
</script>

<template>
  <span
    :class="[
      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
      {
        'bg-green-100 text-green-800': color === 'green',
        'bg-yellow-100 text-yellow-800': color === 'yellow',
        'bg-red-100 text-red-800': color === 'red',
        'bg-blue-100 text-blue-800': color === 'blue',
        'bg-gray-100 text-gray-800': color === 'gray' || !color,
      },
    ]"
  >
    {{ label }}
  </span>
</template>
