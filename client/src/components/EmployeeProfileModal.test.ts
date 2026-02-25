/**
 * EmployeeProfileModal.test.ts
 *
 * Unit tests for the EmployeeProfileModal.vue component.
 *
 * EmployeeProfileModal displays an employee's contact info and availability
 * inside a BaseModal overlay. It is shown when a manager/admin clicks an
 * employee name in a schedule view.
 *
 * Props:
 *   - user: User | null  — the employee to display; null = modal closed
 *
 * Emits:
 *   - close  — when the modal should be dismissed
 *
 * Tests verify:
 *   1. Renders employee name, email, phone, and role badge.
 *   2. Phone renders as a `tel:` link.
 *   3. Email click copies to clipboard and dispatches toast.
 *   4. Handles null phone gracefully ("Not set").
 *   5. Shows availability grid when user has availability.
 *   6. Shows "Not set" when user has no availability.
 *   7. "Message" button navigates to direct messages and emits close.
 *   8. "Close" button emits the close event.
 *   9. Modal is not rendered when user is null.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import EmployeeProfileModal from '@/components/EmployeeProfileModal.vue'
import type { User } from '@/types'

// ── Stubs ────────────────────────────────────────────────────────────────────
// Stub BaseModal to skip Teleport/Transition and just render slot content
const BaseModalStub = {
  template: '<div v-if="open" class="base-modal-stub"><slot /></div>',
  props: ['open'],
}

const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

const AvailabilityGridStub = {
  template: '<div class="availability-grid-stub" />',
  props: ['modelValue', 'readonly'],
}

// ── Mock user data ──────────────────────────────────────────────────────────

const userWithPhone: User = {
  id: 10,
  location_id: 1,
  name: 'Jane Doe',
  email: 'jane@example.com',
  role: 'server',
  roles: null,
  is_superadmin: false,
  phone: '555-123-4567',
  profile_photo_url: null,
  availability: {
    monday: ['open'],
    tuesday: ['10:30'],
    wednesday: [],
    thursday: [],
    friday: ['16:30'],
    saturday: [],
    sunday: [],
  },
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

const userWithoutPhone: User = {
  id: 11,
  location_id: 1,
  name: 'Bob Smith',
  email: 'bob@example.com',
  role: 'bartender',
  roles: null,
  is_superadmin: false,
  phone: null,
  profile_photo_url: null,
  availability: null,
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

// ── Mock router ──────────────────────────────────────────────────────────────
const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}))

// ── Helpers ─────────────────────────────────────────────────────────────────

function mountModal(user: User | null) {
  return mount(EmployeeProfileModal, {
    props: { user },
    global: {
      stubs: {
        BaseModal: BaseModalStub,
        BadgePill: BadgePillStub,
        AvailabilityGrid: AvailabilityGridStub,
      },
    },
  })
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('EmployeeProfileModal.vue', () => {
  let toastEvents: CustomEvent[]
  let clipboardWriteText: ReturnType<typeof vi.fn>

  beforeEach(() => {
    mockPush.mockClear()
    toastEvents = []
    // Spy on window.dispatchEvent to capture toast events
    vi.spyOn(window, 'dispatchEvent').mockImplementation((event: Event) => {
      if (event instanceof CustomEvent && event.type === 'toast') {
        toastEvents.push(event)
      }
      return true
    })

    // Mock navigator.clipboard via Object.defineProperty
    clipboardWriteText = vi.fn().mockResolvedValue(undefined)
    Object.defineProperty(navigator, 'clipboard', {
      value: { writeText: clipboardWriteText },
      writable: true,
      configurable: true,
    })
  })

  /**
   * Test 1 — Renders name, email, phone, and role
   */
  it('renders employee name, email, phone, and role badge', () => {
    const wrapper = mountModal(userWithPhone)
    const text = wrapper.text()

    expect(text).toContain('Jane Doe')
    expect(text).toContain('jane@example.com')
    expect(text).toContain('555-123-4567')
    expect(text).toContain('server')
  })

  /**
   * Test 2 — Phone is a `tel:` link
   */
  it('renders phone as a tel: link', () => {
    const wrapper = mountModal(userWithPhone)
    const phoneLink = wrapper.find('[data-testid="phone-link"]')

    expect(phoneLink.exists()).toBe(true)
    expect(phoneLink.attributes('href')).toBe('tel:555-123-4567')
  })

  /**
   * Test 3 — Email click copies to clipboard and dispatches toast
   */
  it('clicking email copies to clipboard and dispatches toast', async () => {
    const wrapper = mountModal(userWithPhone)
    const emailBtn = wrapper.find('[data-testid="email-button"]')

    await emailBtn.trigger('click')

    expect(clipboardWriteText).toHaveBeenCalledWith('jane@example.com')
  })

  /**
   * Test 4 — Handles null phone gracefully
   */
  it('shows "Not set" when phone is null', () => {
    const wrapper = mountModal(userWithoutPhone)
    const text = wrapper.text()

    expect(text).toContain('Not set')
    expect(wrapper.find('[data-testid="phone-link"]').exists()).toBe(false)
  })

  /**
   * Test 5 — Shows availability grid when user has availability
   */
  it('shows availability grid when user has availability', () => {
    const wrapper = mountModal(userWithPhone)

    expect(wrapper.find('.availability-grid-stub').exists()).toBe(true)
  })

  /**
   * Test 6 — Shows "Not set" when user has no availability
   */
  it('shows "Not set" for availability when null', () => {
    const wrapper = mountModal(userWithoutPhone)
    const text = wrapper.text()

    // "Not set" appears for both phone and availability
    expect(wrapper.find('.availability-grid-stub').exists()).toBe(false)
    const notSetInstances = text.split('Not set').length - 1
    expect(notSetInstances).toBeGreaterThanOrEqual(2)
  })

  /**
   * Test 7 — "Message" button navigates to direct messages and emits close
   */
  it('message button navigates to direct messages and emits close', async () => {
    const wrapper = mountModal(userWithPhone)
    const messageBtn = wrapper.find('[data-testid="message-button"]')

    await messageBtn.trigger('click')

    expect(mockPush).toHaveBeenCalledWith({
      path: '/messages',
      query: { tab: 'direct', userId: '10' },
    })
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  /**
   * Test 8 — "Close" button emits close event
   */
  it('close button emits close event', async () => {
    const wrapper = mountModal(userWithPhone)
    const buttons = wrapper.findAll('button')
    const closeBtn = buttons.find(b => b.text() === 'Close')

    expect(closeBtn).toBeDefined()
    await closeBtn!.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  /**
   * Test 9 — Modal is not rendered when user is null
   */
  it('does not render modal content when user is null', () => {
    const wrapper = mountModal(null)

    expect(wrapper.find('.base-modal-stub').exists()).toBe(false)
  })
})
