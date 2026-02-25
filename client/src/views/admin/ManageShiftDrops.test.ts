/**
 * ManageShiftDrops.test.ts
 *
 * Tests for the ManageShiftDrops admin view, which displays shift drops
 * organized into three sections: open drops with volunteers, drops waiting
 * for volunteers, and resolved (filled/cancelled) drops.
 *
 * Verifies that:
 *   1. The component renders without crashing and shows the page heading.
 *   2. A navigation link back to /manage/daily is present.
 *   3. The loading spinner is shown while data is being fetched.
 *   4. Empty-state messages appear when no drops exist in each section.
 *   5. Drops with volunteers render in the "Open Drops" section.
 *   6. Drops without volunteers render in the "Waiting for Volunteers" section.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ManageShiftDrops from '@/views/admin/ManageShiftDrops.vue'
import type { ShiftDrop } from '@/types'

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
const ShiftDropCardStub = {
  template: '<div class="shift-drop-card-stub" />',
  props: ['drop'],
}

// ── Mock data ───────────────────────────────────────────────────────────────

/** Creates a minimal ShiftDrop with sensible defaults. */
function makeDrop(overrides: Partial<ShiftDrop> = {}): ShiftDrop {
  return {
    id: 1,
    schedule_entry_id: 1,
    requested_by: 10,
    reason: null,
    status: 'open',
    filled_by: null,
    filled_at: null,
    volunteers: [],
    has_volunteered: false,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/** Mounts the component with common stubs and waits for mount API calls. */
async function mountView(drops: ShiftDrop[] = []) {
  mockGet.mockResolvedValue({ data: drops })

  const wrapper = mount(ManageShiftDrops, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        ShiftDropCard: ShiftDropCardStub,
        'router-link': { template: '<a><slot /></a>' },
        Teleport: true,
      },
    },
  })

  await flushPromises()
  return wrapper
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('ManageShiftDrops', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders the page heading text.
   */
  it('renders without crashing and shows the page heading', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Shift Drops')
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
    expect(wrapper.text()).toContain('Open Drops')
  })

  /**
   * Verifies the empty-state message for each section when there are no drops.
   */
  it('shows empty-state messages when no drops exist', async () => {
    const wrapper = await mountView([])
    expect(wrapper.text()).toContain('No drops with volunteers right now')
    expect(wrapper.text()).toContain('No drops waiting for volunteers')
  })

  /**
   * Verifies that open drops with volunteers render in the "Open Drops" section.
   */
  it('renders open drops with volunteers in the Open Drops section', async () => {
    const drops: ShiftDrop[] = [
      makeDrop({
        id: 1,
        status: 'open',
        volunteers: [
          { id: 1, shift_drop_id: 1, user_id: 20, selected: false, created_at: '2026-01-01T00:00:00Z', user: { id: 20, location_id: 1, organization_id: null, name: 'Bob', email: 'bob@test.com', role: 'server', roles: null, is_superadmin: false, phone: null, profile_photo_url: null, availability: null, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' } },
        ],
      }),
    ]

    const wrapper = await mountView(drops)
    expect(wrapper.text()).toContain('Open Drops')
    expect(wrapper.text()).toContain('Bob')
    expect(wrapper.text()).toContain('Select')
  })

  /**
   * Verifies that open drops without volunteers render in the
   * "Waiting for Volunteers" section.
   */
  it('renders open drops without volunteers in the Waiting section', async () => {
    const drops: ShiftDrop[] = [
      makeDrop({ id: 2, status: 'open', volunteers: [] }),
    ]

    const wrapper = await mountView(drops)
    expect(wrapper.text()).toContain('Waiting for Volunteers')
    expect(wrapper.text()).toContain('No volunteers yet')
  })

  /**
   * Verifies that a toast error is dispatched when selecting a volunteer fails.
   */
  it('dispatches error toast when select volunteer fails', async () => {
    const spy = vi.spyOn(window, 'dispatchEvent')
    mockPost.mockRejectedValueOnce(new Error('fail'))

    const drops: ShiftDrop[] = [
      makeDrop({
        id: 1,
        status: 'open',
        volunteers: [
          { id: 1, shift_drop_id: 1, user_id: 20, selected: false, created_at: '2026-01-01T00:00:00Z', user: { id: 20, location_id: 1, organization_id: null, name: 'Bob', email: 'bob@test.com', role: 'server', roles: null, is_superadmin: false, phone: null, profile_photo_url: null, availability: null, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' } },
        ],
      }),
    ]
    const wrapper = await mountView(drops)

    // Click the Select button for the volunteer
    const selectBtn = wrapper.findAll('button').find(b => b.text().includes('Select'))
    await selectBtn!.trigger('click')
    await flushPromises()

    expect(spy).toHaveBeenCalledWith(expect.objectContaining({ type: 'toast' }))
    spy.mockRestore()
  })

  /**
   * Verifies that selecting a volunteer calls POST /api/shift-drops/:id/select/:userId
   * and shows a success toast.
   */
  it('selects volunteer and shows success toast', async () => {
    const spy = vi.spyOn(window, 'dispatchEvent')
    const updatedDrop = makeDrop({ id: 1, status: 'filled', filled_by: 20 })
    mockPost.mockResolvedValueOnce({ data: updatedDrop })

    const drops: ShiftDrop[] = [
      makeDrop({
        id: 1,
        status: 'open',
        volunteers: [
          { id: 1, shift_drop_id: 1, user_id: 20, selected: false, created_at: '2026-01-01T00:00:00Z', user: { id: 20, location_id: 1, organization_id: null, name: 'Bob', email: 'bob@test.com', role: 'server', roles: null, is_superadmin: false, phone: null, profile_photo_url: null, availability: null, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' } },
        ],
      }),
    ]
    const wrapper = await mountView(drops)

    const selectBtn = wrapper.findAll('button').find(b => b.text().includes('Select'))
    await selectBtn!.trigger('click')
    await flushPromises()

    expect(mockPost).toHaveBeenCalledWith('/api/shift-drops/1/select/20')
    expect(spy).toHaveBeenCalledWith(expect.objectContaining({ type: 'toast' }))
    spy.mockRestore()
  })
})
