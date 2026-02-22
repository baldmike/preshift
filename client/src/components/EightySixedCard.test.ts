/**
 * EightySixedCard.test.ts
 *
 * Unit tests for the EightySixedCard.vue component.
 *
 * EightySixedCard renders a compact, red-themed card for a single 86'd item
 * showing:
 *   - The item name as the heading
 *   - An optional reason the item was 86'd
 *   - The name of the user who flagged the item
 *   - A timestamp
 *   - An AcknowledgeButton so staff can mark the item as seen
 *
 * Props:
 *   - item: EightySixed
 *
 * Tests verify:
 *   1. The item_name is rendered as the heading.
 *   2. The reason is shown when provided.
 *   3. The reason is hidden when null.
 *   4. The user name is displayed when the user relationship is loaded.
 *   5. The AcknowledgeButton child component is rendered.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import EightySixedCard from '@/components/EightySixedCard.vue'
import type { EightySixed } from '@/types'

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

// ── Stub for the AcknowledgeButton child component ──────────────────────────
const AcknowledgeButtonStub = {
  template: '<button class="ack-stub">ACK</button>',
  props: ['type', 'id', 'acknowledged', 'size'],
}

// ── Helper: build an EightySixed item with sensible defaults ────────────────
function makeItem(overrides: Partial<EightySixed> = {}): EightySixed {
  return {
    id: 1,
    location_id: 1,
    menu_item_id: null,
    item_name: 'Salmon',
    reason: 'Ran out of fresh salmon',
    eighty_sixed_by: 10,
    restored_at: null,
    user: {
      id: 10,
      location_id: 1,
      name: 'Chef Mike',
      email: 'mike@example.com',
      role: 'manager',
      roles: null,
      is_superadmin: false,
      phone: null,
      availability: null,
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
    created_at: '2026-02-18T14:30:00Z',
    updated_at: '2026-02-18T14:30:00Z',
    ...overrides,
  }
}

// ── Stub for RouterLink ──────────────────────────────────────────────────────
const RouterLinkStub = {
  template: '<a class="router-link-stub"><slot /></a>',
  props: ['to'],
}

describe('EightySixedCard.vue', () => {
  beforeEach(() => {
    mockIsAdmin.value = false
    mockIsManager.value = false
  })

  /**
   * Test 1 — Renders the item_name as the heading
   *
   * The card heading should display the name of the 86'd item so staff
   * can immediately identify what is unavailable.
   */
  it('renders item_name as the heading', () => {
    const item = makeItem({ item_name: 'Salmon' })

    const wrapper = mount(EightySixedCard, {
      props: { item },
      global: { stubs: { AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    const heading = wrapper.find('h4')
    expect(heading.text()).toBe('Salmon')
  })

  /**
   * Test 2 — Shows reason when provided
   *
   * When the 86'd item has a reason string, it should be visible in
   * the card body so staff understands why the item is unavailable.
   */
  it('shows reason when provided', () => {
    const item = makeItem({ reason: 'Ran out of fresh salmon' })

    const wrapper = mount(EightySixedCard, {
      props: { item },
      global: { stubs: { AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.text()).toContain('Ran out of fresh salmon')
  })

  /**
   * Test 3 — Hides reason when null
   *
   * When the reason field is null, the reason paragraph element should
   * not be rendered at all (v-if="item.reason" guard).
   */
  it('hides reason when null', () => {
    const item = makeItem({ reason: null })

    const wrapper = mount(EightySixedCard, {
      props: { item },
      global: { stubs: { AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    // The only <p> in the template is the reason paragraph; it should not exist
    const reasonParagraph = wrapper.find('p')
    expect(reasonParagraph.exists()).toBe(false)
  })

  /**
   * Test 4 — Shows the user name
   *
   * When the user relationship is eagerly loaded, the card should display
   * the name of the staff member who flagged the item as 86'd.
   */
  it('shows user name when user relationship is loaded', () => {
    const item = makeItem()

    const wrapper = mount(EightySixedCard, {
      props: { item },
      global: { stubs: { AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.text()).toContain('Chef Mike')
  })

  /**
   * Test 5 — Renders the AcknowledgeButton
   *
   * The card should include an AcknowledgeButton child component so staff
   * can confirm they have seen the 86'd item.
   */
  it('renders AcknowledgeButton', () => {
    const item = makeItem()

    const wrapper = mount(EightySixedCard, {
      props: { item },
      global: { stubs: { AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    const ackButton = wrapper.find('.ack-stub')
    expect(ackButton.exists()).toBe(true)
  })

  it('shows AcknowledgeButton for staff users', () => {
    const item = makeItem()

    const wrapper = mount(EightySixedCard, {
      props: { item },
      global: { stubs: { AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.find('.ack-stub').exists()).toBe(true)
    expect(wrapper.find('.router-link-stub').exists()).toBe(false)
  })

  it('shows Edit link instead of AcknowledgeButton for managers', () => {
    mockIsManager.value = true
    const item = makeItem()

    const wrapper = mount(EightySixedCard, {
      props: { item },
      global: { stubs: { AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.find('.router-link-stub').exists()).toBe(true)
    expect(wrapper.find('.router-link-stub').text()).toBe('Edit')
    expect(wrapper.find('.ack-stub').exists()).toBe(false)
  })

  it('shows Edit link instead of AcknowledgeButton for admins', () => {
    mockIsAdmin.value = true
    const item = makeItem()

    const wrapper = mount(EightySixedCard, {
      props: { item },
      global: { stubs: { AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.find('.router-link-stub').exists()).toBe(true)
    expect(wrapper.find('.router-link-stub').text()).toBe('Edit')
    expect(wrapper.find('.ack-stub').exists()).toBe(false)
  })
})
