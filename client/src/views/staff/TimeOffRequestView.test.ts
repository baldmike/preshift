/**
 * TimeOffRequestView.test.ts
 *
 * Tests for the TimeOffRequestView component which allows staff to
 * submit and view their time-off requests.
 *
 * Verifies:
 *   1. The component renders without crashing and displays the page heading.
 *   2. Sub-navigation pill links render with correct routes.
 *   3. The request form renders with date inputs and a submit button.
 *   4. The loading state text is visible while fetching.
 *   5. The empty state message renders when no requests exist.
 *   6. The "New Request" form heading renders.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import TimeOffRequestView from '@/views/staff/TimeOffRequestView.vue'

// ── Mock composables ────────────────────────────────────────────────────────

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockImplementation((url: string) => {
      if (url === '/api/time-off-requests') {
        return Promise.resolve({ data: [] })
      }
      if (url === '/api/config/settings') {
        return Promise.resolve({ data: { time_off_advance_days: 14 } })
      }
      return Promise.resolve({ data: [] })
    }),
    post: vi.fn().mockResolvedValue({ data: {} }),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

// ── Stubs ───────────────────────────────────────────────────────────────────

const routerLinkStub = {
  template: '<a :href="to" class="router-link"><slot /></a>',
  props: ['to'],
}

function mountView() {
  return mount(TimeOffRequestView, {
    global: {
      stubs: {
        'router-link': routerLinkStub,
        AppShell: { template: '<div><slot /></div>' },
        Teleport: true,
      },
      mocks: {
        $route: { path: '/time-off' },
      },
    },
  })
}

/** Restores the default api.get mock implementation */
async function resetApiMock() {
  const useApi = await import('@/composables/useApi')
  const apiMock = useApi.default as any
  apiMock.get.mockImplementation((url: string) => {
    if (url === '/api/time-off-requests') {
      return Promise.resolve({ data: [] })
    }
    if (url === '/api/config/settings') {
      return Promise.resolve({ data: { time_off_advance_days: 14 } })
    }
    return Promise.resolve({ data: [] })
  })
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('TimeOffRequestView', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    await resetApiMock()
  })

  /**
   * The page heading "Time Off" must be visible so staff know which
   * view they are on.
   */
  it('renders the page heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Time Off')
  })

  /**
   * The subtitle must describe the page purpose.
   */
  it('renders the page subtitle', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Request and manage your time off')
  })

  /**
   * The sub-navigation pill links must point to the correct sibling
   * schedule-related routes.
   */
  it('renders sub-navigation pill links', async () => {
    const wrapper = mountView()
    await flushPromises()

    const links = wrapper.findAll('a.router-link')
    const hrefs = links.map(l => l.attributes('href'))

    expect(hrefs).toContain('/tonights-schedule')
    expect(hrefs).toContain('/shift-drops')
  })

  /**
   * The pill link labels should match the expected text content.
   */
  it('pill links display correct labels', async () => {
    const wrapper = mountView()
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain("Tonight's Schedule")
    expect(text).toContain('Drop Board')
  })

  /**
   * The "New Request" form heading must render so users know where
   * to submit new time-off requests.
   */
  it('renders the New Request form heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('New Request')
  })

  /**
   * The form must include date inputs (type="date") for start and end dates
   * and a submit button.
   */
  it('renders the request form with date inputs and submit button', async () => {
    const wrapper = mountView()
    await flushPromises()

    const dateInputs = wrapper.findAll('input[type="date"]')
    expect(dateInputs.length).toBe(2)

    const submitButton = wrapper.find('button[type="submit"]')
    expect(submitButton.exists()).toBe(true)
    expect(submitButton.text()).toContain('Submit Request')
  })

  /**
   * The form labels for Start Date and End Date must render.
   */
  it('renders date field labels', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Start Date')
    expect(wrapper.text()).toContain('End Date')
  })

  /**
   * When the store has no time-off requests and loading is complete,
   * the empty state message should render.
   */
  it('shows empty state when no requests exist', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('No time-off requests yet')
    expect(wrapper.text()).toContain('Use the form above to submit your first request')
  })

  /**
   * The loading state text "Loading requests..." must be visible while
   * the API fetch is in progress. We verify by making the API return a
   * promise that never resolves, keeping loading=true.
   */
  it('shows loading text while fetching', async () => {
    const useApi = await import('@/composables/useApi')
    const apiMock = useApi.default as any
    apiMock.get.mockImplementation(() => new Promise(() => {}))

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Loading requests...')
  })
})
