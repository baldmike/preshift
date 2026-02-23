/**
 * stores/messages.ts
 *
 * Pinia store that manages all message board and direct messaging state.
 *
 * Data flow:
 *  1. Board messages are fetched via `GET /api/board-messages` and updated
 *     in real time via Reverb events on the location channel.
 *  2. Conversations and DMs are fetched via `/api/conversations` endpoints
 *     and updated in real time via Reverb events on the user channel.
 *
 * State shape:
 *  - `boardMessages`         : Array of top-level board posts (with replies nested)
 *  - `conversations`         : Array of the user's DM conversations
 *  - `activeConversationId`  : ID of the currently open conversation (null if none)
 *  - `directMessages`        : Array of messages in the active conversation
 *  - `unreadDmCount`         : Total conversations with unread messages
 *  - `boardLoading`          : Boolean flag for board fetch in flight
 *  - `dmLoading`             : Boolean flag for DM fetch in flight
 */

import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/composables/useApi'
import type { BoardMessage, Conversation, DirectMessage } from '@/types'

export const useMessageStore = defineStore('messages', () => {
  // -------------------------------------------------------------------------
  // State
  // -------------------------------------------------------------------------

  /** Top-level board posts with nested replies */
  const boardMessages = ref<BoardMessage[]>([])

  /** The user's DM conversations */
  const conversations = ref<Conversation[]>([])

  /** Currently active conversation ID */
  const activeConversationId = ref<number | null>(null)

  /** Messages in the active conversation */
  const directMessages = ref<DirectMessage[]>([])

  /** Total conversations with unread messages */
  const unreadDmCount = ref(0)

  /** Loading flag for board messages */
  const boardLoading = ref(false)

  /** Loading flag for DM operations */
  const dmLoading = ref(false)

  // -------------------------------------------------------------------------
  // Board Actions
  // -------------------------------------------------------------------------

  /**
   * Fetch all top-level board messages for the current location.
   * Endpoint: GET /api/board-messages
   */
  async function fetchBoardMessages() {
    boardLoading.value = true
    try {
      const { data } = await api.get<BoardMessage[]>('/api/board-messages')
      boardMessages.value = data
    } finally {
      boardLoading.value = false
    }
  }

  /**
   * Create a new board message (post or reply).
   * Endpoint: POST /api/board-messages
   */
  async function createBoardMessage(payload: {
    body: string
    parent_id?: number | null
    visibility?: 'all' | 'managers'
  }) {
    const { data } = await api.post<BoardMessage>('/api/board-messages', payload)

    if (data.parent_id) {
      // It's a reply — find the parent and add to its replies array
      const parent = boardMessages.value.find((m) => m.id === data.parent_id)
      if (parent) {
        if (!parent.replies) parent.replies = []
        parent.replies.push(data)
      }
    } else {
      // Top-level post — add to the beginning
      boardMessages.value.unshift(data)
    }

    return data
  }

  /**
   * Update an existing board message.
   * Endpoint: PATCH /api/board-messages/{id}
   */
  async function updateBoardMessage(id: number, payload: {
    body: string
    visibility?: 'all' | 'managers'
  }) {
    const { data } = await api.patch<BoardMessage>(`/api/board-messages/${id}`, payload)
    updateBoardMessageLocal(data)
    return data
  }

  /**
   * Delete a board message.
   * Endpoint: DELETE /api/board-messages/{id}
   */
  async function deleteBoardMessage(id: number) {
    await api.delete(`/api/board-messages/${id}`)
    removeBoardMessage({ id, parent_id: boardMessages.value.find((m) => m.id === id)?.parent_id ?? null })
  }

  /**
   * Toggle pin status on a board message.
   * Endpoint: POST /api/board-messages/{id}/pin
   */
  async function togglePin(id: number) {
    const { data } = await api.post<BoardMessage>(`/api/board-messages/${id}/pin`)
    updateBoardMessageLocal(data)
    return data
  }

  // -------------------------------------------------------------------------
  // Board Realtime Mutations
  // -------------------------------------------------------------------------

  /**
   * Add a board message received via WebSocket event.
   * Handles both top-level posts and replies.
   */
  function addBoardMessage(message: BoardMessage) {
    if (message.parent_id) {
      const parent = boardMessages.value.find((m) => m.id === message.parent_id)
      if (parent) {
        if (!parent.replies) parent.replies = []
        if (!parent.replies.some((r) => r.id === message.id)) {
          parent.replies.push(message)
        }
      }
    } else {
      if (!boardMessages.value.some((m) => m.id === message.id)) {
        boardMessages.value.unshift(message)
      }
    }
  }

  /**
   * Update a board message in the local state (from WebSocket or API response).
   */
  function updateBoardMessageLocal(message: BoardMessage) {
    if (message.parent_id) {
      // It's a reply — find in parent's replies
      const parent = boardMessages.value.find((m) => m.id === message.parent_id)
      if (parent?.replies) {
        const idx = parent.replies.findIndex((r) => r.id === message.id)
        if (idx !== -1) parent.replies[idx] = message
      }
    } else {
      const idx = boardMessages.value.findIndex((m) => m.id === message.id)
      if (idx !== -1) boardMessages.value[idx] = message
    }
  }

  /**
   * Remove a board message from local state (from WebSocket event).
   */
  function removeBoardMessage(payload: { id: number; parent_id: number | null }) {
    if (payload.parent_id) {
      const parent = boardMessages.value.find((m) => m.id === payload.parent_id)
      if (parent?.replies) {
        parent.replies = parent.replies.filter((r) => r.id !== payload.id)
      }
    } else {
      boardMessages.value = boardMessages.value.filter((m) => m.id !== payload.id)
    }
  }

  // -------------------------------------------------------------------------
  // DM Actions
  // -------------------------------------------------------------------------

  /**
   * Fetch the user's conversations.
   * Endpoint: GET /api/conversations
   */
  async function fetchConversations() {
    dmLoading.value = true
    try {
      const { data } = await api.get<Conversation[]>('/api/conversations')
      conversations.value = data
    } finally {
      dmLoading.value = false
    }
  }

  /**
   * Find or create a conversation with a target user.
   * Endpoint: POST /api/conversations
   */
  async function findOrCreateConversation(userId: number) {
    const { data } = await api.post<Conversation>('/api/conversations', { user_id: userId })

    // Add to local conversations if not already present
    if (!conversations.value.some((c) => c.id === data.id)) {
      conversations.value.unshift(data)
    }

    return data
  }

  /**
   * Fetch messages for a conversation (also marks as read).
   * Endpoint: GET /api/conversations/{id}/messages
   */
  async function fetchMessages(conversationId: number) {
    activeConversationId.value = conversationId
    dmLoading.value = true
    try {
      const { data } = await api.get<DirectMessage[]>(`/api/conversations/${conversationId}/messages`)
      directMessages.value = data

      // Update local unread count for this conversation
      const conv = conversations.value.find((c) => c.id === conversationId)
      if (conv) conv.unread_count = 0
    } finally {
      dmLoading.value = false
    }
  }

  /**
   * Send a message in a conversation.
   * Endpoint: POST /api/conversations/{id}/messages
   */
  async function sendMessage(conversationId: number, body: string) {
    const { data } = await api.post<DirectMessage>(
      `/api/conversations/${conversationId}/messages`,
      { body }
    )

    // Add to active messages if this is the open conversation
    if (activeConversationId.value === conversationId) {
      directMessages.value.push(data)
    }

    // Update conversation's latest message
    const conv = conversations.value.find((c) => c.id === conversationId)
    if (conv) {
      conv.latest_message = data
    }

    return data
  }

  /**
   * Fetch total unread DM conversation count.
   * Endpoint: GET /api/conversations/unread-count
   */
  async function fetchUnreadCount() {
    const { data } = await api.get<{ unread_count: number }>('/api/conversations/unread-count')
    unreadDmCount.value = data.unread_count
  }

  // -------------------------------------------------------------------------
  // DM Realtime Mutations
  // -------------------------------------------------------------------------

  /**
   * Push a new direct message received via WebSocket event.
   * Updates the active conversation's messages and the conversation list.
   */
  function pushDirectMessage(message: DirectMessage) {
    // Add to active conversation if it's the one being viewed
    if (activeConversationId.value === message.conversation_id) {
      if (!directMessages.value.some((m) => m.id === message.id)) {
        directMessages.value.push(message)
      }
    }

    // Update conversation list
    const conv = conversations.value.find((c) => c.id === message.conversation_id)
    if (conv) {
      conv.latest_message = message
      // Only increment unread if this conversation isn't currently active
      if (activeConversationId.value !== message.conversation_id) {
        conv.unread_count = (conv.unread_count || 0) + 1
        unreadDmCount.value += 1
      }
    }
  }

  // -------------------------------------------------------------------------
  // Public API
  // -------------------------------------------------------------------------
  return {
    // State
    boardMessages,
    conversations,
    activeConversationId,
    directMessages,
    unreadDmCount,
    boardLoading,
    dmLoading,
    // Board actions
    fetchBoardMessages,
    createBoardMessage,
    updateBoardMessage,
    deleteBoardMessage,
    togglePin,
    // Board realtime mutations
    addBoardMessage,
    updateBoardMessageLocal,
    removeBoardMessage,
    // DM actions
    fetchConversations,
    findOrCreateConversation,
    fetchMessages,
    sendMessage,
    fetchUnreadCount,
    // DM realtime mutations
    pushDirectMessage,
  }
})
