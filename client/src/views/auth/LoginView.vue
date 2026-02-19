<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseInput from '@/components/ui/BaseInput.vue'

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
