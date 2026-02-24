/**
 * ProfileView.test.ts
 *
 * Tests for the ProfileView component which displays and allows editing
 * of the user's personal profile (name, availability).
 *
 * Verifies:
 *   1. The component renders without crashing and displays the page heading.
 *   2. The "Corner!" back link points to /dashboard.
 *   3. The user's name, email, and role are displayed.
 *   4. Read-only profile field labels render correctly.
 *   5. The "My Availability" section heading renders.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import ProfileView from '@/views/staff/ProfileView.vue'

// ── Mock composables ────────────────────────────────────────────────────────

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockResolvedValue({ data: [] }),
    post: vi.fn().mockResolvedValue({ data: {} }),
    put: vi.fn().mockResolvedValue({ data: {} }),
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
  return mount(ProfileView, {
    global: {
      stubs: {
        'router-link': routerLinkStub,
        AppShell: { template: '<div><slot /></div>' },
        BadgePill: { template: '<span class="badge-pill"><slot />{{ label }}</span>', props: ['label', 'color'] },
        AvailabilityGrid: true,
        Teleport: true,
      },
      mocks: {
        $route: { path: '/profile' },
      },
    },
  })
}

// ── Tests ───────────────────────────────────────────────────────────────────

describe('ProfileView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())

    // Seed the auth store with a test user
    const authStore = useAuthStore()
    authStore.user = {
      id: 1,
      name: 'Jane Server',
      email: 'jane@example.com',
      role: 'server',
      location_id: 1,
      location: { id: 1, name: 'The Test Bar' },
      availability: null,
    } as any
  })

  /**
   * The page heading "My Profile" must be visible so the user knows
   * they are on the profile page.
   */
  it('renders the page heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('My Profile')
  })

  /**
   * The "Corner!" back link must navigate to /dashboard.
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
   * The user's name must be displayed in the profile info card.
   */
  it('displays the user name', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Jane Server')
  })

  /**
   * The user's email must be displayed as a read-only field.
   */
  it('displays the user email', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('jane@example.com')
  })

  /**
   * The profile field labels (Name, Email, Role, Location) must render
   * so the user can identify each piece of information.
   */
  it('renders profile field labels', async () => {
    const wrapper = mountView()
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('Name')
    expect(text).toContain('Email')
    expect(text).toContain('Role')
    expect(text).toContain('Location')
  })

  /**
   * The user's location name should be rendered from the nested
   * location object on the user.
   */
  it('displays the location name', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('The Test Bar')
  })

  /**
   * The "My Availability" section heading must render so the user
   * knows they can manage their weekly availability from this page.
   */
  it('renders the My Availability section heading', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('My Availability')
  })

  /**
   * The subtitle text "Manage your name and availability" must be
   * visible below the heading to describe the page purpose.
   */
  it('renders the page subtitle', async () => {
    const wrapper = mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Manage your name and availability')
  })
})
