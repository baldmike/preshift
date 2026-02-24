<script setup lang="ts">
/**
 * BottomNav.vue
 *
 * Fixed bottom navigation bar for mobile-first layout. Displays a persistent
 * copyright line above icon+label links to Dashboard, Schedule, Messages,
 * and (for admins/managers) Manage. The active route is highlighted in amber.
 *
 * 86'd and Specials are accessed from the dashboard tiles rather than
 * having dedicated nav items, keeping the footer clean and focused.
 */
import { onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import { useMessageStore } from '@/stores/messages'

const { isAdmin, isManager } = useAuth()
const messageStore = useMessageStore()

onMounted(() => {
  messageStore.fetchUnreadCount()
})
</script>

<template>
  <Teleport to="body">
    <nav class="fixed bottom-0 left-0 right-0 bg-gray-900 border-t border-gray-800 z-50">
      <p class="text-center text-[9px] text-gray-600 pt-1">&copy; BALDMIKE</p>
      <div class="flex items-center justify-around h-14 max-w-lg mx-auto">
        <router-link
          to="/dashboard"
          data-tour="bottom-nav-dashboard"
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
          :to="(isAdmin || isManager) ? '/manage/schedule' : '/my-schedule'"
          data-tour="bottom-nav-schedule"
          class="flex flex-col items-center gap-0.5 text-xs transition-colors"
          :class="($route.path === '/my-schedule' || $route.path === '/manage/schedule') ? 'text-amber-400' : 'text-gray-500 hover:text-gray-300'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span>Schedule</span>
        </router-link>

        <router-link
          to="/messages"
          data-tour="bottom-nav-messages"
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
          data-tour="bottom-nav-manage"
          class="flex flex-col items-center gap-0.5 text-xs transition-colors"
          :class="$route.path.startsWith('/manage') ? 'text-amber-400' : 'text-gray-500 hover:text-gray-300'"
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
  </Teleport>
</template>
