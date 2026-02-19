<script setup lang="ts">
/**
 * LoginView -- the authentication entry point for all users.
 * Renders a centered login form with email/password fields.
 * On successful authentication, redirects to the staff dashboard.
 * Does NOT use the AppShell layout since the user is not yet authenticated.
 */
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

// Router used to navigate to the dashboard after successful login
const router = useRouter()
// Auth store provides the login action that sends credentials to the API
const authStore = useAuthStore()

// Reactive form state
const email = ref('')    // Bound to the email input via v-model
const password = ref('')  // Bound to the password input via v-model
const error = ref('')     // Holds the error message displayed when login fails
const loading = ref(false) // Tracks whether the login API call is in-flight

/**
 * Handles the login form submission. Clears any previous error,
 * calls the auth store's login action with the credentials,
 * and navigates to /dashboard on success. On failure, displays
 * the server error message or a generic fallback.
 */
async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    router.push('/dashboard')
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Invalid credentials. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-900 flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-white">PreShift</h1>
        <p class="text-gray-400 mt-2">Sign in to your account</p>
      </div>

      <form @submit.prevent="handleLogin" class="bg-white rounded-xl shadow-lg p-6 space-y-4">
        <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
          {{ error }}
        </div>

        <BaseInput
          v-model="email"
          label="Email"
          type="email"
          placeholder="you@example.com"
        />

        <BaseInput
          v-model="password"
          label="Password"
          type="password"
          placeholder="Your password"
        />

        <BaseButton
          type="submit"
          variant="primary"
          size="lg"
          :loading="loading"
          class="w-full"
        >
          Sign In
        </BaseButton>
      </form>
    </div>
  </div>
</template>
