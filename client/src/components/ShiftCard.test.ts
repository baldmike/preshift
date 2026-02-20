/**
 * ShiftCard.test.ts
 *
 * Unit tests for the ShiftCard.vue component.
 *
 * ShiftCard renders a compact, blue-themed card for a single schedule entry
 * showing:
 *   - A formatted time range as the heading (e.g. "10:30 AM – 3:00 PM")
 *   - The entry date in short format (e.g. "Wed, Feb 18")
 *   - A role badge (via BadgePill child component)
 *   - Optional manager notes
 *   - A "Give Up Shift" button that emits 'give-up' with the entry id
 *
 * Props:
 *   - entry: ScheduleEntry  (expects shift_template to be eagerly loaded)
 *
 * Emits:
 *   - 'give-up': [entryId: number]
 *
 * Tests verify:
 *   1. The time range is rendered as the heading.
 *   2. The fallback heading "Shift" appears when shift_template is not loaded.
 *   3. The "Give Up Shift" button is visible.
 *   4. Clicking the button emits 'give-up' with the correct entry id.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ShiftCard from '@/components/ShiftCard.vue'
import type { ScheduleEntry } from '@/types'

// ── Mock for the BadgePill child component ─────────────────────────────────
const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Helper: build a ScheduleEntry with sensible defaults ────────────────────
function makeEntry(overrides: Partial<ScheduleEntry> = {}): ScheduleEntry {
  return {
    id: 42,
    schedule_id: 1,
    user_id: 100,
    shift_template_id: 1,
    date: '2026-02-18',
    role: 'server',
    notes: null,
    shift_template: {
      id: 1,
      location_id: 1,
      name: 'Opening',
      start_time: '10:30:00',
      end_time: '15:00:00',
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

describe('ShiftCard.vue', () => {
  /**
   * Test 1 — Renders the time range as the heading
   *
   * The card heading should show the formatted time range from the
   * shift_template's start_time and end_time (e.g. "10:30 AM – 3:00 PM")
   * rather than a template name.
   */
  it('renders time range as the heading', () => {
    const entry = makeEntry()

    const wrapper = mount(ShiftCard, {
      props: { entry },
      global: { stubs: { BadgePill: BadgePillStub } },
    })

    const heading = wrapper.find('h4')
    // Should show the formatted time range, NOT a template name
    expect(heading.text()).toContain('10:30 AM')
    expect(heading.text()).toContain('3:00 PM')
  })

  /**
   * Test 2 — Falls back to "Shift" when shift_template is not loaded
   *
   * When the shift_template relationship is null/undefined, the heading
   * should gracefully fall back to "Shift".
   */
  it('shows "Shift" fallback when shift_template is not loaded', () => {
    const entry = makeEntry({ shift_template: undefined })

    const wrapper = mount(ShiftCard, {
      props: { entry },
      global: { stubs: { BadgePill: BadgePillStub } },
    })

    const heading = wrapper.find('h4')
    expect(heading.text()).toBe('Shift')
  })

  /**
   * Test 3 — Shows the "Give Up Shift" button
   *
   * The card includes a button at the bottom that allows staff to
   * initiate the shift-drop process.
   */
  it('shows "Give Up Shift" button', () => {
    const entry = makeEntry()

    const wrapper = mount(ShiftCard, {
      props: { entry },
      global: { stubs: { BadgePill: BadgePillStub } },
    })

    const button = wrapper.find('button')
    expect(button.exists()).toBe(true)
    expect(button.text()).toBe('Give Up Shift')
  })

  /**
   * Test 4 — Emits 'give-up' event when the button is clicked
   *
   * The event payload should be the entry's primary key so the parent
   * knows which schedule entry the user wants to drop.
   */
  it("emits 'give-up' event when button is clicked", async () => {
    const entry = makeEntry({ id: 42 })

    const wrapper = mount(ShiftCard, {
      props: { entry },
      global: { stubs: { BadgePill: BadgePillStub } },
    })

    const button = wrapper.find('button')
    await button.trigger('click')

    const emitted = wrapper.emitted('give-up')
    expect(emitted).toBeTruthy()
    expect(emitted).toHaveLength(1)
    expect(emitted![0][0]).toBe(42)
  })

  /**
   * Test 5 — Renders the formatted date
   *
   * The card should display the entry date in a human-readable format
   * like "Wed, Feb 18".
   */
  it('renders formatted date', () => {
    const entry = makeEntry({ date: '2026-02-18' })

    const wrapper = mount(ShiftCard, {
      props: { entry },
      global: { stubs: { BadgePill: BadgePillStub } },
    })

    const text = wrapper.text()
    // Should contain the day number at minimum
    expect(text).toContain('18')
    expect(text).toContain('Feb')
  })
})
