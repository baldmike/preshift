/**
 * ShiftDropCard.test.ts
 *
 * Comprehensive unit tests for the ShiftDropCard.vue component.
 *
 * ShiftDropCard renders a compact card summarising a "shift drop" — a request
 * from a staff member to give up one of their scheduled shifts so that
 * another team member can volunteer to pick it up.
 *
 * The component accepts a single prop:
 *   - drop: ShiftDrop  (the full shift-drop object with eagerly-loaded relations)
 *
 * It displays:
 *   - The requester's name (from `drop.requester.name`, falls back to "Staff member")
 *   - A BadgePill showing the drop's status with a colour mapping:
 *       open      → yellow
 *       filled    → green
 *       cancelled → gray
 *   - The shift name and date (from the nested schedule_entry.shift_template)
 *   - The time range of the shift
 *   - The number of volunteers who have offered to pick up the shift
 *   - An optional reason for the drop request
 *
 * These tests verify:
 *   1. Requester name is rendered from `drop.requester.name`.
 *   2. Status badge receives the correct colour for each status.
 *   3. Volunteer count is displayed when volunteers exist.
 *   4. Reason text is displayed when the `reason` field is provided.
 *   5. Reason paragraph is NOT rendered when `reason` is null.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ShiftDropCard from '@/components/ShiftDropCard.vue'
import type { ShiftDrop } from '@/types'

// ── Mock for the BadgePill child component ─────────────────────────────────
//
// BadgePill is a presentational child component used inside ShiftDropCard.
// We stub it so we can inspect the props it receives (label, color) without
// depending on its actual rendering logic.  The stub renders a simple <span>
// with a data attribute for easy querying.
const BadgePillStub = {
  template: '<span class="badge-pill-stub" :data-label="label" :data-color="color">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Helper: build a full ShiftDrop object with sensible defaults ───────────
//
// The ShiftDrop interface has many fields and nested relationships.
// This factory function provides valid defaults for every field so that
// individual tests only need to override the fields they care about.
function makeShiftDrop(overrides: Partial<ShiftDrop> = {}): ShiftDrop {
  return {
    id: 1,
    schedule_entry_id: 10,
    requested_by: 100,
    reason: null,
    status: 'open',
    filled_by: null,
    filled_at: null,
    // Eagerly-loaded requester relationship — provides the name displayed
    // at the top of the card.
    requester: {
      id: 100,
      location_id: 1,
      name: 'Jane Doe',
      email: 'jane@example.com',
      role: 'server',
      roles: null,
      is_superadmin: false,
      phone: null,
      profile_photo_url: null,
      availability: null,
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
    // Eagerly-loaded schedule entry with its own nested shift_template.
    // The component reads schedule_entry.shift_template.name for the
    // shift label and start_time for the time display.
    schedule_entry: {
      id: 10,
      schedule_id: 1,
      user_id: 100,
      shift_template_id: 1,
      date: '2026-02-18',
      role: 'server',
      notes: null,
      shift_template: {
        id: 1,
        location_id: 1,
        name: 'Lunch',
        start_time: '10:30:00',
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
      },
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
    // Volunteers array — each element represents a staff member who has
    // offered to cover this dropped shift.
    volunteers: [],
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    // Spread any test-specific overrides last so they take precedence.
    ...overrides,
  }
}

describe('ShiftDropCard.vue', () => {
  /**
   * Test 1 — Renders the requester's name
   *
   * The card displays the name of the staff member who is dropping the shift.
   * This name comes from `drop.requester.name` and is rendered inside an <h4>
   * element with the class "text-amber-300".
   */
  it('renders requester name from drop.requester.name', () => {
    // Build a ShiftDrop whose requester is named "Jane Doe".
    const drop = makeShiftDrop()

    const wrapper = mount(ShiftDropCard, {
      props: { drop },
      global: {
        // Stub BadgePill so its internals do not interfere with this test.
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // Find the <h4> heading that displays the requester name.
    const heading = wrapper.find('h4')

    // Confirm it contains "Jane Doe" — the name from our mock data.
    expect(heading.text()).toBe('Jane Doe')
  })

  /**
   * Test 2 — Status badge colour mapping
   *
   * ShiftDropCard passes the drop's status string and a computed colour to
   * a BadgePill child component.  The colour mapping is:
   *   - 'open'      → 'yellow'
   *   - 'filled'    → 'green'
   *   - 'cancelled' → 'gray'
   *
   * We test all three statuses by mounting the card with each status value
   * and inspecting the props passed to the stubbed BadgePill.
   */
  it('shows status badge with correct color for each status', () => {
    // Define the expected mapping of status → colour.
    const statusColorMap: Record<ShiftDrop['status'], string> = {
      open: 'yellow',
      filled: 'green',
      cancelled: 'gray',
    }

    // Iterate over each status and verify the BadgePill receives the
    // correct colour prop.
    for (const [status, expectedColor] of Object.entries(statusColorMap)) {
      const drop = makeShiftDrop({ status: status as ShiftDrop['status'] })

      const wrapper = mount(ShiftDropCard, {
        props: { drop },
        global: {
          stubs: { BadgePill: BadgePillStub },
        },
      })

      // Find the stubbed BadgePill by its distinctive class.
      const badge = wrapper.find('.badge-pill-stub')

      // Verify the label prop matches the raw status string.
      expect(badge.attributes('data-label')).toBe(status)

      // Verify the colour prop matches the expected mapping.
      expect(badge.attributes('data-color')).toBe(expectedColor)
    }
  })

  /**
   * Test 3 — Volunteer count display
   *
   * When volunteers have signed up to cover the dropped shift, the card
   * shows a line like "2 volunteers" (or "1 volunteer" for singular).
   * The count comes from `drop.volunteers.length`.
   *
   * The paragraph is only rendered when the volunteer count is > 0
   * (controlled by `v-if="volunteerCount > 0"`).
   */
  it('shows volunteer count when volunteers exist', () => {
    // Create a drop with two volunteers in the array.
    const drop = makeShiftDrop({
      volunteers: [
        {
          id: 1,
          shift_drop_id: 1,
          user_id: 200,
          selected: false,
          created_at: '2026-01-01T00:00:00Z',
        },
        {
          id: 2,
          shift_drop_id: 1,
          user_id: 201,
          selected: false,
          created_at: '2026-01-01T00:00:00Z',
        },
      ],
    })

    const wrapper = mount(ShiftDropCard, {
      props: { drop },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // The full rendered text of the component should contain "2 volunteers".
    // (The plural "volunteers" is used because the count !== 1.)
    expect(wrapper.text()).toContain('2 volunteers')
  })

  /**
   * Test 4 — Reason text is displayed when provided
   *
   * The `reason` field is an optional string explaining why the staff member
   * is dropping the shift (e.g. "Doctor appointment").  When present, the
   * component renders it inside an italic <p> element.
   */
  it('shows reason when provided', () => {
    // Provide a reason string in the drop data.
    const drop = makeShiftDrop({ reason: 'Doctor appointment' })

    const wrapper = mount(ShiftDropCard, {
      props: { drop },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // The rendered text should include the reason string.
    expect(wrapper.text()).toContain('Doctor appointment')

    // Additionally, verify the reason is rendered inside an italic paragraph.
    // The component uses the CSS class "italic" on the reason <p> element.
    const reasonParagraph = wrapper.find('p.italic')
    expect(reasonParagraph.exists()).toBe(true)
    expect(reasonParagraph.text()).toBe('Doctor appointment')
  })

  /**
   * Test 5 — Reason paragraph is hidden when reason is null
   *
   * When `drop.reason` is null (no reason was provided), the <p> element
   * with the italic class should not be rendered at all (it is guarded
   * by `v-if="drop.reason"`).
   */
  it('hides reason when null', () => {
    // Create a drop with reason explicitly set to null (the default).
    const drop = makeShiftDrop({ reason: null })

    const wrapper = mount(ShiftDropCard, {
      props: { drop },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // The italic <p> element used for the reason should NOT exist in the DOM
    // because the v-if condition is falsy when reason is null.
    const reasonParagraph = wrapper.find('p.italic')
    expect(reasonParagraph.exists()).toBe(false)
  })
})
