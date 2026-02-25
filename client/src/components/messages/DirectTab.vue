<script setup lang="ts">
/**
 * DirectTab.vue
 *
 * The "Direct" tab content for the Messages view. On desktop, shows a
 * conversation list on the left and the active conversation thread on the
 * right. On mobile, shows either the list or the thread with back navigation.
 *
 * Includes a "New Message" button that opens a user picker populated from
 * GET /api/users. Selecting a recipient calls findOrCreateConversation and
 * opens the thread immediately.
 *
 * When `initialUserId` is provided (from the EmployeeProfileModal "Message"
 * button), auto-finds or creates a conversation with that user on mount.
 *
 * Listens on the user's private channel for incoming direct messages.
 *
 * Props:
 *   - initialUserId : number | null — auto-open/create conversation with this user
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/composables/useApi'
import { useAuth } from '@/composables/useAuth'
import { useMessages } from '@/composables/useMessages'
import { useMessageStore } from '@/stores/messages'
import { useUserChannel } from '@/composables/useReverb'
import type { DirectMessage, User } from '@/types'
import ConversationListItem from './ConversationListItem.vue'
import ConversationThread from './ConversationThread.vue'
import UserAvatar from '@/components/ui/UserAvatar.vue'

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

// ── New message user picker ──

/** Controls visibility of the user picker panel */
const showUserPicker = ref(false)
/** All users at this location (loaded on demand) */
const locationUsers = ref<User[]>([])
/** True while user list is loading */
const loadingUsers = ref(false)
/** True while a conversation is being created from the picker */
const creatingConversation = ref(false)
/** Search filter for the user picker */
const userSearch = ref('')

/** Role sort order for the user picker list */
const roleOrder: Record<string, number> = { admin: 0, manager: 1, bartender: 2, server: 3 }

/**
 * Users available for new conversations: excludes the current user,
 * filtered by the search input, sorted by role then name.
 */
const filteredUsers = computed(() => {
  if (!user.value) return []
  const search = userSearch.value.toLowerCase()
  return locationUsers.value
    .filter(u => u.id !== user.value!.id)
    .filter(u => !search || u.name.toLowerCase().includes(search))
    .sort((a, b) =>
      (roleOrder[a.role] ?? 99) - (roleOrder[b.role] ?? 99) || a.name.localeCompare(b.name)
    )
})

/**
 * Opens the user picker and fetches the location's user list.
 * The list is fetched each time to stay current with staff changes.
 */
async function openUserPicker() {
  showUserPicker.value = true
  userSearch.value = ''
  loadingUsers.value = true
  try {
    const { data } = await api.get<User[]>('/api/users')
    locationUsers.value = data
  } catch {
    toast('Failed to load users', 'error')
  } finally {
    loadingUsers.value = false
  }
}

/**
 * Handles selecting a user from the picker. Finds or creates a conversation
 * with the selected user, closes the picker, and opens the thread.
 */
async function selectRecipient(userId: number) {
  creatingConversation.value = true
  try {
    const conversation = await findOrCreateConversation(userId)
    showUserPicker.value = false
    selectConversation(conversation.id)
  } catch {
    toast('Could not open conversation', 'error')
  } finally {
    creatingConversation.value = false
  }
}

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
      <!-- New Message button -->
      <div class="px-3 py-2.5 border-b border-gray-700/50">
        <button
          type="button"
          class="w-full flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 transition-colors"
          @click="openUserPicker"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          New Message
        </button>
      </div>

      <!-- User picker panel (replaces conversation list when open) -->
      <div v-if="showUserPicker" class="flex flex-col">
        <!-- Picker header with search and close -->
        <div class="px-3 py-2 border-b border-gray-700/50 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Choose Recipient</span>
            <button
              type="button"
              class="text-gray-500 hover:text-gray-300 transition-colors"
              @click="showUserPicker = false"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <input
            v-model="userSearch"
            type="text"
            placeholder="Search by name..."
            class="w-full px-2.5 py-1.5 text-sm text-gray-200 bg-white/5 border border-white/10 rounded-md outline-none focus:border-white/25 placeholder-gray-600"
          />
        </div>

        <!-- Loading state -->
        <div v-if="loadingUsers" class="p-4 text-center">
          <p class="text-gray-500 text-sm">Loading team...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredUsers.length === 0" class="p-4 text-center">
          <p class="text-gray-500 text-sm">No users found.</p>
        </div>

        <!-- User list -->
        <button
          v-else
          v-for="u in filteredUsers"
          :key="u.id"
          type="button"
          :disabled="creatingConversation"
          class="w-full text-left px-3 py-2.5 flex items-center gap-3 hover:bg-gray-800/50 transition-colors disabled:opacity-50"
          @click="selectRecipient(u.id)"
        >
          <!-- Avatar -->
          <UserAvatar :user="u" size="sm" />
          <div class="flex items-center gap-1.5 min-w-0">
            <span class="text-sm font-medium text-white truncate">{{ u.name }}</span>
            <span
              class="text-[9px] font-medium px-1 py-0.5 rounded-full shrink-0"
              :class="{
                'bg-red-500/20 text-red-300': u.role === 'admin',
                'bg-yellow-500/20 text-yellow-300': u.role === 'manager',
                'bg-blue-500/20 text-blue-300': u.role === 'server',
                'bg-green-500/20 text-green-300': u.role === 'bartender',
              }"
            >
              {{ u.role }}
            </span>
          </div>
        </button>
      </div>

      <!-- Conversation list (hidden when user picker is open) -->
      <template v-else>
        <!-- Loading state -->
        <div v-if="dmLoading && conversations.length === 0" class="p-4 text-center">
          <p class="text-gray-500 text-sm">Loading conversations...</p>
        </div>

        <!-- Empty state -->
        <div v-else-if="conversations.length === 0" class="p-4 text-center">
          <p class="text-gray-500 text-sm">No conversations yet.</p>
          <p class="text-gray-600 text-xs mt-1">Tap "New Message" to start one.</p>
        </div>

        <!-- Conversation list -->
        <ConversationListItem
          v-for="conv in conversations"
          :key="conv.id"
          :conversation="conv"
          :active="activeConversationId === conv.id"
          @select="selectConversation"
        />
      </template>
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
