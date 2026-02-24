/**
 * ConversationThread.test.ts
 *
 * Unit tests for the ConversationThread.vue component.
 *
 * Tests verify:
 *  1. Fetches messages when conversationId prop is set.
 *  2. Shows loading state while messages are being fetched.
 *  3. Shows empty state when there are no messages.
 *  4. Renders messages aligned by sender (current user right, other left).
 *  5. Displays message body text in each bubble.
 *  6. Calls sendMessage when the composer emits a submit event.
 *  7. Renders the MessageComposer at the bottom for composing replies.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { ref } from 'vue'
import ConversationThread from '@/components/messages/ConversationThread.vue'
import type { DirectMessage } from '@/types'

// ── Mock useAuth ──────────────────────────────────────────────────────────
const mockUser = ref<{ id: number; role: string; name: string } | null>({ id: 1, role: 'server', name: 'Current User' })

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: mockUser,
  }),
}))

// ── Mock useMessages ──────────────────────────────────────────────────────
const mockDirectMessages = ref<DirectMessage[]>([])
const mockDmLoading = ref(false)
const mockFetchMessages = vi.fn()
const mockSendMessage = vi.fn()

vi.mock('@/composables/useMessages', () => ({
  useMessages: () => ({
    directMessages: mockDirectMessages,
    dmLoading: mockDmLoading,
    fetchMessages: mockFetchMessages,
    sendMessage: mockSendMessage,
  }),
}))

// ── Stubs ─────────────────────────────────────────────────────────────────
const MessageComposerStub = {
  template: '<div class="composer-stub"><button data-testid="send-btn" @click="$emit(\'submit\', \'Hi there\')">Send</button></div>',
  props: ['placeholder', 'loading'],
  emits: ['submit'],
}

// ── Helper ────────────────────────────────────────────────────────────────

/** Creates a minimal DirectMessage object with overrides */
function makeMessage(overrides: Partial<DirectMessage> = {}): DirectMessage {
  return {
    id: 1,
    conversation_id: 5,
    sender_id: 2,
    body: 'Hello from the other side',
    created_at: '2026-02-24T12:00:00.000Z',
    ...overrides,
  }
}

function mountThread(conversationId = 5) {
  return mount(ConversationThread, {
    props: { conversationId },
    global: {
      stubs: {
        MessageComposer: MessageComposerStub,
      },
    },
  })
}

describe('ConversationThread', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockDirectMessages.value = []
    mockDmLoading.value = false
    mockFetchMessages.mockResolvedValue(undefined)
    mockSendMessage.mockResolvedValue(undefined)
    mockUser.value = { id: 1, role: 'server', name: 'Current User' }
  })

  /**
   * Checks that fetchMessages is called with the conversationId on mount
   * due to the immediate watcher on the prop.
   */
  it('fetches messages for the given conversationId on mount', async () => {
    mountThread(5)
    await flushPromises()

    expect(mockFetchMessages).toHaveBeenCalledWith(5)
  })

  /**
   * Checks that the loading text is displayed while DM messages are
   * being fetched from the API.
   */
  it('shows loading state when dmLoading is true', () => {
    mockDmLoading.value = true
    const wrapper = mountThread()

    expect(wrapper.text()).toContain('Loading messages...')
  })

  /**
   * Checks that the empty state message is shown when there are no
   * messages in the conversation.
   */
  it('shows empty state when there are no messages', () => {
    mockDmLoading.value = false
    mockDirectMessages.value = []
    const wrapper = mountThread()

    expect(wrapper.text()).toContain('No messages yet. Say hello!')
  })

  /**
   * Checks that messages from the other user are aligned to the left
   * (justify-start) in the thread layout.
   */
  it('aligns messages from other users to the left', () => {
    mockDirectMessages.value = [makeMessage({ sender_id: 2 })]
    const wrapper = mountThread()

    const msgRow = wrapper.find('.justify-start')
    expect(msgRow.exists()).toBe(true)
  })

  /**
   * Checks that messages from the current user are aligned to the right
   * (justify-end) in the thread layout.
   */
  it('aligns messages from the current user to the right', () => {
    mockDirectMessages.value = [makeMessage({ sender_id: 1 })]
    const wrapper = mountThread()

    const msgRow = wrapper.find('.justify-end')
    expect(msgRow.exists()).toBe(true)
  })

  /**
   * Checks that the message body text is rendered inside each message
   * bubble in the thread.
   */
  it('renders message body text', () => {
    mockDirectMessages.value = [
      makeMessage({ id: 1, body: 'First message' }),
      makeMessage({ id: 2, body: 'Second message' }),
    ]
    const wrapper = mountThread()

    expect(wrapper.text()).toContain('First message')
    expect(wrapper.text()).toContain('Second message')
  })

  /**
   * Checks that current user messages get the amber styling and other
   * user messages get the gray styling.
   */
  it('applies correct bubble styling based on sender', () => {
    mockDirectMessages.value = [
      makeMessage({ id: 1, sender_id: 1, body: 'My message' }),
      makeMessage({ id: 2, sender_id: 2, body: 'Their message' }),
    ]
    const wrapper = mountThread()

    const amberBubble = wrapper.find('.bg-amber-500\\/20')
    expect(amberBubble.exists()).toBe(true)

    const grayBubble = wrapper.find('.bg-gray-700\\/50')
    expect(grayBubble.exists()).toBe(true)
  })

  /**
   * Checks that sendMessage is called with the correct conversationId
   * and body when the MessageComposer emits a submit event.
   */
  it('calls sendMessage when the composer submits', async () => {
    const wrapper = mountThread(5)

    await wrapper.find('[data-testid="send-btn"]').trigger('click')
    await flushPromises()

    expect(mockSendMessage).toHaveBeenCalledWith(5, 'Hi there')
  })

  /**
   * Checks that the MessageComposer component is present at the bottom
   * of the thread for composing replies.
   */
  it('renders the MessageComposer at the bottom', () => {
    const wrapper = mountThread()

    expect(wrapper.find('.composer-stub').exists()).toBe(true)
  })

  /**
   * Checks that fetchMessages is called again with the new ID when the
   * conversationId prop changes.
   */
  it('refetches messages when conversationId changes', async () => {
    const wrapper = mountThread(5)
    await flushPromises()

    expect(mockFetchMessages).toHaveBeenCalledWith(5)

    await wrapper.setProps({ conversationId: 10 })
    await flushPromises()

    expect(mockFetchMessages).toHaveBeenCalledWith(10)
  })
})
