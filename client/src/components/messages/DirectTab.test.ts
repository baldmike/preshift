/**
 * DirectTab.test.ts
 *
 * Unit tests for the DirectTab.vue component.
 *
 * Tests verify:
 *  1. Fetches conversations on mount.
 *  2. Shows loading state while conversations are being fetched.
 *  3. Shows empty state when there are no conversations.
 *  4. Renders ConversationListItem for each conversation.
 *  5. Shows the "New Message" button.
 *  6. Opens the user picker and fetches users when "New Message" is clicked.
 *  7. Filters users in the picker by search input.
 *  8. Creates a conversation when a user is selected from the picker.
 *  9. Shows the ConversationThread when a conversation is active.
 * 10. Shows the placeholder message when no conversation is selected.
 * 11. Subscribes to realtime DM events on mount.
 * 12. Auto-opens a conversation when initialUserId is provided.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { ref, reactive } from 'vue'
import DirectTab from '@/components/messages/DirectTab.vue'
import type { Conversation, User } from '@/types'

// ── Mock useApi ───────────────────────────────────────────────────────────
const mockApiGet = vi.fn()
const mockApiPost = vi.fn()

vi.mock('@/composables/useApi', () => ({
  default: {
    get: (...args: unknown[]) => mockApiGet(...args),
    post: (...args: unknown[]) => mockApiPost(...args),
  },
}))

// ── Mock useAuth ──────────────────────────────────────────────────────────
const mockUser = ref<{ id: number; role: string; name: string } | null>({ id: 1, role: 'server', name: 'Current User' })
const mockLocationId = ref<number | null>(1)

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: mockUser,
    locationId: mockLocationId,
  }),
}))

// ── Mock useMessages ──────────────────────────────────────────────────────
const mockConversations = ref<Conversation[]>([])
const mockActiveConversationId = ref<number | null>(null)
const mockDmLoading = ref(false)
const mockFetchConversations = vi.fn()
const mockFindOrCreateConversation = vi.fn()
const mockPushDirectMessage = vi.fn()
const mockFetchUnreadCount = vi.fn()

vi.mock('@/composables/useMessages', () => ({
  useMessages: () => ({
    conversations: mockConversations,
    activeConversationId: mockActiveConversationId,
    dmLoading: mockDmLoading,
    fetchConversations: mockFetchConversations,
    findOrCreateConversation: mockFindOrCreateConversation,
    pushDirectMessage: mockPushDirectMessage,
    fetchUnreadCount: mockFetchUnreadCount,
  }),
}))

// ── Mock message store ────────────────────────────────────────────────────
const mockStore = reactive({ activeConversationId: null as number | null })

vi.mock('@/stores/messages', () => ({
  useMessageStore: () => mockStore,
}))

// ── Mock useReverb ────────────────────────────────────────────────────────
const mockListen = vi.fn().mockReturnThis()
const mockStopListening = vi.fn()

vi.mock('@/composables/useReverb', () => ({
  useUserChannel: () => ({
    listen: mockListen,
    stopListening: mockStopListening,
  }),
}))

// ── Stubs ─────────────────────────────────────────────────────────────────
const ConversationListItemStub = {
  template: '<div class="conv-list-item-stub" :data-conv-id="conversation.id"><button data-testid="select-conv" @click="$emit(\'select\', conversation.id)">Select</button></div>',
  props: ['conversation', 'active'],
  emits: ['select'],
}

const ConversationThreadStub = {
  template: '<div class="conv-thread-stub" :data-conv-id="conversationId">Thread</div>',
  props: ['conversationId'],
}

// ── Helpers ───────────────────────────────────────────────────────────────

/** Creates a minimal User object */
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

/** Creates a minimal Conversation object */
function makeConversation(overrides: Partial<Conversation> = {}): Conversation {
  return {
    id: 10,
    location_id: 1,
    participants: [
      makeUser({ id: 1, name: 'Current User' }),
      makeUser({ id: 2, name: 'Jane Doe' }),
    ],
    latest_message: null,
    unread_count: 0,
    created_at: '',
    updated_at: '',
    ...overrides,
  }
}

