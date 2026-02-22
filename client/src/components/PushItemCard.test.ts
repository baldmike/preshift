/**
 * PushItemCard.test.ts
 *
 * Unit tests for the PushItemCard.vue component.
 *
 * PushItemCard renders a compact, amber-themed card for a single push item
 * showing:
 *   - The item title as the heading
 *   - A color-coded priority badge (high/medium/low) via BadgePill
 *   - An optional description with talking points for staff
 *   - An optional reason management wants the item pushed
 *   - An AcknowledgeButton for staff confirmation
 *
 * Props:
 *   - item: PushItem
 *
 * Tests verify:
 *   1. The title is rendered as the heading.
 *   2. The priority badge is shown via BadgePill when priority is set.
 *   3. The description is shown when provided.
 *   4. The reason is shown when provided.
 *   5. The description is hidden when null.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import PushItemCard from '@/components/PushItemCard.vue'
import type { PushItem } from '@/types'

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

// ── Stub for the BadgePill child component ──────────────────────────────────
const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Stub for the AcknowledgeButton child component ──────────────────────────
const AcknowledgeButtonStub = {
  template: '<button class="ack-stub">ACK</button>',
  props: ['type', 'id', 'acknowledged', 'size'],
}

// ── Helper: build a PushItem with sensible defaults ─────────────────────────
function makeItem(overrides: Partial<PushItem> = {}): PushItem {
  return {
    id: 1,
    location_id: 1,
    menu_item_id: null,
    title: 'Truffle Fries',
    description: 'Great upsell with any entree',
    reason: 'Overstocked on truffle oil',
    priority: 'high',
    is_active: true,
    created_by: 10,
    created_at: '2026-02-18T10:00:00Z',
    updated_at: '2026-02-18T10:00:00Z',
    ...overrides,
  }
}

// ── Stub for RouterLink ──────────────────────────────────────────────────────
const RouterLinkStub = {
  template: '<a class="router-link-stub"><slot /></a>',
  props: ['to'],
}

describe('PushItemCard.vue', () => {
  beforeEach(() => {
    mockIsAdmin.value = false
    mockIsManager.value = false
  })

  /**
   * Test 1 — Renders the title as the heading
   *
   * The card heading should display the push item title so staff can
   * immediately identify which item to promote.
   */
  it('renders title as the heading', () => {
    const item = makeItem({ title: 'Truffle Fries' })

    const wrapper = mount(PushItemCard, {
      props: { item },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    const heading = wrapper.find('h4')
    expect(heading.text()).toBe('Truffle Fries')
  })

  /**
   * Test 2 — Shows priority badge when priority is set
   *
   * When the push item has a priority value, a BadgePill should render
   * next to the title showing the priority level.
   */
  it('shows priority badge via BadgePill', () => {
    const item = makeItem({ priority: 'high' })

    const wrapper = mount(PushItemCard, {
      props: { item },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    const badge = wrapper.find('.badge-pill-stub')
    expect(badge.exists()).toBe(true)
    expect(badge.text()).toBe('high')
  })

  /**
   * Test 3 — Shows description when provided
   *
   * When the push item includes a description, it should be rendered in
   * the card body to give staff talking points.
   */
  it('shows description when provided', () => {
    const item = makeItem({ description: 'Great upsell with any entree' })

    const wrapper = mount(PushItemCard, {
      props: { item },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.text()).toContain('Great upsell with any entree')
  })

  /**
   * Test 4 — Shows reason when provided
   *
   * When the push item includes a reason for the push, it should be
   * displayed so staff understands the motivation.
   */
  it('shows reason when provided', () => {
    const item = makeItem({ reason: 'Overstocked on truffle oil' })

    const wrapper = mount(PushItemCard, {
      props: { item },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.text()).toContain('Overstocked on truffle oil')
  })

  /**
   * Test 5 — Hides description when null
   *
   * When the description field is null, the description paragraph element
   * should not be rendered (v-if="item.description" guard).
   */
  it('hides description when null', () => {
    const item = makeItem({ description: null, reason: null })

    const wrapper = mount(PushItemCard, {
      props: { item },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    // Neither description nor reason paragraphs should be rendered
    const paragraphs = wrapper.findAll('p')
    expect(paragraphs).toHaveLength(0)
  })

  it('shows AcknowledgeButton for staff users', () => {
    const item = makeItem()

    const wrapper = mount(PushItemCard, {
      props: { item },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.find('.ack-stub').exists()).toBe(true)
    expect(wrapper.find('.router-link-stub').exists()).toBe(false)
  })

  it('shows Edit link instead of AcknowledgeButton for managers', () => {
    mockIsManager.value = true
    const item = makeItem()

    const wrapper = mount(PushItemCard, {
      props: { item },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.find('.router-link-stub').exists()).toBe(true)
    expect(wrapper.find('.router-link-stub').text()).toBe('Edit')
    expect(wrapper.find('.ack-stub').exists()).toBe(false)
  })

  it('shows Edit link instead of AcknowledgeButton for admins', () => {
    mockIsAdmin.value = true
    const item = makeItem()

    const wrapper = mount(PushItemCard, {
      props: { item },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.find('.router-link-stub').exists()).toBe(true)
    expect(wrapper.find('.router-link-stub').text()).toBe('Edit')
    expect(wrapper.find('.ack-stub').exists()).toBe(false)
  })
})
