<script setup lang="ts">
/**
 * ConversationListItem.vue
 *
 * Renders a single row in the conversation list sidebar for the Direct tab.
 * Shows the other participant's name and role badge, a truncated preview of
 * the last message, a relative timestamp, and an unread indicator dot.
 *
 * Props:
 *   - conversation : Conversation — the conversation to display
 *   - active       : boolean — whether this conversation is currently selected
 *
 * Emits:
 *   - select(id) : Fired when the conversation row is clicked
 */
import { computed } from 'vue'
import type { Conversation } from '@/types'
import { useAuth } from '@/composables/useAuth'

const props = defineProps<{
  conversation: Conversation
  active?: boolean
}>()

const emit = defineEmits<{
  select: [id: number]
}>()

const { user } = useAuth()

/** The other participant (not the current user) */
const otherParticipant = computed(() => {
  if (!user.value) return null
  return props.conversation.participants.find((p) => p.id !== user.value!.id) ?? null
})

/** Truncated last message preview */
const preview = computed(() => {
  const msg = props.conversation.latest_message
  if (!msg) return 'No messages yet'
  const maxLen = 40
  return msg.body.length > maxLen ? msg.body.substring(0, maxLen) + '...' : msg.body
})

/** Relative time for the last message */
function relativeTime(dateStr: string | undefined): string {
  if (!dateStr) return ''
  const now = Date.now()
  const then = new Date(dateStr).getTime()
  const diffMs = now - then
  const diffMins = Math.floor(diffMs / 60000)

  if (diffMins < 1) return 'now'
  if (diffMins < 60) return `${diffMins}m`
  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `${diffHours}h`
  const diffDays = Math.floor(diffHours / 24)
  return `${diffDays}d`
}
</script>

<template>
  <button
    type="button"
    class="w-full text-left px-3 py-3 flex items-center gap-3 transition-colors rounded-md"
    :class="active ? 'bg-gray-700/50' : 'hover:bg-gray-800/50'"
    @click="emit('select', conversation.id)"
  >
    <!-- Avatar initial -->
    <div class="w-9 h-9 rounded-full bg-gray-700 flex items-center justify-center text-sm font-bold text-gray-300 shrink-0 relative">
      {{ otherParticipant ? otherParticipant.name.charAt(0).toUpperCase() : '?' }}
      <!-- Unread dot -->
      <div
        v-if="conversation.unread_count > 0"
        class="absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full bg-amber-500 border-2 border-gray-900"
      />
    </div>

    <div class="flex-1 min-w-0">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-1.5 min-w-0">
          <span class="text-sm font-semibold text-white truncate">
            {{ otherParticipant?.name ?? 'Unknown' }}
          </span>
          <span
            v-if="otherParticipant?.role"
            class="text-[9px] font-medium px-1 py-0.5 rounded-full shrink-0"
            :class="{
              'bg-red-500/20 text-red-300': otherParticipant.role === 'admin',
              'bg-yellow-500/20 text-yellow-300': otherParticipant.role === 'manager',
              'bg-blue-500/20 text-blue-300': otherParticipant.role === 'server',
              'bg-green-500/20 text-green-300': otherParticipant.role === 'bartender',
            }"
          >
            {{ otherParticipant.role }}
          </span>
        </div>
        <span class="text-[10px] text-gray-500 shrink-0">
          {{ relativeTime(conversation.latest_message?.created_at) }}
        </span>
      </div>
      <p class="text-xs text-gray-500 truncate mt-0.5">{{ preview }}</p>
    </div>
  </button>
</template>
