<script setup lang="ts">
/**
 * TopBar.vue
 *
 * Sticky top header bar displayed on every authenticated page. Shows the
 * establishment name, a live-updating date/time clock localized to the
 * user's location timezone, a real-time connection indicator, a user
 * avatar dropdown with change-password and logout functionality.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import api from '@/composables/useApi'
import RealtimeIndicator from '@/components/RealtimeIndicator.vue'
import NotificationBell from '@/components/layout/NotificationBell.vue'

const { user, isAdmin, isManager } = useAuth()
const authStore = useAuthStore()
const router = useRouter()

const now = ref(new Date())
let timer: ReturnType<typeof setInterval>

onMounted(() => {
  timer = setInterval(() => { now.value = new Date() }, 1000)
})
onUnmounted(() => clearInterval(timer))

// Close menu when clicking outside
const menuOpen = ref(false)
function onClickOutside(e: Event) {
  if (menuOpen.value) menuOpen.value = false
}
onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))

const timezone = computed(() => user.value?.location?.timezone || 'America/New_York')

const dayName = computed(() =>
  now.value.toLocaleDateString('en-US', { weekday: 'long', timeZone: timezone.value })
)

const dateStr = computed(() =>
  now.value.toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    timeZone: timezone.value,
  })
)

const timeStr = computed(() =>
  now.value.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    timeZone: timezone.value,
  })
)

// Establishment name: fetched from settings API, falls back to location name
const settingsName = ref<string | null>(null)
const establishment = computed(() => settingsName.value || user.value?.location?.name || 'PreShift86')

onMounted(async () => {
  try {
    const { data } = await api.get('/api/config/settings')
    if (data.establishment_name) {
      settingsName.value = data.establishment_name
    }
  } catch {
    // Fall back to location name
  }
})

/** City, State label from the user's location (e.g. "Austin, TX") */
const cityState = computed(() => {
  const loc = user.value?.location
  if (!loc?.city) return null
  return loc.state ? `${loc.city}, ${loc.state}` : loc.city
})

const initials = computed(() => {
  if (!user.value?.name) return '?'
  return user.value.name
    .split(' ')
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})

async function handleLogout() {
  menuOpen.value = false
  await authStore.logout()
  router.push('/login')
}

// ── Change Password ──
const showChangePassword = ref(false)
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const changePwLoading = ref(false)
const changePwError = ref('')

function openChangePassword() {
  showChangePassword.value = true
  changePwError.value = ''
  currentPassword.value = ''
  newPassword.value = ''
  confirmPassword.value = ''
}

function cancelChangePassword() {
  showChangePassword.value = false
  changePwError.value = ''
}

async function changePassword() {
  changePwError.value = ''

  if (newPassword.value !== confirmPassword.value) {
    changePwError.value = 'New passwords do not match.'
    return
  }

  if (newPassword.value.length < 8) {
    changePwError.value = 'New password must be at least 8 characters.'
    return
  }

  changePwLoading.value = true
  try {
    await api.post('/api/change-password', {
      current_password: currentPassword.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    })
    showChangePassword.value = false
    menuOpen.value = false
    window.dispatchEvent(new CustomEvent('toast', {
      detail: { message: 'Password changed successfully.', type: 'success' }
    }))
  } catch (err: any) {
    changePwError.value = err.response?.data?.message || 'Failed to change password.'
  } finally {
    changePwLoading.value = false
  }
}
</script>

