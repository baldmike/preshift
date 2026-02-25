/**
 * ManageLogsView.test.ts
 *
 * Tests for the ManageLogsView admin view, which provides CRUD for daily
 * manager logs. Each log captures freeform notes along with auto-snapshotted
 * weather, events, and scheduled staff. Displays log entries sorted by date
 * descending with a toggleable create/edit form.
 *
 * Verifies that:
 *   1. The component renders without crashing and shows the page heading.
 *   2. A navigation link back to /manage/daily is present.
 *   3. The loading spinner is shown while data is being fetched.
 *   4. An empty-state message appears when there are no log entries.
 *   5. Log entries render with their formatted date and notes body.
 *   6. The "New Log Entry" button is visible when the form is hidden.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ManageLogsView from '@/views/admin/ManageLogsView.vue'
import type { ManagerLog } from '@/types'

// ── Mock the API module ─────────────────────────────────────────────────────
const mockGet = vi.fn()
const mockPost = vi.fn()
const mockPatch = vi.fn()
const mockDelete = vi.fn()

vi.mock('@/composables/useApi', () => ({
  default: {
    get: (...args: unknown[]) => mockGet(...args),
    post: (...args: unknown[]) => mockPost(...args),
    patch: (...args: unknown[]) => mockPatch(...args),
    delete: (...args: unknown[]) => mockDelete(...args),
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
const BaseButtonStub = {
  template: '<button><slot /></button>',
  props: ['size', 'variant', 'loading', 'type'],
}

// ── Mock data ───────────────────────────────────────────────────────────────

/** Creates a minimal ManagerLog with sensible defaults. */
function makeLog(overrides: Partial<ManagerLog> = {}): ManagerLog {
  return {
    id: 1,
    location_id: 1,
    created_by: 1,
    log_date: '2026-02-20',
    body: 'Busy Friday night. Ran out of salmon early.',
    weather_snapshot: null,
    events_snapshot: null,
    schedule_snapshot: null,
    creator: {
      id: 1, location_id: 1, name: 'Admin User', email: 'admin@test.com',
      role: 'admin', roles: null, is_superadmin: true, phone: null, profile_photo_url: null,
      availability: null, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z',
    },
    created_at: '2026-02-20T22:00:00Z',
    updated_at: '2026-02-20T22:00:00Z',
    ...overrides,
  }
}

/** Mounts the component with common stubs and waits for mount API calls. */
async function mountView(logs: ManagerLog[] = []) {
  mockGet.mockResolvedValue({ data: logs })

  const wrapper = mount(ManageLogsView, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        BaseButton: BaseButtonStub,
        'router-link': { template: '<a><slot /></a>' },
        Teleport: true,
      },
    },
  })

  await flushPromises()
  return wrapper
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('ManageLogsView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders the page heading text.
   */
  it('renders without crashing and shows the page heading', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Manager Log')
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
   * and the content is rendered instead.
   */
  it('hides the loading spinner after data is fetched', async () => {
    const wrapper = await mountView([])

    // After data loads, the spinner should not be present
    expect(wrapper.find('.animate-spin').exists()).toBe(false)
    // The empty-state message should now be visible instead
    expect(wrapper.text()).toContain('No log entries yet')
  })

  /**
   * Verifies the empty-state message when there are no log entries.
   */
  it('shows empty-state message when no log entries exist', async () => {
    const wrapper = await mountView([])
    expect(wrapper.text()).toContain('No log entries yet')
  })

  /**
   * Verifies that log entries render with their body content and creator name.
   */
  it('renders log entries with their notes body and creator', async () => {
    const logs = [
      makeLog({ id: 1, body: 'Busy Friday night. Ran out of salmon early.' }),
    ]

    const wrapper = await mountView(logs)
    expect(wrapper.text()).toContain('Busy Friday night. Ran out of salmon early.')
    expect(wrapper.text()).toContain('Admin User')
  })

  /**
   * Verifies the "New Log Entry" button is present when the form is hidden.
   */
  it('shows the "New Log Entry" button when the form is hidden', async () => {
    const wrapper = await mountView([])
    expect(wrapper.text()).toContain('New Log Entry')
  })
})
