<script setup lang="ts">
defineProps<{
  color?: 'green' | 'yellow' | 'red' | 'blue' | 'gray'
  label: string
}>()
</script>

<template>
  <span
    :class="[
      'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
      {
        'bg-green-500/15 text-green-400': color === 'green',
        'bg-yellow-500/15 text-yellow-400': color === 'yellow',
        'bg-red-500/15 text-red-400': color === 'red',
        'bg-blue-500/15 text-blue-400': color === 'blue',
        'bg-gray-500/15 text-gray-400': color === 'gray' || !color,
      },
    ]"
  >
    {{ label }}
  </span>
</template>
