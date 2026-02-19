<script setup lang="ts">
/**
 * BaseButton -- reusable button primitive used throughout the app.
 * Supports three visual variants, three sizes, and a loading state
 * that disables the button and shows a spinner animation.
 */

// Props:
// - variant: Visual style -- 'primary' (indigo, default), 'secondary' (gray), or 'danger' (red)
// - size:    Button sizing -- 'sm', 'md' (default), or 'lg'
// - loading: When true, disables the button and prepends an animated spinner SVG
defineProps<{
  variant?: 'primary' | 'secondary' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  loading?: boolean
}>()
</script>

<template>
  <button
    :disabled="loading"
    :class="[
      'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
      {
        'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500': variant === 'primary' || !variant,
        'bg-gray-200 text-gray-800 hover:bg-gray-300 focus:ring-gray-400': variant === 'secondary',
        'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500': variant === 'danger',
        'px-2.5 py-1.5 text-xs': size === 'sm',
        'px-4 py-2 text-sm': size === 'md' || !size,
        'px-6 py-3 text-base': size === 'lg',
      },
    ]"
  >
    <svg
      v-if="loading"
      class="animate-spin -ml-1 mr-2 h-4 w-4"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <slot />
  </button>
</template>
