/**
 * TonightsScheduleView.spec.ts
 *
 * Unit tests for the Tonight's Schedule view component.
 *
 * Tests verify:
 *  1. Grouped shift entries render correctly with staff names and role badges.
 *  2. Empty state renders when no entries match today.
 *  3. Loading spinner renders while data is being fetched.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import { useScheduleStore } from '@/stores/schedule'
import TonightsScheduleView from '@/views/staff/TonightsScheduleView.vue'
import type { Schedule, ScheduleEntry, ShiftTemplate } from '@/types'

// ── Stubs ──────────────────────────────────────────────────────────────────

/** Stub AppShell to avoid importing the full layout tree */
const AppShellStub = { template: '<div><slot /></div>' }

/** Stub router-link to avoid requiring vue-router in tests */
const RouterLinkStub = {
  template: '<a><slot /></a>',
  props: ['to'],
}

// ── Helpers ────────────────────────────────────────────────────────────────

/** Freeze Date to a specific day so todayISO / todayLabel are predictable */
function freezeDate(isoDate: string) {
  const frozen = new Date(isoDate + 'T12:00:00')
  const OrigDate = globalThis.Date

  globalThis.Date = class extends OrigDate {
    constructor(...args: any[]) {
      if (args.length === 0) {
        super(frozen.getTime())
      } else {
        // @ts-expect-error — spread into Date constructor
        super(...args)
      }
    }
    static now() {
      return frozen.getTime()
    }
  } as DateConstructor

  return () => {
    globalThis.Date = OrigDate
  }
}

function makeEntry(overrides: Partial<ScheduleEntry> & { date: string }): ScheduleEntry {
  return {
    id: Math.floor(Math.random() * 10000),
    schedule_id: 1,
    user_id: 1,
    shift_template_id: 1,
    role: 'server' as const,
    notes: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

function makeSchedule(entries: ScheduleEntry[]): Schedule {
  return {
    id: 1,
    location_id: 1,
    week_start: '2026-02-16',
    status: 'published',
    published_at: '2026-02-16T08:00:00Z',
    published_by: 1,
    entries,
    created_at: '2026-02-16T00:00:00Z',
    updated_at: '2026-02-16T00:00:00Z',
  }
}

// ── Test suite ─────────────────────────────────────────────────────────────

describe('TonightsScheduleView', () => {
  let restoreDate: () => void

  beforeEach(() => {
    setActivePinia(createPinia())
    // Freeze to Wednesday Feb 18, 2026
    restoreDate = freezeDate('2026-02-18')
  })

  afterEach(() => {
    restoreDate()
  })

  function mountView() {
    const store = useScheduleStore()
    // Stub fetchCurrentWeekSchedule so it resolves immediately without API calls
    store.fetchCurrentWeekSchedule = vi.fn().mockResolvedValue(undefined)

    return mount(TonightsScheduleView, {
      global: {
        stubs: {
          AppShell: AppShellStub,
          'router-link': RouterLinkStub,
        },
      },
    })
  }

  it('renders grouped shift entries with staff names and role badges', async () => {
    const store = useScheduleStore()

    // Pre-populate the store with a schedule containing today's entries
    store.currentSchedule = makeSchedule([
      makeEntry({
        id: 1,
        date: '2026-02-18',
        shift_template_id: 10,
        user_id: 1,
        role: 'server',
        user: { id: 1, name: 'Alice', email: 'a@t.com', role: 'server', roles: null, location_id: 1, is_superadmin: false, phone: null, availability: null, created_at: '', updated_at: '' },
        shift_template: { id: 10, location_id: 1, name: 'Dinner', start_time: '16:00', created_at: '', updated_at: '' },
      }),
      makeEntry({
        id: 2,
        date: '2026-02-18',
        shift_template_id: 10,
        user_id: 2,
        role: 'bartender',
        user: { id: 2, name: 'Bob', email: 'b@t.com', role: 'bartender', roles: null, location_id: 1, is_superadmin: false, phone: null, availability: null, created_at: '', updated_at: '' },
        shift_template: { id: 10, location_id: 1, name: 'Dinner', start_time: '16:00', created_at: '', updated_at: '' },
      }),
    ])

    const wrapper = mountView()
    await flushPromises()

    // Should show shift time heading "4:00 PM"
    expect(wrapper.text()).toContain('4:00 PM')
    // Should show both staff names
    expect(wrapper.text()).toContain('Alice')
    expect(wrapper.text()).toContain('Bob')
    // Should show role badges
    expect(wrapper.text()).toContain('server')
    expect(wrapper.text()).toContain('bartender')
    // Should show the count badge
    expect(wrapper.text()).toContain('2')
  })

  it('shows empty state when no entries match today', async () => {
    const store = useScheduleStore()

    // Schedule exists but entries are for a different day
    store.currentSchedule = makeSchedule([
      makeEntry({ id: 1, date: '2026-02-19', shift_template_id: 10 }),
    ])

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('No shifts scheduled today')
  })

  it('shows empty state when no schedule exists', async () => {
    const store = useScheduleStore()
    store.currentSchedule = null

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('No shifts scheduled today')
  })

  it('shows loading spinner while fetching', async () => {
    const store = useScheduleStore()

    // Make fetchCurrentWeekSchedule hang so loading stays true
    let resolvePromise: () => void
    store.fetchCurrentWeekSchedule = vi.fn().mockImplementation(
      () => new Promise<void>((resolve) => { resolvePromise = resolve })
    )

    const wrapper = mount(TonightsScheduleView, {
      global: {
        stubs: {
          AppShell: AppShellStub,
          'router-link': RouterLinkStub,
        },
      },
    })

    // Wait for onMounted to execute and set loading = true
    await nextTick()

    // Loading spinner should be visible
    expect(wrapper.find('.animate-spin').exists()).toBe(true)
    expect(wrapper.text()).toContain('Loading schedule...')

    // Resolve the promise and check loading goes away
    resolvePromise!()
    await flushPromises()

    expect(wrapper.find('.animate-spin').exists()).toBe(false)
  })
})
