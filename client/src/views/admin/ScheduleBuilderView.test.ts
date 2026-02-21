/**
 * ScheduleBuilderView.test.ts
 *
 * Tests for the one-shift-per-day filtering logic in the Schedule Builder.
 *
 * The ScheduleBuilderView has a staff dropdown in the add-entry form that
 * filters out users who are already scheduled on the selected date. These
 * tests verify that:
 *   1. Users already scheduled on the selected date are hidden from the dropdown.
 *   2. Users scheduled on different dates still appear in the dropdown.
 *   3. All users appear when the active schedule has no entries.
 *   4. Multiple users scheduled on the same date are all filtered out.
 *   5. Users on other dates are not affected by filtering.
 *
 * Because ScheduleBuilderView is a complex view with many child components
 * and API calls on mount, we stub all child components and mock the API
 * module to respond based on URL.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ScheduleBuilderView from '@/views/admin/ScheduleBuilderView.vue'
import type { Schedule, ScheduleEntry, ShiftTemplate, User } from '@/types'

// ── Mock the API module ─────────────────────────────────────────────────────
// We use a single mock function that routes based on the URL argument,
// mimicking a real API. This lets selectSchedule(), fetchUsers(), etc.
// all resolve correctly without careful ordering of mockResolvedValueOnce.

const mockGet = vi.fn()
const mockPost = vi.fn()
const mockDelete = vi.fn()

vi.mock('@/composables/useApi', () => ({
  default: {
    get: (...args: unknown[]) => mockGet(...args),
    post: (...args: unknown[]) => mockPost(...args),
    delete: (...args: unknown[]) => mockDelete(...args),
  },
}))

// ── Mock the useSchedule composable ─────────────────────────────────────────
vi.mock('@/composables/useSchedule', () => ({
  useSchedule: () => ({
    formatWeekLabel: (date: string) => `Week of ${date}`,
  }),
}))

// ── Stubs for child components ──────────────────────────────────────────────
const AppShellStub = { template: '<div><slot /></div>' }
const ScheduleGridStub = {
  template: '<div class="schedule-grid-stub" />',
  props: ['schedule', 'shiftTemplates'],
  emits: ['add-entry', 'remove-entry'],
}
const TimeOffBadgeStub = {
  template: '<div class="time-off-badge-stub" />',
  props: ['request'],
}
const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Mock data ───────────────────────────────────────────────────────────────

/** Creates a minimal User object with sensible defaults. */
function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 1,
    location_id: 1,
    name: 'Test User',
    email: 'test@test.com',
    role: 'server',
    is_superadmin: false,
    phone: null,
    availability: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/** Creates a minimal ScheduleEntry object with sensible defaults. */
