/**
 * stores/auth.test.ts
 *
 * Unit tests for the useAuthStore Pinia store.
 *
 * These tests verify:
 *  1. Computed getters (`isLoggedIn`, `isAdmin`, `isManager`, `isStaff`, `locationId`)
 *     return correct values based on the current user and token state.
 *  2. The `login()` action calls the correct API endpoint, persists the token
 *     to localStorage, and populates both `token` and `user` state.
 *  3. The `logout()` action invalidates the session server-side, clears local
 *     state, and removes the token from localStorage -- even when the API
 *     call fails.
 *  4. The `fetchUser()` action rehydrates the user profile from the API.
 *  5. The token is initialised from localStorage when the store is created,
 *     so sessions survive page reloads.
 *
 * We mock:
 *  - `@/composables/useApi` so no real HTTP requests are made.
 *  - `localStorage` so we can verify reads/writes without touching the real
 *    browser storage.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types'

// ── Mock the API module ────────────────────────────────────────────────────
// Replace the default export of `@/composables/useApi` with an object whose
// `post` and `get` methods are Vitest spies.  This lets us control the
// resolved/rejected values and assert that the correct endpoints are called.
vi.mock('@/composables/useApi', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
  },
}))

// ── Mock localStorage ──────────────────────────────────────────────────────
// The auth store reads `localStorage.getItem('preshift_token')` at creation
// time and writes/removes it during login/logout.  We replace the global
// `localStorage` with a mock so we can inspect those interactions.
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
}
Object.defineProperty(globalThis, 'localStorage', { value: localStorageMock })

// ── Helpers ────────────────────────────────────────────────────────────────

/**
 * Creates a valid User object with sensible defaults.
 * Pass an `overrides` partial to customise individual fields (e.g. `role`).
 * This keeps each test focused on only the fields it cares about.
 */
function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 1,
    location_id: 1,
    name: 'Test User',
    email: 'test@test.com',
    role: 'server',
    is_superadmin: false,
    phone: null,
    availability: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

// ── Test suite ─────────────────────────────────────────────────────────────

