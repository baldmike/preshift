<script setup lang="ts">
/**
 * MessagesView.vue
 *
 * Main view for the /messages route. Displays a two-tab layout with "Board"
 * (location-scoped bulletin board) and "Direct" (private 1-on-1 messaging).
 *
 * Tab state is driven by the `tab` query parameter (defaults to 'board').
 * The Direct tab accepts an optional `userId` query parameter to auto-open
 * a conversation with that user (used by the EmployeeProfileModal).
 *
 * Fetches board messages and conversations on mount. Shows an unread badge
 * on the Direct tab when there are unread DMs.
 */
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useMessageStore } from '@/stores/messages'
import BoardTab from '@/components/messages/BoardTab.vue'
import DirectTab from '@/components/messages/DirectTab.vue'

const route = useRoute()
const router = useRouter()
const messageStore = useMessageStore()

/** Active tab from query parameter */
const activeTab = computed(() => {
  const tab = route.query.tab as string
  return tab === 'direct' ? 'direct' : 'board'
})

/** User ID from query for auto-opening a DM conversation */
const initialUserId = computed(() => {
  const id = route.query.userId as string
  return id ? Number(id) : null
})

/** Switch tab by updating the query parameter */
function switchTab(tab: 'board' | 'direct') {
  router.replace({ query: { ...route.query, tab, userId: undefined } })
}

/** Fetch unread count on mount */
onMounted(() => {
  messageStore.fetchUnreadCount()
})
</script>

<template>
  <div class="min-h-screen bg-gray-900 pb-24">
    <!-- Header -->
    <div class="sticky top-0 z-30 bg-gray-900 border-b border-gray-800">
      <div class="max-w-2xl mx-auto px-4 pt-4 pb-0">
        <div class="flex items-center justify-between mb-3">
          <h1 class="text-lg font-bold text-white">Messages</h1>
          <router-link
            to="/dashboard"
            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md text-[11px] font-semibold whitespace-nowrap bg-white/[0.06] text-gray-400 hover:bg-white/[0.1] hover:text-white transition-colors"
          >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Corner!
          </router-link>
        </div>

        <!-- Tab bar -->
        <div class="flex gap-1">
          <button
            type="button"
            class="flex-1 px-4 py-2 text-sm font-medium rounded-t-md transition-colors"
            :class="activeTab === 'board'
              ? 'bg-gray-800 text-amber-400 border-b-2 border-amber-400'
              : 'text-gray-500 hover:text-gray-300'"
            @click="switchTab('board')"
          >
            Board
          </button>
          <button
            type="button"
            class="flex-1 px-4 py-2 text-sm font-medium rounded-t-md transition-colors relative"
            :class="activeTab === 'direct'
              ? 'bg-gray-800 text-amber-400 border-b-2 border-amber-400'
              : 'text-gray-500 hover:text-gray-300'"
            @click="switchTab('direct')"
          >
            Direct
            <!-- Unread badge -->
            <span
              v-if="messageStore.unreadDmCount > 0"
              class="absolute -top-1 right-2 min-w-[18px] h-[18px] flex items-center justify-center text-[10px] font-bold bg-amber-500 text-gray-900 rounded-full px-1"
            >
              {{ messageStore.unreadDmCount > 99 ? '99+' : messageStore.unreadDmCount }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Tab content -->
    <div class="max-w-2xl mx-auto px-4 py-4">
      <BoardTab v-if="activeTab === 'board'" />
      <DirectTab
        v-else
        :initial-user-id="initialUserId"
      />
    </div>
  </div>
</template>