function makeEntry(overrides: Partial<ScheduleEntry> = {}): ScheduleEntry {
  return {
    id: 1,
    schedule_id: 1,
    user_id: 1,
    shift_template_id: 1,
    date: '2026-02-16',
    role: 'server',
    notes: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/** Three test users: two servers and a bartender */
const testUsers: User[] = [
  makeUser({ id: 10, name: 'Alice Server', email: 'alice@test.com', role: 'server' }),
  makeUser({ id: 20, name: 'Bob Bartender', email: 'bob@test.com', role: 'bartender' }),
  makeUser({ id: 30, name: 'Charlie Server', email: 'charlie@test.com', role: 'server' }),
]

/** A shift template for Dinner at 4:30 PM */
const testTemplate: ShiftTemplate = {
  id: 1,
  location_id: 1,
  name: 'Dinner',
  start_time: '16:30:00',
  created_at: '2026-01-01T00:00:00Z',
  updated_at: '2026-01-01T00:00:00Z',
}

// ── Helper: configure the API mock for a given schedule + user set ───────────

/**
 * Configures mockGet to respond based on URL patterns.
 * This lets all the onMounted API calls resolve correctly
 * and also handles the selectSchedule() call when a schedule button is clicked.
 */
function configureMockApi(schedule: Schedule, users?: User[]) {
  mockGet.mockImplementation((url: string) => {
    if (url === '/api/schedules') {
      return Promise.resolve({ data: [schedule] })
    }
    if (url.startsWith('/api/schedules/')) {
      return Promise.resolve({ data: schedule })
    }
    if (url === '/api/shift-templates') {
      return Promise.resolve({ data: [testTemplate] })
    }
    if (url === '/api/users') {
      return Promise.resolve({ data: users ?? testUsers })
    }
    if (url === '/api/time-off-requests') {
      return Promise.resolve({ data: [] })
    }
    return Promise.resolve({ data: [] })
  })
}

/**
 * Mounts ScheduleBuilderView, waits for mount, clicks the schedule
 * in the sidebar to select it, then triggers the add-entry form for a
 * specific date. Returns the wrapper with the form visible and populated.
 */
async function mountAndOpenForm(schedule: Schedule, date: string, users?: User[]) {
  configureMockApi(schedule, users)

  const wrapper = mount(ScheduleBuilderView, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        ScheduleGrid: ScheduleGridStub,
        TimeOffBadge: TimeOffBadgeStub,
        BadgePill: BadgePillStub,
        'router-link': { template: '<a><slot /></a>' },
      },
    },
  })

  // Wait for onMounted API calls to resolve
  await flushPromises()

  // Click the schedule button in the sidebar to trigger selectSchedule()
  const scheduleButtons = wrapper.findAll('button')
  const scheduleBtn = scheduleButtons.find(btn => btn.text().includes('Week of'))
  if (scheduleBtn) {
    await scheduleBtn.trigger('click')
    await flushPromises()
  }

  // Now the ScheduleGrid is rendered. Emit 'add-entry' to open the form.
  const grid = wrapper.findComponent(ScheduleGridStub)
  if (grid.exists()) {
    grid.vm.$emit('add-entry', { shiftTemplateId: testTemplate.id, date })
    await flushPromises()
  }

  return wrapper
}

/**
 * Extracts the numeric user IDs from the staff <select> dropdown options.
 * The staff select is the one whose disabled option says "Select staff...".
 */
