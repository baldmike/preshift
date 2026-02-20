/**
 * ShiftCard.test.ts
 *
 * Comprehensive unit tests for the ShiftCard.vue component.
 *
 * ShiftCard renders a compact, blue-themed card for a single schedule entry
 * (i.e. one staff member assigned to one shift on a specific date).  It shows:
 *
 *   - The shift template name (from `entry.shift_template.name`, falls back
 *     to "Shift" when the relationship is not loaded)
 *   - A formatted time range (e.g. "10:30 AM - 3:00 PM")
 *   - The entry date in short format (e.g. "Wed, Feb 18")
 *   - A role badge (via BadgePill child component)
 *   - Optional manager notes (when entry.notes is non-null)
 *   - A "Give Up Shift" button that emits the 'give-up' event with the entry id
 *
 * Props:
 *   - entry: ScheduleEntry  (the schedule entry to render; expects the
 *     `shift_template` relationship to be eagerly loaded)
 *
 * Emits:
 *   - 'give-up': [entryId: number]  — fired when the "Give Up Shift" button
 *     is clicked, passing the entry's primary key
 *
 * These tests verify:
 *   1. The shift template name is rendered.
 *   2. The formatted time range is rendered.
 *   3. The "Give Up Shift" button is visible.
 *   4. Clicking the button emits the 'give-up' event with the correct entry id.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ShiftCard from '@/components/ShiftCard.vue'
import type { ScheduleEntry } from '@/types'

// ── Mock for the BadgePill child component ─────────────────────────────────
//
// ShiftCard uses BadgePill to render a coloured role badge (e.g. "server"
// in blue, "bartender" in green).  We stub it with a simple <span> so the
// tests focus on ShiftCard's own logic rather than BadgePill rendering.
const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Helper: build a full ScheduleEntry with sensible defaults ──────────────
//
// The ScheduleEntry interface has many fields and an optional nested
// ShiftTemplate relationship.  This factory provides valid defaults so
// each test only overrides the fields relevant to its assertion.
function makeEntry(overrides: Partial<ScheduleEntry> = {}): ScheduleEntry {
  return {
    id: 42,
    schedule_id: 1,
    user_id: 100,
    shift_template_id: 1,
    date: '2026-02-18',
    role: 'server',
    notes: null,
    // Eagerly-loaded shift template — ShiftCard reads this to get the
    // shift name, start_time, and end_time.
    shift_template: {
      id: 1,
      location_id: 1,
      name: 'Lunch',
      start_time: '10:30:00',
      end_time: '15:00:00',
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    // Spread overrides last so test-specific values win.
    ...overrides,
  }
}

describe('ShiftCard.vue', () => {
  /**
   * Test 1 — Renders the shift template name
   *
   * The card displays the shift template's name (e.g. "Lunch", "Dinner")
   * in an <h4> heading at the top of the card.  This name comes from the
   * computed `shiftName` which reads `entry.shift_template.name` and falls
   * back to "Shift" when the relationship is not loaded.
   *
   * Here we provide a fully loaded shift_template with name "Lunch" and
   * verify it appears in the rendered output.
   */
  it('renders shift template name', () => {
    const entry = makeEntry()

    const wrapper = mount(ShiftCard, {
      props: { entry },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // Find the <h4> heading that displays the shift name.
    const heading = wrapper.find('h4')

    // It should contain "Lunch" — the name from our mock shift template.
    expect(heading.text()).toBe('Lunch')
  })

  /**
   * Test 2 — Renders the formatted shift time range
   *
   * The card shows the shift's time range formatted in 12-hour notation,
   * e.g. "10:30 AM - 3:00 PM".  The component's internal `formatShiftTime`
   * helper converts 24-hour "HH:MM:SS" strings into this human-readable
   * format.
   *
   * Our mock data uses start_time="10:30:00" and end_time="15:00:00",
   * which should produce "10:30 AM" and "3:00 PM" respectively.
   *
   * Note: The component uses an en-dash character (U+2013, " -- ") as the
   * separator between start and end times, not a regular hyphen.
   */
  it('renders shift time range', () => {
    const entry = makeEntry()

    const wrapper = mount(ShiftCard, {
      props: { entry },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // The component's rendered text should include both the formatted
    // start time and the formatted end time.
    const text = wrapper.text()
    expect(text).toContain('10:30 AM')
    expect(text).toContain('3:00 PM')
  })

  /**
   * Test 3 — Shows the "Give Up Shift" button
   *
   * The card includes a <button> at the bottom that reads "Give Up Shift".
   * This button allows a staff member to initiate the shift-drop process.
   * We verify the button is present and contains the expected label text.
   */
  it('shows "Give Up Shift" button', () => {
    const entry = makeEntry()

    const wrapper = mount(ShiftCard, {
      props: { entry },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // Find the <button> element within the rendered card.
    const button = wrapper.find('button')

    // The button should exist in the DOM.
    expect(button.exists()).toBe(true)

    // Its text content should be "Give Up Shift".
    expect(button.text()).toBe('Give Up Shift')
  })

  /**
   * Test 4 — Emits 'give-up' event when the button is clicked
   *
   * When the "Give Up Shift" button is clicked, the component should emit
   * a 'give-up' event.  The emit signature is:
   *
   *   emit('give-up', entry.id)
   *
   * So the event payload should be the entry's primary key (42 in our
   * mock data).  This allows the parent component to know which specific
   * schedule entry the user wants to drop.
   */
  it("emits 'give-up' event when button is clicked", async () => {
    // Use an entry with a known id (42) so we can assert the payload.
    const entry = makeEntry({ id: 42 })

    const wrapper = mount(ShiftCard, {
      props: { entry },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // Find and click the "Give Up Shift" button.
    // `trigger('click')` is async because Vue needs to process the event.
    const button = wrapper.find('button')
    await button.trigger('click')

    // After the click, the component should have emitted exactly one
    // 'give-up' event.
    const emitted = wrapper.emitted('give-up')

    // Verify the event was emitted at all.
    expect(emitted).toBeTruthy()

    // Verify it was emitted exactly once (one click = one event).
    expect(emitted).toHaveLength(1)

    // Verify the event payload is the entry's id (42).
    // Vue Test Utils wraps each emission's arguments in an array,
    // so emitted[0] is [42] and emitted[0][0] is 42.
    expect(emitted![0][0]).toBe(42)
  })
})
