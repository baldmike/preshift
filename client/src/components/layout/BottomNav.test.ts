/**
 * BottomNav.test.ts
 *
 * Unit tests for the BottomNav.vue component.
 *
 * Tests verify:
 *   1. The Config link is visible when the user is a SuperAdmin.
 *   2. The Config link is hidden when the user is not a SuperAdmin.
 */

import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import BottomNav from '@/components/layout/BottomNav.vue'

// Track the mock return values so tests can override them
const mockIsAdmin = ref(false)
const mockIsManager = ref(false)
const mockIsSuperAdmin = ref(false)

vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    isAdmin: mockIsAdmin,
    isManager: mockIsManager,
    isSuperAdmin: mockIsSuperAdmin,
  }),
}))

// Stub router-link to render as a plain anchor tag
const routerLinkStub = {
  template: '<a :href="to" class="router-link"><slot /></a>',
  props: ['to'],
}

describe('BottomNav', () => {
  function mountNav() {
    return mount(BottomNav, {
      global: {
        stubs: {
          'router-link': routerLinkStub,
        },
        mocks: {
          $route: { path: '/dashboard' },
        },
      },
    })
  }

  it('shows Config link when user is superadmin', () => {
    mockIsSuperAdmin.value = true
    mockIsManager.value = true
    mockIsAdmin.value = false

    const wrapper = mountNav()
    expect(wrapper.text()).toContain('Config')
  })

  it('hides Config link when user is not superadmin', () => {
    mockIsSuperAdmin.value = false
    mockIsManager.value = true
    mockIsAdmin.value = false

    const wrapper = mountNav()
    expect(wrapper.text()).not.toContain('Config')
  })

  it('shows Manage link for managers', () => {
    mockIsSuperAdmin.value = false
    mockIsManager.value = true
    mockIsAdmin.value = false

    const wrapper = mountNav()
    expect(wrapper.text()).toContain('Manage')
  })

  it('hides Manage link for regular staff', () => {
    mockIsSuperAdmin.value = false
    mockIsManager.value = false
    mockIsAdmin.value = false

    const wrapper = mountNav()
    expect(wrapper.text()).not.toContain('Manage')
  })
})
