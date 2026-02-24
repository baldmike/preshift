/**
 * BoardTab.test.ts
 *
 * Unit tests for the BoardTab.vue component.
 *
 * Tests verify:
 *  1. Fetches board messages and subscribes to realtime events on mount.
 *  2. Shows loading state while board messages are being fetched.
 *  3. Shows empty state when there are no pinned or regular posts.
 *  4. Renders pinned and regular BoardPostCard components when posts exist.
 *  5. Shows the visibility toggle checkbox only for managers/admins.
 *  6. Hides the visibility toggle for regular staff.
 *  7. Calls createBoardMessage when a post is submitted.
 *  8. Calls createBoardMessage with managers visibility when toggle is checked.
 *  9. Calls deleteBoardMessage when a delete event is received.
 * 10. Calls togglePin when a pin event is received.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { ref, computed } from 'vue'
import BoardTab from '@/components/messages/BoardTab.vue'
import type { BoardMessage } from '@/types'

// ── Mock useAuth ──────────────────────────────────────────────────────────
const mockUser = ref({ id: 1, role: 'server' })
const mockIsAdmin = ref(false)
const mockIsManager = ref(false)
const mockLocationId = ref(1)

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: mockUser,
    isAdmin: mockIsAdmin,
    isManager: mockIsManager,
    locationId: mockLocationId,
  }),
}))

// ── Mock useMessages ──────────────────────────────────────────────────────
const mockPinnedPosts = ref<BoardMessage[]>([])
const mockRegularPosts = ref<BoardMessage[]>([])
const mockBoardLoading = ref(false)
const mockFetchBoardMessages = vi.fn()
const mockCreateBoardMessage = vi.fn()
const mockUpdateBoardMessage = vi.fn()
const mockDeleteBoardMessage = vi.fn()
const mockTogglePin = vi.fn()
const mockAddBoardMessage = vi.fn()
const mockUpdateBoardMessageLocal = vi.fn()
const mockRemoveBoardMessage = vi.fn()

vi.mock('@/composables/useMessages', () => ({
  useMessages: () => ({
    pinnedPosts: mockPinnedPosts,
    regularPosts: mockRegularPosts,
    boardLoading: mockBoardLoading,
    fetchBoardMessages: mockFetchBoardMessages,
    createBoardMessage: mockCreateBoardMessage,
    updateBoardMessage: mockUpdateBoardMessage,
    deleteBoardMessage: mockDeleteBoardMessage,
    togglePin: mockTogglePin,
    addBoardMessage: mockAddBoardMessage,
    updateBoardMessageLocal: mockUpdateBoardMessageLocal,
    removeBoardMessage: mockRemoveBoardMessage,
  }),
}))

// ── Mock useReverb ────────────────────────────────────────────────────────
const mockListen = vi.fn().mockReturnThis()
const mockStopListening = vi.fn()

vi.mock('@/composables/useReverb', () => ({
  useLocationChannel: () => ({
    listen: mockListen,
    stopListening: mockStopListening,
  }),
}))

// ── Stubs ─────────────────────────────────────────────────────────────────
const MessageComposerStub = {
  template: '<div class="composer-stub"><button data-testid="submit-btn" @click="$emit(\'submit\', \'Hello team\')">Send</button></div>',
  props: ['placeholder', 'loading'],
  emits: ['submit'],
}

const BoardPostCardStub = {
  template: '<div class="post-card-stub" :data-post-id="message.id"><button data-testid="delete-btn" @click="$emit(\'delete\', message.id)">Delete</button><button data-testid="pin-btn" @click="$emit(\'pin\', message.id)">Pin</button></div>',
  props: ['message'],
  emits: ['reply', 'edit', 'delete', 'pin'],
}

// ── Helper ────────────────────────────────────────────────────────────────
function makePost(overrides: Partial<BoardMessage> = {}): BoardMessage {
  return {
    id: 1,
    location_id: 1,
    user_id: 1,
    parent_id: null,
    body: 'Test post',
    visibility: 'all',
    pinned: false,
    user: { id: 1, name: 'Test User', role: 'server', email: 'test@test.com', location_id: 1, roles: null, is_superadmin: false, phone: null, availability: null, created_at: '', updated_at: '' },
    replies: [],
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    ...overrides,
  }
}

function mountBoardTab() {
  return mount(BoardTab, {
    global: {
      stubs: {
        MessageComposer: MessageComposerStub,
        BoardPostCard: BoardPostCardStub,
      },
    },
  })
}

describe('BoardTab', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    mockPinnedPosts.value = []
    mockRegularPosts.value = []
    mockBoardLoading.value = false
    mockIsAdmin.value = false
    mockIsManager.value = false
    mockLocationId.value = 1
  })

  /**
   * Verifies that fetchBoardMessages is called on mount to load initial data
   * and that realtime listeners are registered on the location channel.
   */
  it('fetches board messages and subscribes to realtime events on mount', () => {
    mountBoardTab()

    expect(mockFetchBoardMessages).toHaveBeenCalled()
    expect(mockListen).toHaveBeenCalledWith('.board-message.posted', expect.any(Function))
    expect(mockListen).toHaveBeenCalledWith('.board-message.updated', expect.any(Function))
    expect(mockListen).toHaveBeenCalledWith('.board-message.deleted', expect.any(Function))
  })

  /**
   * Verifies that the loading text is displayed while board messages
   * are being fetched from the API.
   */
  it('shows loading state while board messages are loading', () => {
    mockBoardLoading.value = true
    const wrapper = mountBoardTab()

    expect(wrapper.text()).toContain('Loading board...')
  })

  /**
   * Verifies that the empty state message is shown when there are
   * no pinned posts and no regular posts.
   */
  it('shows empty state when there are no posts', () => {
    mockBoardLoading.value = false
    mockPinnedPosts.value = []
    mockRegularPosts.value = []
    const wrapper = mountBoardTab()

    expect(wrapper.text()).toContain('No posts yet. Be the first to share!')
  })

  /**
   * Verifies that BoardPostCard components are rendered for both pinned
   * and regular posts when they exist.
   */
  it('renders pinned and regular posts', () => {
    mockBoardLoading.value = false
    mockPinnedPosts.value = [makePost({ id: 1, pinned: true })]
    mockRegularPosts.value = [makePost({ id: 2, pinned: false })]
    const wrapper = mountBoardTab()

    const cards = wrapper.findAll('.post-card-stub')
    expect(cards).toHaveLength(2)
    expect(cards[0].attributes('data-post-id')).toBe('1')
    expect(cards[1].attributes('data-post-id')).toBe('2')
  })

  /**
   * Verifies that the "Managers only" visibility checkbox is shown for
   * users with the manager role.
   */
  it('shows visibility toggle for managers', () => {
    mockIsManager.value = true
    const wrapper = mountBoardTab()

    expect(wrapper.text()).toContain('Managers only')
    expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true)
  })

  /**
   * Verifies that the "Managers only" visibility checkbox is also shown
   * for admin users.
   */
  it('shows visibility toggle for admins', () => {
    mockIsAdmin.value = true
    const wrapper = mountBoardTab()

    expect(wrapper.text()).toContain('Managers only')
  })

  /**
   * Verifies that the visibility checkbox is hidden from regular staff
   * (server/bartender roles).
   */
  it('hides visibility toggle for regular staff', () => {
    mockIsAdmin.value = false
    mockIsManager.value = false
    const wrapper = mountBoardTab()

    expect(wrapper.find('input[type="checkbox"]').exists()).toBe(false)
  })

  /**
   * Verifies that submitting a post via the MessageComposer calls
   * createBoardMessage with the expected body text.
   */
  it('calls createBoardMessage when a post is submitted', async () => {
    mockCreateBoardMessage.mockResolvedValue({})
    const wrapper = mountBoardTab()

    await wrapper.find('[data-testid="submit-btn"]').trigger('click')
    await flushPromises()

    expect(mockCreateBoardMessage).toHaveBeenCalledWith({ body: 'Hello team' })
  })

  /**
   * Verifies that when a manager has the visibility toggle checked to
   * "managers", the createBoardMessage payload includes visibility.
   */
  it('includes managers visibility when toggle is checked', async () => {
    mockIsManager.value = true
    mockCreateBoardMessage.mockResolvedValue({})
    const wrapper = mountBoardTab()

    const checkbox = wrapper.find('input[type="checkbox"]')
    await checkbox.setValue(true)
    await wrapper.find('[data-testid="submit-btn"]').trigger('click')
    await flushPromises()

    expect(mockCreateBoardMessage).toHaveBeenCalledWith({
      body: 'Hello team',
      visibility: 'managers',
    })
  })

  /**
   * Verifies that the delete handler calls deleteBoardMessage with
   * the correct post ID when a BoardPostCard emits the delete event.
   */
  it('calls deleteBoardMessage on delete event', async () => {
    mockDeleteBoardMessage.mockResolvedValue(undefined)
    mockBoardLoading.value = false
    mockRegularPosts.value = [makePost({ id: 5 })]
    const wrapper = mountBoardTab()

    await wrapper.find('[data-testid="delete-btn"]').trigger('click')
    await flushPromises()

    expect(mockDeleteBoardMessage).toHaveBeenCalledWith(5)
  })

  /**
   * Verifies that the pin handler calls togglePin with the correct
   * post ID when a BoardPostCard emits the pin event.
   */
  it('calls togglePin on pin event', async () => {
    mockTogglePin.mockResolvedValue(undefined)
    mockBoardLoading.value = false
    mockRegularPosts.value = [makePost({ id: 7 })]
    const wrapper = mountBoardTab()

    await wrapper.find('[data-testid="pin-btn"]').trigger('click')
    await flushPromises()

    expect(mockTogglePin).toHaveBeenCalledWith(7)
  })
})
