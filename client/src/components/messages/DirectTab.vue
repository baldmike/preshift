<script setup lang="ts">
/**
 * DirectTab.vue
 *
 * The "Direct" tab content for the Messages view. On desktop, shows a
 * conversation list on the left and the active conversation thread on the
 * right. On mobile, shows either the list or the thread with back navigation.
 *
 * When `initialUserId` is provided (from the EmployeeProfileModal "Message"
 * button), auto-finds or creates a conversation with that user on mount.
 *
 * Listens on the user's private channel for incoming direct messages.
 *
 * Props:
 *   - initialUserId : number | null — auto-open/create conversation with this user
 */
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useMessages } from '@/composables/useMessages'
import { useMessageStore } from '@/stores/messages'
import { useUserChannel } from '@/composables/useReverb'
import type { DirectMessage } from '@/types'
import ConversationListItem from './ConversationListItem.vue'
import ConversationThread from './ConversationThread.vue'

const props = withDefaults(defineProps<{
  initialUserId?: number | null
}>(), {
  initialUserId: null,
})

const { user, locationId } = useAuth()
const store = useMessageStore()
const {
  conversations,
  activeConversationId,
  dmLoading,
  fetchConversations,
  findOrCreateConversation,
  pushDirectMessage,
  fetchUnreadCount,
} = useMessages()

/** On mobile, whether we're viewing the thread (vs the list) */
const showThread = ref(false)

/** Select a conversation and open the thread */
function selectConversation(id: number) {
  store.activeConversationId = id
  showThread.value = true
}

/** Go back to the conversation list (mobile) */
function goBack() {
  showThread.value = false
}

/** Toast helper */
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

// ── Realtime ──
let channel: ReturnType<typeof useUserChannel> | null = null

onMounted(async () => {
  await fetchConversations()

  // If initialUserId is provided, auto-open the conversation
  if (props.initialUserId) {
    try {
      const conversation = await findOrCreateConversation(props.initialUserId)
      selectConversation(conversation.id)
    } catch {
      toast('Could not open conversation', 'error')
    }
  }

  // Subscribe to the user's private channel for DM events
  if (user.value && locationId.value) {
    channel = useUserChannel(user.value.id, locationId.value)
    channel.listen('.direct-message.sent', (e: DirectMessage) => {
      pushDirectMessage(e)
    })
  }
})

onUnmounted(() => {
  if (channel) {
    channel.stopListening('.direct-message.sent')
  }
})
</script>

<template>
  <div class="flex h-full min-h-[400px]">
    <!-- Conversation list (always visible on desktop, toggleable on mobile) -->
    <div
      class="border-r border-gray-700/50 overflow-y-auto"
      :class="showThread ? 'hidden md:block md:w-1/3' : 'w-full md:w-1/3'"
    >
      <!-- Loading state -->
      <div v-if="dmLoading && conversations.length === 0" class="p-4 text-center">
        <p class="text-gray-500 text-sm">Loading conversations...</p>
      </div>

      <!-- Empty state -->
      <div v-else-if="conversations.length === 0" class="p-4 text-center">
        <p class="text-gray-500 text-sm">No conversations yet.</p>
        <p class="text-gray-600 text-xs mt-1">Start one from a team member's profile.</p>
      </div>

      <!-- Conversation list -->
      <ConversationListItem
        v-for="conv in conversations"
        :key="conv.id"
        :conversation="conv"
        :active="activeConversationId === conv.id"
        @select="selectConversation"
      />
    </div>

    <!-- Conversation thread (always visible on desktop, toggleable on mobile) -->
    <div
      class="flex-1"
      :class="showThread ? 'block' : 'hidden md:block'"
    >
      <!-- Back button (mobile only) -->
      <div v-if="showThread" class="md:hidden border-b border-gray-700/50 px-3 py-2">
        <button
          type="button"
          class="text-xs text-gray-400 hover:text-white flex items-center gap-1"
          @click="goBack"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Back
        </button>
      </div>

      <!-- Active thread or placeholder -->
      <ConversationThread
        v-if="activeConversationId"
        :conversation-id="activeConversationId"
      />
      <div v-else class="flex items-center justify-center h-full">
        <p class="text-gray-500 text-sm">Select a conversation to start messaging.</p>
      </div>
    </div>
  </div>
</template>
