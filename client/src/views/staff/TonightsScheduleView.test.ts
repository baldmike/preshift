/**
 * TonightsScheduleView.test.ts
 *
 * Tests for the TonightsScheduleView component which shows all staff
 * working today at the user's location, grouped by shift template.
 *
 * Verifies:
 *   1. The component renders without crashing and displays the page heading.
 *   2. Sub-navigation pill links render with correct routes.
 *   3. The loading state text is visible while fetching.
 *   4. The empty state message renders when no shifts are scheduled today.
 *   5. The today label subtitle renders with context about today's date.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import TonightsScheduleView from '@/views/staff/TonightsScheduleView.vue'

// ── Mock composables ────────────────────────────────────────────────────────

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: ref({ id: 1, name: 'Test', role: 'server', location_id: 1 }),
    locationId: ref(1),
    isAdmin: ref(false),
    isManager: ref(false),
    isStaff: ref(true),
  }),
}))

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockImplementation((url: string) => {
      if (url === '/api/schedules') {
        return Promise.resolve({ data: [] })
      }
      if (url === '/api/acknowledgments/summary') {
        return Promise.resolve({ data: { total_items: 0, users: [] } })
      }
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
  return mount(TonightsScheduleView, {
    global: {
      stubs: {
        'router-link': routerLinkStub,
        AppShell: { template: '<div><slot /></div>' },
        EmployeeProfileModal: true,
        Teleport: true,
      },
      mocks: {
        $route: { path: '/tonights-schedule' },
      },
    },
  })
}

/** Restores the default api.get mock implementation */
async function resetApiMock() {
  const useApi = await import('@/composables/useApi')
  const apiMock = useApi.default as any
  apiMock.get.mockImplementation((url: string) => {
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

describe('TonightsScheduleView', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    await resetApiMock()
  })

  /**
   * The page heading "Tonight's Schedule" must be visible so staff
   * know which view they are looking at.
   */
  it('renders the page heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain("Tonight's Schedule")
  })

  /**
   * The subtitle must include "everyone working today" to describe
   * the page purpose.
   */
  it('renders the subtitle with today context', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('everyone working today')
  })

  /**
   * The sub-navigation pill links must point to the correct sibling
   * schedule-related routes (Drop Board and Time Off).
   */
  it('renders sub-navigation pill links', async () => {
    const wrapper = mountView()
    await flushPromises()

    const links = wrapper.findAll('a.router-link')
    const hrefs = links.map(l => l.attributes('href'))

    expect(hrefs).toContain('/shift-drops')
    expect(hrefs).toContain('/time-off')
  })

  /**
   * The pill link labels should match the expected text content.
   */
  it('pill links display correct labels', async () => {
    const wrapper = mountView()
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('Drop Board')
    expect(text).toContain('Time Off')
  })

  /**
   * When no schedule entries exist for today, the empty state message
   * should be displayed indicating no shifts are scheduled.
   */
  it('shows empty state when no shifts are scheduled today', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('No shifts scheduled today')
    expect(wrapper.text()).toContain('Check the full schedule for upcoming shifts')
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
