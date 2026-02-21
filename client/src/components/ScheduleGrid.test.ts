/**
 * ScheduleGrid.test.ts
 *
 * Unit tests for the ScheduleGrid.vue component.
 *
 * ScheduleGrid renders a weekly schedule as a table with:
 *   - 7 day columns (Mon through Sun) plus a "Time" label column on the left
 *   - One row per time slot (derived from shift templates)
 *   - Each cell showing assigned staff with role badges, or a "+" button to add
 *
 * Props:
 *   - schedule: Schedule                -- the weekly schedule with its entries
 *   - shiftTemplates?: ShiftTemplate[]  -- optional; derived from entries if omitted
 *
 * Emits:
 *   - 'add-entry':    { shiftTemplateId: number, date: string }
 *   - 'remove-entry': number
 *
 * Key behaviours tested:
 *   1. 7 column headers rendered (Mon–Sun) with "Time" label in the corner.
 *   2. Column headers include formatted dates (e.g. "Mon 2/16").
 *   3. One row per time slot when shiftTemplates prop is provided.
 *   4. "+" button appears for empty cells and always for adding multiple staff.
 *   5. Clicking "+" emits 'add-entry' with correct payload.
 *   6. ISO datetime format from API does not produce NaN in headers.
 *   7. Row headers show time range (not template name).
 *   8. effectiveTemplates derives rows from entries when prop is omitted.
 */

import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ScheduleGrid from '@/components/ScheduleGrid.vue'
import type { Schedule, ShiftTemplate, ScheduleEntry } from '@/types'

// ── Mock for the BadgePill child component ─────────────────────────────────
//
// ScheduleGrid uses BadgePill to render role badges on staff entries.
// We stub it to simplify the rendered output and keep tests focused.
const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Mock data ──────────────────────────────────────────────────────────────
//
// IMPORTANT: `week_start` uses the full ISO datetime format with a timezone
// suffix that the Laravel API actually returns. This is the format that
// previously caused a NaN bug in the date parsing logic.

