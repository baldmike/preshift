/**
 * stores/auth.ts
 *
 * Pinia store that manages authentication state for the entire application.
 * Uses the Composition API ("setup store") syntax for defining state, getters,
 * and actions.
 *
 * State shape:
 *  - `user`  : The currently authenticated User object, or null if not logged in.
 *  - `token` : The Bearer token string used for API authentication.
 *              Initialised from localStorage so that sessions survive page reloads.
 *
 * Getters (computed):
 *  - `isLoggedIn` : True only when both a token AND a user object exist.
 *  - `isAdmin`    : True if the user's role is 'admin'.
 *  - `isManager`  : True if the user's role is 'manager'.
 *  - `isStaff`    : True if the user's role is 'server' or 'bartender'.
 *  - `locationId` : The user's location_id, or null if not logged in.
 *
 * Actions:
 *  - `login(email, password)` : Authenticates with the API and persists the token.
 *  - `logout()`               : Invalidates the session server-side and clears local state.
 *  - `fetchUser()`            : Fetches the current user profile from the API (used to
 *                               rehydrate `user` after a page reload when only the token
 *                               exists in localStorage).
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/composables/useApi'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  // -------------------------------------------------------------------------
  // State
  // -------------------------------------------------------------------------

  /** The authenticated user profile; null when logged out or before fetchUser() completes. */
  const user = ref<User | null>(null)

  /**
   * The Bearer token for API requests.
   * Initialised from localStorage so a page refresh does not force re-login.
   * Kept in sync with localStorage by the login/logout actions.
   */
  const token = ref<string | null>(localStorage.getItem('preshift_token'))

  // -------------------------------------------------------------------------
  // Getters (computed properties)
  // -------------------------------------------------------------------------

  /** User is considered logged in only when we have both a token and a loaded user profile. */
  const isLoggedIn = computed(() => !!token.value && !!user.value)

  /** True if the current user has the 'admin' role. */
  const isAdmin = computed(() => user.value?.role === 'admin')

  /** True if the current user has the 'manager' role. */
  const isManager = computed(() => user.value?.role === 'manager')

  /** True if the current user is front-of-house staff ('server' or 'bartender'). */
  const isStaff = computed(
    () => !!user.value && ['server', 'bartender'].includes(user.value.role)
  )

  /** True if the current user has the SuperAdmin privilege. */
  const isSuperAdmin = computed(() => user.value?.is_superadmin === true)

  /** Shortcut to the user's location_id; null if no user is loaded. */
  const locationId = computed(() => user.value?.location_id ?? null)

  // -------------------------------------------------------------------------
  // Actions
  // -------------------------------------------------------------------------

  /**
   * Authenticates the user by posting credentials to `POST /api/login`.
   *
   * Expected API response shape:
   *   { token: string, user: User }
   *
   * On success the token is persisted to localStorage so it survives page
   * reloads, and both `token` and `user` reactive refs are updated.
   *
   * @param email    - The user's email address
   * @param password - The user's plaintext password
   * @throws Will throw an AxiosError if credentials are invalid (422) or on network failure
   */
  async function login(email: string, password: string) {
    const { data } = await api.post('/api/login', { email, password })
    token.value = data.token
    user.value = data.user
    // Persist the token so the session survives browser refreshes
    localStorage.setItem('preshift_token', data.token)
  }

  /**
   * Logs the user out by calling `POST /api/logout` (which invalidates the
   * token server-side) and then clearing all local auth state.
   *
   * Errors from the API call are silently caught because the important part
   * is clearing the local state -- even if the server request fails (e.g.
   * network down, token already expired), the user should still be logged
   * out on the client side.
   */
  async function logout() {
    try {
      // Invalidate the token server-side (revoke the Sanctum/Passport token)
      await api.post('/api/logout')
    } catch {
      // Ignore logout errors -- clearing local state is what matters
    }
    // Clear all local auth state
    token.value = null
    user.value = null
    localStorage.removeItem('preshift_token')
  }

  /**
   * Fetches the currently authenticated user's profile from `GET /api/user`.
   *
   * Expected API response: a User object (directly, not wrapped).
   *
   * This is called by the router navigation guard when a token exists in
   * localStorage but `user` is still null (e.g. after a page reload).
   *
   * @throws Will throw an AxiosError if the token is invalid/expired (401)
   */
  async function fetchUser() {
    const { data } = await api.get('/api/user')
    user.value = data
  }

  // -------------------------------------------------------------------------
  // Public API exposed to components and other stores
  // -------------------------------------------------------------------------
  return {
    user,
    token,
    isLoggedIn,
    isAdmin,
    isManager,
    isStaff,
    isSuperAdmin,
    locationId,
    login,
    logout,
    fetchUser,
  }
})