function mountDirectTab(props: Record<string, unknown> = {}) {
  return mount(DirectTab, {
    props: {
      initialUserId: null,
      ...props,
    },
    global: {
      stubs: {
        ConversationListItem: ConversationListItemStub,
        ConversationThread: ConversationThreadStub,
      },
    },
  })
}

describe('DirectTab', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockConversations.value = []
    mockActiveConversationId.value = null
    mockDmLoading.value = false
    mockStore.activeConversationId = null
    mockFetchConversations.mockResolvedValue(undefined)
    mockFindOrCreateConversation.mockResolvedValue(makeConversation())
    mockUser.value = { id: 1, role: 'server', name: 'Current User' }
    mockLocationId.value = 1
  })

  /**
   * Checks that fetchConversations is called on mount to load the
   * conversation list.
   */
  it('fetches conversations on mount', async () => {
    mountDirectTab()
    await flushPromises()

    expect(mockFetchConversations).toHaveBeenCalled()
  })

  /**
   * Checks that the loading text is displayed while conversations
   * are being loaded from the API.
   */
  it('shows loading state while conversations are loading', async () => {
    mockDmLoading.value = true
    mockConversations.value = []
    const wrapper = mountDirectTab()
    await flushPromises()

    expect(wrapper.text()).toContain('Loading conversations...')
  })

  /**
   * Checks that the empty state message is shown when the user has
   * no conversations yet.
   */
  it('shows empty state when there are no conversations', async () => {
    mockDmLoading.value = false
    mockConversations.value = []
    const wrapper = mountDirectTab()
    await flushPromises()

    expect(wrapper.text()).toContain('No conversations yet.')
    expect(wrapper.text()).toContain('Tap "New Message" to start one.')
  })

  /**
   * Checks that ConversationListItem components are rendered for each
   * conversation in the list.
   */
  it('renders a ConversationListItem for each conversation', async () => {
    mockConversations.value = [
      makeConversation({ id: 10 }),
      makeConversation({ id: 20 }),
    ]
    const wrapper = mountDirectTab()
    await flushPromises()

    const items = wrapper.findAll('.conv-list-item-stub')
    expect(items).toHaveLength(2)
    expect(items[0].attributes('data-conv-id')).toBe('10')
    expect(items[1].attributes('data-conv-id')).toBe('20')
  })

  /**
   * Checks that the "New Message" button is present for starting new
   * conversations.
   */
  it('shows the New Message button', async () => {
    const wrapper = mountDirectTab()
    await flushPromises()

    expect(wrapper.text()).toContain('New Message')
  })

  /**
   * Checks that clicking "New Message" opens the user picker panel and
   * fetches the list of users from the API.
   */
  it('opens user picker and fetches users on New Message click', async () => {
    const users = [
      makeUser({ id: 2, name: 'Jane Doe', role: 'server' }),
      makeUser({ id: 3, name: 'Bob Smith', role: 'manager' }),
    ]
    mockApiGet.mockResolvedValue({ data: users })
    const wrapper = mountDirectTab()
    await flushPromises()

    const newMsgBtn = wrapper.findAll('button').find(b => b.text().includes('New Message'))
    expect(newMsgBtn).toBeTruthy()

    await newMsgBtn!.trigger('click')
    await flushPromises()

    expect(mockApiGet).toHaveBeenCalledWith('/api/users')
    expect(wrapper.text()).toContain('Choose Recipient')
    expect(wrapper.text()).toContain('Jane Doe')
    expect(wrapper.text()).toContain('Bob Smith')
  })

  /**
   * Checks that the user list in the picker excludes the current user
   * and can be filtered by the search input.
   */
  it('filters users in the picker by search input', async () => {
    const users = [
      makeUser({ id: 1, name: 'Current User', role: 'server' }),
      makeUser({ id: 2, name: 'Jane Doe', role: 'server' }),
      makeUser({ id: 3, name: 'Bob Smith', role: 'manager' }),
    ]
    mockApiGet.mockResolvedValue({ data: users })
    const wrapper = mountDirectTab()
    await flushPromises()

    const newMsgBtn = wrapper.findAll('button').find(b => b.text().includes('New Message'))
    await newMsgBtn!.trigger('click')
    await flushPromises()

    // Current user (id: 1) should be excluded from the picker
    const userButtons = wrapper.findAll('button.text-left')
    const names = userButtons.map(b => b.text())
    expect(names.some(n => n.includes('Current User'))).toBe(false)

    // Type in the search box to filter
    const searchInput = wrapper.find('input[type="text"]')
    await searchInput.setValue('jane')
    await flushPromises()

    expect(wrapper.text()).toContain('Jane Doe')
    expect(wrapper.text()).not.toContain('Bob Smith')
  })

  /**
   * Checks that selecting a user from the picker calls
   * findOrCreateConversation with that user's ID.
   */
  it('creates a conversation when a user is selected from the picker', async () => {
    const users = [makeUser({ id: 2, name: 'Jane Doe', role: 'server' })]
    mockApiGet.mockResolvedValue({ data: users })
    mockFindOrCreateConversation.mockResolvedValue(makeConversation({ id: 99 }))

    const wrapper = mountDirectTab()
    await flushPromises()

    const newMsgBtn = wrapper.findAll('button').find(b => b.text().includes('New Message'))
    await newMsgBtn!.trigger('click')
    await flushPromises()

    // Click the user button in the picker
    const userBtn = wrapper.findAll('button').find(b => b.text().includes('Jane Doe'))
    expect(userBtn).toBeTruthy()
    await userBtn!.trigger('click')
    await flushPromises()

    expect(mockFindOrCreateConversation).toHaveBeenCalledWith(2)
  })

  /**
   * Checks that the placeholder message is shown when no conversation
   * is currently selected.
   */
  it('shows placeholder when no conversation is selected', async () => {
    mockActiveConversationId.value = null
    const wrapper = mountDirectTab()
    await flushPromises()

    expect(wrapper.text()).toContain('Select a conversation to start messaging.')
  })

  /**
   * Checks that the ConversationThread component is rendered when a
   * conversation is actively selected.
   */
  it('shows ConversationThread when a conversation is active', async () => {
    mockActiveConversationId.value = 10
    const wrapper = mountDirectTab()
    await flushPromises()

    const thread = wrapper.find('.conv-thread-stub')
    expect(thread.exists()).toBe(true)
    expect(thread.attributes('data-conv-id')).toBe('10')
  })

  /**
   * Checks that realtime event listeners are registered on the user's
   * private channel on mount.
   */
  it('subscribes to realtime DM events on mount', async () => {
    mountDirectTab()
    await flushPromises()

    expect(mockListen).toHaveBeenCalledWith('.direct-message.sent', expect.any(Function))
  })

  /**
   * Checks that when initialUserId is provided, the component auto-finds
   * or creates a conversation with that user on mount.
   */
  it('auto-opens a conversation when initialUserId is provided', async () => {
    mockFindOrCreateConversation.mockResolvedValue(makeConversation({ id: 50 }))

    mountDirectTab({ initialUserId: 3 })
    await flushPromises()

    expect(mockFindOrCreateConversation).toHaveBeenCalledWith(3)
  })

  /**
   * Checks that the user picker can be closed by clicking the close
   * button without selecting a recipient.
   */
  it('closes the user picker without selecting a user', async () => {
    const users = [makeUser({ id: 2, name: 'Jane Doe', role: 'server' })]
    mockApiGet.mockResolvedValue({ data: users })

    const wrapper = mountDirectTab()
    await flushPromises()

    // Open the picker
    const newMsgBtn = wrapper.findAll('button').find(b => b.text().includes('New Message'))
    await newMsgBtn!.trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Choose Recipient')

    // Find the close button (contains the X SVG path)
    const closeBtn = wrapper.findAll('button').find(b => {
      return b.find('path[d="M6 18L18 6M6 6l12 12"]').exists()
    })
    expect(closeBtn).toBeTruthy()
    await closeBtn!.trigger('click')
    await flushPromises()

    // Picker should be closed
    expect(wrapper.text()).not.toContain('Choose Recipient')
  })
})
