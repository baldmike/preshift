<script setup lang="ts">
/**
 * ConversationThread.vue
 *
 * Displays the message history for a single DM conversation in a scrollable
 * thread. Messages are aligned left (other person) or right (current user).
 * Auto-scrolls to the bottom on new messages. Includes a MessageComposer
 * at the bottom for sending replies.
 *
 * Props:
 *   - conversationId : number — the active conversation ID
 *
 * The component reads messages from the message store and auto-scrolls
 * whenever the message list changes.
 */
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useMessages } from '@/composables/useMessages'
import MessageComposer from './MessageComposer.vue'

const props = defineProps<{
  conversationId: number
}>()

const { user } = useAuth()
const { directMessages, dmLoading, fetchMessages, sendMessage } = useMessages()

/** Ref to the scrollable message container */
const scrollContainer = ref<HTMLElement | null>(null)

/** Whether a send is in progress */
const sending = ref(false)

/** Scroll to the bottom of the message list */
function scrollToBottom() {
  nextTick(() => {
    if (scrollContainer.value) {
      scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight
    }
  })
}

/** Handle sending a message */
async function handleSend(body: string) {
  sending.value = true
  try {
    await sendMessage(props.conversationId, body)
    scrollToBottom()
  } finally {
    sending.value = false
  }
}

/** Format time for message bubble */
function formatTime(dateStr: string): string {
  const d = new Date(dateStr)
  return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
}

// Fetch messages when the conversation changes
watch(() => props.conversationId, async (id) => {
  if (id) {
    await fetchMessages(id)
    scrollToBottom()
  }
}, { immediate: true })

// Auto-scroll when new messages arrive
watch(directMessages, () => {
  scrollToBottom()
}, { deep: true })
</script>

<template>
  <div class="flex flex-col h-full">
    <!-- Scrollable message area -->
    <div
      ref="scrollContainer"
      class="flex-1 overflow-y-auto px-3 py-4 space-y-3"
    >
      <!-- Loading state -->
      <div v-if="dmLoading" class="text-center py-8">
        <p class="text-gray-500 text-sm">Loading messages...</p>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="directMessages.length === 0"
        class="text-center py-8"
      >
        <p class="text-gray-500 text-sm">No messages yet. Say hello!</p>
      </div>

      <!-- Messages -->
      <template v-else>
        <div
          v-for="msg in directMessages"
          :key="msg.id"
          class="flex"
          :class="msg.sender_id === user?.id ? 'justify-end' : 'justify-start'"
        >
          <div
            class="max-w-[75%] rounded-lg px-3 py-2 space-y-1"
            :class="msg.sender_id === user?.id
              ? 'bg-amber-500/20 text-amber-100'
              : 'bg-gray-700/50 text-gray-300'"
          >
            <p class="text-sm whitespace-pre-wrap">{{ msg.body }}</p>
            <p class="text-[10px] opacity-60 text-right">{{ formatTime(msg.created_at) }}</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Compose area -->
    <div class="border-t border-gray-700/50 p-3">
      <MessageComposer
        placeholder="Type a message..."
        :loading="sending"
        @submit="handleSend"
      />
    </div>
  </div>
</template>
