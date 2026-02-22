/**
 * AnnouncementCard.test.ts
 *
 * Unit tests for the AnnouncementCard.vue component.
 *
 * AnnouncementCard renders a compact, purple-themed card for a single
 * announcement showing:
 *   - The announcement title as the heading
 *   - A color-coded priority badge (urgent/important/normal) via BadgePill
 *   - Optional body text (may contain markdown)
 *   - The poster's name (who published the announcement)
 *   - An expiration date when set
 *   - An AcknowledgeButton so staff can mark the announcement as read
 *
 * Props:
 *   - announcement: Announcement
 *
 * Tests verify:
 *   1. The title is rendered as the heading.
 *   2. The priority badge is shown via BadgePill when priority is set.
 *   3. The body text is shown when provided.
 *   4. The poster name is displayed when the poster relationship is loaded.
 *   5. The expiration date is displayed when expires_at is set.
 */

import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AnnouncementCard from '@/components/AnnouncementCard.vue'
import type { Announcement } from '@/types'

// ── Mock for the useAcknowledgments composable ──────────────────────────────
vi.mock('@/composables/useAcknowledgments', () => ({
  useAcknowledgments: () => ({
    acknowledge: vi.fn().mockResolvedValue(undefined),
    isAcknowledged: vi.fn().mockReturnValue(false),
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

// ── Helper: build an Announcement with sensible defaults ────────────────────
function makeAnnouncement(overrides: Partial<Announcement> = {}): Announcement {
  return {
    id: 1,
    location_id: 1,
    title: 'Staff Meeting Tomorrow',
    body: 'All hands meeting at 3 PM in the break room.',
    priority: 'urgent',
    target_roles: null,
    posted_by: 10,
    expires_at: '2026-02-28T23:59:00Z',
    poster: {
      id: 10,
      location_id: 1,
      name: 'Manager Sarah',
      email: 'sarah@example.com',
      role: 'manager',
      roles: null,
      is_superadmin: false,
      phone: null,
      availability: null,
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
    created_at: '2026-02-18T09:00:00Z',
    updated_at: '2026-02-18T09:00:00Z',
    ...overrides,
  }
}

describe('AnnouncementCard.vue', () => {
  /**
   * Test 1 — Renders the title as the heading
   *
   * The card heading should display the announcement title so staff can
   * immediately see the subject.
   */
  it('renders title as the heading', () => {
    const announcement = makeAnnouncement({ title: 'Staff Meeting Tomorrow' })

    const wrapper = mount(AnnouncementCard, {
      props: { announcement },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub } },
    })

    const heading = wrapper.find('h4')
    expect(heading.text()).toBe('Staff Meeting Tomorrow')
  })

  /**
   * Test 2 — Shows priority badge when priority is set
   *
   * When the announcement has a priority value, a BadgePill should render
   * next to the title showing the priority level.
   */
  it('shows priority badge via BadgePill', () => {
    const announcement = makeAnnouncement({ priority: 'urgent' })

    const wrapper = mount(AnnouncementCard, {
      props: { announcement },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub } },
    })

    const badge = wrapper.find('.badge-pill-stub')
    expect(badge.exists()).toBe(true)
    expect(badge.text()).toBe('urgent')
  })

  /**
   * Test 3 — Shows body text when provided
   *
   * When the announcement includes body text, it should be rendered in
   * the card so staff can read the full message.
   */
  it('shows body text when provided', () => {
    const announcement = makeAnnouncement({ body: 'All hands meeting at 3 PM in the break room.' })

    const wrapper = mount(AnnouncementCard, {
      props: { announcement },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub } },
    })

    expect(wrapper.text()).toContain('All hands meeting at 3 PM in the break room.')
  })

  /**
   * Test 4 — Shows poster name when poster relationship is loaded
   *
   * When the poster relationship is eagerly loaded, the card should
   * display the name of the manager who posted the announcement.
   */
  it('shows poster name', () => {
    const announcement = makeAnnouncement()

    const wrapper = mount(AnnouncementCard, {
      props: { announcement },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub } },
    })

    expect(wrapper.text()).toContain('Manager Sarah')
  })

  /**
   * Test 5 — Shows expiration date when expires_at is set
   *
   * When the announcement has an expiration datetime, the card should
   * display it in a formatted string prefixed with "Exp".
   */
  it('shows expiration date when expires_at is set', () => {
    const announcement = makeAnnouncement({ expires_at: '2026-02-28T23:59:00Z' })

    const wrapper = mount(AnnouncementCard, {
      props: { announcement },
      global: { stubs: { BadgePill: BadgePillStub, AcknowledgeButton: AcknowledgeButtonStub } },
    })

    const text = wrapper.text()
    // Should contain "Exp" prefix and the formatted date parts
    expect(text).toContain('Exp')
    expect(text).toContain('Feb')
    expect(text).toContain('28')
  })
})
