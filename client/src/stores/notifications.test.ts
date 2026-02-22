/**
 * stores/notifications.test.ts
 *
 * Unit tests for the useNotificationStore Pinia store.
 *
 * Tests verify:
 *  1. Initial state is clean (empty array, counts at zero).
 *  2. `fetchNotifications()` populates state from API response.
 *  3. `markRead()` optimistically updates the notification and decrements count.
 *  4. `markAllRead()` marks all notifications and resets count to 0.
 *  5. `pushNotification()` prepends a new notification and increments count.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useNotificationStore } from '@/stores/notifications'
import type { AppNotification } from '@/types'

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

function makeNotification(overrides: Partial<AppNotification> = {}): AppNotification {
  return {
    id: crypto.randomUUID(),
    type: 'time_off_requested',
    title: 'Time Off Request',
    body: 'Server User requested time off.',
    link: '/manage/time-off',
    source_id: 1,
    read_at: null,
    created_at: '2026-02-22T12:00:00Z',
    ...overrides,
  }
}

describe('useNotificationStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('has correct initial state', () => {
    const store = useNotificationStore()
    expect(store.notifications).toEqual([])
    expect(store.unreadCount).toBe(0)
    expect(store.loading).toBe(false)
  })

  it('fetchNotifications populates state from API', async () => {
    const { default: api } = await import('@/composables/useApi')
    const n1 = makeNotification({ id: 'aaa' })
    const n2 = makeNotification({ id: 'bbb', read_at: '2026-02-22T13:00:00Z' })

    vi.mocked(api.get).mockResolvedValueOnce({
      data: { notifications: [n1, n2], unread_count: 1 },
    } as any)

    const store = useNotificationStore()
    await store.fetchNotifications()

    expect(store.notifications).toHaveLength(2)
    expect(store.unreadCount).toBe(1)
    expect(store.loading).toBe(false)
  })

  it('fetchNotifications sets loading flag', async () => {
    const { default: api } = await import('@/composables/useApi')

    let resolvePromise: (v: any) => void
    vi.mocked(api.get).mockReturnValueOnce(
      new Promise((resolve) => { resolvePromise = resolve }) as any
    )

    const store = useNotificationStore()
    const promise = store.fetchNotifications()

    expect(store.loading).toBe(true)

    resolvePromise!({ data: { notifications: [], unread_count: 0 } })
    await promise

    expect(store.loading).toBe(false)
  })

  it('markRead optimistically updates notification and decrements count', async () => {
    const { default: api } = await import('@/composables/useApi')
    vi.mocked(api.post).mockResolvedValueOnce({} as any)

    const store = useNotificationStore()
    const n = makeNotification({ id: 'test-id' })
    store.notifications = [n]
    store.unreadCount = 1

    await store.markRead('test-id')

    expect(store.notifications[0].read_at).not.toBeNull()
    expect(store.unreadCount).toBe(0)
    expect(api.post).toHaveBeenCalledWith('/api/notifications/test-id/read')
  })

  it('markRead does not decrement below zero', async () => {
    const { default: api } = await import('@/composables/useApi')
    vi.mocked(api.post).mockResolvedValueOnce({} as any)

    const store = useNotificationStore()
    const n = makeNotification({ id: 'test-id', read_at: '2026-02-22T12:00:00Z' })
    store.notifications = [n]
    store.unreadCount = 0

    await store.markRead('test-id')

    expect(store.unreadCount).toBe(0)
  })

  it('markAllRead marks all notifications and resets count', async () => {
    const { default: api } = await import('@/composables/useApi')
    vi.mocked(api.post).mockResolvedValueOnce({} as any)

    const store = useNotificationStore()
    store.notifications = [
      makeNotification({ id: 'a' }),
      makeNotification({ id: 'b' }),
    ]
    store.unreadCount = 2

    await store.markAllRead()

    expect(store.notifications.every((n) => n.read_at !== null)).toBe(true)
    expect(store.unreadCount).toBe(0)
    expect(api.post).toHaveBeenCalledWith('/api/notifications/read-all')
  })

  it('pushNotification prepends and increments count', () => {
    const store = useNotificationStore()
    const existing = makeNotification({ id: 'old' })
    store.notifications = [existing]
    store.unreadCount = 0

    const newN = makeNotification({ id: 'new', title: 'New Alert' })
    store.pushNotification(newN)

    expect(store.notifications).toHaveLength(2)
    expect(store.notifications[0].id).toBe('new')
    expect(store.unreadCount).toBe(1)
  })
})
