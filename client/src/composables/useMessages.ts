/**
 * composables/useMessages.ts
 *
 * A thin composable wrapper around the Pinia `messageStore`. Exposes the
 * store's state and actions directly, plus convenience computed refs for
 * separating pinned from regular posts and computing total unread count.
 *
 * Usage:
 *   ```ts
 *   const { pinnedPosts, regularPosts, totalUnread, fetchBoardMessages } = useMessages()
 *   ```
 */

import { computed } from 'vue'
import { useMessageStore } from '@/stores/messages'

/**
 * Provides reactive access to message board and DM state with convenience
 * computed properties.
 *
 * @returns Store state, actions, and convenience computed refs:
 *  - `pinnedPosts`   : Board posts where `pinned === true`
 *  - `regularPosts`  : Board posts where `pinned === false`
 *  - `totalUnread`   : Total unread DM count from the store
 */
export function useMessages() {
  const store = useMessageStore()

  /** Board posts that are pinned to the top */
  const pinnedPosts = computed(() =>
    store.boardMessages.filter((m) => m.pinned)
  )

  /** Board posts that are not pinned (regular chronological order) */
  const regularPosts = computed(() =>
    store.boardMessages.filter((m) => !m.pinned)
  )

  /** Total unread DM conversation count */
  const totalUnread = computed(() => store.unreadDmCount)

  return {
    // Convenience computed
    pinnedPosts,
    regularPosts,
    totalUnread,
    // Pass-through state
    boardMessages: computed(() => store.boardMessages),
    conversations: computed(() => store.conversations),
    activeConversationId: computed(() => store.activeConversationId),
    directMessages: computed(() => store.directMessages),
    boardLoading: computed(() => store.boardLoading),
    dmLoading: computed(() => store.dmLoading),
    // Pass-through actions
    fetchBoardMessages: store.fetchBoardMessages,
    createBoardMessage: store.createBoardMessage,
    updateBoardMessage: store.updateBoardMessage,
    deleteBoardMessage: store.deleteBoardMessage,
    togglePin: store.togglePin,
    fetchConversations: store.fetchConversations,
    findOrCreateConversation: store.findOrCreateConversation,
    fetchMessages: store.fetchMessages,
    sendMessage: store.sendMessage,
    fetchUnreadCount: store.fetchUnreadCount,
    // Realtime mutations
    addBoardMessage: store.addBoardMessage,
    updateBoardMessageLocal: store.updateBoardMessageLocal,
    removeBoardMessage: store.removeBoardMessage,
    pushDirectMessage: store.pushDirectMessage,
  }
}
