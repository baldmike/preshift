/**
 * ManagePushItems.test.ts
 *
 * Tests for the ManagePushItems admin view, which provides CRUD
 * functionality for push items (menu items that management wants
 * staff to actively promote or upsell).
 *
 * These tests verify that:
 *   1. The component renders without crashing after API calls resolve.
 *   2. The "Manage Push Items" page heading is displayed.
 *   3. The "Corner!" back link navigates to the daily management page.
 *   4. The "Add Push Item" create button is rendered.
 *   5. The empty state row is shown when there are no push items.
 *   6. Push items from the API are rendered in the table.
 *   7. The table displays correct column headers.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ManagePushItems from '@/views/admin/ManagePushItems.vue'

/* Mock the API module — routes based on URL */
const mockGet = vi.fn()
const mockPost = vi.fn()
const mockPatch = vi.fn()
const mockDelete = vi.fn()

vi.mock('@/composables/useApi', () => ({
  default: {
    get: (...args: unknown[]) => mockGet(...args),
    post: (...args: unknown[]) => mockPost(...args),
    patch: (...args: unknown[]) => mockPatch(...args),
    delete: (...args: unknown[]) => mockDelete(...args),
  },
}))

/* Mock useAuth — simulate an admin user */
vi.mock('@/composables/useAuth', () => ({
  useAuth: () => ({
    user: { value: { id: 1, role: 'admin', location_id: 1 } },
    isAdmin: { value: true },
    isManager: { value: false },
    locationId: { value: 1 },
  }),
}))

/* Stubs for child components */
const AppShellStub = { template: '<div><slot /></div>' }
const BaseButtonStub = {
  template: '<button :disabled="loading"><slot /></button>',
  props: ['size', 'variant', 'loading', 'type'],
}
const BaseInputStub = {
  template: '<input :value="modelValue" />',
  props: ['modelValue', 'label', 'placeholder', 'type'],
}
const BadgePillStub = {
  template: '<span class="badge-pill-stub">{{ label }}</span>',
  props: ['label', 'color'],
}

/**
 * Configures the mock API to return the provided push items array.
 */
function configureMockApi(items: unknown[] = []) {
  mockGet.mockImplementation((url: string) => {
    if (url === '/api/push-items') {
      return Promise.resolve({ data: items })
    }
    return Promise.resolve({ data: [] })
  })
}

/**
 * Mounts ManagePushItems with stubs and waits for onMounted API calls to resolve.
 */
async function mountView(items: unknown[] = []) {
  configureMockApi(items)

  const wrapper = mount(ManagePushItems, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        BaseButton: BaseButtonStub,
        BaseInput: BaseInputStub,
        BadgePill: BadgePillStub,
        'router-link': { template: '<a><slot /></a>' },
        Teleport: true,
      },
    },
  })

  await flushPromises()
  return wrapper
}

describe('ManagePushItems', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders without throwing errors
   * when the push items API returns an empty array.
   */
  it('renders without crashing', async () => {
    const wrapper = await mountView()
    expect(wrapper.exists()).toBe(true)
  })

  /**
   * Verifies the page heading "Manage Push Items" is displayed
   * so the user knows which management page they are on.
   */
  it('displays the "Manage Push Items" heading', async () => {
    const wrapper = await mountView()
    const heading = wrapper.find('h1')
    expect(heading.text()).toBe('Manage Push Items')
  })

  /**
   * Verifies the "Corner!" back link is rendered to navigate back
   * to the daily management dashboard.
   */
  it('shows the "Corner!" back link', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Corner!')
  })

  /**
   * Verifies the "Add Push Item" button is visible when the form
   * is not currently shown, enabling users to create a new push item.
   */
  it('shows the "Add Push Item" create button', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Add Push Item')
  })

  /**
   * Verifies the empty state message is displayed in the table when
   * the API returns no push items.
   */
  it('shows empty state when no push items exist', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('No push items')
  })

  /**
   * Verifies the table header columns are rendered correctly,
   * including Title, Reason, Priority, and Actions.
   */
  it('renders the table header columns', async () => {
    const wrapper = await mountView()
    const headers = wrapper.findAll('th')
    const headerTexts = headers.map(h => h.text())

    expect(headerTexts).toContain('Title')
    expect(headerTexts).toContain('Reason')
    expect(headerTexts).toContain('Priority')
    expect(headerTexts).toContain('Actions')
  })

  /**
   * Verifies that push items returned from the API are rendered
   * in the table with their title visible.
   */
  it('renders push item data in the table', async () => {
    const items = [
      {
        id: 1,
        location_id: 1,
        menu_item_id: null,
        title: 'House Cabernet',
        description: 'Great margin wine',
        reason: 'Overstock',
        priority: 'high',
        is_active: true,
        created_by: 1,
        created_at: '2026-02-20T10:00:00Z',
        updated_at: '2026-02-20T10:00:00Z',
      },
      {
        id: 2,
        location_id: 1,
        menu_item_id: null,
        title: 'Truffle Fries',
        description: 'Upsell to every table',
        reason: null,
        priority: 'medium',
        is_active: true,
        created_by: 1,
        created_at: '2026-02-20T10:00:00Z',
        updated_at: '2026-02-20T10:00:00Z',
      },
    ]
    const wrapper = await mountView(items)
    const text = wrapper.text()

    expect(text).toContain('House Cabernet')
    expect(text).toContain('Truffle Fries')
    expect(text).toContain('Overstock')
  })
})
