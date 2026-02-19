import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/composables/useApi'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('preshift_token'))

  const isLoggedIn = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isManager = computed(() => user.value?.role === 'manager')
  const isStaff = computed(
    () => !!user.value && ['server', 'bartender'].includes(user.value.role)
  )
  const locationId = computed(() => user.value?.location_id ?? null)

  async function login(email: string, password: string) {
    const { data } = await api.post('/api/login', { email, password })
    token.value = data.token
    user.value = data.user
    localStorage.setItem('preshift_token', data.token)
  }

  async function logout() {
    try {
      await api.post('/api/logout')
    } catch {
      // Ignore logout errors
    }
    token.value = null
    user.value = null
    localStorage.removeItem('preshift_token')
  }

  async function fetchUser() {
    const { data } = await api.get('/api/user')
    user.value = data
  }

  return {
    user,
    token,
    isLoggedIn,
    isAdmin,
    isManager,
    isStaff,
    locationId,
    login,
    logout,
    fetchUser,
  }
})