function getDropdownUserIds(wrapper: ReturnType<typeof mount>): number[] {
  const selects = wrapper.findAll('select')

  // Find the staff member select by looking for the "Select staff..." placeholder
  for (const sel of selects) {
    const options = sel.findAll('option')
    const hasStaffPlaceholder = options.some(opt => opt.text().includes('Select staff'))
    if (!hasStaffPlaceholder) continue

    return options
      .filter(opt => {
        const val = opt.attributes('value')
        return val !== undefined && val !== '' && !opt.attributes('disabled')
      })
      .map(opt => Number(opt.attributes('value')))
      .filter(v => !isNaN(v) && v !== 0)
  }

  return []
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('ScheduleBuilderView — one shift per day filtering', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  /**
   * Test 1: Users already scheduled on the selected date are hidden.
   *
   * When Alice (id: 10) is already assigned on 2026-02-16 and the manager
   * opens the add-entry form for that same date, Alice should NOT appear
   * in the staff dropdown. Bob and Charlie should still be available.
   */
  it('hides users already scheduled on the selected date', async () => {
    const schedule: Schedule = {
      id: 1, location_id: 1,
      week_start: '2026-02-16T00:00:00.000000Z',
      status: 'draft', published_at: null, published_by: null,
      entries: [makeEntry({ id: 101, user_id: 10, date: '2026-02-16' })],
      created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z',
    }

    const wrapper = await mountAndOpenForm(schedule, '2026-02-16')
    const userIds = getDropdownUserIds(wrapper)

    // Alice (id: 10) is already scheduled on 2026-02-16 — she should be filtered out
    expect(userIds).not.toContain(10)
    // Bob (id: 20) and Charlie (id: 30) should still appear
    expect(userIds).toContain(20)
    expect(userIds).toContain(30)
  })

  /**
   * Test 2: Users scheduled on different dates still appear.
   *
   * When Alice is scheduled on 2026-02-16 but the manager opens the form
   * for 2026-02-17, Alice should still appear because she's not scheduled
   * on that different date.
   */
  it('shows users when form date differs from their scheduled date', async () => {
    const schedule: Schedule = {
      id: 1, location_id: 1,
      week_start: '2026-02-16T00:00:00.000000Z',
      status: 'draft', published_at: null, published_by: null,
      entries: [makeEntry({ id: 101, user_id: 10, date: '2026-02-16' })],
      created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z',
    }

    const wrapper = await mountAndOpenForm(schedule, '2026-02-17')
    const userIds = getDropdownUserIds(wrapper)

    // Alice is scheduled on 02-16, not 02-17 — all three should appear
    expect(userIds).toContain(10)
    expect(userIds).toContain(20)
    expect(userIds).toContain(30)
  })

  /**
   * Test 3: All users appear when the schedule has no entries.
   *
   * When the active schedule is empty (no one is scheduled yet), every
   * user should appear in the dropdown regardless of the selected date.
   */
  it('shows all users when schedule has no entries', async () => {
    const schedule: Schedule = {
      id: 1, location_id: 1,
      week_start: '2026-02-16T00:00:00.000000Z',
      status: 'draft', published_at: null, published_by: null,
      entries: [],
      created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z',
    }

    const wrapper = await mountAndOpenForm(schedule, '2026-02-16')
    const userIds = getDropdownUserIds(wrapper)

    // All three users should be available
    expect(userIds).toContain(10)
    expect(userIds).toContain(20)
    expect(userIds).toContain(30)
  })

  /**
   * Test 4: Multiple users scheduled on the same date are all filtered out.
   *
   * When both Alice and Bob are scheduled on 2026-02-16, only Charlie
   * should appear in the dropdown for that date.
   */
  it('filters out multiple users scheduled on the same date', async () => {
    const schedule: Schedule = {
      id: 1, location_id: 1,
      week_start: '2026-02-16T00:00:00.000000Z',
      status: 'draft', published_at: null, published_by: null,
      entries: [
        makeEntry({ id: 101, user_id: 10, date: '2026-02-16' }),
        makeEntry({ id: 102, user_id: 20, date: '2026-02-16' }),
      ],
      created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z',
    }

    const wrapper = await mountAndOpenForm(schedule, '2026-02-16')
    const userIds = getDropdownUserIds(wrapper)

    // Alice (10) and Bob (20) are both scheduled — only Charlie (30) should remain
    expect(userIds).not.toContain(10)
    expect(userIds).not.toContain(20)
    expect(userIds).toContain(30)
  })

  /**
   * Test 5: Users on other dates are not affected by filtering.
   *
   * When Alice is on Monday (02-16) and Bob is on Tuesday (02-17), opening
   * the form for Monday should only filter out Alice. Bob and Charlie remain.
   */
  it('only filters users for the specific selected date', async () => {
    const schedule: Schedule = {
      id: 1, location_id: 1,
      week_start: '2026-02-16T00:00:00.000000Z',
      status: 'draft', published_at: null, published_by: null,
      entries: [
        makeEntry({ id: 101, user_id: 10, date: '2026-02-16' }),
        makeEntry({ id: 102, user_id: 20, date: '2026-02-17' }),
      ],
      created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z',
    }

    // Open form for Monday (02-16) — only Alice should be filtered
    const wrapper = await mountAndOpenForm(schedule, '2026-02-16')
    const mondayUserIds = getDropdownUserIds(wrapper)

    expect(mondayUserIds).not.toContain(10)  // Alice is on Monday
    expect(mondayUserIds).toContain(20)       // Bob is on Tuesday, not Monday
    expect(mondayUserIds).toContain(30)       // Charlie is free
  })
})
