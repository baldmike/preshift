<script setup lang="ts">
import { useAuth } from '@/composables/useAuth'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import RealtimeIndicator from '@/components/RealtimeIndicator.vue'

const { user } = useAuth()
const authStore = useAuthStore()
const router = useRouter()

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
