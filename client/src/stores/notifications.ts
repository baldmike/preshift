/**
 * stores/notifications.ts
 *
 * Pinia store for manager/admin in-app notifications. Manages the notification
 * list, unread count, and provides actions for fetching, marking as read, and
 * handling real-time pushes via Reverb.
 */

import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/composables/useApi'
import type { AppNotification } from '@/types'

export const useNotificationStore = defineStore('notifications', () => {
  // ── State ──────────────────────────────────────────────────────────────

  const notifications = ref<AppNotification[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)

  // ── Actions ────────────────────────────────────────────────────────────

  /** Fetch the latest notifications from the API. */
  async function fetchNotifications() {
    loading.value = true
    try {
      const { data } = await api.get<{ notifications: AppNotification[]; unread_count: number }>(
        '/api/notifications'
      )
      notifications.value = data.notifications
      unreadCount.value = data.unread_count
    } finally {
      loading.value = false
    }
  }

  /** Mark a single notification as read (optimistic update). */
  async function markRead(id: string) {
    const n = notifications.value.find((n) => n.id === id)
    if (n && !n.read_at) {
      n.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
    await api.post(`/api/notifications/${id}/read`)
  }

  /** Mark all notifications as read (optimistic update). */
  async function markAllRead() {
    notifications.value.forEach((n) => {
      if (!n.read_at) n.read_at = new Date().toISOString()
    })
    unreadCount.value = 0
    await api.post('/api/notifications/read-all')
  }

  /** Push a new notification from a Reverb event into the store. */
  function pushNotification(notification: AppNotification) {
    notifications.value.unshift(notification)
    unreadCount.value += 1
  }

  return {
    notifications,
    unreadCount,
    loading,
    fetchNotifications,
    markRead,
    markAllRead,
    pushNotification,
  }
})
