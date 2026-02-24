/**
 * BottomNav.test.ts
 *
 * Unit tests for the BottomNav.vue component.
 *
 * Tests verify:
 *   1. The Manage link is visible for managers.
 *   2. The Manage link is hidden for regular staff.
 *   3. Config link is not present (moved to manage page).
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import BottomNav from '@/components/layout/BottomNav.vue'

// Track the mock return values so tests can override them
const mockIsAdmin = ref(false)
const mockIsManager = ref(false)

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    isAdmin: mockIsAdmin,
    isManager: mockIsManager,
  }),
}))

// Mock the API to prevent real HTTP calls from useMessageStore
vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockResolvedValue({ data: { unread_count: 0 } }),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

// Stub router-link to render as a plain anchor tag
const routerLinkStub = {
  template: '<a :href="to" class="router-link"><slot /></a>',
  props: ['to'],
}

describe('BottomNav', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  function mountNav() {
    return mount(BottomNav, {
      global: {
        stubs: {
          'router-link': routerLinkStub,
          Teleport: true,
        },
        mocks: {
          $route: { path: '/dashboard' },
        },
      },
    })
  }

  it('shows Manage link for managers', () => {
    mockIsManager.value = true
    mockIsAdmin.value = false

    const wrapper = mountNav()
    expect(wrapper.text()).toContain('Manage')
  })

  it('hides Manage link for regular staff', () => {
    mockIsManager.value = false
    mockIsAdmin.value = false

    const wrapper = mountNav()
    expect(wrapper.text()).not.toContain('Manage')
  })

  it('does not show Config link in bottom nav', () => {
    mockIsManager.value = true
    mockIsAdmin.value = true

    const wrapper = mountNav()
    expect(wrapper.text()).not.toContain('Config')
  })

  it('shows Manage link for admins', () => {
    mockIsManager.value = false
    mockIsAdmin.value = true

    const wrapper = mountNav()
    expect(wrapper.text()).toContain('Manage')
  })
})
