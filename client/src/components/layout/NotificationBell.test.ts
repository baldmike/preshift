/**
 * components/layout/NotificationBell.test.ts
 *
 * Unit tests for the NotificationBell component.
 *
 * Tests verify:
 *  1. The bell icon renders without errors.
 *  2. The unread badge is visible when there are unread notifications.
 *  3. The badge is hidden when unread count is zero.
 *  4. Clicking the bell toggles the dropdown.
 *  5. Notification rows display title, body, and time.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import NotificationBell from '@/components/layout/NotificationBell.vue'
import { useNotificationStore } from '@/stores/notifications'

// Mock dependencies that require browser/network APIs
vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockResolvedValue({ data: { notifications: [], unread_count: 0 } }),
    post: vi.fn().mockResolvedValue({}),
  },
}))

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: { value: { id: 1, role: 'manager', location_id: 1, name: 'Test' } },
    locationId: { value: 1 },
    isAdmin: { value: false },
    isManager: { value: true },
  }),
}))

vi.mock('@/composables/useReverb', () => ({
  useReverb: () => ({
    private: () => ({
      listen: vi.fn().mockReturnThis(),
      notification: vi.fn().mockReturnThis(),
    }),
  }),
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn(),
  }),
}))

describe('NotificationBell', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders the bell icon', () => {
    const wrapper = mount(NotificationBell)
    expect(wrapper.find('button').exists()).toBe(true)
    expect(wrapper.find('svg').exists()).toBe(true)
  })

  it('shows unread badge when there are unread notifications', async () => {
    const wrapper = mount(NotificationBell)
    const store = useNotificationStore()
    store.unreadCount = 3

    await wrapper.vm.$nextTick()

    const badge = wrapper.find('span.bg-red-500')
    expect(badge.exists()).toBe(true)
    expect(badge.text()).toBe('3')
  })

  it('hides unread badge when count is zero', async () => {
    const wrapper = mount(NotificationBell)
    const store = useNotificationStore()
    store.unreadCount = 0

    await wrapper.vm.$nextTick()

    expect(wrapper.find('span.bg-red-500').exists()).toBe(false)
  })

  it('shows 9+ for counts above 9', async () => {
    const wrapper = mount(NotificationBell)
    const store = useNotificationStore()
    store.unreadCount = 15

    await wrapper.vm.$nextTick()

    const badge = wrapper.find('span.bg-red-500')
    expect(badge.text()).toBe('9+')
  })

  it('toggles dropdown on click', async () => {
    const wrapper = mount(NotificationBell)

    // Dropdown should be hidden initially
    expect(wrapper.find('.max-h-80').exists()).toBe(false)

    // Click the bell
    await wrapper.find('button').trigger('click')

    // Dropdown should be visible
    expect(wrapper.find('.max-h-80').exists()).toBe(true)
  })

  it('displays notification rows in dropdown', async () => {
    const { default: api } = await import('@/composables/useApi')
    const notification = {
      id: 'test-1',
      type: 'time_off_requested',
      title: 'Time Off Request',
      body: 'Server User requested time off.',
      link: '/manage/time-off',
      source_id: 1,
      read_at: null,
      created_at: new Date().toISOString(),
    }

    vi.mocked(api.get).mockResolvedValueOnce({
      data: { notifications: [notification], unread_count: 1 },
    } as any)

    const wrapper = mount(NotificationBell)
    // Wait for fetchNotifications to resolve
    await new Promise((r) => setTimeout(r, 10))
    await wrapper.vm.$nextTick()

    // Open dropdown
    await wrapper.find('button').trigger('click')
    await wrapper.vm.$nextTick()

    const text = wrapper.text()
    expect(text).toContain('Time Off Request')
    expect(text).toContain('Server User requested time off.')
  })
})
