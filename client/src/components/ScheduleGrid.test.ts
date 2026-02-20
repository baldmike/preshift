/**
 * ScheduleGrid.test.ts
 *
 * Comprehensive unit tests for the ScheduleGrid.vue component.
 *
 * ScheduleGrid renders a weekly schedule as a table with:
 *   - 7 day columns (Mon through Sun) plus a "Shift" label column on the left
 *   - One row per ShiftTemplate (e.g. "Lunch", "Dinner")
 *   - Each cell showing either assigned staff or a "+" button to add an entry
 *
 * Props:
 *   - schedule: Schedule           — the weekly schedule with its entries array
 *   - shiftTemplates: ShiftTemplate[] — all shift templates for the location
 *
 * Emits:
 *   - 'add-entry':    { shiftTemplateId: number, date: string }
 *       Fired when the "+" button in an empty cell is clicked.
 *   - 'remove-entry': number
 *       Fired with the entry id when a remove ("x") button is clicked.
 *
 * Key implementation detail (the NaN bug fix):
 *   The API returns `week_start` as a full ISO datetime string with a
 *   timezone suffix, e.g. "2026-02-16T00:00:00.000000Z".  The component's
 *   `weekDates` computed strips the time portion via `.split('T')[0]` before
 *   parsing, which avoids NaN issues when constructing Date objects from
 *   date strings with unexpected timezone suffixes.
 *
 * These tests verify:
 *   1. 7 column headers are rendered (Mon through Sun).
 *   2. Column headers include formatted dates (e.g. "Mon 2/16").
 *   3. One row per shift template is rendered.
 *   4. The "+" button appears when a cell has no entries.
 *   5. Clicking "+" emits 'add-entry' with the correct payload.
 *   6. ISO datetime format from the API (with T00:00:00.000000Z) does not
 *      produce NaN in the column headers.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ScheduleGrid from '@/components/ScheduleGrid.vue'
import type { Schedule, ShiftTemplate } from '@/types'

// ── Mock for the BadgePill child component ─────────────────────────────────
//
// ScheduleGrid uses BadgePill to render role badges on staff entries.
// We stub it to simplify the rendered output and keep tests focused on
// ScheduleGrid's own logic.
const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Mock data ──────────────────────────────────────────────────────────────
//
// These fixtures represent the minimal data needed to render ScheduleGrid.
//
// IMPORTANT: `week_start` intentionally uses the full ISO datetime format
// with the "T00:00:00.000000Z" suffix that the Laravel API actually returns.
// This is the exact format that previously caused a NaN bug in the date
// parsing logic.  Test 6 specifically validates this scenario.

const mockSchedule: Schedule = {
  id: 1,
  location_id: 1,
  // This is the format the API sends — a full ISO datetime, NOT a plain date.
  // The component's weekDates computed must handle this by stripping the time
  // portion before parsing.
  week_start: '2026-02-16T00:00:00.000000Z',
  status: 'draft',
  published_at: null,
  published_by: null,
  // Empty entries array — no staff are assigned yet.  This means every cell
  // should show the "+" add button.
  entries: [],
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

const mockTemplates: ShiftTemplate[] = [
  {
    id: 1,
    location_id: 1,
    name: 'Lunch',
    start_time: '10:30:00',
    end_time: '15:00:00',
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
]

// ── Helper: mount ScheduleGrid with default stubs + provided props ─────────
//
// All tests need the same global stubs (BadgePill), so this helper reduces
// boilerplate.  Individual tests can override props by passing them in.
function mountGrid(
  propsOverrides: {
    schedule?: Schedule
    shiftTemplates?: ShiftTemplate[]
  } = {},
) {
  return mount(ScheduleGrid, {
    props: {
      schedule: propsOverrides.schedule ?? mockSchedule,
      shiftTemplates: propsOverrides.shiftTemplates ?? mockTemplates,
    },
    global: {
      stubs: { BadgePill: BadgePillStub },
    },
  })
}

describe('ScheduleGrid.vue', () => {
  /**
   * Test 1 — Renders 7 column headers (Mon through Sun)
   *
   * The <thead> section should contain one <th> for the "Shift" row-header
   * column plus seven <th> elements for Mon, Tue, Wed, Thu, Fri, Sat, Sun.
   * That gives a total of 8 <th> elements.  We verify there are exactly 8
   * and that each day abbreviation appears in the header text.
   */
  it('renders 7 column headers (Mon through Sun)', () => {
    const wrapper = mountGrid()

    // Find all <th> elements inside the <thead>.
    const thElements = wrapper.findAll('thead th')

    // There should be 8 total: 1 for the "Shift" label + 7 for the days.
    expect(thElements).toHaveLength(8)

    // Extract just the text content from each <th> for easy assertion.
    const headerTexts = thElements.map((th) => th.text())

    // The first header is the "Shift" label for the row-header column.
    expect(headerTexts[0]).toBe('Shift')

    // The remaining 7 headers should contain the day abbreviations.
    const dayAbbreviations = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
    dayAbbreviations.forEach((day) => {
      // At least one header text should contain this day abbreviation.
      const found = headerTexts.some((text) => text.includes(day))
      expect(found).toBe(true)
    })
  })

  /**
   * Test 2 — Column headers include formatted dates
   *
   * Beyond just showing "Mon", "Tue", etc., each column header should also
   * include a formatted date like "2/16", "2/17", etc.  The component
   * computes these from the `week_start` value by adding 0–6 days.
   *
   * Given week_start = '2026-02-16T00:00:00.000000Z' (a Monday):
   *   Mon → 2/16
   *   Tue → 2/17
   *   Wed → 2/18
   *   Thu → 2/19
   *   Fri → 2/20
   *   Sat → 2/21
   *   Sun → 2/22
   *
   * We verify that at least the first and last date numbers appear.
   */
  it('column headers include formatted dates', () => {
    const wrapper = mountGrid()

    // Grab all <th> elements and extract their text.
    const thElements = wrapper.findAll('thead th')
    const headerTexts = thElements.map((th) => th.text())

    // The Monday column (index 1, after the "Shift" column) should include
    // the date "2/16" since Feb 16 is the Monday of our mock week.
    expect(headerTexts[1]).toContain('Mon')
    expect(headerTexts[1]).toContain('2/16')

    // The Sunday column (index 7) should include "2/22".
    expect(headerTexts[7]).toContain('Sun')
    expect(headerTexts[7]).toContain('2/22')

    // Spot-check a mid-week day: Wednesday should be 2/18.
    expect(headerTexts[3]).toContain('Wed')
    expect(headerTexts[3]).toContain('2/18')
  })

  /**
   * Test 3 — Renders a row for each shift template
   *
   * The <tbody> should contain one <tr> for every element in the
   * `shiftTemplates` prop.  With our single-template mock data, we expect
   * exactly one row.  We also verify the shift template name ("Lunch")
   * appears in the row header <td>.
   */
  it('renders a row for each shift template', () => {
    // Use two shift templates to verify the row count scales correctly.
    const twoTemplates: ShiftTemplate[] = [
      {
        id: 1,
        location_id: 1,
        name: 'Lunch',
        start_time: '10:30:00',
        end_time: '15:00:00',
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
      },
      {
        id: 2,
        location_id: 1,
        name: 'Dinner',
        start_time: '17:00:00',
        end_time: '22:00:00',
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
      },
    ]

    const wrapper = mountGrid({ shiftTemplates: twoTemplates })

    // Find all <tr> elements inside <tbody>.
    const rows = wrapper.findAll('tbody tr')

    // There should be exactly 2 rows — one for "Lunch", one for "Dinner".
    expect(rows).toHaveLength(2)

    // The full rendered text should contain both template names.
    const text = wrapper.text()
    expect(text).toContain('Lunch')
    expect(text).toContain('Dinner')
  })

  /**
   * Test 4 — Shows "+" button when no entries exist for a cell
   *
   * When a particular (shiftTemplate, date) combination has no schedule
   * entries assigned, the component renders a "+" button in that cell.
   * This serves as the "add entry" action for managers.
   *
   * Our mock data has an empty entries array, so EVERY cell should have
   * a "+" button.  With 1 template and 7 days, that is 7 "+" buttons.
   */
  it('shows "+" button when no entries exist for a cell', () => {
    const wrapper = mountGrid()

    // Find all <button> elements that contain the "+" text.
    // The "+" button has the `title="Add entry"` attribute for accessibility.
    const addButtons = wrapper.findAll('button[title="Add entry"]')

    // With 1 shift template and 7 days of the week, there should be
    // exactly 7 add buttons (one per empty cell).
    expect(addButtons).toHaveLength(7)

    // Each button should display "+" as its text content.
    addButtons.forEach((btn) => {
      expect(btn.text()).toBe('+')
    })
  })

  /**
   * Test 5 — Emits 'add-entry' when "+" is clicked
   *
   * Clicking one of the "+" buttons should emit an 'add-entry' event with
   * a payload containing the shiftTemplateId and the ISO date string for
   * that cell.
   *
   * The emit signature is:
   *   emit('add-entry', { shiftTemplateId: number, date: string })
   *
   * We click the first "+" button (Monday's cell for the Lunch template)
   * and verify the emitted payload matches { shiftTemplateId: 1, date: '2026-02-16' }.
   */
  it("emits 'add-entry' when '+' is clicked", async () => {
    const wrapper = mountGrid()

    // Find all "+" buttons.
    const addButtons = wrapper.findAll('button[title="Add entry"]')

    // There should be at least one button to click.
    expect(addButtons.length).toBeGreaterThan(0)

    // Click the FIRST "+" button.
    // This corresponds to the Monday cell (date 2026-02-16) for the
    // first shift template (id=1, "Lunch").
    await addButtons[0].trigger('click')

    // Retrieve the emitted 'add-entry' events.
    const emitted = wrapper.emitted('add-entry')

    // Verify the event was emitted.
    expect(emitted).toBeTruthy()

    // Verify it was emitted exactly once.
    expect(emitted).toHaveLength(1)

    // Verify the payload contains the correct shiftTemplateId and date.
    // The first "+" button is in the Monday column for the Lunch template,
    // so the date should be '2026-02-16' and the shiftTemplateId should be 1.
    const payload = emitted![0][0] as { shiftTemplateId: number; date: string }
    expect(payload.shiftTemplateId).toBe(1)
    expect(payload.date).toBe('2026-02-16')
  })

  /**
   * Test 6 — Handles ISO datetime format from API without NaN
   *
   * This test specifically validates the fix for a bug where the component
   * would produce "NaN/NaN" in column headers when `week_start` was an ISO
   * datetime string like "2026-02-16T00:00:00.000000Z" instead of a plain
   * date like "2026-02-16".
   *
   * The fix in ScheduleGrid's `weekDates` computed is:
   *   const base = new Date(props.schedule.week_start.split('T')[0] + 'T12:00:00')
   *
   * This strips the "T00:00:00.000000Z" suffix (which caused Date parsing
   * issues) and replaces it with "T12:00:00" (a safe noon time that avoids
   * timezone drift).
   *
   * We verify that none of the rendered column header texts contain "NaN".
   */
  it('handles ISO datetime format from API (week_start with T00:00:00.000000Z) without NaN', () => {
    // Use the mockSchedule which already has the problematic ISO format.
    // If the bug were still present, column headers would read "NaN/NaN".
    const wrapper = mountGrid()

    // Grab all column header texts (skip the first "Shift" header).
    const thElements = wrapper.findAll('thead th')
    const dayHeaders = thElements.slice(1).map((th) => th.text())

    // There should be exactly 7 day columns.
    expect(dayHeaders).toHaveLength(7)

    // NONE of the header texts should contain "NaN".
    dayHeaders.forEach((headerText) => {
      expect(headerText).not.toContain('NaN')
    })

    // Additionally, verify the headers contain valid date numbers.
    // For the week of 2026-02-16, the dates should run from 16 to 22.
    expect(dayHeaders[0]).toContain('16')  // Mon 2/16
    expect(dayHeaders[6]).toContain('22')  // Sun 2/22

    // Verify the full component text does not contain "NaN" anywhere.
    expect(wrapper.text()).not.toContain('NaN')
  })
})
