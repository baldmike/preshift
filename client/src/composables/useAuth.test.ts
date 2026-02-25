/**
 * useAuth.test.ts
 *
 * Unit tests for the useAuth composable.
 *
 * These tests verify:
 *  1. All returned properties are reactive computed refs that reflect the
 *     underlying auth store state.
 *  2. `isLoggedIn` correctly requires both a token and a user.
 *  3. `isAdmin`, `isManager`, `isStaff`, and `isSuperAdmin` respond to
 *     role changes on the store user object.
 *  4. `locationId` returns the user's location_id or null when logged out.
 *  5. `hasMultipleLocations` and `needsSetup` compute correctly for edge cases.
 *  6. Initial state (no user) returns safe defaults (null, false, etc.).
 *
 * We use a real Pinia instance (no mocks) since useAuth is a thin wrapper
 * around the auth store and we want to verify the reactive bindings.
 */

import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useAuth } from '@/composables/useAuth'
import type { User, LocationMembership } from '@/types'

// ── Helpers ────────────────────────────────────────────────────────────────

/** Creates a minimal User object with sensible defaults for testing. */
function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 1,
    location_id: 10,
    name: 'Test User',
    email: 'test@example.com',
    role: 'server',
    roles: null,
    is_superadmin: false,
    phone: null,
    profile_photo_url: null,
    availability: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

/** Creates a minimal LocationMembership for testing. */
function makeMembership(overrides: Partial<LocationMembership> = {}): LocationMembership {
  return {
    id: 10,
    name: 'Test Venue',
    role: 'server',
    ...overrides,
  }
}

// ── Test suite ─────────────────────────────────────────────────────────────

describe('useAuth composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  // ── Initial state ──────────────────────────────────────────────────────

  /* Verifies that all computed refs return safe defaults when the store
     has no user loaded (the initial logged-out state). */
  it('returns safe defaults when no user is loaded', () => {
    const { user, isLoggedIn, isAdmin, isManager, isStaff, isSuperAdmin, locationId, locations, hasMultipleLocations, needsSetup } = useAuth()

    expect(user.value).toBeNull()
    expect(isLoggedIn.value).toBe(false)
    expect(isAdmin.value).toBe(false)
    expect(isManager.value).toBe(false)
    expect(isStaff.value).toBe(false)
    expect(isSuperAdmin.value).toBe(false)
    expect(locationId.value).toBeNull()
    expect(locations.value).toEqual([])
    expect(hasMultipleLocations.value).toBe(false)
    expect(needsSetup.value).toBe(false)
  })

  // ── isLoggedIn ─────────────────────────────────────────────────────────

  /* Verifies that isLoggedIn is true only when BOTH the token and user are
     present, not just one or the other. */
  it('returns true for isLoggedIn only when both token and user exist', () => {
    const store = useAuthStore()
    const { isLoggedIn } = useAuth()

    // Token only, no user
    store.token = 'some-token'
    expect(isLoggedIn.value).toBe(false)

    // Token and user
    store.user = makeUser()
    expect(isLoggedIn.value).toBe(true)

    // User only, no token
    store.token = null
    expect(isLoggedIn.value).toBe(false)
  })

  // ── Role checks ────────────────────────────────────────────────────────

  /* Verifies that isAdmin is true when the user's role is 'admin' and
     false for all other roles. */
  it('computes isAdmin correctly based on user role', () => {
    const store = useAuthStore()
    const { isAdmin } = useAuth()

    store.user = makeUser({ role: 'admin' })
    expect(isAdmin.value).toBe(true)

    store.user = makeUser({ role: 'manager' })
    expect(isAdmin.value).toBe(false)
  })

  /* Verifies that isManager is true only for the 'manager' role. */
  it('computes isManager correctly based on user role', () => {
    const store = useAuthStore()
    const { isManager } = useAuth()

    store.user = makeUser({ role: 'manager' })
    expect(isManager.value).toBe(true)

    store.user = makeUser({ role: 'server' })
    expect(isManager.value).toBe(false)
  })

  /* Verifies that isStaff is true for 'server' and 'bartender' but false
     for 'admin' and 'manager'. */
  it('computes isStaff correctly for server and bartender roles', () => {
    const store = useAuthStore()
    const { isStaff } = useAuth()

    store.user = makeUser({ role: 'server' })
    expect(isStaff.value).toBe(true)

    store.user = makeUser({ role: 'bartender' })
    expect(isStaff.value).toBe(true)

    store.user = makeUser({ role: 'admin' })
    expect(isStaff.value).toBe(false)

    store.user = makeUser({ role: 'manager' })
    expect(isStaff.value).toBe(false)
  })

  /* Verifies that isSuperAdmin is true only when the user's is_superadmin
     flag is explicitly true. */
  it('computes isSuperAdmin from the user flag', () => {
    const store = useAuthStore()
    const { isSuperAdmin } = useAuth()

    store.user = makeUser({ is_superadmin: true })
    expect(isSuperAdmin.value).toBe(true)

    store.user = makeUser({ is_superadmin: false })
    expect(isSuperAdmin.value).toBe(false)
  })

  // ── locationId ─────────────────────────────────────────────────────────

  /* Verifies that locationId reflects the user's location_id and returns
     null when the user is not loaded. */
  it('returns the user location_id or null', () => {
    const store = useAuthStore()
    const { locationId } = useAuth()

    expect(locationId.value).toBeNull()

    store.user = makeUser({ location_id: 42 })
    expect(locationId.value).toBe(42)
  })

  // ── hasMultipleLocations ───────────────────────────────────────────────

  /* Verifies that hasMultipleLocations is true only when the store's
     locations array has more than one entry. */
  it('returns true when user has more than one location membership', () => {
    const store = useAuthStore()
    const { hasMultipleLocations } = useAuth()

    store.locations = [makeMembership({ id: 1 })]
    expect(hasMultipleLocations.value).toBe(false)

    store.locations = [makeMembership({ id: 1 }), makeMembership({ id: 2 })]
    expect(hasMultipleLocations.value).toBe(true)
  })

  // ── needsSetup ─────────────────────────────────────────────────────────

  /* Verifies that needsSetup is true only when the user is an admin with
     zero location memberships (the first-time setup case). */
  it('returns true for admin users with no location memberships', () => {
    const store = useAuthStore()
    const { needsSetup } = useAuth()

    // Admin with no locations
    store.user = makeUser({ role: 'admin' })
    store.locations = []
    expect(needsSetup.value).toBe(true)

    // Admin with locations
    store.locations = [makeMembership()]
    expect(needsSetup.value).toBe(false)

    // Non-admin with no locations
    store.user = makeUser({ role: 'server' })
    store.locations = []
    expect(needsSetup.value).toBe(false)
  })
})
