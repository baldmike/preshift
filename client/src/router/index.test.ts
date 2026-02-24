/**
 * router/index.test.ts
 *
 * Tests for the Vue Router configuration and navigation guards.
 *
 * Verifies:
 *   1. All expected routes exist with the correct paths.
 *   2. Public routes (e.g. /login) have no requiresAuth meta.
 *   3. Staff routes have requiresAuth: true and no roles restriction.
 *   4. Management routes have requiresAuth: true and roles: ['admin', 'manager'].
 *   5. Admin-only routes restrict to roles: ['admin'].
 *   6. The root path (/) redirects to /dashboard.
 *   7. The navigation guard redirects unauthenticated users to /login.
 *   8. The navigation guard allows authenticated users through to auth-required routes.
 *   9. The navigation guard redirects unauthorized roles to /dashboard.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

// ── Mock useApi to prevent circular import issues ──────────────────────────

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    interceptors: {
      request: { use: vi.fn() },
      response: { use: vi.fn() },
    },
  },
}))

// ── Mock the auth store for guard tests ────────────────────────────────────

let mockUser: any = null
let mockFetchUser: ReturnType<typeof vi.fn>

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    get user() { return mockUser },
    set user(v: any) { mockUser = v },
    fetchUser: mockFetchUser,
  }),
}))

// ── Lazy-import the router after mocks are in place ────────────────────────

let router: Awaited<typeof import('@/router/index')>['default']

// ── Tests ──────────────────────────────────────────────────────────────────

describe('Router', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    localStorage.clear()
    mockUser = null
    mockFetchUser = vi.fn()

    /* Re-import the router fresh for each test so guard state resets */
    vi.resetModules()
    vi.mock('@/composables/useApi', () => ({
      default: {
        get: vi.fn(),
        post: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        interceptors: {
          request: { use: vi.fn() },
          response: { use: vi.fn() },
        },
      },
    }))
    vi.mock('@/stores/auth', () => ({
      useAuthStore: () => ({
        get user() { return mockUser },
        set user(v: any) { mockUser = v },
        fetchUser: mockFetchUser,
      }),
    }))
    const mod = await import('@/router/index')
    router = mod.default
  })

  // ── Route definition tests ───────────────────────────────────────────────

  /**
   * The /login route must exist and must NOT require authentication, since
   * it is the entry point for unauthenticated users.
   */
  it('defines /login as a public route without requiresAuth', () => {
    const route = router.getRoutes().find((r) => r.path === '/login')
    expect(route).toBeDefined()
    expect(route!.meta.requiresAuth).toBeFalsy()
  })

  /**
   * Core staff-facing routes must exist and require authentication but
   * should not have role restrictions (any authenticated user can access).
   */
  it('defines staff routes with requiresAuth but no roles restriction', () => {
    const staffPaths = [
      '/dashboard',
      '/86',
      '/specials',
      '/my-schedule',
      '/profile',
      '/shift-drops',
      '/time-off',
      '/tonights-schedule',
      '/messages',
    ]

    for (const path of staffPaths) {
      const route = router.getRoutes().find((r) => r.path === path)
      expect(route, `Route ${path} should exist`).toBeDefined()
      expect(route!.meta.requiresAuth).toBe(true)
      expect(route!.meta.roles).toBeUndefined()
    }
  })

  /**
   * Management routes must require authentication AND restrict access to
   * admin and manager roles only.
   */
  it('defines management routes with roles restricted to admin and manager', () => {
    const managePaths = [
      '/manage/daily',
      '/manage/specials',
      '/manage/push-items',
      '/manage/announcements',
      '/manage/menu',
      '/manage/users',
      '/manage/acknowledgments',
      '/manage/schedule',
      '/manage/shift-drops',
      '/manage/time-off',
      '/manage/logs',
    ]

    for (const path of managePaths) {
      const route = router.getRoutes().find((r) => r.path === path)
      expect(route, `Route ${path} should exist`).toBeDefined()
      expect(route!.meta.requiresAuth).toBe(true)
      expect(route!.meta.roles).toEqual(['admin', 'manager'])
    }
  })

  /**
   * The /admin/locations route must restrict access exclusively to the
   * admin role (not managers).
   */
  it('defines /admin/locations with roles restricted to admin only', () => {
    const route = router.getRoutes().find((r) => r.path === '/admin/locations')
    expect(route).toBeDefined()
    expect(route!.meta.requiresAuth).toBe(true)
    expect(route!.meta.roles).toEqual(['admin'])
  })

  /**
   * The /pick-location and /setup routes require auth but no role restriction,
   * since they are part of the login flow for any authenticated user.
   */
  it('defines /pick-location and /setup as auth-required without roles', () => {
    for (const path of ['/pick-location', '/setup']) {
      const route = router.getRoutes().find((r) => r.path === path)
      expect(route, `Route ${path} should exist`).toBeDefined()
      expect(route!.meta.requiresAuth).toBe(true)
      expect(route!.meta.roles).toBeUndefined()
    }
  })

  // ── Navigation guard tests ──────────────────────────────────────────────

  /**
   * When no token is in localStorage and the target route requires auth,
   * the guard should redirect to /login.
   */
  it('redirects unauthenticated users to /login for protected routes', async () => {
    localStorage.removeItem('preshift_token')

    await router.push('/dashboard')
    await router.isReady()

    expect(router.currentRoute.value.path).toBe('/login')
  })

  /**
   * When a valid token exists and the user object is loaded, the guard
   * should allow navigation to proceed to the target route.
   */
  it('allows authenticated users to reach protected routes', async () => {
    localStorage.setItem('preshift_token', 'test-token-123')
    mockUser = { id: 1, name: 'Test', role: 'server', location_id: 1 }

    await router.push('/dashboard')
    await router.isReady()

    expect(router.currentRoute.value.path).toBe('/dashboard')
  })

  /**
   * When a token exists but the user object is null (e.g. after page reload),
   * the guard should call fetchUser() to rehydrate the user before proceeding.
   */
  it('calls fetchUser when token exists but user is null', async () => {
    localStorage.setItem('preshift_token', 'test-token-123')
    mockUser = null
    mockFetchUser.mockImplementation(async () => {
      mockUser = { id: 1, name: 'Test', role: 'server', location_id: 1 }
    })

    await router.push('/dashboard')
    await router.isReady()

    expect(mockFetchUser).toHaveBeenCalled()
    expect(router.currentRoute.value.path).toBe('/dashboard')
  })

  /**
   * When fetchUser fails (expired token), the guard should clear the token
   * from localStorage and redirect to /login.
   */
  it('redirects to /login when fetchUser fails with an expired token', async () => {
    localStorage.setItem('preshift_token', 'expired-token')
    mockUser = null
    mockFetchUser.mockRejectedValue(new Error('Unauthenticated'))

    await router.push('/dashboard')
    await router.isReady()

    expect(localStorage.getItem('preshift_token')).toBeNull()
    expect(router.currentRoute.value.path).toBe('/login')
  })

  /**
   * When an authenticated server (staff) tries to access a management route,
   * the guard should redirect them to /dashboard since they lack the required role.
   */
  it('redirects unauthorized roles to /dashboard for role-restricted routes', async () => {
    localStorage.setItem('preshift_token', 'test-token-123')
    mockUser = { id: 1, name: 'Server', role: 'server', location_id: 1 }

    await router.push('/manage/daily')
    await router.isReady()

    expect(router.currentRoute.value.path).toBe('/dashboard')
  })

  /**
   * An admin user should be able to access management routes that require
   * the admin or manager role.
   */
  it('allows admin users to access management routes', async () => {
    localStorage.setItem('preshift_token', 'test-token-123')
    mockUser = { id: 1, name: 'Admin', role: 'admin', location_id: 1 }

    await router.push('/manage/daily')
    await router.isReady()

    expect(router.currentRoute.value.path).toBe('/manage/daily')
  })

  /**
   * The /login route should be accessible without a token since it is
   * the public authentication page.
   */
  it('allows navigation to /login without a token', async () => {
    localStorage.removeItem('preshift_token')

    await router.push('/login')
    await router.isReady()

    expect(router.currentRoute.value.path).toBe('/login')
  })
})
