/**
 * composables/useAuth.ts
 *
 * A thin composable wrapper around the Pinia `authStore`.  It exposes the
 * most commonly needed auth properties as Vue `computed` refs so that
 * components can destructure them directly in `<script setup>` without
 * importing the store themselves.
 *
 * This keeps template code clean:
 *   ```ts
 *   const { user, isAdmin, locationId } = useAuth()
 *   ```
 *
 * All returned values are read-only computed refs that automatically update
 * when the underlying store state changes.
 */

import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Provides reactive, read-only access to the current authentication state.
 *
 * @returns An object of computed refs:
 *  - `user`       : The currently authenticated User object, or null
 *  - `isLoggedIn` : Whether the user is fully authenticated (token + user loaded)
 *  - `isAdmin`    : Whether the current user has the 'admin' role
 *  - `isManager`  : Whether the current user has the 'manager' role
 *  - `isStaff`    : Whether the current user is front-of-house ('server' or 'bartender')
 *  - `locationId` : The location_id of the current user, or null
 */
export function useAuth() {
  const store = useAuthStore()

  // Wrap each store getter/state in a computed ref for reactivity in templates
  const user = computed(() => store.user)
  const isLoggedIn = computed(() => store.isLoggedIn)
  const isAdmin = computed(() => store.isAdmin)
  const isManager = computed(() => store.isManager)
  const isStaff = computed(() => store.isStaff)
  const isSuperAdmin = computed(() => store.isSuperAdmin)
  const locationId = computed(() => store.locationId)

  return {
    user,
    isLoggedIn,
    isAdmin,
    isManager,
    isStaff,
    isSuperAdmin,
    locationId,
  }
}
