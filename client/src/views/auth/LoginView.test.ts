/**
 * LoginView.test.ts
 *
 * Tests for the LoginView authentication page.
 *
 * Verifies:
 *   1. The login form renders email input, password input, and submit button.
 *   2. Typing in inputs updates the bound v-model values.
 *   3. Successful login as a staff user redirects to /dashboard.
 *   4. Successful login as a manager redirects to /manage/daily.
 *   5. Successful login when needsSetup is true redirects to /setup.
 *   6. Successful login with multiple locations redirects to /pick-location.
 *   7. Failed login displays the error banner with the server message.
 *   8. The submit button is disabled while loading.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import LoginView from '@/views/auth/LoginView.vue'

// ── Track router push calls ────────────────────────────────────────────────

const pushMock = vi.fn()

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: vi.fn() }),
}))

// ── Mock the auth store ────────────────────────────────────────────────────

let mockUser: any = null
let mockNeedsSetup = false
let mockHasMultipleLocations = false
let loginMock: ReturnType<typeof vi.fn>

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    get user() { return mockUser },
    set user(v: any) { mockUser = v },
    get needsSetup() { return mockNeedsSetup },
    get hasMultipleLocations() { return mockHasMultipleLocations },
    login: loginMock,
  }),
}))

// ── Tests ──────────────────────────────────────────────────────────────────

describe('LoginView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    pushMock.mockClear()
    mockUser = null
    mockNeedsSetup = false
    mockHasMultipleLocations = false
    loginMock = vi.fn()
  })

  /**
   * The form must render the email and password inputs and the Sign In button
   * so users can authenticate.
   */
  it('renders the login form with email, password, and submit button', () => {
    const wrapper = mount(LoginView)

    const inputs = wrapper.findAll('input')
    expect(inputs.length).toBe(2)
    expect(inputs[0].attributes('inputmode')).toBe('email')
    expect(inputs[1].attributes('type')).toBe('password')

    const button = wrapper.find('button[type="submit"]')
    expect(button.exists()).toBe(true)
    expect(button.text()).toContain('Sign In')
  })

  /**
   * Typing into the email and password fields should update the reactive
   * v-model bindings so handleLogin receives the correct credentials.
   */
  it('binds email and password inputs via v-model', async () => {
    const wrapper = mount(LoginView)

    const emailInput = wrapper.find('input[inputmode="email"]')
    const passwordInput = wrapper.find('input[type="password"]')

    await emailInput.setValue('user@example.com')
    await passwordInput.setValue('secret123')

    expect((emailInput.element as HTMLInputElement).value).toBe('user@example.com')
    expect((passwordInput.element as HTMLInputElement).value).toBe('secret123')
  })

  /**
   * When a staff user (server) logs in successfully the component should
   * redirect to /dashboard, not the management panel.
   */
  it('redirects staff to /dashboard on successful login', async () => {
    loginMock.mockImplementation(async () => {
      mockUser = { id: 1, name: 'Staff', role: 'server', location_id: 1 }
    })

    const wrapper = mount(LoginView)

    await wrapper.find('input[inputmode="email"]').setValue('staff@test.com')
    await wrapper.find('input[type="password"]').setValue('password')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(loginMock).toHaveBeenCalledWith('staff@test.com', 'password')
    expect(pushMock).toHaveBeenCalledWith('/dashboard')
  })

  /**
   * When a manager logs in successfully the component should redirect to
   * /manage/daily since managers need the management dashboard.
   */
  it('redirects manager to /manage/daily on successful login', async () => {
    loginMock.mockImplementation(async () => {
      mockUser = { id: 2, name: 'Manager', role: 'manager', location_id: 1 }
    })

    const wrapper = mount(LoginView)

    await wrapper.find('input[inputmode="email"]').setValue('mgr@test.com')
    await wrapper.find('input[type="password"]').setValue('password')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(pushMock).toHaveBeenCalledWith('/manage/daily')
  })

  /**
   * When needsSetup is true after login (admin with no locations), the
   * component should redirect to /setup for the initial establishment flow.
   */
  it('redirects to /setup when user needs initial setup', async () => {
    loginMock.mockImplementation(async () => {
      mockUser = { id: 3, name: 'Admin', role: 'admin', location_id: null }
      mockNeedsSetup = true
    })

    const wrapper = mount(LoginView)

    await wrapper.find('input[inputmode="email"]').setValue('admin@test.com')
    await wrapper.find('input[type="password"]').setValue('password')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(pushMock).toHaveBeenCalledWith('/setup')
  })

  /**
   * When the user belongs to multiple locations, the component should
   * redirect to /pick-location after login.
   */
  it('redirects to /pick-location when user has multiple locations', async () => {
    loginMock.mockImplementation(async () => {
      mockUser = { id: 4, name: 'Multi', role: 'server', location_id: 1 }
      mockHasMultipleLocations = true
    })

    const wrapper = mount(LoginView)

    await wrapper.find('input[inputmode="email"]').setValue('multi@test.com')
    await wrapper.find('input[type="password"]').setValue('password')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(pushMock).toHaveBeenCalledWith('/pick-location')
  })

  /**
   * When login fails, the error banner should appear with the message
   * returned by the server (or a default fallback).
   */
  it('displays error message on failed login', async () => {
    loginMock.mockRejectedValue({
      response: { data: { message: 'These credentials do not match our records.' } },
    })

    const wrapper = mount(LoginView)

    await wrapper.find('input[inputmode="email"]').setValue('bad@test.com')
    await wrapper.find('input[type="password"]').setValue('wrong')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('These credentials do not match our records.')
  })

  /**
   * While the login request is in flight, the submit button should be
   * disabled and show the loading spinner text.
   */
  it('disables the submit button and shows spinner while loading', async () => {
    let resolveLogin: () => void
    loginMock.mockImplementation(() => new Promise<void>((resolve) => {
      resolveLogin = resolve
    }))

    const wrapper = mount(LoginView)

    await wrapper.find('input[inputmode="email"]').setValue('user@test.com')
    await wrapper.find('input[type="password"]').setValue('password')
    await wrapper.find('form').trigger('submit')

    await wrapper.vm.$nextTick()

    const button = wrapper.find('button[type="submit"]')
    expect(button.attributes('disabled')).toBeDefined()
    expect(wrapper.text()).toContain('Signing In...')

    mockUser = { id: 1, name: 'Test', role: 'server', location_id: 1 }
    resolveLogin!()
    await flushPromises()

    expect(button.attributes('disabled')).toBeUndefined()
  })
})
