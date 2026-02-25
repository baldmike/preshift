/**
 * AccessPendingView.test.ts
 *
 * Tests for the AccessPendingView component, shown when a user is
 * authenticated but has no location memberships yet.
 *
 * Verifies:
 *   1. The waiting message is rendered.
 *   2. The logout button calls authStore.logout and redirects to /login.
 *   3. The refresh button calls fetchUser and redirects when access is granted.
 *   4. The refresh button stays on the page when access is still pending.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import AccessPendingView from '@/views/auth/AccessPendingView.vue'

// ── Hoisted mock references (available inside vi.mock factories) ───────────

const { pushMock, fetchUserMock, logoutMock } = vi.hoisted(() => ({
  pushMock: vi.fn(),
  fetchUserMock: vi.fn(),
  logoutMock: vi.fn(),
}))

// ── Track router push calls ────────────────────────────────────────────────

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: vi.fn() }),
}))

// ── Mock useApi ────────────────────────────────────────────────────────────

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

// ── Mock the auth store ────────────────────────────────────────────────────

let mockAccessPending = true

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    get accessPending() { return mockAccessPending },
    fetchUser: fetchUserMock,
    logout: logoutMock,
  }),
}))

// ── Tests ──────────────────────────────────────────────────────────────────

describe('AccessPendingView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    pushMock.mockClear()
    fetchUserMock.mockClear()
    logoutMock.mockClear()
    mockAccessPending = true
  })

  /**
   * The access pending view must display a message informing the user
   * that their account is set up and a manager needs to assign them.
   */
  it('renders the waiting message', () => {
    const wrapper = mount(AccessPendingView)

    expect(wrapper.text()).toContain('Access Pending')
    expect(wrapper.text()).toContain('Your account is set up')
    expect(wrapper.text()).toContain('A manager will assign you to a location')
  })

  /**
   * Clicking the Log Out button should call authStore.logout and
   * redirect the user to the login page.
   */
  it('logout button calls authStore.logout and redirects to /login', async () => {
    logoutMock.mockResolvedValue(undefined)

    const wrapper = mount(AccessPendingView)

    const logoutButton = wrapper.findAll('button').find(b => b.text().includes('Log Out'))
    expect(logoutButton).toBeDefined()

    await logoutButton!.trigger('click')
    await flushPromises()

    expect(logoutMock).toHaveBeenCalled()
    expect(pushMock).toHaveBeenCalledWith('/login')
  })

  /**
   * Clicking the Check Again button should call fetchUser. When access
   * is granted (accessPending becomes false), redirect to /dashboard.
   */
  it('refresh button calls fetchUser and redirects when access granted', async () => {
    fetchUserMock.mockImplementation(async () => {
      mockAccessPending = false
    })

    const wrapper = mount(AccessPendingView)

    const refreshButton = wrapper.findAll('button').find(b => b.text().includes('Check Again'))
    expect(refreshButton).toBeDefined()

    await refreshButton!.trigger('click')
    await flushPromises()

    expect(fetchUserMock).toHaveBeenCalled()
    expect(pushMock).toHaveBeenCalledWith('/dashboard')
  })

  /**
   * When the refresh check still shows access pending, the user should
   * remain on the page without being redirected.
   */
  it('refresh button stays on page when access still pending', async () => {
    fetchUserMock.mockResolvedValue(undefined)
    // mockAccessPending stays true

    const wrapper = mount(AccessPendingView)

    const refreshButton = wrapper.findAll('button').find(b => b.text().includes('Check Again'))
    await refreshButton!.trigger('click')
    await flushPromises()

    expect(fetchUserMock).toHaveBeenCalled()
    expect(pushMock).not.toHaveBeenCalled()
  })
})
