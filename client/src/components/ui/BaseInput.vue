<script setup lang="ts">
/**
 * BaseInput -- reusable text input with optional label and error message.
 * Implements v-model via modelValue prop + update:modelValue emit so
 * parent components can bind with `v-model`.
 */

// Props:
// - modelValue:  Current input value, bound via v-model from the parent
// - label:       Optional label text rendered above the input
// - type:        HTML input type attribute (defaults to 'text' in the template)
// - error:       Validation error string; when present, input border turns red
// - placeholder: Placeholder text shown when input is empty
defineProps<{
  modelValue?: string | number
  label?: string
  type?: string
  error?: string
  placeholder?: string
}>()

// Emits:
// - update:modelValue: Fired on every input event with the new string value,
//   enabling two-way binding via v-model in the parent component
defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-400 mb-1">
      {{ label }}
    </label>
    <input
      :type="type || 'text'"
      :value="modelValue"
      :placeholder="placeholder"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      :class="[
        'block w-full rounded-lg border bg-gray-700 px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500',
        error ? 'border-red-300 text-red-400 placeholder-red-300' : 'border-gray-600 text-white placeholder-gray-400',
      ]"
    />
    <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