describe('useAuthStore', () => {
  /**
   * Before every test we:
   *  1. Create a fresh Pinia instance so stores start with clean state.
   *  2. Reset all vi.fn() call history so assertions are scoped to each test.
   *  3. Make `localStorage.getItem` return `null` by default (no persisted
   *     token) -- individual tests can override this.
   */
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    localStorageMock.getItem.mockReturnValue(null)
  })

  // ── isLoggedIn ──────────────────────────────────────────────────────────

  describe('isLoggedIn', () => {
    /**
     * When neither a token nor a user exists the user is definitely not
     * logged in.  This is the default state after store creation (assuming
     * localStorage has no persisted token).
     */
    it('returns false when no token and no user', () => {
      const store = useAuthStore()
      // Both `token` and `user` are null by default
      expect(store.isLoggedIn).toBe(false)
    })

    /**
     * Having a token alone is not enough -- the user profile must also be
     * loaded.  This can happen right after a page reload: the token is read
     * from localStorage, but `fetchUser()` hasn't completed yet.
     */
    it('returns false when token exists but no user', () => {
      const store = useAuthStore()
      // Manually set a token without setting a user
      store.token = 'some-token'
      expect(store.isLoggedIn).toBe(false)
    })

    /**
     * The user is considered fully authenticated only when both the token
     * AND the user profile are present in the store.
     */
    it('returns true when both token and user exist', () => {
      const store = useAuthStore()
      // Set both pieces of auth state
      store.token = 'valid-token'
      store.user = makeUser()
      expect(store.isLoggedIn).toBe(true)
    })
  })

  // ── isAdmin ─────────────────────────────────────────────────────────────

  describe('isAdmin', () => {
    /**
     * The `isAdmin` computed should return true ONLY when the user's role
     * is exactly 'admin'.  Other roles (manager, server, bartender) must
     * not be treated as admin.
     */
    it('returns true only when user.role === "admin"', () => {
      const store = useAuthStore()

      // No user at all -- isAdmin should be false (not throw)
      expect(store.isAdmin).toBe(false)

      // Set an admin user and verify the getter flips to true
      store.user = makeUser({ role: 'admin' })
      expect(store.isAdmin).toBe(true)

      // Non-admin roles must return false
      store.user = makeUser({ role: 'manager' })
      expect(store.isAdmin).toBe(false)

      store.user = makeUser({ role: 'server' })
      expect(store.isAdmin).toBe(false)

      store.user = makeUser({ role: 'bartender' })
      expect(store.isAdmin).toBe(false)
    })
  })

  // ── isManager ───────────────────────────────────────────────────────────

  describe('isManager', () => {
    /**
     * The `isManager` computed should return true ONLY for the 'manager'
     * role.  Admins are NOT considered managers in this application's
     * permission model (they have their own `isAdmin` getter).
     */
    it('returns true only when user.role === "manager"', () => {
      const store = useAuthStore()

      // No user -- should be false
      expect(store.isManager).toBe(false)

      // Manager role
      store.user = makeUser({ role: 'manager' })
      expect(store.isManager).toBe(true)

      // Admin is NOT a manager
      store.user = makeUser({ role: 'admin' })
      expect(store.isManager).toBe(false)

      // Staff roles are not managers
      store.user = makeUser({ role: 'server' })
      expect(store.isManager).toBe(false)
    })
  })

  // ── isStaff ─────────────────────────────────────────────────────────────

  describe('isStaff', () => {
    /**
     * `isStaff` is true for front-of-house roles: 'server' and 'bartender'.
     * Admins and managers are NOT considered staff for this getter's purpose.
     */
    it('returns true for "server" and "bartender" roles', () => {
      const store = useAuthStore()

      // No user -- should be false
      expect(store.isStaff).toBe(false)

      // Server is staff
      store.user = makeUser({ role: 'server' })
      expect(store.isStaff).toBe(true)

      // Bartender is staff
      store.user = makeUser({ role: 'bartender' })
      expect(store.isStaff).toBe(true)

      // Admin is NOT staff
      store.user = makeUser({ role: 'admin' })
      expect(store.isStaff).toBe(false)

      // Manager is NOT staff
      store.user = makeUser({ role: 'manager' })
      expect(store.isStaff).toBe(false)
    })
  })

  // ── isSuperAdmin ───────────────────────────────────────────────────────

  describe('isSuperAdmin', () => {
    it('returns false when no user is loaded', () => {
      const store = useAuthStore()
      expect(store.isSuperAdmin).toBe(false)
    })

    it('returns true when user has is_superadmin = true', () => {
      const store = useAuthStore()
      store.user = makeUser({ is_superadmin: true })
      expect(store.isSuperAdmin).toBe(true)
    })

    it('returns false when user has is_superadmin = false', () => {
      const store = useAuthStore()
      store.user = makeUser({ is_superadmin: false })
      expect(store.isSuperAdmin).toBe(false)
    })
  })

  // ── locationId ──────────────────────────────────────────────────────────

  describe('locationId', () => {
    /**
     * `locationId` is a convenience getter that returns the user's
     * `location_id` field, or null if no user is loaded.  This is used
     * throughout the app to scope API requests to the correct location.
     */
    it('returns user.location_id or null when no user', () => {
      const store = useAuthStore()

      // No user loaded -- locationId should be null
      expect(store.locationId).toBeNull()

      // Set a user with location_id = 42
      store.user = makeUser({ location_id: 42 })
      expect(store.locationId).toBe(42)

      // Change user to a different location
      store.user = makeUser({ location_id: 7 })
      expect(store.locationId).toBe(7)
    })
  })

  // ── login() ─────────────────────────────────────────────────────────────

  describe('login()', () => {
    /**
     * The `login()` action should:
     *  1. POST credentials to `/api/login`.
     *  2. Set `store.token` from the response.
     *  3. Set `store.user` from the response.
     *  4. Persist the token to localStorage under the key 'preshift_token'.
     */
    it('calls POST /api/login, sets token and user, stores token in localStorage', async () => {
      // Import the mocked API so we can configure its return value
      const api = (await import('@/composables/useApi')).default

      // Prepare the fake API response that `POST /api/login` would return
      const fakeUser = makeUser({ id: 5, name: 'Jane Doe', email: 'jane@test.com' })
      const fakeToken = 'jwt-token-abc123'
      ;(api.post as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: { token: fakeToken, user: fakeUser },
      })

      const store = useAuthStore()

      // Call login with email and password
      await store.login('jane@test.com', 'secret-password')

      // Verify the API was called with the correct endpoint and payload
      expect(api.post).toHaveBeenCalledWith('/api/login', {
        email: 'jane@test.com',
        password: 'secret-password',
      })

      // Verify the store state was updated from the API response
      expect(store.token).toBe(fakeToken)
      expect(store.user).toEqual(fakeUser)

      // Verify the token was persisted to localStorage
      expect(localStorageMock.setItem).toHaveBeenCalledWith('preshift_token', fakeToken)
    })
  })

  // ── logout() ────────────────────────────────────────────────────────────

  describe('logout()', () => {
    /**
     * The `logout()` action should:
     *  1. POST to `/api/logout` to invalidate the token server-side.
     *  2. Clear `store.token` and `store.user` to null.
     *  3. Remove 'preshift_token' from localStorage.
     */
    it('calls POST /api/logout, clears token and user, removes from localStorage', async () => {
      const api = (await import('@/composables/useApi')).default

      // Make the API call succeed (empty response is fine for logout)
      ;(api.post as ReturnType<typeof vi.fn>).mockResolvedValueOnce({ data: {} })

      const store = useAuthStore()

      // Pre-populate the store with an authenticated session
      store.token = 'existing-token'
      store.user = makeUser()

      // Perform logout
      await store.logout()

      // Verify the API was called
      expect(api.post).toHaveBeenCalledWith('/api/logout')

      // Verify all local auth state was cleared
      expect(store.token).toBeNull()
      expect(store.user).toBeNull()

      // Verify the token was removed from localStorage
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('preshift_token')
    })

    /**
     * Even if the API call fails (e.g. network error, token already expired),
     * the local state must still be cleared.  The user should be logged out
     * on the client side regardless of server-side outcome.
     */
    it('clears state even if API call fails', async () => {
      const api = (await import('@/composables/useApi')).default

      // Make the API call reject (simulating a network error)
      ;(api.post as ReturnType<typeof vi.fn>).mockRejectedValueOnce(new Error('Network Error'))

      const store = useAuthStore()

      // Pre-populate with an authenticated session
      store.token = 'old-token'
      store.user = makeUser()

      // Perform logout -- should NOT throw even though the API fails
      await store.logout()

      // State must still be cleared despite the API error
      expect(store.token).toBeNull()
      expect(store.user).toBeNull()
      expect(localStorageMock.removeItem).toHaveBeenCalledWith('preshift_token')
    })
  })

  // ── fetchUser() ─────────────────────────────────────────────────────────

  describe('fetchUser()', () => {
    /**
     * The `fetchUser()` action should call `GET /api/user` and populate
     * `store.user` with the returned User object.  This is used after a
     * page reload when a token exists in localStorage but `user` is null.
     */
    it('calls GET /api/user and sets user', async () => {
      const api = (await import('@/composables/useApi')).default

      // Prepare the fake user profile that the API would return
      const fakeUser = makeUser({ id: 10, name: 'Reloaded User' })
      ;(api.get as ReturnType<typeof vi.fn>).mockResolvedValueOnce({
        data: fakeUser,
      })

      const store = useAuthStore()

      // Initially no user is loaded
      expect(store.user).toBeNull()

      // Fetch the user profile
      await store.fetchUser()

      // Verify the correct endpoint was called
      expect(api.get).toHaveBeenCalledWith('/api/user')

      // Verify the user was set from the API response
      expect(store.user).toEqual(fakeUser)
      expect(store.user!.name).toBe('Reloaded User')
    })
  })

  // ── Token initialisation from localStorage ──────────────────────────────

  describe('token initialisation', () => {
    /**
     * When the store is created, it reads `localStorage.getItem('preshift_token')`
     * to initialise the `token` ref.  This ensures that a previously
     * authenticated session survives a page reload.
     *
     * To test this, we set up localStorage.getItem to return a token string
     * BEFORE creating the store (via a fresh Pinia instance).
     */
    it('is initialised from localStorage on store creation', () => {
      // Configure localStorage to return a persisted token
      localStorageMock.getItem.mockReturnValue('persisted-token-xyz')

      // Create a brand-new Pinia so the store factory runs again
      // and reads localStorage during initialisation
      setActivePinia(createPinia())
      const store = useAuthStore()

      // The token should have been read from localStorage
      expect(localStorageMock.getItem).toHaveBeenCalledWith('preshift_token')

      // The store's token ref should hold the persisted value
      expect(store.token).toBe('persisted-token-xyz')
    })
  })
})
