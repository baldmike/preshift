/**
 * TopBar.test.ts
 *
 * Unit tests for the TopBar.vue component.
 *
 * TopBar is the sticky top header bar displayed on every authenticated page.
 * It shows the establishment name, a live date/time clock, a real-time
 * connection indicator, a notification bell (for managers/admins), and a
 * user avatar dropdown with change-password and logout functionality.
 *
 * Tests verify:
 *   1. The establishment name is displayed in the header.
 *   2. User initials are rendered on the avatar button.
 *   3. The dropdown menu opens when the avatar button is clicked.
 *   4. The dropdown shows the user's name and email.
 *   5. The Log Out button is visible in the dropdown.
 *   6. The Change Password button is visible in the dropdown.
 *   7. NotificationBell is shown for managers but hidden for staff.
 *   8. Date and time information is displayed.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import TopBar from '@/components/layout/TopBar.vue'

// ── Mock useAuth ────────────────────────────────────────────────────────────
const mockUser = ref({
  id: 1,
  name: 'Jane Doe',
  email: 'jane@example.com',
  location_id: 1,
  location: { id: 1, name: 'The Rustic Pub', timezone: 'America/New_York', city: 'Austin', state: 'TX' },
})
const mockIsAdmin = ref(false)
const mockIsManager = ref(false)
const mockHasMultipleLocations = ref(false)

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: mockUser,
    isAdmin: mockIsAdmin,
    isManager: mockIsManager,
    isStaff: { value: true },
    locations: { value: [] },
    hasMultipleLocations: mockHasMultipleLocations,
    locationId: { value: 1 },
  }),
}))

// ── Mock useApi ─────────────────────────────────────────────────────────────
vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockResolvedValue({ data: {} }),
    post: vi.fn().mockResolvedValue({ data: {} }),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

// ── Mock vue-router ─────────────────────────────────────────────────────────
const mockPush = vi.fn()
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: mockPush }),
}))

// ── Stubs ───────────────────────────────────────────────────────────────────
const RealtimeIndicatorStub = { template: '<div class="realtime-stub">Live</div>' }
const NotificationBellStub = { template: '<div class="bell-stub">Bell</div>' }
const RouterLinkStub = {
  template: '<a class="router-link"><slot /></a>',
  props: ['to'],
}

describe('TopBar.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    mockIsAdmin.value = false
    mockIsManager.value = false
    mockHasMultipleLocations.value = false
  })

  function mountTopBar() {
    return mount(TopBar, {
      global: {
        stubs: {
          RealtimeIndicator: RealtimeIndicatorStub,
          NotificationBell: NotificationBellStub,
          'router-link': RouterLinkStub,
          Teleport: true,
        },
      },
    })
  }

  /**
   * Test 1 — Displays the establishment name
   *
   * The header should show the establishment name fetched from settings,
   * falling back to the location name or 'PreShift86'.
   */
  it('displays the establishment name in the header', () => {
    const wrapper = mountTopBar()

    // Falls back to location name when settings API returns empty
    expect(wrapper.text()).toContain('The Rustic Pub')
  })

  /**
   * Test 2 — Renders user initials on the avatar button
   *
   * The avatar button should show the first letters of the user's name
   * (up to two characters) in uppercase.
   */
  it('renders user initials on the avatar button', () => {
    const wrapper = mountTopBar()

    // Jane Doe -> JD
    const avatar = wrapper.find('button')
    expect(avatar.text()).toContain('JD')
  })

  /**
   * Test 3 — Dropdown opens on avatar click
   *
   * Clicking the avatar button should toggle the dropdown menu to visible.
   */
  it('opens dropdown menu when avatar button is clicked', async () => {
    const wrapper = mountTopBar()

    // Menu should be closed initially
    expect(wrapper.text()).not.toContain('Log Out')

    // Click the avatar button (the one with initials)
    const avatarBtn = wrapper.findAll('button').find(b => b.text().includes('JD'))
    await avatarBtn!.trigger('click')

    expect(wrapper.text()).toContain('Log Out')
  })

  /**
   * Test 4 — Dropdown shows user name and email
   *
   * When the dropdown is open, the user's name and email should be
   * displayed in the user info section at the top of the menu.
   */
  it('shows user name and email in the dropdown', async () => {
    const wrapper = mountTopBar()

    const avatarBtn = wrapper.findAll('button').find(b => b.text().includes('JD'))
    await avatarBtn!.trigger('click')

    expect(wrapper.text()).toContain('Jane Doe')
    expect(wrapper.text()).toContain('jane@example.com')
  })

  /**
   * Test 5 — Log Out button is visible in dropdown
   *
   * The dropdown menu should contain a Log Out button so the user
   * can end their session.
   */
  it('shows Log Out button in the dropdown', async () => {
    const wrapper = mountTopBar()

    const avatarBtn = wrapper.findAll('button').find(b => b.text().includes('JD'))
    await avatarBtn!.trigger('click')

    expect(wrapper.text()).toContain('Log Out')
  })

  /**
   * Test 6 — Change Password button is visible in dropdown
   *
   * The dropdown menu should contain a Change Password button that
   * opens the inline password change form.
   */
  it('shows Change Password button in the dropdown', async () => {
    const wrapper = mountTopBar()

    const avatarBtn = wrapper.findAll('button').find(b => b.text().includes('JD'))
    await avatarBtn!.trigger('click')

    expect(wrapper.text()).toContain('Change Password')
  })

  /**
   * Test 7 — NotificationBell is shown for managers
   *
   * When the user is a manager, the notification bell should be visible.
   * When the user is regular staff, it should be hidden.
   */
  it('shows NotificationBell for managers but not for staff', async () => {
    // Staff should not see the bell
    mockIsManager.value = false
    mockIsAdmin.value = false
    const staffWrapper = mountTopBar()
    expect(staffWrapper.find('.bell-stub').exists()).toBe(false)

    // Manager should see the bell
    mockIsManager.value = true
    const managerWrapper = mountTopBar()
    expect(managerWrapper.find('.bell-stub').exists()).toBe(true)
  })

  /**
   * Test 8 — Date and time information is displayed
   *
   * The header should show the day name, date, and time, giving staff
   * a live clock in the top bar.
   */
  it('displays date and time information', () => {
    const wrapper = mountTopBar()

    // The component shows dayName, dateStr, and timeStr
    // We check for the year and common time-related text patterns
    const text = wrapper.text()
    expect(text).toMatch(/\d{4}/) // year
    expect(text).toMatch(/\d{1,2}:\d{2}/) // time pattern like "3:45"
  })
})
