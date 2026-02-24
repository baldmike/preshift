/**
 * SpecialsView.test.ts
 *
 * Tests for the SpecialsView component which lists current daily specials.
 *
 * Verifies:
 *   1. The component renders without crashing and displays the page heading.
 *   2. The "Corner!" back link points to /dashboard.
 *   3. The empty state message renders when no specials are returned.
 *   4. SpecialCard stubs render when specials data is present.
 *   5. The loading spinner markup exists in the template.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import SpecialsView from '@/views/staff/SpecialsView.vue'

// ── Mock composables ────────────────────────────────────────────────────────

/** Specials returned by the mocked GET /api/specials endpoint */
let mockSpecials: any[] = []

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockImplementation((url: string) => {
      if (url === '/api/specials') {
        return Promise.resolve({ data: mockSpecials })
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
  return mount(SpecialsView, {
    global: {
      stubs: {
        'router-link': routerLinkStub,
        AppShell: { template: '<div><slot /></div>' },
        SpecialCard: true,
        Teleport: true,
      },
      mocks: {
        $route: { path: '/specials' },
      },
    },
  })
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('SpecialsView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockSpecials = []
  })

  /**
   * The page heading "Today's Specials" must always be visible so staff
   * know which view they are on.
   */
  it('renders the page heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain("Today's Specials")
  })

  /**
   * The "Corner!" back link must navigate to /dashboard so users
   * can return to the main view.
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
   * When the API returns an empty specials array, the empty state text
   * "No specials running today." must be displayed.
   */
  it('shows empty state when no specials exist', async () => {
    mockSpecials = []
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('No specials running today.')
  })

  /**
   * When the API returns specials, SpecialCard components should be
   * rendered as stubs and the empty state text should not appear.
   */
  it('renders special cards when data is returned', async () => {
    mockSpecials = [
      { id: 1, name: 'Half-price wings', description: 'All night', type: 'food' },
      { id: 2, name: '$5 margaritas', description: 'Until 8 PM', type: 'drink' },
    ]
    const wrapper = mountView()
    await flushPromises()

    const cards = wrapper.findAllComponents({ name: 'SpecialCard' })
    expect(cards.length).toBe(2)
    expect(wrapper.text()).not.toContain('No specials running today.')
  })

  /**
   * The loading spinner must be visible while the API fetch is in progress.
   * We verify by making the API return a promise that never resolves,
   * keeping loading=true.
   */
  it('shows loading spinner while fetching', async () => {
    const useApi = await import('@/composables/useApi')
    const apiMock = useApi.default as any
    apiMock.get.mockImplementationOnce(() => new Promise(() => {}))

    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.html()).toContain('animate-spin')
  })
})
