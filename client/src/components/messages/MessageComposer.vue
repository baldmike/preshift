<script setup lang="ts">
/**
 * MessageComposer.vue
 *
 * Reusable text input with a send button for composing board posts, replies,
 * and direct messages. Shows a character count when approaching the limit.
 *
 * Props:
 *   - placeholder : Input placeholder text
 *   - maxLength   : Maximum character count (default 2000)
 *   - loading     : Disables input and button while submitting
 *
 * Emits:
 *   - submit(body) : Fired when the user clicks Send or presses Enter
 */
import { ref, computed } from 'vue'

const props = withDefaults(defineProps<{
  placeholder?: string
  maxLength?: number
  loading?: boolean
}>(), {
  placeholder: 'Write a message...',
  maxLength: 2000,
  loading: false,
})

const emit = defineEmits<{
  submit: [body: string]
}>()

/** The current text input value */
const body = ref('')

/** Whether the input has content and is not over the limit */
const canSend = computed(() =>
  body.value.trim().length > 0 && body.value.length <= props.maxLength && !props.loading
)

/** Whether to show the character count (within 200 of the limit) */
const showCount = computed(() => body.value.length >= props.maxLength - 200)

/** Handle form submission */
function handleSubmit() {
  if (!canSend.value) return
  emit('submit', body.value.trim())
  body.value = ''
}

/** Handle Enter key (submit on Enter, newline on Shift+Enter) */
function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    handleSubmit()
  }
}
</script>

<template>
  <div class="space-y-1">
    <div class="flex gap-2">
      <textarea
        v-model="body"
        :placeholder="placeholder"
        :maxlength="maxLength"
        :disabled="loading"
        rows="2"
        class="flex-1 bg-gray-800 text-white text-sm rounded-md border border-gray-700 px-3 py-2 placeholder-gray-500 focus:outline-none focus:border-amber-500 resize-none disabled:opacity-50"
        data-testid="composer-input"
        @keydown="handleKeydown"
      />
      <button
        type="button"
        :disabled="!canSend"
        class="self-end px-4 py-2 text-xs font-semibold rounded-md bg-amber-500 text-gray-900 hover:bg-amber-400 transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
        data-testid="composer-send"
        @click="handleSubmit"
      >
        Send
      </button>
    </div>
    <p
      v-if="showCount"
      class="text-xs text-right"
      :class="body.length > maxLength ? 'text-red-400' : 'text-gray-500'"
    >
      {{ body.length }} / {{ maxLength }}
    </p>
  </div>
</template>
