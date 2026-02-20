<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import RealtimeIndicator from '@/components/RealtimeIndicator.vue'

const { user } = useAuth()
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

const establishment = computed(() => user.value?.location?.name || 'PreShift86')

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
</script>

<template>
  <header class="bg-gray-900 text-white sticky top-0 z-40">
    <div class="px-4 py-3 sm:py-4 sm:px-6 max-w-4xl mx-auto w-full">
      <div class="flex items-start justify-between">
        <!-- Left: establishment + date/time -->
        <div class="min-w-0 flex-1">
          <h1 class="text-lg sm:text-xl font-bold tracking-tight truncate">
            {{ establishment }}
          </h1>
          <div class="flex flex-wrap items-center gap-x-2 text-sm text-gray-400 mt-0.5">
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
                class="absolute right-0 mt-2 w-48 origin-top-right rounded-lg bg-gray-800
                       border border-gray-700 shadow-lg ring-1 ring-black/10 overflow-hidden"
              >
                <!-- User info -->
                <div class="px-4 py-3 border-b border-gray-700">
                  <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ user.email }}</p>
                </div>

                <!-- Menu items -->
                <div class="py-1">
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
