import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useAuth() {
  const store = useAuthStore()

  const user = computed(() => store.user)
  const isLoggedIn = computed(() => store.isLoggedIn)
  const isAdmin = computed(() => store.isAdmin)
  const isManager = computed(() => store.isManager)
  const isStaff = computed(() => store.isStaff)
  const locationId = computed(() => store.locationId)

  return {
    user,
    isLoggedIn,
    isAdmin,
    isManager,
    isStaff,
    locationId,
  }
}
