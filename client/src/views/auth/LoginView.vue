<script setup lang="ts">
/**
 * LoginView.vue
 *
 * Full-screen authentication page with email and password fields. On
 * successful login, redirects admins and managers to the daily management
 * view and regular staff to the dashboard. Displays an error banner for
 * invalid credentials and includes a loading spinner during submission.
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    const role = authStore.user?.role
    router.push(role === 'admin' || role === 'manager' ? '/manage/daily' : '/dashboard')
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Invalid credentials. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <!-- Ambient background glow -->
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <div class="relative z-10 w-full max-w-sm mx-auto px-6">
      <!-- Brand -->
      <div class="text-center mb-10">
        <div class="inline-flex items-center gap-3 mb-3">
          <span class="h-px w-8 bg-amber-500/60"></span>
          <span class="text-amber-400 text-xs font-semibold tracking-[0.3em] uppercase">Staff Portal</span>
          <span class="h-px w-8 bg-amber-500/60"></span>
        </div>
        <h1 class="text-4xl sm:text-5xl font-bold text-white tracking-tight">
          <span class="text-green-400">Pre</span><span class="text-yellow-400">Shift</span><span class="text-red-400">86</span>
        </h1>
        <p class="text-gray-500 text-sm mt-2">Sign in to start your shift</p>
      </div>

      <!-- Login form -->
      <form @submit.prevent="handleLogin" class="space-y-5">
        <!-- Error banner -->
        <div
          v-if="error"
          class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 text-sm backdrop-blur-sm"
        >
          {{ error }}
        </div>

        <!-- Email -->
        <div>
          <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">
            Email
          </label>
          <input
            v-model="email"
            type="text"
            inputmode="email"
            autocomplete="email"
            placeholder="you@restaurant.com"
            required
            class="login-input"
          />
        </div>

        <!-- Password -->
        <div>
          <label class="block text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">
            Password
          </label>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            placeholder="Enter your password"
            required
            class="login-input"
          />
        </div>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="loading"
          class="w-full relative py-3 px-6 rounded-lg text-sm font-semibold tracking-wide uppercase
                 bg-amber-500 text-gray-950 hover:bg-amber-400
                 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-gray-950
                 disabled:opacity-50 disabled:cursor-not-allowed
                 transition-all duration-200 active:scale-[0.98]"
        >
          <span v-if="loading" class="inline-flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            Signing In...
          </span>
          <span v-else>Sign In</span>
        </button>
      </form>

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

.login-input {
  display: block;
  width: 100%;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: #f3f4f6;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 0.5rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.login-input::placeholder {
  color: #4b5563;
}

.login-input:focus {
  border-color: #f59e0b;
  box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
}
</style>
