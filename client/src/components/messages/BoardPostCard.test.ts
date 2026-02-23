/**
 * BoardPostCard.test.ts
 *
 * Unit tests for the BoardPostCard.vue component.
 *
 * Tests verify:
 *  1. Renders the author name and post body.
 *  2. Shows the pin icon when the post is pinned.
 *  3. Shows the "Managers only" badge for managers-visibility posts.
 *  4. Shows edit/delete buttons for the post author.
 *  5. Hides edit/delete buttons for non-author staff.
 */

import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import BoardPostCard from '@/components/messages/BoardPostCard.vue'
import type { BoardMessage } from '@/types'

// ── Mock useAuth ──────────────────────────────────────────────────────────
const mockUser = { id: 1, role: 'server' }
const mockIsAdmin = { value: false }
const mockIsManager = { value: false }

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: { value: mockUser },
    isAdmin: mockIsAdmin,
    isManager: mockIsManager,
  }),
}))

// ── Helper: make a test BoardMessage ──────────────────────────────────────
function makeMessage(overrides: Partial<BoardMessage> = {}): BoardMessage {
  return {
    id: 1,
    location_id: 1,
    user_id: 1,
    parent_id: null,
    body: 'Test post body',
    visibility: 'all',
    pinned: false,
    user: { id: 1, name: 'Test User', role: 'server', email: 'test@test.com', location_id: 1, roles: null, is_superadmin: false, phone: null, availability: null, created_at: '', updated_at: '' },
    replies: [],
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    ...overrides,
  }
}

// ── Stub for MessageComposer ──────────────────────────────────────────────
const MessageComposerStub = {
  template: '<div class="composer-stub" />',
  props: ['placeholder'],
}

/**
 * Helper: mount a BoardPostCard with a given message.
 */
function mountCard(message: BoardMessage) {
  return mount(BoardPostCard, {
    props: { message },
    global: {
      stubs: {
        MessageComposer: MessageComposerStub,
      },
    },
  })
}

describe('BoardPostCard', () => {
  /**
   * Checks that the component renders the author name and post body text.
   */
  it('renders author name and body', () => {
    const wrapper = mountCard(makeMessage({ body: 'Hello world' }))

    expect(wrapper.text()).toContain('Test User')
    expect(wrapper.find('[data-testid="post-body"]').text()).toBe('Hello world')
  })

  /**
   * Checks that the pin icon is visible when the post is pinned.
   */
  it('shows pin icon when pinned', () => {
    const wrapper = mountCard(makeMessage({ pinned: true }))

    expect(wrapper.find('[data-testid="pin-icon"]').exists()).toBe(true)
  })

  /**
   * Checks that the pin icon is hidden when the post is not pinned.
   */
  it('hides pin icon when not pinned', () => {
    const wrapper = mountCard(makeMessage({ pinned: false }))

    expect(wrapper.find('[data-testid="pin-icon"]').exists()).toBe(false)
  })

  /**
   * Checks that the "Managers only" badge appears for managers-visibility posts.
   */
  it('shows managers-only badge', () => {
    const wrapper = mountCard(makeMessage({ visibility: 'managers' }))

    expect(wrapper.find('[data-testid="managers-only-badge"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Managers only')
  })

  /**
   * Checks that the author can see edit/delete buttons on their own post.
   */
  it('shows edit/delete for post author', () => {
    mockUser.id = 1
    const wrapper = mountCard(makeMessage({ user_id: 1 }))

    expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(true)
  })

  /**
   * Checks that a non-author staff member cannot see edit/delete buttons.
   */
  it('hides edit/delete for non-author staff', () => {
    mockUser.id = 99
    mockIsAdmin.value = false
    mockIsManager.value = false
    const wrapper = mountCard(makeMessage({ user_id: 1 }))

    expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(false)
  })
})
