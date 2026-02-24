/**
 * ManageEightySixed.test.ts
 *
 * Tests for the ManageEightySixed admin view, which provides CRUD
 * functionality for 86'd items (items the kitchen has run out of).
 * Supports creating, editing, and restoring 86'd items via a toggleable
 * form and a table listing.
 *
 * These tests verify that:
 *   1. The component renders without crashing after API calls resolve.
 *   2. The "Manage 86'd Items" page heading is displayed.
 *   3. The "Corner!" back link navigates to the daily management page.
 *   4. The "86 an Item" create button is rendered.
 *   5. The empty state row is shown when there are no 86'd items.
 *   6. 86'd items from the API are rendered in the table.
 *   7. The table displays correct column headers.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ManageEightySixed from '@/views/admin/ManageEightySixed.vue'

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

/**
 * Configures the mock API to return the provided 86'd items array.
 */
function configureMockApi(items: unknown[] = []) {
  mockGet.mockImplementation((url: string) => {
    if (url === '/api/eighty-sixed') {
      return Promise.resolve({ data: items })
    }
    return Promise.resolve({ data: [] })
  })
}

/**
 * Mounts ManageEightySixed with stubs and waits for onMounted API calls to resolve.
 */
async function mountView(items: unknown[] = []) {
  configureMockApi(items)

  const wrapper = mount(ManageEightySixed, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        BaseButton: BaseButtonStub,
        BaseInput: BaseInputStub,
        'router-link': { template: '<a><slot /></a>' },
        Teleport: true,
      },
    },
  })

  await flushPromises()
  return wrapper
}

describe('ManageEightySixed', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders without throwing errors
   * when the 86'd items API returns an empty array.
   */
  it('renders without crashing', async () => {
    const wrapper = await mountView()
    expect(wrapper.exists()).toBe(true)
  })

  /**
   * Verifies the page heading "Manage 86'd Items" is displayed
   * so the user knows which management page they are on.
   */
  it('displays the "Manage 86\'d Items" heading', async () => {
    const wrapper = await mountView()
    const heading = wrapper.find('h1')
    expect(heading.text()).toBe("Manage 86'd Items")
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
   * Verifies the "86 an Item" button is visible when the form
   * is not currently shown, enabling users to 86 a new item.
   */
  it('shows the "86 an Item" create button', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('86 an Item')
  })

  /**
   * Verifies the empty state message is displayed in the table when
   * the API returns no 86'd items.
   */
  it('shows empty state when no items are 86\'d', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain("No items currently 86'd")
  })

  /**
   * Verifies the table header columns are rendered correctly,
   * including Item, Reason, 86'd By, When, and Actions.
   */
  it('renders the table header columns', async () => {
    const wrapper = await mountView()
    const headers = wrapper.findAll('th')
    const headerTexts = headers.map(h => h.text())

    expect(headerTexts).toContain('Item')
    expect(headerTexts).toContain('Reason')
    expect(headerTexts).toContain("86'd By")
    expect(headerTexts).toContain('When')
    expect(headerTexts).toContain('Actions')
  })

  /**
   * Verifies that 86'd items returned from the API are rendered
   * in the table with their item_name visible.
   */
  it('renders 86\'d item data in the table', async () => {
    const items = [
      {
        id: 1,
        location_id: 1,
        menu_item_id: null,
        item_name: 'Salmon',
        reason: 'Ran out',
        eighty_sixed_by: 1,
        restored_at: null,
        user: { id: 1, name: 'Chef Mike' },
        created_at: '2026-02-20T14:00:00Z',
        updated_at: '2026-02-20T14:00:00Z',
      },
      {
        id: 2,
        location_id: 1,
        menu_item_id: null,
        item_name: 'Lobster Bisque',
        reason: null,
        eighty_sixed_by: 1,
        restored_at: null,
        user: { id: 1, name: 'Chef Mike' },
        created_at: '2026-02-20T15:00:00Z',
        updated_at: '2026-02-20T15:00:00Z',
      },
    ]
    const wrapper = await mountView(items)
    const text = wrapper.text()

    expect(text).toContain('Salmon')
    expect(text).toContain('Lobster Bisque')
    expect(text).toContain('Ran out')
  })
})
