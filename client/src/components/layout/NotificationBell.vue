<script setup lang="ts">
/**
 * NotificationBell.vue
 *
 * Bell icon with unread badge for the TopBar. Clicking toggles a dropdown
 * that lists recent notifications. Each notification row shows a title,
 * body, and relative time. Clicking a row navigates to the related page
 * and marks it as read. A "Mark all read" button in the header clears the
 * badge. On mount, subscribes to the user's private Reverb channel to
 * receive real-time notification pushes.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/stores/notifications'
import { useAuth } from '@/composables/useAuth'
import { useReverb } from '@/composables/useReverb'
import type { AppNotification } from '@/types'

const store = useNotificationStore()
const router = useRouter()
const { user, locationId } = useAuth()
const dropdownOpen = ref(false)

// ── Fetch on mount + subscribe to Reverb ────────────────────────────────

onMounted(() => {
  store.fetchNotifications()

  if (locationId.value && user.value) {
    const echo = useReverb(locationId.value)
    echo.private(`App.Models.User.${user.value.id}`)
      .notification((notification: AppNotification) => {
        store.pushNotification(notification)
        window.dispatchEvent(new CustomEvent('toast', {
          detail: { message: notification.title, type: 'info' }
        }))
      })
  }
})

// ── Click outside to close ──────────────────────────────────────────────

function onClickOutside() {
  if (dropdownOpen.value) dropdownOpen.value = false
}
onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))

// ── Helpers ─────────────────────────────────────────────────────────────

const hasUnread = computed(() => store.unreadCount > 0)

function timeAgo(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}

function handleClick(n: AppNotification) {
  if (!n.read_at) store.markRead(n.id)
  dropdownOpen.value = false
  router.push(n.link)
}

function handleMarkAllRead() {
  store.markAllRead()
}
</script>

<template>
  <div class="relative">
    <!-- Bell button -->
    <button
      @click.stop="dropdownOpen = !dropdownOpen"
      class="relative p-1 text-gray-400 hover:text-white transition-colors focus:outline-none
             focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 focus:ring-offset-gray-900 rounded"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      <!-- Unread badge -->
      <span
        v-if="hasUnread"
        class="absolute -top-0.5 -right-0.5 flex items-center justify-center w-4 h-4
               bg-red-500 text-white text-[10px] font-bold rounded-full"
      >
        {{ store.unreadCount > 9 ? '9+' : store.unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="dropdownOpen"
        @click.stop
        class="absolute right-0 mt-2 w-80 origin-top-right rounded-lg bg-gray-800
               border border-gray-700 shadow-lg ring-1 ring-black/10 overflow-hidden z-50"
      >
        <!-- Header -->
        <div class="px-4 py-2.5 border-b border-gray-700 flex items-center justify-between">
          <span class="text-sm font-medium text-white">Notifications</span>
          <button
            v-if="hasUnread"
            @click="handleMarkAllRead"
            class="text-xs text-amber-400 hover:text-amber-300 transition-colors"
          >
            Mark all read
          </button>
        </div>

        <!-- Notification list -->
        <div class="max-h-80 overflow-y-auto">
          <div v-if="store.notifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
            No notifications yet.
          </div>
          <button
            v-for="n in store.notifications"
            :key="n.id"
            @click="handleClick(n)"
            class="w-full text-left px-4 py-3 hover:bg-gray-700/50 transition-colors border-b border-gray-700/50 last:border-0"
            :class="{ 'opacity-60': n.read_at }"
          >
            <div class="flex items-start justify-between gap-2">
              <p class="text-sm font-medium text-white leading-tight">{{ n.title }}</p>
              <span
                v-if="!n.read_at"
                class="mt-1 flex-shrink-0 w-2 h-2 rounded-full bg-amber-400"
              />
            </div>
            <p class="text-xs text-gray-400 mt-0.5 leading-snug">{{ n.body }}</p>
            <p class="text-[10px] text-gray-500 mt-1">{{ timeAgo(n.created_at) }}</p>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>
