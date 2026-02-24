/**
 * ShiftDropBoardView.test.ts
 *
 * Tests for the ShiftDropBoardView component which shows available shifts
 * to pick up and the user's own drops.
 *
 * Verifies:
 *   1. The component renders without crashing and displays the page heading.
 *   2. Sub-navigation pill links render with correct routes.
 *   3. Both section headers ("Available Shifts" and "My Drops") render.
 *   4. The loading state text is present in the template.
 *   5. Empty state messages render when no drops exist.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import ShiftDropBoardView from '@/views/staff/ShiftDropBoardView.vue'

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
      if (url === '/api/shift-drops') {
        return Promise.resolve({ data: [] })
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
  return mount(ShiftDropBoardView, {
    global: {
      stubs: {
        'router-link': routerLinkStub,
        AppShell: { template: '<div><slot /></div>' },
        ShiftDropCard: true,
        Teleport: true,
      },
      mocks: {
        $route: { path: '/shift-drops' },
      },
    },
  })
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('ShiftDropBoardView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  /**
   * The page heading "Drop Board" must be visible so staff know
   * which view they are looking at.
   */
  it('renders the page heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Drop Board')
  })

  /**
   * The subtitle must be displayed to describe the page purpose.
   */
  it('renders the page subtitle', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Pick up available shifts or manage your drops')
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
    expect(hrefs).toContain('/time-off')
  })

  /**
   * The pill link labels should match the expected text content.
   */
  it('pill links display correct labels', async () => {
    const wrapper = mountView()
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain("Tonight's Schedule")
    expect(text).toContain('Time Off')
  })

  /**
   * Both section headers ("Available Shifts" and "My Drops") must render
   * so users can distinguish between the two lists of shift drops.
   */
  it('renders both section headers', async () => {
    const wrapper = mountView()
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('Available Shifts')
    expect(text).toContain('My Drops')
  })

  /**
   * The loading state text "Loading shift drops..." must be visible while
   * the API fetch is in progress. We verify by making the store's fetch
   * return a promise that never resolves, keeping loading=true.
   */
  it('shows loading text while fetching', async () => {
    const useApi = await import('@/composables/useApi')
    const apiMock = useApi.default as any
    apiMock.get.mockImplementationOnce(() => new Promise(() => {}))

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Loading shift drops...')
  })

  /**
   * When no drops exist, both empty state messages should render:
   * one for available shifts and one for the user's own drops.
   */
  it('shows empty state messages when no drops exist', async () => {
    const wrapper = mountView()
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('No available shifts to pick up right now')
    expect(text).toContain("You haven't dropped any shifts")
  })
})
