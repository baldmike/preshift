/**
 * DashboardView.test.ts
 *
 * Tests for the DashboardView component's navigation elements.
 *
 * Verifies:
 *   1. Schedule nav pill links (Tonight's Schedule, Drop Board, Time Off) always
 *      render regardless of whether a published schedule exists.
 *   2. The pill links point to the correct route paths.
 *   3. The Messages section link to /messages renders.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import DashboardView from '@/views/staff/DashboardView.vue'

// ── Mock composables ────────────────────────────────────────────────────────

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: ref({ id: 1, name: 'Test', role: 'server', location_id: 1 }),
    locationId: ref(1),
    isAdmin: ref(false),
    isManager: ref(false),
  }),
}))

/** Empty preshift response matching the shape GET /api/preshift returns */
const emptyPreshiftData = {
  eighty_sixed: [],
  specials: [],
  push_items: [],
  announcements: [{ id: 1, body: 'Test announcement', created_at: '2026-01-01' }],
  events: [],
  acknowledgments: [],
}

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockImplementation((url: string) => {
      if (url === '/api/preshift') {
        return Promise.resolve({ data: emptyPreshiftData })
      }
      if (url === '/api/weather') {
        return Promise.resolve({ data: null })
      }
      // Default: schedules, shifts, etc.
      return Promise.resolve({ data: [] })
    }),
    post: vi.fn().mockResolvedValue({ data: {} }),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

vi.mock('@/composables/useReverb', () => ({
  useLocationChannel: () => ({
    listen: vi.fn().mockReturnThis(),
    stopListening: vi.fn(),
  }),
}))

vi.mock('@/composables/useSchedule', () => ({
  useSchedule: () => ({
    nextShift: ref(null),
    currentWeekShifts: ref([]),
    currentWeekRange: ref(''),
    formatShiftTime: (t: string) => t,
  }),
}))

// ── Stubs ───────────────────────────────────────────────────────────────────

const routerLinkStub = {
  template: '<a :href="to" class="router-link"><slot /></a>',
  props: ['to'],
}

function mountDashboard() {
  return mount(DashboardView, {
    global: {
      stubs: {
        'router-link': routerLinkStub,
        AppShell: { template: '<div><slot /></div>' },
        EightySixedCard: true,
        SpecialCard: true,
        PushItemCard: true,
        AnnouncementCard: true,
        EmployeeProfileModal: true,
        TileDetailModal: true,
        Teleport: true,
      },
      mocks: {
        $route: { path: '/dashboard' },
      },
    },
  })
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('DashboardView navigation', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  /**
   * The three schedule nav pill links must render even when no published
   * schedule exists for the current week (scheduleStore.currentSchedule is null).
   * This was a bug where the links were trapped inside a v-if that hid them.
   */
  it('renders schedule nav pills when no published schedule exists', async () => {
    const wrapper = mountDashboard()
    await flushPromises()

    const links = wrapper.findAll('a.router-link')
    const hrefs = links.map(l => l.attributes('href'))

    expect(hrefs).toContain('/tonights-schedule')
    expect(hrefs).toContain('/shift-drops')
    expect(hrefs).toContain('/time-off')
  })

  /**
   * Verify that the pill link text content matches the expected labels
   * so users can identify the navigation targets.
   */
  it('pill links display correct labels', async () => {
    const wrapper = mountDashboard()
    await flushPromises()

    const text = wrapper.text()

    expect(text).toContain("Tonight's Schedule")
    expect(text).toContain('Drop Board')
    expect(text).toContain('Time Off')
  })

  /**
   * The Messages section should always render with a link to the messages view.
   */
  it('renders Messages section link', async () => {
    const wrapper = mountDashboard()
    await flushPromises()

    const links = wrapper.findAll('a.router-link')
    const hrefs = links.map(l => l.attributes('href'))

    expect(hrefs).toContain('/messages')
  })
})
