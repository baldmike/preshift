/**
 * TileDetailModal.test.ts
 *
 * Unit tests for the TileDetailModal.vue component.
 *
 * TileDetailModal displays the full, untruncated content of a dashboard tile
 * (86'd Items, Specials, Push Items, or Announcements) inside a BaseModal.
 * It is opened when a user clicks a tile header on the dashboard.
 *
 * Props:
 *   - tileType: 'eightySixed' | 'specials' | 'pushItems' | 'announcements' | null
 *               (null = modal closed)
 *
 * Emits:
 *   - close — when the modal should be dismissed
 *
 * Tests verify:
 *   1. Modal does not render when tileType is null.
 *   2. Modal renders with correct title for each tile type.
 *   3. Items display full content (no truncation).
 *   4. Staff see AcknowledgeButtons (not edit controls).
 *   5. Managers/admins see edit and delete buttons but no AcknowledgeButtons.
 *   6. Empty tile shows empty state message.
 *   7. Close button emits close event.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import TileDetailModal from '@/components/TileDetailModal.vue'
import { usePreshiftStore } from '@/stores/preshift'

// ── Mock for useApi ─────────────────────────────────────────────────────────
vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

// ── Mock for the useAcknowledgments composable ──────────────────────────────
vi.mock('@/composables/useAcknowledgments', () => ({
  useAcknowledgments: () => ({
    acknowledge: vi.fn().mockResolvedValue(undefined),
    isAcknowledged: vi.fn().mockReturnValue(false),
  }),
}))

// ── Mock for the useAuth composable ─────────────────────────────────────────
const mockIsAdmin = { value: false }
const mockIsManager = { value: false }
vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: { value: null },
    isLoggedIn: { value: true },
    isAdmin: mockIsAdmin,
    isManager: mockIsManager,
    isStaff: { value: true },
    isSuperAdmin: { value: false },
    locationId: { value: 1 },
  }),
}))

// ── Stubs ────────────────────────────────────────────────────────────────────
const BaseModalStub = {
  template: '<div v-if="open" class="base-modal-stub"><slot /></div>',
  props: ['open', 'size'],
}

const AcknowledgeButtonStub = {
  template: '<button class="ack-stub">ACK</button>',
  props: ['type', 'id', 'acknowledged', 'size'],
}

const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Test data ───────────────────────────────────────────────────────────────

function makeEightySixed(overrides = {}) {
  return {
    id: 1,
    location_id: 1,
    menu_item_id: null,
    item_name: 'Salmon',
    reason: 'Ran out of fresh stock this morning',
    eighty_sixed_by: 10,
    restored_at: null,
    user: { id: 10, name: 'Chef Mike', email: 'mike@example.com', role: 'manager', roles: null, is_superadmin: false, phone: null, availability: null, location_id: 1, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
    created_at: '2026-02-22T10:00:00Z',
    updated_at: '2026-02-22T10:00:00Z',
    ...overrides,
  }
}

function makeSpecial(overrides = {}) {
  return {
    id: 1,
    location_id: 1,
    menu_item_id: null,
    title: 'Happy Hour Margaritas',
    description: 'Half price margaritas from 4-6 PM every weekday',
    type: 'happy_hour',
    starts_at: '2026-02-22T00:00:00Z',
    ends_at: '2026-02-28T00:00:00Z',
    is_active: true,
    quantity: 50,
    created_by: 10,
    created_at: '2026-02-22T10:00:00Z',
    updated_at: '2026-02-22T10:00:00Z',
    ...overrides,
  }
}

function makePushItem(overrides = {}) {
  return {
    id: 1,
    location_id: 1,
    menu_item_id: null,
    title: 'House Red Wine',
    description: 'We have a surplus of the 2023 Cabernet Sauvignon that needs to move',
    reason: 'Overstocked',
    priority: 'high',
    is_active: true,
    created_by: 10,
    created_at: '2026-02-22T10:00:00Z',
    updated_at: '2026-02-22T10:00:00Z',
    ...overrides,
  }
}

function makeAnnouncement(overrides = {}) {
  return {
    id: 1,
    location_id: 1,
    title: 'Staff Meeting Tomorrow',
    body: 'All hands meeting at 3 PM in the break room. Attendance is mandatory.',
    priority: 'urgent',
    target_roles: null,
    posted_by: 10,
    expires_at: '2026-02-28T23:59:00Z',
    poster: { id: 10, name: 'Manager Sarah', email: 'sarah@example.com', role: 'manager', roles: null, is_superadmin: false, phone: null, availability: null, location_id: 1, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
    created_at: '2026-02-22T10:00:00Z',
    updated_at: '2026-02-22T10:00:00Z',
    ...overrides,
  }
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function mountModal(tileType: string | null) {
  return mount(TileDetailModal, {
    props: { tileType: tileType as any },
    global: {
      stubs: {
        BaseModal: BaseModalStub,
        AcknowledgeButton: AcknowledgeButtonStub,
        BadgePill: BadgePillStub,
      },
    },
  })
}

// ── Tests ────────────────────────────────────────────────────────────────────

describe('TileDetailModal.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockIsAdmin.value = false
    mockIsManager.value = false
  })

  /**
   * Test 1 — Modal does not render when tileType is null
   *
   * When tileType is null, the BaseModal's open prop is false so no
   * content should be rendered.
   */
  it('does not render when tileType is null', () => {
    const wrapper = mountModal(null)

    expect(wrapper.find('.base-modal-stub').exists()).toBe(false)
  })

  /**
   * Test 2 — Modal renders with correct title for each tile type
   *
   * Each tile type should display its corresponding title in the
   * modal header.
   */
  it('renders correct title for eightySixed', () => {
    const store = usePreshiftStore()
    store.eightySixed = [makeEightySixed()] as any

    const wrapper = mountModal('eightySixed')
    expect(wrapper.find('h2').text()).toBe("86'd Items")
  })

  it('renders correct title for specials', () => {
    const store = usePreshiftStore()
    store.specials = [makeSpecial()] as any

    const wrapper = mountModal('specials')
    expect(wrapper.find('h2').text()).toBe('Specials')
  })

  it('renders correct title for pushItems', () => {
    const store = usePreshiftStore()
    store.pushItems = [makePushItem()] as any

    const wrapper = mountModal('pushItems')
    expect(wrapper.find('h2').text()).toBe('Push Items')
  })

  it('renders correct title for announcements', () => {
    const store = usePreshiftStore()
    store.announcements = [makeAnnouncement()] as any

    const wrapper = mountModal('announcements')
    expect(wrapper.find('h2').text()).toBe('Announcements')
  })

  /**
   * Test 3 — Items display full content (no truncation)
   *
   * Unlike the dashboard cards which use line-clamp-2, the modal should
   * show the complete text of each item's description/reason/body.
   */
  it('displays full content without truncation for eightySixed', () => {
    const store = usePreshiftStore()
    store.eightySixed = [makeEightySixed({ reason: 'Ran out of fresh stock this morning' })] as any

    const wrapper = mountModal('eightySixed')
    const text = wrapper.text()

    expect(text).toContain('Salmon')
    expect(text).toContain('Ran out of fresh stock this morning')
    // Verify no line-clamp class is applied
    expect(wrapper.html()).not.toContain('line-clamp')
  })

  it('displays full content without truncation for announcements', () => {
    const store = usePreshiftStore()
    store.announcements = [makeAnnouncement({ body: 'All hands meeting at 3 PM in the break room. Attendance is mandatory.' })] as any

    const wrapper = mountModal('announcements')
    const text = wrapper.text()

    expect(text).toContain('All hands meeting at 3 PM in the break room. Attendance is mandatory.')
    expect(wrapper.html()).not.toContain('line-clamp')
  })

  /**
   * Test 4 — Staff see AcknowledgeButtons (not edit controls)
   *
   * When the current user is a staff member (server/bartender), each
   * item should render an AcknowledgeButton but no edit/delete buttons.
   */
  it('shows AcknowledgeButtons for staff users', () => {
    const store = usePreshiftStore()
    store.eightySixed = [makeEightySixed()] as any

    const wrapper = mountModal('eightySixed')

    expect(wrapper.find('.ack-stub').exists()).toBe(true)
    expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(false)
  })

  /**
   * Test 5 — Managers see edit and delete buttons but no AcknowledgeButtons
   *
   * When the current user is a manager, each item should render
   * edit/delete controls but NOT the AcknowledgeButton.
   */
  it('shows edit and delete buttons for managers without AcknowledgeButton', () => {
    mockIsManager.value = true
    const store = usePreshiftStore()
    store.specials = [makeSpecial()] as any

    const wrapper = mountModal('specials')

    expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(true)
    expect(wrapper.find('.ack-stub').exists()).toBe(false)
  })

  it('shows edit and delete buttons for admins without AcknowledgeButton', () => {
    mockIsAdmin.value = true
    const store = usePreshiftStore()
    store.pushItems = [makePushItem()] as any

    const wrapper = mountModal('pushItems')

    expect(wrapper.find('[data-testid="edit-button"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="delete-button"]').exists()).toBe(true)
    expect(wrapper.find('.ack-stub').exists()).toBe(false)
  })

  /**
   * Test 6 — Empty tile shows empty state message
   *
   * When the store array for a tile type is empty, the modal should
   * display the appropriate empty state text.
   */
  it('shows empty state for eightySixed', () => {
    usePreshiftStore() // initialize empty store

    const wrapper = mountModal('eightySixed')

    expect(wrapper.find('[data-testid="empty-state"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="empty-state"]').text()).toBe("Nothing 86'd")
  })

  it('shows empty state for specials', () => {
    usePreshiftStore()

    const wrapper = mountModal('specials')
    expect(wrapper.find('[data-testid="empty-state"]').text()).toBe('No specials today')
  })

  it('shows empty state for pushItems', () => {
    usePreshiftStore()

    const wrapper = mountModal('pushItems')
    expect(wrapper.find('[data-testid="empty-state"]').text()).toBe('No push items')
  })

  it('shows empty state for announcements', () => {
    usePreshiftStore()

    const wrapper = mountModal('announcements')
    expect(wrapper.find('[data-testid="empty-state"]').text()).toBe('No announcements')
  })

  /**
   * Test 7 — Close button emits close event
   *
   * Clicking the X close button in the modal header should emit the
   * close event so the parent can set tileType back to null.
   */
  it('close button emits close event', async () => {
    const store = usePreshiftStore()
    store.eightySixed = [makeEightySixed()] as any

    const wrapper = mountModal('eightySixed')
    const closeBtn = wrapper.findAll('button').find(b => {
      // The close button contains the X SVG icon
      return b.find('svg path[d*="M6 18L18 6"]').exists()
    })

    expect(closeBtn).toBeDefined()
    await closeBtn!.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })
})
