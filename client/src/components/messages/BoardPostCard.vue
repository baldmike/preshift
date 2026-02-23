<script setup lang="ts">
/**
 * BoardPostCard.vue
 *
 * Renders a single board post with author info, body text, relative timestamp,
 * pin indicator, managers-only badge, and expandable threaded replies.
 * Shows edit/delete actions for the post author or admin/manager users.
 * Includes an inline reply composer when the replies section is expanded.
 *
 * Props:
 *   - message : BoardMessage — the board post to display
 *
 * Emits:
 *   - reply(parentId, body) : Fired when a reply is submitted
 *   - edit(id, body)        : Fired when an edit is saved
 *   - delete(id)            : Fired when delete is confirmed
 *   - pin(id)               : Fired when pin/unpin is clicked
 */
import { ref, computed } from 'vue'
import type { BoardMessage } from '@/types'
import { useAuth } from '@/composables/useAuth'
import MessageComposer from './MessageComposer.vue'

const props = defineProps<{
  message: BoardMessage
}>()

const emit = defineEmits<{
  reply: [parentId: number, body: string]
  edit: [id: number, body: string]
  delete: [id: number]
  pin: [id: number]
}>()

const { user, isAdmin, isManager } = useAuth()

/** Whether replies are expanded */
const showReplies = ref(false)

/** Whether editing mode is active */
const editing = ref(false)

/** The edit text buffer */
const editBody = ref('')

/** Whether this user can edit/delete this post */
const canModify = computed(() => {
  if (!user.value) return false
  if (isAdmin.value || isManager.value) return true
  return user.value.id === props.message.user_id
})

/** Whether this user can pin posts */
const canPin = computed(() => isAdmin.value || isManager.value)

/** Start editing the post */
function startEdit() {
  editBody.value = props.message.body
  editing.value = true
}

/** Save the edit */
function saveEdit() {
  if (!editBody.value.trim()) return
  emit('edit', props.message.id, editBody.value.trim())
  editing.value = false
}

/** Cancel editing */
function cancelEdit() {
  editing.value = false
  editBody.value = ''
}

/** Format a relative timestamp (e.g. "2h ago", "just now") */
function relativeTime(dateStr: string): string {
  const now = Date.now()
  const then = new Date(dateStr).getTime()
  const diffMs = now - then
  const diffMins = Math.floor(diffMs / 60000)

  if (diffMins < 1) return 'just now'
  if (diffMins < 60) return `${diffMins}m ago`
  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `${diffHours}h ago`
  const diffDays = Math.floor(diffHours / 24)
  return `${diffDays}d ago`
}

/** Get the first letter of a name for the avatar */
function initial(name: string): string {
  return name.charAt(0).toUpperCase()
}
</script>

<template>
  <div
    class="rounded-lg border p-4 space-y-3"
    :class="message.pinned ? 'border-amber-500/40 bg-amber-500/5' : 'border-gray-700/50 bg-gray-800/50'"
    data-testid="board-post-card"
  >
    <!-- Header: avatar, name, role, time, badges, actions -->
    <div class="flex items-start gap-3">
      <!-- Avatar initial -->
      <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-xs font-bold text-gray-300 shrink-0">
        {{ message.user ? initial(message.user.name) : '?' }}
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-sm font-semibold text-white">{{ message.user?.name ?? 'Unknown' }}</span>
          <span
            v-if="message.user?.role"
            class="text-[10px] font-medium px-1.5 py-0.5 rounded-full"
            :class="{
              'bg-red-500/20 text-red-300': message.user.role === 'admin',
              'bg-yellow-500/20 text-yellow-300': message.user.role === 'manager',
              'bg-blue-500/20 text-blue-300': message.user.role === 'server',
              'bg-green-500/20 text-green-300': message.user.role === 'bartender',
            }"
          >
            {{ message.user.role }}
          </span>
          <span class="text-[10px] text-gray-500">{{ relativeTime(message.created_at) }}</span>

          <!-- Pin icon -->
          <svg
            v-if="message.pinned"
            class="w-3.5 h-3.5 text-amber-400"
            fill="currentColor"
            viewBox="0 0 20 20"
            data-testid="pin-icon"
          >
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a2 2 0 114 0v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
          </svg>

          <!-- Managers-only badge -->
          <span
            v-if="message.visibility === 'managers'"
            class="text-[10px] font-medium px-1.5 py-0.5 rounded-full bg-purple-500/20 text-purple-300"
            data-testid="managers-only-badge"
          >
            Managers only
          </span>
        </div>
      </div>

      <!-- Action menu -->
      <div v-if="canModify || canPin" class="flex items-center gap-1 shrink-0">
        <button
          v-if="canPin && !message.parent_id"
          type="button"
          class="p-1 text-gray-500 hover:text-amber-400 transition-colors"
          :title="message.pinned ? 'Unpin' : 'Pin'"
          @click="emit('pin', message.id)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
          </svg>
        </button>
        <button
          v-if="canModify"
          type="button"
          class="p-1 text-gray-500 hover:text-blue-400 transition-colors"
          title="Edit"
          data-testid="edit-button"
          @click="startEdit"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
          </svg>
        </button>
        <button
          v-if="canModify"
          type="button"
          class="p-1 text-gray-500 hover:text-red-400 transition-colors"
          title="Delete"
          data-testid="delete-button"
          @click="emit('delete', message.id)"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Body (or edit form) -->
    <div v-if="editing" class="space-y-2">
      <textarea
        v-model="editBody"
        rows="3"
        maxlength="2000"
        class="w-full bg-gray-800 text-white text-sm rounded-md border border-gray-700 px-3 py-2 focus:outline-none focus:border-amber-500 resize-none"
      />
      <div class="flex gap-2">
        <button
          type="button"
          class="px-3 py-1 text-xs font-semibold rounded-md bg-amber-500 text-gray-900 hover:bg-amber-400"
          @click="saveEdit"
        >
          Save
        </button>
        <button
          type="button"
          class="px-3 py-1 text-xs font-semibold rounded-md bg-white/[0.06] text-gray-400 hover:bg-white/[0.1]"
          @click="cancelEdit"
        >
          Cancel
        </button>
      </div>
    </div>
    <p v-else class="text-sm text-gray-300 whitespace-pre-wrap" data-testid="post-body">
      {{ message.body }}
    </p>

    <!-- Replies toggle & list -->
    <div v-if="!message.parent_id">
      <button
        type="button"
        class="text-xs text-gray-500 hover:text-gray-300 transition-colors"
        @click="showReplies = !showReplies"
      >
        {{ showReplies ? 'Hide replies' : `Replies (${message.replies?.length ?? 0})` }}
      </button>

      <div v-if="showReplies" class="mt-3 pl-4 border-l border-gray-700/50 space-y-3">
        <!-- Reply list -->
        <div
          v-for="reply in message.replies"
          :key="reply.id"
          class="space-y-1"
        >
          <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center text-[10px] font-bold text-gray-300">
              {{ reply.user ? initial(reply.user.name) : '?' }}
            </div>
            <span class="text-xs font-semibold text-white">{{ reply.user?.name ?? 'Unknown' }}</span>
            <span class="text-[10px] text-gray-500">{{ relativeTime(reply.created_at) }}</span>
          </div>
          <p class="text-xs text-gray-400 whitespace-pre-wrap ml-8">{{ reply.body }}</p>
        </div>

        <!-- Reply composer -->
        <MessageComposer
          placeholder="Write a reply..."
          @submit="(body: string) => emit('reply', message.id, body)"
        />
      </div>
    </div>
  </div>
</template>
