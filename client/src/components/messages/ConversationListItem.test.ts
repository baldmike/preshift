/**
 * ConversationListItem.test.ts
 *
 * Unit tests for the ConversationListItem.vue component.
 *
 * Tests verify:
 *  1. Renders the other participant's name (not the current user).
 *  2. Shows 'Unknown' when the other participant cannot be determined.
 *  3. Displays a role badge with the correct role text.
 *  4. Truncates long message previews to 40 characters with an ellipsis.
 *  5. Shows "No messages yet" when there is no latest message.
 *  6. Shows the unread indicator dot when unread_count > 0.
 *  7. Hides the unread indicator dot when unread_count is 0.
 *  8. Emits 'select' with the conversation ID when clicked.
 *  9. Applies the active class when the active prop is true.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import ConversationListItem from '@/components/messages/ConversationListItem.vue'
import type { Conversation, User } from '@/types'

// ── Mock useAuth ──────────────────────────────────────────────────────────
const mockUser = ref<{ id: number; role: string; name: string } | null>({ id: 1, role: 'server', name: 'Current User' })

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: mockUser,
  }),
}))

// ── Helpers ───────────────────────────────────────────────────────────────

/** Creates a minimal User object with overrides */
function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 2,
    location_id: 1,
    name: 'Jane Doe',
    email: 'jane@test.com',
    role: 'server',
    roles: null,
    is_superadmin: false,
    phone: null,
    profile_photo_url: null,
    availability: null,
    created_at: '',
    updated_at: '',
    ...overrides,
  }
}

/** Creates a minimal Conversation object with overrides */
function makeConversation(overrides: Partial<Conversation> = {}): Conversation {
  return {
    id: 10,
    location_id: 1,
    participants: [
      makeUser({ id: 1, name: 'Current User' }),
      makeUser({ id: 2, name: 'Jane Doe', role: 'manager' }),
    ],
    latest_message: {
      id: 100,
      conversation_id: 10,
      sender_id: 2,
      body: 'Hey, are you working tonight?',
      created_at: new Date().toISOString(),
    },
    unread_count: 0,
    created_at: '',
    updated_at: '',
    ...overrides,
  }
}

/** Mount the component with a given conversation and optional active flag */
function mountItem(conversation: Conversation, active = false) {
  return mount(ConversationListItem, {
    props: { conversation, active },
  })
}

describe('ConversationListItem', () => {
  beforeEach(() => {
    mockUser.value = { id: 1, role: 'server', name: 'Current User' }
  })

  /**
   * Checks that the component renders the OTHER participant's name, not
   * the current user. The current user (id: 1) should be filtered out.
   */
  it('renders the other participant name', () => {
    const wrapper = mountItem(makeConversation())

    expect(wrapper.text()).toContain('Jane Doe')
  })

  /**
   * Checks that "Unknown" is shown when the other participant cannot be
   * determined (e.g. only the current user appears in participants).
   */
  it('shows Unknown when other participant is not found', () => {
    const conv = makeConversation({
      participants: [makeUser({ id: 1, name: 'Current User' })],
    })
    const wrapper = mountItem(conv)

    expect(wrapper.text()).toContain('Unknown')
  })

  /**
   * Checks that the role badge is rendered with the correct role text
   * based on the other participant's role.
   */
  it('displays a role badge for the other participant', () => {
    const conv = makeConversation()
    const wrapper = mountItem(conv)

    expect(wrapper.text()).toContain('manager')
  })

  /**
   * Checks that a long message body is truncated to 40 characters with
   * trailing ellipsis for the preview display.
   */
  it('truncates long message previews at 40 characters', () => {
    const longBody = 'This is a very long message that should be truncated after forty characters'
    const conv = makeConversation({
      latest_message: {
        id: 101,
        conversation_id: 10,
        sender_id: 2,
        body: longBody,
        created_at: new Date().toISOString(),
      },
    })
    const wrapper = mountItem(conv)

    const expectedPreview = longBody.substring(0, 40) + '...'
    expect(wrapper.text()).toContain(expectedPreview)
  })

  /**
   * Checks that short messages are shown in full without truncation.
   */
  it('shows short messages without truncation', () => {
    const shortBody = 'Hey!'
    const conv = makeConversation({
      latest_message: {
        id: 101,
        conversation_id: 10,
        sender_id: 2,
        body: shortBody,
        created_at: new Date().toISOString(),
      },
    })
    const wrapper = mountItem(conv)

    expect(wrapper.text()).toContain('Hey!')
    expect(wrapper.text()).not.toContain('...')
  })

  /**
   * Checks that "No messages yet" is shown when the conversation has
   * no latest_message (null).
   */
  it('shows "No messages yet" when latest_message is null', () => {
    const conv = makeConversation({ latest_message: null })
    const wrapper = mountItem(conv)

    expect(wrapper.text()).toContain('No messages yet')
  })

  /**
   * Checks that the unread indicator dot is visible when the conversation
   * has unread messages.
   */
  it('shows unread dot when unread_count > 0', () => {
    const conv = makeConversation({ unread_count: 3 })
    const wrapper = mountItem(conv)

    expect(wrapper.find('.bg-amber-500').exists()).toBe(true)
  })

  /**
   * Checks that the unread indicator dot is hidden when there are no
   * unread messages.
   */
  it('hides unread dot when unread_count is 0', () => {
    const conv = makeConversation({ unread_count: 0 })
    const wrapper = mountItem(conv)

    expect(wrapper.find('.bg-amber-500').exists()).toBe(false)
  })

  /**
   * Checks that clicking the conversation row emits 'select' with the
   * conversation's ID so the parent can open the thread.
   */
  it('emits select with conversation id on click', async () => {
    const conv = makeConversation({ id: 42 })
    const wrapper = mountItem(conv)

    await wrapper.find('button').trigger('click')

    expect(wrapper.emitted('select')).toBeTruthy()
    expect(wrapper.emitted('select')![0]).toEqual([42])
  })

  /**
   * Checks that the active styling class is applied when the active
   * prop is true, indicating this conversation is currently selected.
   */
  it('applies active styling when active prop is true', () => {
    const wrapper = mountItem(makeConversation(), true)

    expect(wrapper.find('button').classes()).toContain('bg-gray-700/50')
  })

  /**
   * Checks that the hover styling class is applied when the active prop
   * is false, indicating the conversation is not selected.
   */
  it('applies hover styling when active prop is false', () => {
    const wrapper = mountItem(makeConversation(), false)

    expect(wrapper.find('button').classes()).toContain('hover:bg-gray-800/50')
  })

  /**
   * Checks that the avatar initial shows the uppercase first letter of
   * the other participant's name.
   */
  it('shows the avatar initial of the other participant', () => {
    const conv = makeConversation()
    const wrapper = mountItem(conv)

    const avatar = wrapper.find('.rounded-full')
    expect(avatar.text()).toContain('J')
  })
})
