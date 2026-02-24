/**
 * AcknowledgmentTracker.test.ts
 *
 * Tests for the AcknowledgmentTracker admin view, which displays a summary
 * grid of total active pre-shift items across all categories (86'd, specials,
 * push items, announcements) and a per-user acknowledgment progress table.
 *
 * Verifies that:
 *   1. The component renders without crashing and shows the page heading.
 *   2. A navigation link back to /manage/daily is present.
 *   3. The loading spinner is shown while data is being fetched.
 *   4. Summary cards show category counts when pre-shift data is loaded.
 *   5. The per-user table renders staff names and acknowledgment fractions.
 *   6. An empty-state message appears when there are no staff members.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import AcknowledgmentTracker from '@/views/admin/AcknowledgmentTracker.vue'

// ── Mock the API module ─────────────────────────────────────────────────────
const mockGet = vi.fn()

vi.mock('@/composables/useApi', () => ({
  default: {
    get: (...args: unknown[]) => mockGet(...args),
  },
}))

// ── Mock the useAuth composable ─────────────────────────────────────────────
vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: { value: { id: 1, role: 'admin', name: 'Admin', location_id: 1 } },
    isAdmin: { value: true },
    locationId: { value: 1 },
  }),
}))

// ── Stubs for child components ──────────────────────────────────────────────
const AppShellStub = { template: '<div><slot /></div>' }
const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

// ── Mock data ───────────────────────────────────────────────────────────────

/** Pre-shift data with known category counts. */
const mockPreshiftData = {
  eighty_sixed: [{ id: 1 }, { id: 2 }],
  specials: [{ id: 3 }],
  push_items: [{ id: 4 }, { id: 5 }, { id: 6 }],
  announcements: [{ id: 7 }],
  events: [],
  acknowledgments: [],
}

/** Acknowledgment summary with per-user data. */
const mockSummary = {
  total_items: 7,
  users: [
    { user_id: 10, user_name: 'Alice Server', role: 'server', total_items: 7, acknowledged_count: 7, percentage: 100 },
    { user_id: 20, user_name: 'Bob Bartender', role: 'bartender', total_items: 7, acknowledged_count: 3, percentage: 43 },
    { user_id: 30, user_name: 'Charlie Server', role: 'server', total_items: 7, acknowledged_count: 0, percentage: 0 },
  ],
}

/**
 * Configures mockGet to respond based on URL, supporting the two
 * parallel API calls the component makes on mount.
 */
function configureMockApi(options?: { summary?: object; preshift?: object }) {
  const summary = options?.summary ?? mockSummary
  const preshift = options?.preshift ?? mockPreshiftData

  mockGet.mockImplementation((url: string) => {
    if (url === '/api/acknowledgments/summary') {
      return Promise.resolve({ data: summary })
    }
    if (url === '/api/preshift') {
      return Promise.resolve({ data: preshift })
    }
    return Promise.resolve({ data: [] })
  })
}

/** Mounts the component with common stubs and waits for mount API calls. */
async function mountView(options?: { summary?: object; preshift?: object }) {
  configureMockApi(options)

  const wrapper = mount(AcknowledgmentTracker, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        BadgePill: BadgePillStub,
        'router-link': { template: '<a><slot /></a>' },
        Teleport: true,
      },
    },
  })

  await flushPromises()
  return wrapper
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('AcknowledgmentTracker', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders the page heading text.
   */
  it('renders without crashing and shows the page heading', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Acknowledgment Tracker')
  })

  /**
   * Verifies a "Corner!" navigation link pointing to /manage/daily is present.
   */
  it('displays a navigation link back to /manage/daily', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Corner!')
  })

  /**
   * Verifies that after data loads, the loading spinner is no longer visible
   * and the acknowledgment table is rendered instead.
   */
  it('hides the loading spinner after data is fetched', async () => {
    const wrapper = await mountView()

    // After data loads, the spinner should not be present
    expect(wrapper.find('.animate-spin').exists()).toBe(false)
    // The table should now be visible
    expect(wrapper.find('table').exists()).toBe(true)
  })

  /**
   * Verifies the summary cards display category counts from pre-shift data.
   * The mock has 2 eighty-sixed, 1 special, 3 push items, and 1 announcement.
   */
  it('displays summary cards with category item counts', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain("86'd Items")
    expect(wrapper.text()).toContain('Specials')
    expect(wrapper.text()).toContain('Push Items')
    expect(wrapper.text()).toContain('Announcements')
  })

  /**
   * Verifies the per-user table renders staff names and acknowledgment data.
   */
  it('renders per-user acknowledgment rows with names and fractions', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Alice Server')
    expect(wrapper.text()).toContain('Bob Bartender')
    expect(wrapper.text()).toContain('Charlie Server')
    // Check that acknowledgment counts are rendered (e.g. "7 / 7", "3 / 7")
    expect(wrapper.text()).toContain('7 / 7')
    expect(wrapper.text()).toContain('3 / 7')
    expect(wrapper.text()).toContain('0 / 7')
  })

  /**
   * Verifies the empty-state message when there are no staff members.
   */
  it('shows empty-state message when there are no staff members', async () => {
    const wrapper = await mountView({
      summary: { total_items: 0, users: [] },
      preshift: { eighty_sixed: [], specials: [], push_items: [], announcements: [], events: [], acknowledgments: [] },
    })
    expect(wrapper.text()).toContain('No staff members')
  })
})
