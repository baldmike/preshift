/**
 * stores/messages.test.ts
 *
 * Unit tests for the useMessageStore Pinia store.
 *
 * These tests verify:
 *  1. Initial state is empty (no board messages, conversations, or DMs).
 *  2. `fetchBoardMessages()` populates the boardMessages array.
 *  3. `createBoardMessage()` adds a new post to the front of the array.
 *  4. Realtime mutations (`addBoardMessage`, `updateBoardMessageLocal`,
 *     `removeBoardMessage`) correctly modify local state.
 *  5. `fetchConversations()` populates the conversations array.
 *  6. `fetchUnreadCount()` updates the unreadDmCount.
 *  7. `pushDirectMessage()` adds a message and increments unread count.
 *
 * We mock:
 *  - `@/composables/useApi` so no real HTTP requests are made.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMessageStore } from '@/stores/messages'
import type { BoardMessage, Conversation, DirectMessage } from '@/types'

// ── Mock the API module ────────────────────────────────────────────────────
vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

import api from '@/composables/useApi'

// ── Helper: make a test BoardMessage ───────────────────────────────────────
function makeBoardMessage(overrides: Partial<BoardMessage> = {}): BoardMessage {
  return {
    id: 1,
    location_id: 1,
    user_id: 1,
    parent_id: null,
    body: 'Test post',
    visibility: 'all',
    pinned: false,
    replies: [],
    created_at: '2026-02-22T10:00:00Z',
    updated_at: '2026-02-22T10:00:00Z',
    ...overrides,
  }
}

// ── Helper: make a test Conversation ──────────────────────────────────────
function makeConversation(overrides: Partial<Conversation> = {}): Conversation {
  return {
    id: 1,
    location_id: 1,
    participants: [],
    latest_message: null,
    unread_count: 0,
    created_at: '2026-02-22T10:00:00Z',
    updated_at: '2026-02-22T10:00:00Z',
    ...overrides,
  }
}

describe('useMessageStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  // ──────────────────────────────────────────────────
  // INITIAL STATE
  // ──────────────────────────────────────────────────

  /**
   * Checks that the store initializes with empty arrays and zero counts.
   * This ensures no stale data from a previous session leaks through.
   */
  it('has empty initial state', () => {
    const store = useMessageStore()

    expect(store.boardMessages).toEqual([])
    expect(store.conversations).toEqual([])
    expect(store.directMessages).toEqual([])
    expect(store.activeConversationId).toBeNull()
    expect(store.unreadDmCount).toBe(0)
    expect(store.boardLoading).toBe(false)
    expect(store.dmLoading).toBe(false)
  })

  // ──────────────────────────────────────────────────
  // FETCH BOARD MESSAGES
  // ──────────────────────────────────────────────────

  /**
   * Checks that fetchBoardMessages() populates the store from the API response.
   */
  it('fetchBoardMessages populates state', async () => {
    const store = useMessageStore()
    const messages = [makeBoardMessage({ id: 1 }), makeBoardMessage({ id: 2 })]

    vi.mocked(api.get).mockResolvedValueOnce({ data: messages } as any)

    await store.fetchBoardMessages()

    expect(api.get).toHaveBeenCalledWith('/api/board-messages')
    expect(store.boardMessages).toEqual(messages)
    expect(store.boardLoading).toBe(false)
  })

  // ──────────────────────────────────────────────────
  // CREATE BOARD MESSAGE
  // ──────────────────────────────────────────────────

  /**
   * Checks that createBoardMessage() adds the new post to the front of the list.
   */
  it('createBoardMessage adds to state', async () => {
    const store = useMessageStore()
    const newMessage = makeBoardMessage({ id: 3, body: 'New post' })

    vi.mocked(api.post).mockResolvedValueOnce({ data: newMessage } as any)

    await store.createBoardMessage({ body: 'New post' })

    expect(api.post).toHaveBeenCalledWith('/api/board-messages', { body: 'New post' })
    expect(store.boardMessages[0]).toEqual(newMessage)
  })

  // ──────────────────────────────────────────────────
  // REALTIME: ADD BOARD MESSAGE
  // ──────────────────────────────────────────────────

  /**
   * Checks that addBoardMessage() inserts a new post at the front.
   */
  it('addBoardMessage inserts new top-level post', () => {
    const store = useMessageStore()
    const post = makeBoardMessage({ id: 10 })

    store.addBoardMessage(post)

    expect(store.boardMessages).toHaveLength(1)
    expect(store.boardMessages[0].id).toBe(10)
  })

  /**
   * Checks that addBoardMessage() adds a reply to the correct parent.
   */
  it('addBoardMessage inserts reply into parent', () => {
    const store = useMessageStore()
    const parent = makeBoardMessage({ id: 1, replies: [] })
    store.boardMessages = [parent]

    const reply = makeBoardMessage({ id: 5, parent_id: 1, body: 'Reply' })
    store.addBoardMessage(reply)

    expect(store.boardMessages[0].replies).toHaveLength(1)
    expect(store.boardMessages[0].replies![0].id).toBe(5)
  })

  // ──────────────────────────────────────────────────
  // REALTIME: UPDATE BOARD MESSAGE
  // ──────────────────────────────────────────────────

  /**
   * Checks that updateBoardMessageLocal() replaces the correct post.
   */
  it('updateBoardMessageLocal replaces the correct post', () => {
    const store = useMessageStore()
    store.boardMessages = [makeBoardMessage({ id: 1, body: 'Original' })]

    store.updateBoardMessageLocal(makeBoardMessage({ id: 1, body: 'Updated' }))

    expect(store.boardMessages[0].body).toBe('Updated')
  })

  // ──────────────────────────────────────────────────
  // REALTIME: REMOVE BOARD MESSAGE
  // ──────────────────────────────────────────────────

  /**
   * Checks that removeBoardMessage() removes the correct post from the list.
   */
  it('removeBoardMessage removes the correct post', () => {
    const store = useMessageStore()
    store.boardMessages = [
      makeBoardMessage({ id: 1 }),
      makeBoardMessage({ id: 2 }),
    ]

    store.removeBoardMessage({ id: 1, parent_id: null })

    expect(store.boardMessages).toHaveLength(1)
    expect(store.boardMessages[0].id).toBe(2)
  })

  // ──────────────────────────────────────────────────
  // FETCH CONVERSATIONS
  // ──────────────────────────────────────────────────

  /**
   * Checks that fetchConversations() populates the conversations array.
   */
  it('fetchConversations populates state', async () => {
    const store = useMessageStore()
    const convs = [makeConversation({ id: 1 }), makeConversation({ id: 2 })]

    vi.mocked(api.get).mockResolvedValueOnce({ data: convs } as any)

    await store.fetchConversations()

    expect(api.get).toHaveBeenCalledWith('/api/conversations')
    expect(store.conversations).toEqual(convs)
  })

  // ──────────────────────────────────────────────────
  // FETCH UNREAD COUNT
  // ──────────────────────────────────────────────────

  /**
   * Checks that fetchUnreadCount() updates the unreadDmCount.
   */
  it('fetchUnreadCount updates count', async () => {
    const store = useMessageStore()

    vi.mocked(api.get).mockResolvedValueOnce({ data: { unread_count: 3 } } as any)

    await store.fetchUnreadCount()

    expect(api.get).toHaveBeenCalledWith('/api/conversations/unread-count')
    expect(store.unreadDmCount).toBe(3)
  })

  // ──────────────────────────────────────────────────
  // PUSH DIRECT MESSAGE
  // ──────────────────────────────────────────────────

  /**
   * Checks that pushDirectMessage() adds to the active conversation messages
   * and increments unread for non-active conversations.
   */
  it('pushDirectMessage increments unread for non-active conversation', () => {
    const store = useMessageStore()
    store.conversations = [makeConversation({ id: 1, unread_count: 0 })]
    store.activeConversationId = null // Not viewing this conversation

    const msg: DirectMessage = {
      id: 1,
      conversation_id: 1,
      sender_id: 99,
      body: 'Hello',
      created_at: '2026-02-22T10:00:00Z',
    }

    store.pushDirectMessage(msg)

    expect(store.conversations[0].unread_count).toBe(1)
    expect(store.unreadDmCount).toBe(1)
  })
})
