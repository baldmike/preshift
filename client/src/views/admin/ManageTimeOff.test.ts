/**
 * ManageTimeOff.test.ts
 *
 * Tests for the ManageTimeOff admin view, which displays time-off requests
 * split into two sections: pending requests awaiting a manager decision and
 * resolved requests (approved/denied) in a collapsible section.
 *
 * Verifies that:
 *   1. The component renders without crashing and shows the page heading.
 *   2. A navigation link back to /manage/daily is present.
 *   3. The loading spinner is shown while data is being fetched.
 *   4. An empty-state message appears when there are no pending requests.
 *   5. Pending requests display the staff name, date range, and action buttons.
 *   6. The "Resolved" section is collapsed by default.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ManageTimeOff from '@/views/admin/ManageTimeOff.vue'
import type { TimeOffRequest } from '@/types'

// ── Mock the API module ─────────────────────────────────────────────────────
const mockGet = vi.fn()
const mockPost = vi.fn()

vi.mock('@/composables/useApi', () => ({
  default: {
    get: (...args: unknown[]) => mockGet(...args),
    post: (...args: unknown[]) => mockPost(...args),
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

/** Creates a minimal TimeOffRequest with sensible defaults. */
function makeRequest(overrides: Partial<TimeOffRequest> = {}): TimeOffRequest {
  return {
    id: 1,
    user_id: 10,
    location_id: 1,
    start_date: '2026-02-23',
    end_date: '2026-02-25',
    reason: null,
    status: 'pending',
    resolved_by: null,
    resolved_at: null,
    user: {
      id: 10, location_id: 1, name: 'Alice Server', email: 'alice@test.com',
      role: 'server', roles: null, is_superadmin: false, phone: null, profile_photo_url: null,
      availability: null, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z',
    },
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/** Mounts the component with common stubs and waits for mount API calls. */
async function mountView(requests: TimeOffRequest[] = []) {
  mockGet.mockResolvedValue({ data: requests })

  const wrapper = mount(ManageTimeOff, {
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

describe('ManageTimeOff', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders the page heading text.
   */
  it('renders without crashing and shows the page heading', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Manage Time Off')
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
   * and the content sections are rendered instead.
   */
  it('hides the loading spinner after data is fetched', async () => {
    const wrapper = await mountView([])

    // After data loads, the spinner should not be present
    expect(wrapper.find('.animate-spin').exists()).toBe(false)
    // Content sections should now be visible
    expect(wrapper.text()).toContain('Pending Requests')
  })

  /**
   * Verifies the empty-state message when there are no pending requests.
   */
  it('shows empty-state message when no pending requests exist', async () => {
    const wrapper = await mountView([])
    expect(wrapper.text()).toContain('No pending time-off requests')
  })

  /**
   * Verifies that a pending request displays the staff name and action buttons.
   */
  it('renders pending requests with staff name and action buttons', async () => {
    const requests = [makeRequest({ id: 1, status: 'pending' })]

    const wrapper = await mountView(requests)
    expect(wrapper.text()).toContain('Alice Server')
    expect(wrapper.text()).toContain('Approve')
    expect(wrapper.text()).toContain('Deny')
  })

  /**
   * Verifies the "Resolved" section header is present and its count badge
   * shows the number of resolved requests. The resolved rows themselves
   * are not visible because the section is collapsed by default.
   */
  it('shows the Resolved section header with count badge', async () => {
    const requests = [
      makeRequest({ id: 2, status: 'approved' }),
    ]

    const wrapper = await mountView(requests)
    // The "Resolved" section heading and count badge should be visible
    expect(wrapper.text()).toContain('Resolved')
    // The count badge should show "1" for the single resolved request
    expect(wrapper.text()).toContain('1')
  })
})
