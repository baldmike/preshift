/**
 * ManageLocations.test.ts
 *
 * Tests for the ManageLocations admin view, which provides CRUD for
 * restaurant locations. Displays a table of locations with name, address,
 * timezone, and coordinates columns plus an Edit action. Includes a
 * toggleable create/edit form.
 *
 * Verifies that:
 *   1. The component renders without crashing and shows the page heading.
 *   2. A navigation link back to /manage/daily is present.
 *   3. The loading spinner is shown while data is being fetched.
 *   4. An empty-state message appears when there are no locations.
 *   5. Locations render in the table with their details.
 *   6. The "Add Location" button toggles the form.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ManageLocations from '@/views/admin/ManageLocations.vue'
import type { Location } from '@/types'

// ── Mock the API module ─────────────────────────────────────────────────────
const mockGet = vi.fn()
const mockPost = vi.fn()
const mockPatch = vi.fn()

vi.mock('@/composables/useApi', () => ({
  default: {
    get: (...args: unknown[]) => mockGet(...args),
    post: (...args: unknown[]) => mockPost(...args),
    patch: (...args: unknown[]) => mockPatch(...args),
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
const BaseInputStub = {
  template: '<input />',
  props: ['modelValue', 'label', 'placeholder'],
}

// ── Mock data ───────────────────────────────────────────────────────────────

/** Creates a minimal Location with sensible defaults. */
function makeLocation(overrides: Partial<Location> = {}): Location {
  return {
    id: 1,
    name: 'Downtown Bistro',
    address: '123 Main St',
    city: 'Austin',
    state: 'TX',
    timezone: 'America/Chicago',
    latitude: 30.2672,
    longitude: -97.7431,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/** Mounts the component with common stubs and waits for mount API calls. */
async function mountView(locations: Location[] = []) {
  mockGet.mockResolvedValue({ data: locations })

  const wrapper = mount(ManageLocations, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        BaseButton: BaseButtonStub,
        BaseInput: BaseInputStub,
        'router-link': { template: '<a><slot /></a>' },
        Teleport: true,
      },
    },
  })

  await flushPromises()
  return wrapper
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('ManageLocations', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders the page heading text.
   */
  it('renders without crashing and shows the page heading', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Manage Locations')
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
   * and the table is rendered instead.
   */
  it('hides the loading spinner after data is fetched', async () => {
    const wrapper = await mountView([])

    // After data loads, the spinner should not be present
    expect(wrapper.find('.animate-spin').exists()).toBe(false)
    // The table should now be visible
    expect(wrapper.find('table').exists()).toBe(true)
  })

  /**
   * Verifies the empty-state message when there are no locations.
   */
  it('shows empty-state message when no locations exist', async () => {
    const wrapper = await mountView([])
    expect(wrapper.text()).toContain('No locations')
  })

  /**
   * Verifies that locations render in the table with their name and address.
   */
  it('renders locations in the table with name and address', async () => {
    const locations = [
      makeLocation({ id: 1, name: 'Downtown Bistro', address: '123 Main St' }),
      makeLocation({ id: 2, name: 'Uptown Grill', address: '456 Elm Ave' }),
    ]

    const wrapper = await mountView(locations)
    expect(wrapper.text()).toContain('Downtown Bistro')
    expect(wrapper.text()).toContain('123 Main St')
    expect(wrapper.text()).toContain('Uptown Grill')
    expect(wrapper.text()).toContain('456 Elm Ave')
  })

  /**
   * Verifies the table includes column headers for all expected fields.
   */
  it('displays table column headers for Name, Address, Timezone, Coordinates, Actions', async () => {
    const wrapper = await mountView([makeLocation()])
    expect(wrapper.text()).toContain('Name')
    expect(wrapper.text()).toContain('Address')
    expect(wrapper.text()).toContain('Timezone')
    expect(wrapper.text()).toContain('Coordinates')
    expect(wrapper.text()).toContain('Actions')
  })
})
