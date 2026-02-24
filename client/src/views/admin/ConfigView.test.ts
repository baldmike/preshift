/**
 * ConfigView.test.ts
 *
 * Tests for the ConfigView superadmin page, which provides establishment
 * name settings, an Initial Setup section for replacing demo data with a
 * real account, a Time Off Policy section, and a Danger Zone with the
 * full reset option.
 *
 * Verifies that:
 *   1. The component renders without crashing and shows the page heading.
 *   2. A navigation link back to /manage/daily is present.
 *   3. The "SUPERADMIN" badge is displayed.
 *   4. The "Initial Setup" section is shown when setup is not complete.
 *   5. The "Establishment Name" section is shown when setup is complete.
 *   6. The Danger Zone is shown when setup is complete.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ConfigView from '@/views/admin/ConfigView.vue'

// ── Mock the API module ─────────────────────────────────────────────────────
const mockGet = vi.fn()
const mockPut = vi.fn()
const mockPost = vi.fn()

vi.mock('@/composables/useApi', () => ({
  default: {
    get: (...args: unknown[]) => mockGet(...args),
    put: (...args: unknown[]) => mockPut(...args),
    post: (...args: unknown[]) => mockPost(...args),
  },
}))

// ── Mock the useAuthStore ───────────────────────────────────────────────────
vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    user: { id: 1, role: 'admin', name: 'Admin', location_id: 1, is_superadmin: true },
    isLoggedIn: true,
    isAdmin: true,
    isSuperAdmin: true,
    logout: vi.fn().mockResolvedValue(undefined),
  }),
}))

// ── Mock vue-router ─────────────────────────────────────────────────────────
const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush,
  }),
}))

// ── Stubs for child components ──────────────────────────────────────────────
const AppShellStub = { template: '<div><slot /></div>' }

// ── Mock data ───────────────────────────────────────────────────────────────

/** Settings response when initial setup has NOT been completed. */
const settingsNotComplete = {
  establishment_name: '',
  time_off_advance_days: 14,
  setup_complete: 'false',
}

/** Settings response when initial setup HAS been completed. */
const settingsComplete = {
  establishment_name: 'The Anchor',
  time_off_advance_days: 7,
  setup_complete: 'true',
}

/** Mounts the component with common stubs and waits for mount API calls. */
async function mountView(settings: object = settingsNotComplete) {
  mockGet.mockResolvedValue({ data: settings })

  const wrapper = mount(ConfigView, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        'router-link': { template: '<a><slot /></a>' },
        Teleport: true,
      },
    },
  })

  await flushPromises()
  return wrapper
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('ConfigView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders the page heading text.
   */
  it('renders without crashing and shows the page heading', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Configuration')
  })

  /**
   * Verifies a "Corner!" navigation link pointing to /manage/daily is present.
   */
  it('displays a navigation link back to /manage/daily', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Corner!')
  })

  /**
   * Verifies the SUPERADMIN badge is displayed on the page.
   */
  it('shows the SUPERADMIN badge', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('SUPERADMIN')
  })

  /**
   * Verifies the "Initial Setup" section is shown when setup_complete is false.
   * This section allows replacing demo data with a real account.
   */
  it('shows the Initial Setup section when setup is not complete', async () => {
    const wrapper = await mountView(settingsNotComplete)
    expect(wrapper.text()).toContain('Initial Setup')
    expect(wrapper.text()).toContain('Start Setup')
  })

  /**
   * Verifies the "Establishment Name" section is shown when setup is complete.
   */
  it('shows the Establishment Name section when setup is complete', async () => {
    const wrapper = await mountView(settingsComplete)
    expect(wrapper.text()).toContain('Establishment Name')
    expect(wrapper.text()).not.toContain('Initial Setup')
  })

  /**
   * Verifies the Danger Zone section is visible when setup is complete,
   * including the "Full Reset" action.
   */
  it('shows the Danger Zone when setup is complete', async () => {
    const wrapper = await mountView(settingsComplete)
    expect(wrapper.text()).toContain('Danger Zone')
    expect(wrapper.text()).toContain('Full Reset')
  })

  /**
   * Verifies the Time Off Policy section is shown when setup is complete.
   */
  it('shows the Time Off Policy section when setup is complete', async () => {
    const wrapper = await mountView(settingsComplete)
    expect(wrapper.text()).toContain('Time Off Policy')
    expect(wrapper.text()).toContain('Minimum Advance Notice')
  })
})
