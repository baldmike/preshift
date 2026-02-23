<script setup lang="ts">
/**
 * BoardTab.vue
 *
 * The "Board" tab content for the Messages view. Displays a compose area at
 * the top, followed by pinned posts (highlighted) and then regular posts in
 * reverse chronological order. Managers see a visibility toggle when composing.
 * Listens on the location channel for real-time board-message events.
 *
 * Uses the message store for state and the useReverb composable for WebSocket
 * subscriptions. Cleans up listeners on unmount.
 */
import { ref, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useMessages } from '@/composables/useMessages'
import { useLocationChannel } from '@/composables/useReverb'
import type { BoardMessage } from '@/types'
import MessageComposer from './MessageComposer.vue'
import BoardPostCard from './BoardPostCard.vue'

const { user, isAdmin, isManager, locationId } = useAuth()
const {
  pinnedPosts,
  regularPosts,
  boardLoading,
  fetchBoardMessages,
  createBoardMessage,
  updateBoardMessage,
  deleteBoardMessage,
  togglePin,
  addBoardMessage,
  updateBoardMessageLocal,
  removeBoardMessage,
} = useMessages()

/** Visibility toggle for the compose area (managers only) */
const composeVisibility = ref<'all' | 'managers'>('all')

/** Whether a compose operation is in progress */
const composing = ref(false)

/** Toast helper */
function toast(message: string, type: string) {
  window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
}

/** Handle creating a new top-level post */
async function handlePost(body: string) {
  composing.value = true
  try {
    const payload: { body: string; visibility?: 'all' | 'managers' } = { body }
    if ((isAdmin.value || isManager.value) && composeVisibility.value === 'managers') {
      payload.visibility = 'managers'
    }
    await createBoardMessage(payload)
    composeVisibility.value = 'all'
  } catch {
    toast('Failed to create post', 'error')
  } finally {
    composing.value = false
  }
}

/** Handle creating a reply */
async function handleReply(parentId: number, body: string) {
  try {
    await createBoardMessage({ body, parent_id: parentId })
  } catch {
    toast('Failed to post reply', 'error')
  }
}

/** Handle editing a post */
async function handleEdit(id: number, body: string) {
  try {
    await updateBoardMessage(id, { body })
  } catch {
    toast('Failed to update post', 'error')
  }
}

/** Handle deleting a post */
async function handleDelete(id: number) {
  try {
    await deleteBoardMessage(id)
  } catch {
    toast('Failed to delete post', 'error')
  }
}

/** Handle toggling pin */
async function handlePin(id: number) {
  try {
    await togglePin(id)
  } catch {
    toast('Failed to toggle pin', 'error')
  }
}

// ── Realtime ──
let channel: ReturnType<typeof useLocationChannel> | null = null

onMounted(() => {
  fetchBoardMessages()

  if (locationId.value) {
    channel = useLocationChannel(locationId.value)
    channel
      .listen('.board-message.posted', (e: BoardMessage) => {
        addBoardMessage(e)
      })
      .listen('.board-message.updated', (e: BoardMessage) => {
        updateBoardMessageLocal(e)
      })
      .listen('.board-message.deleted', (e: { id: number; parent_id: number | null }) => {
        removeBoardMessage(e)
      })
  }
})

onUnmounted(() => {
  if (channel) {
    channel.stopListening('.board-message.posted')
    channel.stopListening('.board-message.updated')
    channel.stopListening('.board-message.deleted')
  }
})
</script>

<template>
  <div class="space-y-4">
    <!-- Compose area -->
    <div class="space-y-2">
      <MessageComposer
        placeholder="Share something with the team..."
        :loading="composing"
        @submit="handlePost"
      />
      <!-- Visibility toggle for managers -->
      <div v-if="isAdmin || isManager" class="flex items-center gap-2">
        <label class="flex items-center gap-1.5 text-xs text-gray-400 cursor-pointer">
          <input
            v-model="composeVisibility"
            type="checkbox"
            true-value="managers"
            false-value="all"
            class="w-3.5 h-3.5 rounded border-gray-600 bg-gray-800 text-amber-500 focus:ring-amber-500 focus:ring-offset-0"
          />
          Managers only
        </label>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="boardLoading" class="text-center py-8">
      <p class="text-gray-500 text-sm">Loading board...</p>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="pinnedPosts.length === 0 && regularPosts.length === 0"
      class="text-center py-8"
    >
      <p class="text-gray-500 text-sm">No posts yet. Be the first to share!</p>
    </div>

    <!-- Posts -->
    <template v-else>
      <!-- Pinned posts -->
      <BoardPostCard
        v-for="post in pinnedPosts"
        :key="post.id"
        :message="post"
        @reply="handleReply"
        @edit="handleEdit"
        @delete="handleDelete"
        @pin="handlePin"
      />

      <!-- Regular posts -->
      <BoardPostCard
        v-for="post in regularPosts"
        :key="post.id"
        :message="post"
        @reply="handleReply"
        @edit="handleEdit"
        @delete="handleDelete"
        @pin="handlePin"
      />
    </template>
  </div>
</template>
