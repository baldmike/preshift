/**
 * useMessages.test.ts
 *
 * Unit tests for the useMessages composable.
 *
 * These tests verify:
 *  1. `pinnedPosts` filters board messages to only those with `pinned === true`.
 *  2. `regularPosts` filters board messages to only those with `pinned === false`.
 *  3. `totalUnread` reflects the store's unreadDmCount.
 *  4. Pass-through computed refs (boardMessages, conversations, etc.) mirror
 *     the underlying store state reactively.
 *  5. Pass-through actions are the same function references from the store.
 *  6. Edge cases: empty board messages, all pinned, no pinned.
 *
 * We mock `@/composables/useApi` to prevent API calls from the message store
 * and use a real Pinia instance to verify reactive computed bindings.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useMessageStore } from '@/stores/messages'
import { useMessages } from '@/composables/useMessages'
import type { BoardMessage } from '@/types'

/* Mock the Axios instance so the store does not make real HTTP calls */
vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
  useApi: vi.fn(),
}))

// ── Helpers ────────────────────────────────────────────────────────────────

/** Creates a minimal BoardMessage with sensible defaults for testing. */
function makePost(overrides: Partial<BoardMessage> = {}): BoardMessage {
  return {
    id: Math.floor(Math.random() * 10000),
    location_id: 1,
    user_id: 1,
    parent_id: null,
    body: 'Test post',
    visibility: 'all',
    pinned: false,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

// ── Test suite ─────────────────────────────────────────────────────────────

describe('useMessages composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  // ── pinnedPosts ────────────────────────────────────────────────────────

  /* Verifies that pinnedPosts returns only messages with pinned === true,
     excluding all unpinned messages. */
  it('filters board messages to only pinned posts', () => {
    const store = useMessageStore()
    store.boardMessages = [
      makePost({ id: 1, pinned: true }),
      makePost({ id: 2, pinned: false }),
      makePost({ id: 3, pinned: true }),
    ]

    const { pinnedPosts } = useMessages()

    expect(pinnedPosts.value).toHaveLength(2)
    expect(pinnedPosts.value.map((p) => p.id)).toEqual([1, 3])
  })

  /* Verifies that pinnedPosts returns an empty array when no messages
     are pinned. */
  it('returns empty array when no posts are pinned', () => {
    const store = useMessageStore()
    store.boardMessages = [
      makePost({ pinned: false }),
      makePost({ pinned: false }),
    ]

    const { pinnedPosts } = useMessages()

    expect(pinnedPosts.value).toEqual([])
  })

  // ── regularPosts ───────────────────────────────────────────────────────

  /* Verifies that regularPosts returns only messages with pinned === false,
     excluding all pinned messages. */
  it('filters board messages to only unpinned posts', () => {
    const store = useMessageStore()
    store.boardMessages = [
      makePost({ id: 1, pinned: true }),
      makePost({ id: 2, pinned: false }),
      makePost({ id: 3, pinned: false }),
    ]

    const { regularPosts } = useMessages()

    expect(regularPosts.value).toHaveLength(2)
    expect(regularPosts.value.map((p) => p.id)).toEqual([2, 3])
  })

  /* Verifies that regularPosts returns an empty array when all messages
     are pinned. */
  it('returns empty array when all posts are pinned', () => {
    const store = useMessageStore()
    store.boardMessages = [
      makePost({ pinned: true }),
      makePost({ pinned: true }),
    ]

    const { regularPosts } = useMessages()

    expect(regularPosts.value).toEqual([])
  })

  // ── Empty state ────────────────────────────────────────────────────────

  /* Verifies that both pinnedPosts and regularPosts return empty arrays
     when no board messages exist at all. */
  it('returns empty arrays when board has no messages', () => {
    const { pinnedPosts, regularPosts } = useMessages()

    expect(pinnedPosts.value).toEqual([])
    expect(regularPosts.value).toEqual([])
  })

  // ── totalUnread ────────────────────────────────────────────────────────

  /* Verifies that totalUnread reflects the store's unreadDmCount and
     reacts to changes. */
  it('reflects the unread DM count from the store', () => {
    const store = useMessageStore()
    const { totalUnread } = useMessages()

    expect(totalUnread.value).toBe(0)

    store.unreadDmCount = 5
    expect(totalUnread.value).toBe(5)
  })

  // ── Pass-through state ─────────────────────────────────────────────────

  /* Verifies that boardMessages computed ref mirrors the store's boardMessages
     reactively. */
  it('exposes boardMessages from the store', () => {
    const store = useMessageStore()
    const post = makePost({ id: 42, body: 'Hello board' })
    store.boardMessages = [post]

    const { boardMessages } = useMessages()

    expect(boardMessages.value).toHaveLength(1)
    expect(boardMessages.value[0].id).toBe(42)
  })

  /* Verifies that loading flags are exposed correctly. */
  it('exposes loading flags from the store', () => {
    const store = useMessageStore()
    const { boardLoading, dmLoading } = useMessages()

    expect(boardLoading.value).toBe(false)
    expect(dmLoading.value).toBe(false)

    store.boardLoading = true
    store.dmLoading = true

    expect(boardLoading.value).toBe(true)
    expect(dmLoading.value).toBe(true)
  })

  // ── Pass-through actions ───────────────────────────────────────────────

  /* Verifies that the composable exposes store actions as direct references
     (not wrapped copies) so callers get the same function objects. */
  it('exposes store actions as direct references', () => {
    const store = useMessageStore()
    const {
      fetchBoardMessages,
      createBoardMessage,
      updateBoardMessage,
      deleteBoardMessage,
      togglePin,
      fetchConversations,
      findOrCreateConversation,
      fetchMessages,
      sendMessage,
      fetchUnreadCount,
      addBoardMessage,
      updateBoardMessageLocal,
      removeBoardMessage,
      pushDirectMessage,
    } = useMessages()

    expect(fetchBoardMessages).toBe(store.fetchBoardMessages)
    expect(createBoardMessage).toBe(store.createBoardMessage)
    expect(updateBoardMessage).toBe(store.updateBoardMessage)
    expect(deleteBoardMessage).toBe(store.deleteBoardMessage)
    expect(togglePin).toBe(store.togglePin)
    expect(fetchConversations).toBe(store.fetchConversations)
    expect(findOrCreateConversation).toBe(store.findOrCreateConversation)
    expect(fetchMessages).toBe(store.fetchMessages)
    expect(sendMessage).toBe(store.sendMessage)
    expect(fetchUnreadCount).toBe(store.fetchUnreadCount)
    expect(addBoardMessage).toBe(store.addBoardMessage)
    expect(updateBoardMessageLocal).toBe(store.updateBoardMessageLocal)
    expect(removeBoardMessage).toBe(store.removeBoardMessage)
    expect(pushDirectMessage).toBe(store.pushDirectMessage)
  })
})
