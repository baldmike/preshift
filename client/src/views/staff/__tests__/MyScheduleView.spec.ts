/**
 * MyScheduleView.spec.ts
 *
 * Tests for the role-based schedule layout:
 *  1. Staff sees "My Shifts" first, team schedule collapsed by default
 *  2. Manager sees full schedule grid first
 *  3. Toggle expands/collapses team schedule for staff
 *  4. Staff entries are highlighted in the grid
 *  5. Staff sees empty state when no shifts are scheduled
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { useScheduleStore } from '@/stores/schedule'
import { useAuthStore } from '@/stores/auth'
import MyScheduleView from '@/views/staff/MyScheduleView.vue'
import type { Schedule, ScheduleEntry, User } from '@/types'

// ── Stubs ──────────────────────────────────────────────────────────────────

const AppShellStub = { template: '<div><slot /></div>' }
const RouterLinkStub = { template: '<a><slot /></a>', props: ['to'] }
const AvailabilityGridStub = { template: '<div data-testid="availability-grid" />', props: ['modelValue', 'saving'], emits: ['save', 'update:modelValue'] }

// ── Helpers ────────────────────────────────────────────────────────────────

function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 1,
    name: 'Test User',
    email: 'test@example.com',
    role: 'server',
    roles: null,
    location_id: 1,
    organization_id: null,
    is_superadmin: false,
    phone: null,
    profile_photo_url: null,
    availability: null,
    created_at: '',
    updated_at: '',
    ...overrides,
  }
}

function makeEntry(overrides: Partial<ScheduleEntry> & { date: string }): ScheduleEntry {
  return {
    id: Math.floor(Math.random() * 10000),
    schedule_id: 1,
    user_id: 1,
    shift_template_id: 1,
    role: 'server',
    notes: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
    ...overrides,
  }
}

function makeSchedule(entries: ScheduleEntry[]): Schedule {
  return {
    id: 1,
    location_id: 1,
    week_start: '2026-02-16',
    status: 'published',
    published_at: '2026-02-16T08:00:00Z',
    published_by: 1,
    entries,
    created_at: '2026-02-16T00:00:00Z',
    updated_at: '2026-02-16T00:00:00Z',
  }
}

function mountView() {
  const scheduleStore = useScheduleStore()
  scheduleStore.fetchMyShifts = vi.fn().mockResolvedValue(undefined)
  scheduleStore.fetchCurrentWeekSchedule = vi.fn().mockResolvedValue(undefined)

  return mount(MyScheduleView, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        'router-link': RouterLinkStub,
        AvailabilityGrid: AvailabilityGridStub,
      },
    },
  })
}

// ── Tests ──────────────────────────────────────────────────────────────────

describe('MyScheduleView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  describe('staff layout (server/bartender)', () => {
    beforeEach(() => {
      const authStore = useAuthStore()
      authStore.user = makeUser({ id: 1, role: 'server' })
      authStore.token = 'test-token'
    })

    it('shows "My Shifts" section first and subtitle "My upcoming shifts"', async () => {
      const scheduleStore = useScheduleStore()
      scheduleStore.myShifts = [
        makeEntry({
          id: 1,
          date: '2026-02-18',
          user_id: 1,
          shift_template_id: 10,
          shift_template: { id: 10, location_id: 1, name: 'Dinner', start_time: '16:00', created_at: '', updated_at: '' },
        }),
      ]
      scheduleStore.currentSchedule = makeSchedule([])

      const wrapper = mountView()
      await flushPromises()

      // Subtitle should reflect staff view
      expect(wrapper.text()).toContain('My upcoming shifts')
      // "My Shifts" heading should be present
      expect(wrapper.text()).toContain('My Shifts')
    })

    it('has team schedule collapsed by default', async () => {
      const scheduleStore = useScheduleStore()
      scheduleStore.myShifts = []
      scheduleStore.currentSchedule = makeSchedule([
        makeEntry({
          id: 1,
          date: '2026-02-18',
          user_id: 2,
          shift_template_id: 10,
          user: makeUser({ id: 2, name: 'Other Person', role: 'server' }),
          shift_template: { id: 10, location_id: 1, name: 'Dinner', start_time: '16:00', created_at: '', updated_at: '' },
        }),
      ])

      const wrapper = mountView()
      await flushPromises()

      // Toggle button should show "View Full Schedule" (collapsed state)
      expect(wrapper.text()).toContain('View Full Schedule')
      // The ScheduleGrid should NOT be rendered
      expect(wrapper.findComponent({ name: 'ScheduleGrid' }).exists()).toBe(false)
    })

    it('expands team schedule when toggle is clicked', async () => {
      const scheduleStore = useScheduleStore()
      scheduleStore.myShifts = []
      scheduleStore.currentSchedule = makeSchedule([
        makeEntry({
          id: 1,
          date: '2026-02-18',
          user_id: 2,
          shift_template_id: 10,
          user: makeUser({ id: 2, name: 'Other Person', role: 'server' }),
          shift_template: { id: 10, location_id: 1, name: 'Dinner', start_time: '16:00', created_at: '', updated_at: '' },
        }),
      ])

      const wrapper = mountView()
      await flushPromises()

      // Click the toggle button
      const toggleBtn = wrapper.find('button[class*="uppercase"]')
      await toggleBtn.trigger('click')

      // Should now show "Hide Full Schedule"
      expect(wrapper.text()).toContain('Hide Full Schedule')
      // ScheduleGrid should now be rendered
      expect(wrapper.findComponent({ name: 'ScheduleGrid' }).exists()).toBe(true)
    })

    it('shows empty state when staff has no shifts', async () => {
      const scheduleStore = useScheduleStore()
      scheduleStore.myShifts = []
      scheduleStore.currentSchedule = makeSchedule([
        makeEntry({ id: 1, date: '2026-02-18', user_id: 2, shift_template_id: 10 }),
      ])

      const wrapper = mountView()
      await flushPromises()

      expect(wrapper.text()).toContain('No upcoming shifts')
    })

    it('passes highlightUserId to ScheduleGrid when expanded', async () => {
      const authStore = useAuthStore()
      authStore.user = makeUser({ id: 42, role: 'bartender' })

      const scheduleStore = useScheduleStore()
      scheduleStore.myShifts = []
      scheduleStore.currentSchedule = makeSchedule([
        makeEntry({
          id: 1,
          date: '2026-02-18',
          user_id: 42,
          shift_template_id: 10,
          user: makeUser({ id: 42, name: 'Me', role: 'bartender' }),
          shift_template: { id: 10, location_id: 1, name: 'Dinner', start_time: '16:00', created_at: '', updated_at: '' },
        }),
      ])

      const wrapper = mountView()
      await flushPromises()

      // Expand the team schedule
      const toggleBtn = wrapper.find('button[class*="uppercase"]')
      await toggleBtn.trigger('click')

      const grid = wrapper.findComponent({ name: 'ScheduleGrid' })
      expect(grid.exists()).toBe(true)
      expect(grid.props('highlightUserId')).toBe(42)
    })
  })

  describe('manager/admin layout', () => {
    beforeEach(() => {
      const authStore = useAuthStore()
      authStore.user = makeUser({ id: 1, role: 'manager' })
      authStore.token = 'test-token'
    })

    it('shows full schedule grid first and subtitle "This week\'s full schedule"', async () => {
      const scheduleStore = useScheduleStore()
      scheduleStore.myShifts = []
      scheduleStore.currentSchedule = makeSchedule([
        makeEntry({
          id: 1,
          date: '2026-02-18',
          user_id: 2,
          shift_template_id: 10,
          user: makeUser({ id: 2, name: 'Staff Member', role: 'server' }),
          shift_template: { id: 10, location_id: 1, name: 'Dinner', start_time: '16:00', created_at: '', updated_at: '' },
        }),
      ])

      const wrapper = mountView()
      await flushPromises()

      // Subtitle
      expect(wrapper.text()).toContain("This week's full schedule")
      // ScheduleGrid should be visible immediately (no toggle needed)
      expect(wrapper.findComponent({ name: 'ScheduleGrid' }).exists()).toBe(true)
      // Should NOT have the toggle button
      expect(wrapper.text()).not.toContain('View Full Schedule')
    })

    it('does not show empty state for shifts', async () => {
      const scheduleStore = useScheduleStore()
      scheduleStore.myShifts = []
      scheduleStore.currentSchedule = makeSchedule([])

      const wrapper = mountView()
      await flushPromises()

      expect(wrapper.text()).not.toContain('No upcoming shifts')
    })
  })
})
