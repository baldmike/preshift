<script setup lang="ts">
/**
 * BottomNav.vue
 *
 * Fixed bottom navigation bar for mobile-first layout. Displays a persistent
 * copyright line above icon+label links to Dashboard, 86'd Board, Specials,
 * Schedule, Messages, and (for admins/managers) Manage.
 * The active route is highlighted in amber.
 *
 * For admins and managers, the 86'd and Specials links point to the manage
 * versions (/manage/86, /manage/specials) instead of the staff read-only views.
 */
import { computed, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useMessageStore } from '@/stores/messages'

const { isAdmin, isManager } = useAuth()
const messageStore = useMessageStore()

/** Route for the 86'd nav item — managers go to /manage/86, staff to /86. */
const eightySixRoute = computed(() => (isAdmin.value || isManager.value) ? '/manage/86' : '/86')

/** Route for the Specials nav item — managers go to /manage/specials, staff to /specials. */
const specialsRoute = computed(() => (isAdmin.value || isManager.value) ? '/manage/specials' : '/specials')

onMounted(() => {
  messageStore.fetchUnreadCount()
})
</script>

<template>
  <nav class="fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-800 z-40">
    <p class="text-center text-[9px] text-gray-600 pt-1">&copy; BALDMIKE</p>
    <div class="flex items-center justify-around h-14 max-w-lg mx-auto">
      <router-link
        to="/dashboard"
        class="flex flex-col items-center gap-0.5 text-xs transition-colors"
        :class="$route.path === '/dashboard' ? 'text-amber-400' : 'text-gray-500 hover:text-gray-300'"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span>Dashboard</span>
      </router-link>

      <router-link
        :to="eightySixRoute"
        class="flex flex-col items-center gap-0.5 text-xs transition-colors"
        :class="$route.path === '/86' || $route.path.startsWith('/manage/86') ? 'text-amber-400' : 'text-gray-500 hover:text-gray-300'"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
        <span>86'd</span>
      </router-link>

      <router-link
        :to="specialsRoute"
        class="flex flex-col items-center gap-0.5 text-xs transition-colors"
        :class="$route.path === '/specials' || $route.path === '/manage/specials' ? 'text-amber-400' : 'text-gray-500 hover:text-gray-300'"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
        <span>Specials</span>
      </router-link>

      <router-link
        to="/my-schedule"
        class="flex flex-col items-center gap-0.5 text-xs transition-colors"
        :class="$route.path === '/my-schedule' ? 'text-amber-400' : 'text-gray-500 hover:text-gray-300'"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span>Schedule</span>
      </router-link>

      <router-link
        to="/messages"
        class="flex flex-col items-center gap-0.5 text-xs transition-colors relative"
        :class="$route.path === '/messages' ? 'text-amber-400' : 'text-gray-500 hover:text-gray-300'"
      >
        <div class="relative">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <span
            v-if="messageStore.unreadDmCount > 0"
            class="absolute -top-1.5 -right-1.5 min-w-[14px] h-[14px] flex items-center justify-center text-[8px] font-bold bg-amber-500 text-gray-900 rounded-full px-0.5"
          >
            {{ messageStore.unreadDmCount > 9 ? '9+' : messageStore.unreadDmCount }}
          </span>
        </div>
        <span>Messages</span>
      </router-link>

      <router-link
        v-if="isAdmin || isManager"
        to="/manage/daily"
        class="flex flex-col items-center gap-0.5 text-xs transition-colors"
        :class="$route.path.startsWith('/manage') && !$route.path.startsWith('/manage/86') && $route.path !== '/manage/specials' ? 'text-amber-400' : 'text-gray-500 hover:text-gray-300'"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span>Manage</span>
      </router-link>

    </div>
  </nav>
</template>
