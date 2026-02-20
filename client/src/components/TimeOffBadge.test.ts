/**
 * TimeOffBadge.test.ts
 *
 * Comprehensive unit tests for the TimeOffBadge.vue component.
 *
 * TimeOffBadge is a compact, inline badge that summarises a time-off request.
 * It is designed to sit inside dense lists or grid cells without dominating
 * the layout.  Unlike a full card component, it renders as an inline-flex
 * <span> with pill-like rounded styling.
 *
 * The component displays:
 *   - The user's name (from `request.user.name`, falls back to "Staff member")
 *   - A formatted date range (e.g. "Feb 23 -- Feb 25", or just "Feb 23" if
 *     start and end are the same day)
 *   - A BadgePill showing the request status with a colour mapping:
 *       pending  → yellow
 *       approved → green
 *       denied   → red
 *
 * Props:
 *   - request: TimeOffRequest  (expects the `user` relationship to be eagerly
 *     loaded for the name display)
 *
 * This component is read-only and does not emit any events.
 *
 * These tests verify:
 *   1. The user name is rendered correctly.
 *   2. The date range is formatted and displayed.
 *   3. The status badge receives the correct colour for the "approved" status.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TimeOffBadge from '@/components/TimeOffBadge.vue'
import type { TimeOffRequest } from '@/types'

// ── Mock for the BadgePill child component ─────────────────────────────────
//
// TimeOffBadge uses BadgePill to render the status label.  We stub it so we
// can inspect the props it receives (label, color) without depending on its
// actual rendering output.  The stub exposes the props as data attributes
// for easy assertion.
const BadgePillStub = {
  template: '<span class="badge-pill-stub" :data-label="label" :data-color="color">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Helper: build a full TimeOffRequest with sensible defaults ─────────────
//
// The TimeOffRequest interface includes timestamps, foreign keys, and an
// optional eagerly-loaded user relationship.  This factory provides valid
// defaults so individual tests only override what they need.
function makeTimeOffRequest(overrides: Partial<TimeOffRequest> = {}): TimeOffRequest {
  return {
    id: 1,
    user_id: 100,
    location_id: 1,
    // Date range: Feb 23 through Feb 25, 2026.
    // The component formats these into "Feb 23 -- Feb 25".
    start_date: '2026-02-23',
    end_date: '2026-02-25',
    reason: 'Vacation',
    status: 'approved',
    resolved_by: null,
    resolved_at: null,
    // Eagerly-loaded user relationship — provides the name displayed
    // at the start of the badge.
    user: {
      id: 100,
      location_id: 1,
      name: 'Alice Johnson',
      email: 'alice@example.com',
      role: 'server',
      is_superadmin: false,
      phone: null,
      availability: null,
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    // Spread overrides last so test-specific values take precedence.
    ...overrides,
  }
}

describe('TimeOffBadge.vue', () => {
  /**
   * Test 1 — Renders the user name
   *
   * The badge displays the requesting user's name at the left side of the
   * inline pill.  The name comes from `request.user.name` via the computed
   * `userName`, which falls back to "Staff member" when the relationship
   * is not loaded.
   *
   * Our mock data provides user.name = "Alice Johnson", so we verify that
   * text appears in the rendered output.
   */
  it('renders the user name', () => {
    const request = makeTimeOffRequest()

    const wrapper = mount(TimeOffBadge, {
      props: { request },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // The rendered badge text should include the user's name.
    expect(wrapper.text()).toContain('Alice Johnson')

    // More specifically, the name is rendered inside a <span> with the
    // class "font-medium" and "text-gray-300".  Let's verify that span
    // exists and contains the correct name.
    const nameSpan = wrapper.find('span.font-medium')
    expect(nameSpan.exists()).toBe(true)
    expect(nameSpan.text()).toBe('Alice Johnson')
  })

  /**
   * Test 2 — Shows the date range
   *
   * The badge displays the time-off date range formatted as
   * "Feb 23 -- Feb 25" (when start and end differ) or just "Feb 23"
   * (when they are the same day).
   *
   * Our mock data uses start_date='2026-02-23' and end_date='2026-02-25',
   * so the displayed range should include both "Feb 23" and "Feb 25".
   *
   * The component's `formatShortDate` helper converts ISO date strings to
   * localised short date labels using `toLocaleDateString([], { month: 'short', day: 'numeric' })`.
   */
  it('shows date range', () => {
    const request = makeTimeOffRequest()

    const wrapper = mount(TimeOffBadge, {
      props: { request },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // The rendered text should contain the start and end date portions.
    // We check for "Feb" and the day numbers since the exact format
    // depends on locale, but "Feb" + "23" and "25" should always be present
    // when the locale is English.
    const text = wrapper.text()
    expect(text).toContain('Feb')
    expect(text).toContain('23')
    expect(text).toContain('25')

    // The date range span is rendered with the class "text-gray-500".
    // Let's verify it exists.
    const dateSpan = wrapper.find('span.text-gray-500')
    expect(dateSpan.exists()).toBe(true)
  })

  /**
   * Test 3 — Applies correct styling for "approved" status
   *
   * TimeOffBadge maps the request status to a BadgePill colour:
   *   - pending  → yellow
   *   - approved → green
   *   - denied   → red
   *
   * Our mock data has status='approved', so the BadgePill should receive
   * color='green' and label='approved'.
   *
   * We verify this by inspecting the data attributes on our BadgePill stub,
   * which expose the `label` and `color` props that TimeOffBadge computed
   * and passed down.
   */
  it('applies correct styling for approved status', () => {
    const request = makeTimeOffRequest({ status: 'approved' })

    const wrapper = mount(TimeOffBadge, {
      props: { request },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // Find the stubbed BadgePill by its distinctive class.
    const badge = wrapper.find('.badge-pill-stub')

    // The stub should exist in the rendered output.
    expect(badge.exists()).toBe(true)

    // Verify the label prop received by BadgePill is the raw status string.
    expect(badge.attributes('data-label')).toBe('approved')

    // Verify the colour prop received by BadgePill matches the expected
    // mapping: 'approved' → 'green'.
    expect(badge.attributes('data-color')).toBe('green')

    // Additionally, let's verify the outer badge container has the expected
    // inline-flex layout class, confirming the component's base styling
    // is applied correctly.
    const outerSpan = wrapper.find('span.inline-flex')
    expect(outerSpan.exists()).toBe(true)
    expect(outerSpan.classes()).toContain('rounded-full')
  })
})
