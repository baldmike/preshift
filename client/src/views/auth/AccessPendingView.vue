<script setup lang="ts">
/**
 * AccessPendingView.vue
 *
 * Displayed when a user is authenticated but has zero location memberships.
 * This means their account exists but a manager hasn't assigned them to a
 * location yet. Provides a logout button and a refresh button that re-fetches
 * the user profile to check if access has been granted.
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const refreshing = ref(false)

async function handleRefresh() {
  refreshing.value = true
  try {
    await authStore.fetchUser()
    if (!authStore.accessPending) {
      router.push('/dashboard')
    }
  } catch {
    // If fetch fails, stay on this page
  } finally {
    refreshing.value = false
  }
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="login-page">
    <!-- Ambient background glow -->
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <div class="relative z-10 w-full max-w-sm mx-auto px-6 text-center">
      <!-- Brand -->
      <div class="mb-10">
        <div class="inline-flex items-center gap-3 mb-3">
          <span class="h-px w-8 bg-amber-500/60"></span>
          <span class="text-amber-400 text-xs font-semibold tracking-[0.3em] uppercase">Almost There</span>
          <span class="h-px w-8 bg-amber-500/60"></span>
        </div>
        <h1 class="text-4xl sm:text-5xl font-bold text-white tracking-tight">
          <span class="text-green-400">Pre</span><span class="text-yellow-400">Shift</span><span class="text-red-400">86</span>
        </h1>
      </div>

      <!-- Waiting message -->
      <div class="bg-white/5 border border-white/10 rounded-xl p-6 mb-6 backdrop-blur-sm">
        <div class="text-amber-400 text-lg font-semibold mb-3">Access Pending</div>
        <p class="text-gray-400 text-sm leading-relaxed">
          Your account is set up. A manager will assign you to a location. Check back soon.
        </p>
      </div>

      <!-- Actions -->
      <div class="space-y-3">
        <button
          @click="handleRefresh"
          :disabled="refreshing"
          class="w-full py-3 px-6 rounded-lg text-sm font-semibold tracking-wide uppercase
                 bg-amber-500 text-gray-950 hover:bg-amber-400
                 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-gray-950
                 disabled:opacity-50 disabled:cursor-not-allowed
                 transition-all duration-200 active:scale-[0.98]"
        >
          <span v-if="refreshing" class="inline-flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Checking...
          </span>
          <span v-else>Check Again</span>
        </button>

        <button
          @click="handleLogout"
          class="w-full py-3 px-6 rounded-lg text-sm font-semibold tracking-wide uppercase
                 bg-transparent border border-white/20 text-gray-400 hover:text-white hover:border-white/40
                 focus:outline-none focus:ring-2 focus:ring-white/20 focus:ring-offset-2 focus:ring-offset-gray-950
                 transition-all duration-200 active:scale-[0.98]"
        >
          Log Out
        </button>
      </div>

      <!-- Footer -->
      <p class="text-gray-600 text-xs mt-8">
        &copy; <span class="text-gray-500">BALDMIKE</span>
      </p>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0a0a0a;
  position: relative;
  overflow: hidden;
}

.glow {
  position: absolute;
  border-radius: 50%;
  filter: blur(100px);
  opacity: 0.15;
  pointer-events: none;
}

.glow-1 {
  width: 400px;
  height: 400px;
  background: #f59e0b;
  top: -10%;
  right: -10%;
}

.glow-2 {
  width: 300px;
  height: 300px;
  background: #d97706;
  bottom: -5%;
  left: -10%;
}
</style>
