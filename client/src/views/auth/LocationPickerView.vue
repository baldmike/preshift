<script setup lang="ts">
/**
 * LocationPickerView.vue
 *
 * Shown after login when a user belongs to multiple establishments.
 * Displays a grid of location cards, each showing the venue name and
 * the user's role there. On selection, switches the active establishment
 * context and redirects to the appropriate dashboard based on role.
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref<number | null>(null)
const error = ref('')

async function pickLocation(locationId: number) {
  error.value = ''
  loading.value = locationId
  try {
    await authStore.switchLocation(locationId)
    const role = authStore.user?.role
    router.push(role === 'admin' || role === 'manager' ? '/manage/daily' : '/dashboard')
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to switch location.'
  } finally {
    loading.value = null
  }
}

/** Role badge color mapping */
function roleBadgeClass(role: string): string {
  switch (role) {
    case 'admin': return 'bg-purple-500/20 text-purple-400 border-purple-500/30'
    case 'manager': return 'bg-blue-500/20 text-blue-400 border-blue-500/30'
    case 'server': return 'bg-green-500/20 text-green-400 border-green-500/30'
    case 'bartender': return 'bg-amber-500/20 text-amber-400 border-amber-500/30'
    default: return 'bg-gray-500/20 text-gray-400 border-gray-500/30'
  }
}
</script>

<template>
  <div class="login-page">
    <!-- Ambient background glow -->
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <div class="relative z-10 w-full max-w-lg mx-auto px-6">
      <!-- Brand -->
      <div class="text-center mb-10">
        <div class="inline-flex items-center gap-3 mb-3">
          <span class="h-px w-8 bg-amber-500/60"></span>
          <span class="text-amber-400 text-xs font-semibold tracking-[0.3em] uppercase">Choose Location</span>
          <span class="h-px w-8 bg-amber-500/60"></span>
        </div>
        <h1 class="text-4xl sm:text-5xl font-bold text-white tracking-tight">
          <span class="text-green-400">Pre</span><span class="text-yellow-400">Shift</span><span class="text-red-400">86</span>
        </h1>
        <p class="text-gray-500 text-sm mt-2">Select the establishment you're working at today</p>
      </div>

      <!-- Error banner -->
      <div
        v-if="error"
        class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 text-sm backdrop-blur-sm mb-4"
      >
        {{ error }}
      </div>

      <!-- Location grid -->
      <div class="grid gap-3">
        <button
          v-for="loc in authStore.locations"
          :key="loc.id"
          @click="pickLocation(loc.id)"
          :disabled="loading !== null"
          class="w-full text-left px-5 py-4 rounded-lg border transition-all duration-200
                 bg-white/5 border-white/10 hover:bg-white/10 hover:border-amber-500/40
                 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-gray-950
                 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <div class="flex items-center justify-between">
            <div class="min-w-0">
              <p class="text-white font-semibold truncate">{{ loc.name }}</p>
            </div>
            <div class="flex items-center gap-2 ml-3 flex-shrink-0">
              <span
                class="text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full border"
                :class="roleBadgeClass(loc.role)"
              >
                {{ loc.role }}
              </span>
              <!-- Loading spinner for selected card -->
              <svg
                v-if="loading === loc.id"
                class="animate-spin h-4 w-4 text-amber-400"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
            </div>
          </div>
        </button>
      </div>

      <!-- Footer -->
      <p class="text-center text-gray-600 text-xs mt-8">
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