<template>
  <header class="bg-gray-900 text-white sticky top-0 z-40">
    <div class="px-3 py-2 sm:py-4 sm:px-6 max-w-4xl mx-auto w-full">
      <div class="flex items-center justify-between">
        <!-- Left: establishment + date/time -->
        <div class="min-w-0 flex-1">
          <h1 class="text-sm sm:text-xl font-bold tracking-tight truncate">
            {{ establishment }}
          </h1>
          <p v-if="cityState" class="text-[10px] sm:text-xs text-gray-500 -mt-0.5">{{ cityState }}</p>
          <div class="flex flex-wrap items-center gap-x-1.5 sm:gap-x-2 text-[11px] sm:text-sm text-gray-400 mt-0.5">
            <span class="font-medium text-gray-300">{{ dayName }}</span>
            <span class="hidden sm:inline text-gray-600">&middot;</span>
            <span>{{ dateStr }}</span>
            <span class="text-gray-600">&middot;</span>
            <span class="tabular-nums text-gray-300">{{ timeStr }}</span>
          </div>
        </div>

        <!-- Right: realtime indicator + user avatar -->
        <div class="flex items-center gap-3 ml-4 pt-1">
          <RealtimeIndicator />

          <!-- Notification bell (managers/admins only) -->
          <NotificationBell v-if="isAdmin || isManager" />

          <!-- User avatar / settings dropdown -->
          <div v-if="user" class="relative">
            <button
              @click.stop="menuOpen = !menuOpen"
              class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500 text-gray-950
                     text-xs font-bold hover:bg-amber-400 transition-colors focus:outline-none
                     focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-gray-900"
            >
              {{ initials }}
            </button>

            <!-- Dropdown menu -->
            <Transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="opacity-0 scale-95"
              enter-to-class="opacity-100 scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="opacity-100 scale-100"
              leave-to-class="opacity-0 scale-95"
            >
              <div
                v-if="menuOpen"
                @click.stop
                class="absolute right-0 mt-2 w-64 origin-top-right rounded-lg bg-gray-800
                       border border-gray-700 shadow-lg ring-1 ring-black/10 overflow-hidden"
              >
                <!-- User info -->
                <div class="px-4 py-3 border-b border-gray-700">
                  <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ user.email }}</p>
                </div>

                <!-- Menu items -->
                <div class="py-1">
                  <!-- My Profile link — navigates to /profile and closes the dropdown -->
                  <router-link
                    to="/profile"
                    @click="menuOpen = false"
                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700
                           hover:text-white transition-colors flex items-center gap-2"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    My Profile
                  </router-link>

                  <!-- Change Password toggle -->
                  <button
                    v-if="!showChangePassword"
                    @click="openChangePassword"
                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700
                           hover:text-white transition-colors flex items-center gap-2"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Change Password
                  </button>

                  <!-- Inline change-password form -->
                  <div v-if="showChangePassword" class="px-4 py-3 border-t border-gray-700 space-y-2">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide">Change Password</p>
                    <input
                      v-model="currentPassword"
                      type="password"
                      placeholder="Current password"
                      class="w-full px-3 py-1.5 text-sm bg-gray-700 border border-gray-600 rounded
                             text-white placeholder-gray-500 focus:outline-none focus:border-amber-500"
                    />
                    <input
                      v-model="newPassword"
                      type="password"
                      placeholder="New password (min 8 chars)"
                      class="w-full px-3 py-1.5 text-sm bg-gray-700 border border-gray-600 rounded
                             text-white placeholder-gray-500 focus:outline-none focus:border-amber-500"
                    />
                    <input
                      v-model="confirmPassword"
                      type="password"
                      placeholder="Confirm new password"
                      class="w-full px-3 py-1.5 text-sm bg-gray-700 border border-gray-600 rounded
                             text-white placeholder-gray-500 focus:outline-none focus:border-amber-500"
                    />
                    <p v-if="changePwError" class="text-xs text-red-400">{{ changePwError }}</p>
                    <div class="flex gap-2">
                      <button
                        @click="changePassword"
                        :disabled="changePwLoading"
                        class="flex-1 px-3 py-1.5 text-xs font-medium bg-amber-500 text-gray-950 rounded
                               hover:bg-amber-400 disabled:opacity-50 transition-colors"
                      >
                        {{ changePwLoading ? 'Saving...' : 'Save' }}
                      </button>
                      <button
                        @click="cancelChangePassword"
                        class="flex-1 px-3 py-1.5 text-xs font-medium bg-gray-600 text-gray-200 rounded
                               hover:bg-gray-500 transition-colors"
                      >
                        Cancel
                      </button>
                    </div>
                  </div>

                  <button
                    @click="handleLogout"
                    class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700
                           hover:text-white transition-colors flex items-center gap-2"
                  >
                    <!-- Logout icon -->
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Log Out
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
