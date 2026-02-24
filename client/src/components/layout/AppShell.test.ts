/**
 * AppShell.test.ts
 *
 * Unit tests for the AppShell.vue component.
 *
 * AppShell is the top-level layout wrapper used by every authenticated page.
 * It provides consistent structure: TopBar, scrollable main content area,
 * BottomNav, ToastContainer, and AppTour. It initializes the Echo/Reverb
 * WebSocket connection on mount and watches the user's active location.
 *
 * Tests verify:
 *   1. The component renders the default slot content in the main area.
 *   2. TopBar child component is rendered.
 *   3. BottomNav child component is rendered.
 *   4. ToastContainer child component is rendered.
 *   5. AppTour child component is rendered.
 *   6. The root container has the expected layout classes.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import AppShell from '@/components/layout/AppShell.vue'

// ── Mock for the useAuth composable ─────────────────────────────────────────
// locationId uses a real ref because AppShell watches it for location changes
const mockLocationId = ref(1)
vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: { value: { id: 1, name: 'Test User', location_id: 1 } },
    isLoggedIn: { value: true },
    isAdmin: { value: false },
    isManager: { value: false },
    isStaff: { value: true },
    isSuperAdmin: { value: false },
    locationId: mockLocationId,
    locations: { value: [] },
    hasMultipleLocations: { value: false },
  }),
}))

// ── Mock for the useReverb composable ───────────────────────────────────────
vi.mock('@/composables/useReverb', () => ({
  useReverb: vi.fn(),
  disconnectReverb: vi.fn(),
}))

// ── Mock for the useOnboarding composable ───────────────────────────────────
vi.mock('@/composables/useOnboarding', () => ({
  useOnboarding: () => ({
    isActive: { value: false },
    currentStep: { value: null },
    stepIndex: { value: 0 },
    totalSteps: { value: 0 },
    nextStep: vi.fn(),
    skipTour: vi.fn(),
    checkAndStart: vi.fn(),
  }),
}))

// ── Mock for the useApi composable ──────────────────────────────────────────
vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn().mockResolvedValue({ data: {} }),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

// ── Stubs for child components ──────────────────────────────────────────────
const TopBarStub = { template: '<header class="topbar-stub">TopBar</header>' }
const BottomNavStub = { template: '<nav class="bottomnav-stub">BottomNav</nav>' }
const ToastContainerStub = { template: '<div class="toast-stub">ToastContainer</div>' }
const AppTourStub = { template: '<div class="apptour-stub">AppTour</div>' }
const RouterLinkStub = {
  template: '<a class="router-link"><slot /></a>',
  props: ['to'],
}

describe('AppShell.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  function mountShell(slotContent = 'Page content') {
    return mount(AppShell, {
      slots: { default: slotContent },
      global: {
        stubs: {
          TopBar: TopBarStub,
          BottomNav: BottomNavStub,
          ToastContainer: ToastContainerStub,
          AppTour: AppTourStub,
          'router-link': RouterLinkStub,
          Teleport: true,
        },
      },
    })
  }

  /**
   * Test 1 — Renders slot content in the main area
   *
   * The default slot content should appear inside the <main> element,
   * serving as the page body for each routed view.
   */
  it('renders default slot content in the main area', () => {
    const wrapper = mountShell('Dashboard page content')

    const main = wrapper.find('main')
    expect(main.exists()).toBe(true)
    expect(main.text()).toContain('Dashboard page content')
  })

  /**
   * Test 2 — TopBar is rendered
   *
   * The TopBar child component should always be present in the layout
   * so users see the header with establishment name and user avatar.
   */
  it('renders the TopBar component', () => {
    const wrapper = mountShell()

    expect(wrapper.find('.topbar-stub').exists()).toBe(true)
  })

  /**
   * Test 3 — BottomNav is rendered
   *
   * The BottomNav child component should always be present to provide
   * the fixed bottom navigation bar.
   */
  it('renders the BottomNav component', () => {
    const wrapper = mountShell()

    expect(wrapper.find('.bottomnav-stub').exists()).toBe(true)
  })

  /**
   * Test 4 — ToastContainer is rendered
   *
   * The ToastContainer should be present in the layout so global
   * toast notifications can be displayed from any page.
   */
  it('renders the ToastContainer component', () => {
    const wrapper = mountShell()

    expect(wrapper.find('.toast-stub').exists()).toBe(true)
  })

  /**
   * Test 5 — AppTour is rendered
   *
   * The AppTour component should be present in the layout to support
   * the onboarding walkthrough overlay.
   */
  it('renders the AppTour component', () => {
    const wrapper = mountShell()

    expect(wrapper.find('.apptour-stub').exists()).toBe(true)
  })

  /**
   * Test 6 — Root container has layout classes
   *
   * The root div should have the dark background, full-height flex
   * layout classes that define the app's overall structure.
   */
  it('applies expected layout classes to the root container', () => {
    const wrapper = mountShell()

    const root = wrapper.find('div')
    expect(root.classes()).toContain('min-h-screen')
    expect(root.classes()).toContain('bg-gray-950')
    expect(root.classes()).toContain('flex')
    expect(root.classes()).toContain('flex-col')
  })
})
