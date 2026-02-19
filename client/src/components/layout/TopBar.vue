<script setup lang="ts">
/**
 * TopBar -- sticky header bar displayed at the top of every authenticated page.
 * Shows the app brand name ("PreShift"), the user's current location name,
 * a RealtimeIndicator dot for WebSocket connection status, and a logout button.
 */
import { useAuth } from '@/composables/useAuth'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import RealtimeIndicator from '@/components/RealtimeIndicator.vue'

// Retrieve the currently authenticated user object from the auth composable
const { user } = useAuth()
// Auth store provides the logout action that invalidates the session server-side
const authStore = useAuthStore()
// Router used to redirect to the login page after successful logout
const router = useRouter()

/**
 * Handles the logout flow: calls the API to invalidate the session,
 * then navigates to the login screen.
 */
async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <header class="bg-gray-900 text-white px-4 py-3 flex items-center justify-between sticky top-0 z-40">
    <div class="text-lg font-bold tracking-tight">PreShift</div>
    <div v-if="user" class="text-sm text-gray-300">
      {{ user.location?.name || 'No Location' }}
    </div>
    <div class="flex items-center gap-3">
      <RealtimeIndicator />
      <button
        v-if="user"
        @click="handleLogout"
        class="text-xs text-gray-400 hover:text-white transition-colors"
      >
        Logout
      </button>
    </div>
  </header>
</template>