/** A schedule with no entries — every cell should show the "+" button */
const mockSchedule: Schedule = {
  id: 1,
  location_id: 1,
  week_start: '2026-02-16T00:00:00.000000Z',
  status: 'draft',
  published_at: null,
  published_by: null,
  entries: [],
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

/** Two time slots: Opening (10:30 AM) and Evening (4:30 PM) */
const mockTemplates: ShiftTemplate[] = [
  {
    id: 1,
    location_id: 1,
    name: 'Opening',
    start_time: '10:30:00',
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
  {
    id: 2,
    location_id: 1,
    name: 'Evening',
    start_time: '16:30:00',
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
  },
]

/** A schedule with entries that carry eagerly-loaded shift_template data */
const mockScheduleWithEntries: Schedule = {
  id: 2,
  location_id: 1,
  week_start: '2026-02-16T00:00:00.000000Z',
  status: 'published',
  published_at: '2026-02-15T12:00:00Z',
  published_by: 2,
  entries: [
    {
      id: 101,
      schedule_id: 2,
      user_id: 3,
      shift_template_id: 1,
      date: '2026-02-16',
      role: 'server',
      notes: null,
      user: { id: 3, location_id: 1, name: 'Jake', email: 'j@test.com', role: 'server', is_superadmin: false, phone: null, availability: null, created_at: '', updated_at: '' },
      shift_template: mockTemplates[0],
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
    {
      id: 102,
      schedule_id: 2,
      user_id: 4,
      shift_template_id: 2,
      date: '2026-02-16',
      role: 'bartender',
      notes: null,
      user: { id: 4, location_id: 1, name: 'Mia', email: 'm@test.com', role: 'bartender', is_superadmin: false, phone: null, availability: null, created_at: '', updated_at: '' },
      shift_template: mockTemplates[1],
      created_at: '2026-01-01T00:00:00Z',
      updated_at: '2026-01-01T00:00:00Z',
    },
  ],
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

// ── Helper: mount ScheduleGrid with default stubs + provided props ─────────
function mountGrid(
  propsOverrides: {
    schedule?: Schedule
    shiftTemplates?: ShiftTemplate[]
  } = {},
) {
  return mount(ScheduleGrid, {
    props: {
      schedule: propsOverrides.schedule ?? mockSchedule,
      ...(propsOverrides.shiftTemplates !== undefined
        ? { shiftTemplates: propsOverrides.shiftTemplates }
        : { shiftTemplates: [mockTemplates[0]] }),
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
   * The <thead> should contain one <th> for the "Time" row-header column
   * plus seven <th> elements for Mon–Sun. Total: 8 <th> elements.
   */
  it('renders 7 column headers (Mon through Sun)', () => {
    const wrapper = mountGrid()
    const thElements = wrapper.findAll('thead th')

    // 1 "Time" label + 7 day columns = 8 total
    expect(thElements).toHaveLength(8)

    const headerTexts = thElements.map((th) => th.text())

    // First header should be the "Time" label (not "Shift" — templates are hidden)
    expect(headerTexts[0]).toBe('Time')

    // The remaining 7 should contain day abbreviations
    const dayAbbreviations = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
    dayAbbreviations.forEach((day) => {
      expect(headerTexts.some((text) => text.includes(day))).toBe(true)
    })
  })

  /**
   * Test 2 — Column headers include formatted dates
   *
   * Given week_start = '2026-02-16T00:00:00.000000Z' (a Monday):
   *   Mon → 2/16, Tue → 2/17, ... Sun → 2/22
   */
  it('column headers include formatted dates', () => {
    const wrapper = mountGrid()
    const thElements = wrapper.findAll('thead th')
    const headerTexts = thElements.map((th) => th.text())

    expect(headerTexts[1]).toContain('Mon')
    expect(headerTexts[1]).toContain('2/16')
    expect(headerTexts[7]).toContain('Sun')
    expect(headerTexts[7]).toContain('2/22')
    expect(headerTexts[3]).toContain('Wed')
    expect(headerTexts[3]).toContain('2/18')
  })

  /**
   * Test 3 — Renders a row for each time slot
   *
   * With two templates provided, there should be two <tr> rows in <tbody>.
   */
  it('renders a row for each time slot', () => {
    const wrapper = mountGrid({ shiftTemplates: mockTemplates })
    const rows = wrapper.findAll('tbody tr')

    expect(rows).toHaveLength(2)
  })

  /**
   * Test 4 — Row headers show start time instead of template name
   *
   * Since we removed template names from the UI, row headers should
   * display the formatted start time (e.g. "10:30 AM") rather than
   * a template name like "Opening".
   */
  it('row headers show start time instead of template name', () => {
    const wrapper = mountGrid({ shiftTemplates: mockTemplates })
    const text = wrapper.text()

    // Should show formatted start times
    expect(text).toContain('10:30 AM')
    expect(text).toContain('4:30 PM')

    // Should NOT show template names
    expect(text).not.toContain('Opening')
    expect(text).not.toContain('Evening')
  })

  /**
   * Test 5 — Shows "+" button when no entries exist for a cell
   *
   * With an empty entries array, every cell should have a "+" button.
   * With 1 template and 7 days, that's 7 "+" buttons.
   */
  it('shows "+" button when no entries exist for a cell', () => {
    const wrapper = mountGrid()
    const addButtons = wrapper.findAll('button[title="Add entry"]')

    expect(addButtons).toHaveLength(7)
    addButtons.forEach((btn) => {
      expect(btn.text()).toBe('+')
    })
  })

  /**
   * Test 6 — Emits 'add-entry' when "+" is clicked
   *
   * Clicking the first "+" (Monday cell for the first template) should
   * emit 'add-entry' with { shiftTemplateId: 1, date: '2026-02-16' }.
   */
  it("emits 'add-entry' when '+' is clicked", async () => {
    const wrapper = mountGrid()
    const addButtons = wrapper.findAll('button[title="Add entry"]')
    expect(addButtons.length).toBeGreaterThan(0)

    await addButtons[0].trigger('click')
    const emitted = wrapper.emitted('add-entry')

    expect(emitted).toBeTruthy()
    expect(emitted).toHaveLength(1)

    const payload = emitted![0][0] as { shiftTemplateId: number; date: string }
    expect(payload.shiftTemplateId).toBe(1)
    expect(payload.date).toBe('2026-02-16')
  })

  /**
   * Test 7 — Handles ISO datetime format from API without NaN
   *
   * The API returns week_start as "2026-02-16T00:00:00.000000Z".
   * The component splits on 'T' to extract the date portion before parsing.
   * None of the headers should contain "NaN".
   */
  it('handles ISO datetime format from API without NaN', () => {
    const wrapper = mountGrid()
    const thElements = wrapper.findAll('thead th')
    const dayHeaders = thElements.slice(1).map((th) => th.text())

    expect(dayHeaders).toHaveLength(7)
    dayHeaders.forEach((headerText) => {
      expect(headerText).not.toContain('NaN')
    })

    expect(dayHeaders[0]).toContain('16') // Mon 2/16
    expect(dayHeaders[6]).toContain('22') // Sun 2/22
    expect(wrapper.text()).not.toContain('NaN')
  })

  /**
   * Test 8 — Derives time slot rows from entries when shiftTemplates prop is omitted
   *
   * When the shiftTemplates prop is not provided, the component's
   * effectiveTemplates computed should extract unique templates from
   * the schedule's entries (via their shift_template relationship).
   * This allows staff views to render the grid without fetching
   * templates separately.
   */
  it('derives time slot rows from entries when shiftTemplates prop is omitted', () => {
    // Mount with entries that have eagerly-loaded shift_template, but NO shiftTemplates prop
    const wrapper = mount(ScheduleGrid, {
      props: {
        schedule: mockScheduleWithEntries,
        // shiftTemplates deliberately omitted
      },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    // Should render 2 rows — one per unique shift_template found in entries
    const rows = wrapper.findAll('tbody tr')
    expect(rows).toHaveLength(2)

    // Should show the time ranges from both templates
    const text = wrapper.text()
    expect(text).toContain('10:30 AM')
    expect(text).toContain('4:30 PM')

    // Should show the staff names from the entries
    expect(text).toContain('Jake')
    expect(text).toContain('Mia')
  })

  /**
   * Test 9 — Shows assigned staff with role badges in cells
   *
   * When entries exist for a cell, each assigned staff member should
   * be displayed with their name and a role badge.
   */
  it('shows assigned staff with role badges', () => {
    const wrapper = mount(ScheduleGrid, {
      props: {
        schedule: mockScheduleWithEntries,
        shiftTemplates: mockTemplates,
      },
      global: {
        stubs: { BadgePill: BadgePillStub },
      },
    })

    const text = wrapper.text()

    // Staff names from the entries should be visible
    expect(text).toContain('Jake')
    expect(text).toContain('Mia')

    // Role badges should be rendered (via our stub)
    expect(text).toContain('server')
    expect(text).toContain('bartender')
  })
})
