/**
 * WeatherWidget.spec.ts
 *
 * Tests for the weather section in DashboardView.
 *
 * Tests verify:
 *  1. Weather section renders when data is available.
 *  2. Weather section is hidden when API returns 404.
 *  3. Temperature and conditions display correctly.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { usePreshiftStore } from '@/stores/preshift'
import { useScheduleStore } from '@/stores/schedule'
import DashboardView from '@/views/staff/DashboardView.vue'

// ── Mock useApi ─────────────────────────────────────────────────────────────

const mockGet = vi.fn()
const mockPost = vi.fn()
const mockPatch = vi.fn()
const mockDelete = vi.fn()

vi.mock('@/composables/useApi', () => ({
  default: {
    get: (...args: any[]) => mockGet(...args),
    post: (...args: any[]) => mockPost(...args),
    patch: (...args: any[]) => mockPatch(...args),
    delete: (...args: any[]) => mockDelete(...args),
  },
  useApi: () => ({
    get: (...args: any[]) => mockGet(...args),
    post: (...args: any[]) => mockPost(...args),
    patch: (...args: any[]) => mockPatch(...args),
    delete: (...args: any[]) => mockDelete(...args),
  }),
}))

// ── Mock useAuth ────────────────────────────────────────────────────────────

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    locationId: { value: 1 },
    user: { value: { id: 1, role: 'server', name: 'Test', location_id: 1 } },
  }),
}))

// ── Mock useReverb ──────────────────────────────────────────────────────────

vi.mock('@/composables/useReverb', () => ({
  useLocationChannel: () => ({
    listen: vi.fn().mockReturnThis(),
    stopListening: vi.fn(),
  }),
}))

// ── Mock useSchedule ────────────────────────────────────────────────────────

vi.mock('@/composables/useSchedule', () => ({
  useSchedule: () => ({
    nextShift: { value: null },
    currentWeekShifts: { value: {} },
    currentWeekRange: { value: { monday: '2026-02-16' } },
    formatShiftTime: (t: string) => t,
  }),
}))

// ── Stubs ───────────────────────────────────────────────────────────────────

const AppShellStub = { template: '<div><slot /></div>' }
const RouterLinkStub = { template: '<a><slot /></a>', props: ['to'] }
const CardStub = { template: '<div />', props: { item: Object, special: Object, announcement: Object } }

// ── Helpers ─────────────────────────────────────────────────────────────────

const weatherData = {
  current: {
    temperature: 72,
    feels_like: 75,
    humidity: 45,
    wind_speed: 8,
    weather_code: 1,
    description: 'Mainly Clear',
  },
  today: {
    high: 78,
    low: 62,
    weather_code: 2,
    description: 'Partly Cloudy',
  },
}

// ── Test suite ──────────────────────────────────────────────────────────────

describe('Weather Widget in DashboardView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  function mountDashboard() {
    const preshiftStore = usePreshiftStore()
    const scheduleStore = useScheduleStore()

    // Stub store fetches
    preshiftStore.fetchAll = vi.fn().mockResolvedValue(undefined)
    scheduleStore.fetchMyShifts = vi.fn().mockResolvedValue(undefined)
    scheduleStore.fetchCurrentWeekSchedule = vi.fn().mockResolvedValue(undefined)

    // Populate some preshift data so hasContent is true
    preshiftStore.announcements = [{ id: 1, title: 'Test', location_id: 1, body: null, priority: null, target_roles: null, posted_by: 1, expires_at: null, created_at: '', updated_at: '' }] as any

    return mount(DashboardView, {
      global: {
        stubs: {
          AppShell: AppShellStub,
          'router-link': RouterLinkStub,
          EightySixedCard: CardStub,
          SpecialCard: CardStub,
          PushItemCard: CardStub,
          AnnouncementCard: CardStub,
        },
      },
    })
  }

  it('renders weather section when data is available', async () => {
    // Weather API succeeds
    mockGet.mockImplementation((url: string) => {
      if (url === '/api/weather') {
        return Promise.resolve({ data: weatherData })
      }
      return Promise.resolve({ data: [] })
    })

    const wrapper = mountDashboard()
    await flushPromises()

    // Weather section should be visible
    expect(wrapper.text()).toContain('Weather')
    expect(wrapper.text()).toContain('72°')
    expect(wrapper.text()).toContain('Mainly Clear')
  })

  it('hides weather section when API returns 404', async () => {
    // Weather API returns 404 (no coordinates configured)
    mockGet.mockImplementation((url: string) => {
      if (url === '/api/weather') {
        return Promise.reject({ response: { status: 404 } })
      }
      return Promise.resolve({ data: [] })
    })

    const wrapper = mountDashboard()
    await flushPromises()

    // Weather section should NOT be visible
    expect(wrapper.text()).not.toContain('Weather')
    expect(wrapper.text()).not.toContain('72°')
  })

  it('displays temperature, high/low, humidity, and wind correctly', async () => {
    mockGet.mockImplementation((url: string) => {
      if (url === '/api/weather') {
        return Promise.resolve({ data: weatherData })
      }
      return Promise.resolve({ data: [] })
    })

    const wrapper = mountDashboard()
    await flushPromises()

    expect(wrapper.text()).toContain('72°')
    expect(wrapper.text()).toContain('Feels like 75°')
    expect(wrapper.text()).toContain('H: 78°')
    expect(wrapper.text()).toContain('L: 62°')
    expect(wrapper.text()).toContain('45%')
    expect(wrapper.text()).toContain('humidity')
    expect(wrapper.text()).toContain('8')
    expect(wrapper.text()).toContain('mph wind')
  })
})
