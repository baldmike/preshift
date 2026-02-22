/**
 * SpecialCard.test.ts
 *
 * Unit tests for the SpecialCard.vue component.
 *
 * SpecialCard renders a compact, blue-themed card for a single daily special
 * showing:
 *   - The special title as the heading
 *   - A type badge via BadgePill (e.g. "happy_hour", "lunch")
 *   - An optional description
 *   - A date range (starts_at and optional ends_at)
 *   - Remaining quantity when present
 *   - An AcknowledgeButton for staff confirmation
 *
 * Props:
 *   - special: Special
 *
 * Tests verify:
 *   1. The title is rendered as the heading.
 *   2. The type badge is shown via BadgePill when type is set.
 *   3. The description is shown when provided.
 *   4. The date range is displayed from starts_at and ends_at.
 *   5. The quantity is shown when present.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SpecialCard from '@/components/SpecialCard.vue'
import type { Special } from '@/types'

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

// ── Helper: build a Special with sensible defaults ──────────────────────────
function makeSpecial(overrides: Partial<Special> = {}): Special {
  return {
    id: 1,
    location_id: 1,
    menu_item_id: null,
    title: 'Happy Hour Margaritas',
    description: 'Half-price margaritas from 4-6 PM',
    type: 'happy_hour',
    starts_at: '2026-02-18T16:00:00Z',
    ends_at: '2026-02-18T18:00:00Z',
    is_active: true,
    quantity: null,
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

describe('SpecialCard.vue', () => {
  beforeEach(() => {
    mockIsAdmin.value = false
    mockIsManager.value = false
  })

  /**
   * Test 1 — Renders the title as the heading
   *
   * The card heading should display the special title so staff can
   * immediately identify the promotion.
   */
  it('renders title as the heading', () => {
    const special = makeSpecial({ title: 'Happy Hour Margaritas' })

    const wrapper = mount(SpecialCard, {
      props: { special },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    const heading = wrapper.find('h4')
    expect(heading.text()).toBe('Happy Hour Margaritas')
  })

  /**
   * Test 2 — Shows type badge when type is set
   *
   * When the special has a type value, a BadgePill should render next
   * to the title showing the type label.
   */
  it('shows type badge via BadgePill', () => {
    const special = makeSpecial({ type: 'happy_hour' })

    const wrapper = mount(SpecialCard, {
      props: { special },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    const badge = wrapper.find('.badge-pill-stub')
    expect(badge.exists()).toBe(true)
    expect(badge.text()).toBe('happy_hour')
  })

  /**
   * Test 3 — Shows description when provided
   *
   * When the special includes a description, it should be rendered in
   * the card body so staff knows the details.
   */
  it('shows description when provided', () => {
    const special = makeSpecial({ description: 'Half-price margaritas from 4-6 PM' })

    const wrapper = mount(SpecialCard, {
      props: { special },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.text()).toContain('Half-price margaritas from 4-6 PM')
  })

  /**
   * Test 4 — Shows the date range
   *
   * The card should display the start date, and when ends_at is set,
   * also show the end date separated by a dash.
   */
  it('shows date range from starts_at and ends_at', () => {
    const special = makeSpecial({
      starts_at: '2026-02-18T16:00:00Z',
      ends_at: '2026-02-20T18:00:00Z',
    })

    const wrapper = mount(SpecialCard, {
      props: { special },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    const text = wrapper.text()
    // Should contain formatted dates for both start and end
    expect(text).toContain('Feb')
    expect(text).toContain('18')
    expect(text).toContain('20')
  })

  /**
   * Test 5 — Shows quantity when present
   *
   * When the special has a limited quantity, the card should display
   * the number remaining (e.g. "5 left").
   */
  it('shows quantity when present', () => {
    const special = makeSpecial({ quantity: 5 })

    const wrapper = mount(SpecialCard, {
      props: { special },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.text()).toContain('5 left')
  })

  it('shows AcknowledgeButton for staff users', () => {
    const special = makeSpecial()

    const wrapper = mount(SpecialCard, {
      props: { special },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.find('.ack-stub').exists()).toBe(true)
    expect(wrapper.find('.router-link-stub').exists()).toBe(false)
  })

  it('shows Edit link instead of AcknowledgeButton for managers', () => {
    mockIsManager.value = true
    const special = makeSpecial()

    const wrapper = mount(SpecialCard, {
      props: { special },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.find('.router-link-stub').exists()).toBe(true)
    expect(wrapper.find('.router-link-stub').text()).toBe('Edit')
    expect(wrapper.find('.ack-stub').exists()).toBe(false)
  })

  it('shows Edit link instead of AcknowledgeButton for admins', () => {
    mockIsAdmin.value = true
    const special = makeSpecial()

    const wrapper = mount(SpecialCard, {
      props: { special },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub, RouterLink: RouterLinkStub } },
    })

    expect(wrapper.find('.router-link-stub').exists()).toBe(true)
    expect(wrapper.find('.router-link-stub').text()).toBe('Edit')
    expect(wrapper.find('.ack-stub').exists()).toBe(false)
  })
})
