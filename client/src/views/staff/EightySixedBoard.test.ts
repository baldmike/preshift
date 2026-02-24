/**
 * EightySixedBoard.test.ts
 *
 * Tests for the EightySixedBoard view which lists all currently 86'd items.
 *
 * Verifies:
 *   1. The component renders without crashing and displays the page heading.
 *   2. The "Corner!" back link points to /dashboard.
 *   3. The empty state message renders when no items exist.
 *   4. Items render as EightySixedCard stubs when data is returned.
 *   5. The loading state text renders while fetching.
 *   6. The manager form is hidden for staff users.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import EightySixedBoard from '@/views/staff/EightySixedBoard.vue'

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

/** Items returned by the mocked GET /api/eighty-sixed endpoint */
let mockItems: any[] = []

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockImplementation((url: string) => {
      if (url === '/api/eighty-sixed') {
        return Promise.resolve({ data: mockItems })
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
  return mount(EightySixedBoard, {
    global: {
      stubs: {
        'router-link': routerLinkStub,
        AppShell: { template: '<div><slot /></div>' },
        EightySixedCard: true,
        Teleport: true,
      },
      mocks: {
        $route: { path: '/eighty-sixed' },
      },
    },
  })
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('EightySixedBoard', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockItems = []
  })

  /**
   * The page heading "86'd Board" must always be visible so staff
   * know which view they are looking at.
   */
  it('renders the page heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain("86'd Board")
  })

  /**
   * The back navigation link labelled "Corner!" must point to /dashboard
   * so staff can return to the main dashboard.
   */
  it('renders the Corner! back link to /dashboard', async () => {
    const wrapper = mountView()
    await flushPromises()

    const links = wrapper.findAll('a.router-link')
    const hrefs = links.map(l => l.attributes('href'))
    expect(hrefs).toContain('/dashboard')

    const cornerLink = links.find(l => l.text().includes('Corner!'))
    expect(cornerLink).toBeTruthy()
  })

  /**
   * When the API returns an empty array of 86'd items, the empty state
   * message "Nothing is 86'd right now" must be displayed.
   */
  it('shows empty state when no items exist', async () => {
    mockItems = []
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain("Nothing is 86'd right now")
    expect(wrapper.text()).toContain('All clear')
  })

  /**
   * When the API returns items, EightySixedCard components should be
   * rendered (as stubs) and the empty state should not appear.
   */
  it('renders item cards when data is returned', async () => {
    mockItems = [
      { id: 1, item_name: 'Salmon', reason: 'Out of stock' },
      { id: 2, item_name: 'Lobster', reason: null },
    ]
    const wrapper = mountView()
    await flushPromises()

    const cards = wrapper.findAllComponents({ name: 'EightySixedCard' })
    expect(cards.length).toBe(2)
    expect(wrapper.text()).not.toContain("Nothing is 86'd right now")
  })

  /**
   * The loading text "Loading 86'd items..." must appear while the
   * API fetch is in progress. We verify by making the API return a
   * promise that never resolves, keeping loading=true.
   */
  it('shows loading text while fetching', async () => {
    const useApi = await import('@/composables/useApi')
    const apiMock = useApi.default as any
    apiMock.get.mockImplementationOnce(() => new Promise(() => {}))

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain("Loading 86'd items...")
  })

  /**
   * When the user is a regular staff member (server), the manager form
   * for adding new 86'd items should not be rendered because the form
   * is gated behind v-if="isAdmin || isManager".
   */
  it('hides the add-item form for staff users', async () => {
    const wrapper = mountView()
    await flushPromises()

    const form = wrapper.find('form')
    expect(form.exists()).toBe(false)
  })
})
