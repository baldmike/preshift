/**
 * MyScheduleView.test.ts
 *
 * Tests for the MyScheduleView component which shows the weekly schedule,
 * the user's shifts, and a self-service availability editor.
 *
 * Verifies:
 *   1. The component renders without crashing and displays the page heading.
 *   2. Sub-navigation pill links render with correct routes.
 *   3. The loading state text is visible while fetching.
 *   4. The "My Availability" section heading renders.
 *   5. The empty schedule state message renders when no schedule is published.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import MyScheduleView from '@/views/staff/MyScheduleView.vue'

// ── Mock composables ────────────────────────────────────────────────────────

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: ref({ id: 1, name: 'Test', role: 'server', location_id: 1, availability: null }),
    locationId: ref(1),
    isAdmin: ref(false),
    isManager: ref(false),
    isStaff: ref(true),
  }),
}))

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockImplementation((url: string) => {
      if (url === '/api/my-shifts') {
        return Promise.resolve({ data: [] })
      }
      if (url === '/api/schedules') {
        return Promise.resolve({ data: [] })
      }
      if (url === '/api/acknowledgments/summary') {
        return Promise.resolve({ data: { total_items: 0, users: [] } })
      }
      return Promise.resolve({ data: [] })
    }),
    post: vi.fn().mockResolvedValue({ data: {} }),
    put: vi.fn().mockResolvedValue({ data: {} }),
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
    currentWeekShifts: ref({}),
    currentWeekRange: ref({ monday: '2026-02-23', sunday: '2026-03-01' }),
    formatShiftTime: (t: string) => t,
  }),
}))

// ── Stubs ───────────────────────────────────────────────────────────────────

const routerLinkStub = {
  template: '<a :href="to" class="router-link"><slot /></a>',
  props: ['to'],
}

function mountView() {
  return mount(MyScheduleView, {
    global: {
      stubs: {
        'router-link': routerLinkStub,
        AppShell: { template: '<div><slot /></div>' },
        ShiftCard: true,
        ScheduleGrid: true,
        AvailabilityGrid: true,
        EmployeeProfileModal: true,
        Teleport: true,
      },
      mocks: {
        $route: { path: '/my-schedule' },
      },
    },
  })
}

/** Restores the default api.get mock implementation */
async function resetApiMock() {
  const useApi = await import('@/composables/useApi')
  const apiMock = useApi.default as any
  apiMock.get.mockImplementation((url: string) => {
    if (url === '/api/my-shifts') {
      return Promise.resolve({ data: [] })
    }
    if (url === '/api/schedules') {
      return Promise.resolve({ data: [] })
    }
    if (url === '/api/acknowledgments/summary') {
      return Promise.resolve({ data: { total_items: 0, users: [] } })
    }
    return Promise.resolve({ data: [] })
  })
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('MyScheduleView', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    await resetApiMock()
  })

  /**
   * The page heading "Schedule" must render so staff can identify the view.
   */
  it('renders the page heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Schedule')
  })

  /**
   * The sub-navigation pill links must render for Tonight's Schedule,
   * Drop Board, and Time Off with the correct route paths.
   */
  it('renders schedule sub-navigation pill links', async () => {
    const wrapper = mountView()
    await flushPromises()

    const links = wrapper.findAll('a.router-link')
    const hrefs = links.map(l => l.attributes('href'))

    expect(hrefs).toContain('/tonights-schedule')
    expect(hrefs).toContain('/shift-drops')
    expect(hrefs).toContain('/time-off')
  })

  /**
   * The pill link labels should display the correct human-readable text
   * so staff can identify each navigation target.
   */
  it('pill links display correct labels', async () => {
    const wrapper = mountView()
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain("Tonight's Schedule")
    expect(text).toContain('Drop Board')
    expect(text).toContain('Time Off')
  })

  /**
   * The "My Availability" section heading must render for all roles
   * as it is shared across staff/manager/admin layouts.
   */
  it('renders the My Availability section heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('My Availability')
  })

  /**
   * When no published schedule exists (store.currentSchedule is null),
   * the empty schedule message should be displayed.
   */
  it('shows empty schedule message when no schedule is published', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('No published schedule this week')
  })

  /**
   * The loading state text "Loading schedule..." must be visible while
   * the API fetch is in progress. We verify by making the API return a
   * promise that never resolves, keeping loading=true.
   */
  it('shows loading text while fetching', async () => {
    const useApi = await import('@/composables/useApi')
    const apiMock = useApi.default as any
    apiMock.get.mockImplementation(() => new Promise(() => {}))

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Loading schedule...')
  })
})
