/**
 * LocationPickerView.test.ts
 *
 * Tests for the LocationPickerView component that appears after login
 * when a user belongs to multiple establishments.
 *
 * Verifies:
 *   1. A button is rendered for each location in the auth store.
 *   2. Each button displays the location name and role badge.
 *   3. Clicking a location calls switchLocation and redirects staff to /dashboard.
 *   4. Clicking a location redirects managers to /manage/daily.
 *   5. A failed switchLocation displays the error banner.
 *   6. All location buttons are disabled while a switch is in progress.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import LocationPickerView from '@/views/auth/LocationPickerView.vue'

// ── Track router push calls ────────────────────────────────────────────────

const pushMock = vi.fn()

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: vi.fn() }),
}))

// ── Mock the auth store ────────────────────────────────────────────────────

let mockUser: any = null
let mockLocations: any[] = []
let switchLocationMock: ReturnType<typeof vi.fn>

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    get user() { return mockUser },
    set user(v: any) { mockUser = v },
    get locations() { return mockLocations },
    set locations(v: any[]) { mockLocations = v },
    switchLocation: switchLocationMock,
  }),
}))

// ── Helpers ────────────────────────────────────────────────────────────────

const sampleLocations = [
  { id: 1, name: 'Downtown Taproom', role: 'server' },
  { id: 2, name: 'Uptown Lounge', role: 'manager' },
]

// ── Tests ──────────────────────────────────────────────────────────────────

describe('LocationPickerView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    pushMock.mockClear()
    mockUser = { id: 1, name: 'Test User', role: 'server', location_id: 1 }
    mockLocations = [...sampleLocations]
    switchLocationMock = vi.fn()
  })

  /**
   * When the user has multiple locations, a button should be rendered
   * for each one showing the establishment name.
   */
  it('renders a button for each location in the store', () => {
    const wrapper = mount(LocationPickerView)

    const buttons = wrapper.findAll('button')
    expect(buttons.length).toBe(2)
    expect(buttons[0].text()).toContain('Downtown Taproom')
    expect(buttons[1].text()).toContain('Uptown Lounge')
  })

  /**
   * Each location button should display a role badge so the user
   * can see what role they hold at that establishment.
   */
  it('displays role badges on each location button', () => {
    const wrapper = mount(LocationPickerView)

    const buttons = wrapper.findAll('button')
    expect(buttons[0].text()).toContain('server')
    expect(buttons[1].text()).toContain('manager')
  })

  /**
   * When a staff user selects a location, switchLocation should be called
   * with the correct ID and the user redirected to /dashboard.
   */
  it('calls switchLocation and redirects staff to /dashboard', async () => {
    switchLocationMock.mockImplementation(async () => {
      mockUser = { id: 1, name: 'Test User', role: 'server', location_id: 1 }
    })

    const wrapper = mount(LocationPickerView)

    await wrapper.findAll('button')[0].trigger('click')
    await flushPromises()

    expect(switchLocationMock).toHaveBeenCalledWith(1)
    expect(pushMock).toHaveBeenCalledWith('/dashboard')
  })

  /**
   * When a manager selects a location, they should be redirected to
   * /manage/daily instead of the staff dashboard.
   */
  it('redirects managers to /manage/daily after picking a location', async () => {
    switchLocationMock.mockImplementation(async () => {
      mockUser = { id: 1, name: 'Test User', role: 'manager', location_id: 2 }
    })

    const wrapper = mount(LocationPickerView)

    await wrapper.findAll('button')[1].trigger('click')
    await flushPromises()

    expect(switchLocationMock).toHaveBeenCalledWith(2)
    expect(pushMock).toHaveBeenCalledWith('/manage/daily')
  })

  /**
   * When switchLocation throws an error, the error banner should appear
   * with the server-provided message.
   */
  it('displays error banner when switchLocation fails', async () => {
    switchLocationMock.mockRejectedValue({
      response: { data: { message: 'Location not found.' } },
    })

    const wrapper = mount(LocationPickerView)

    await wrapper.findAll('button')[0].trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Location not found.')
    expect(pushMock).not.toHaveBeenCalled()
  })

  /**
   * While a location switch is in progress, all buttons should be disabled
   * to prevent double-clicks and race conditions.
   */
  it('disables all buttons while a location switch is in progress', async () => {
    let resolveSwitch: () => void
    switchLocationMock.mockImplementation(() => new Promise<void>((resolve) => {
      resolveSwitch = resolve
    }))

    const wrapper = mount(LocationPickerView)

    await wrapper.findAll('button')[0].trigger('click')
    await wrapper.vm.$nextTick()

    const buttons = wrapper.findAll('button')
    for (const btn of buttons) {
      expect(btn.attributes('disabled')).toBeDefined()
    }

    mockUser = { id: 1, name: 'Test User', role: 'server', location_id: 1 }
    resolveSwitch!()
    await flushPromises()

    const buttonsAfter = wrapper.findAll('button')
    for (const btn of buttonsAfter) {
      expect(btn.attributes('disabled')).toBeUndefined()
    }
  })
})
