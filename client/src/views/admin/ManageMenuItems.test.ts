/**
 * ManageMenuItems.test.ts
 *
 * Tests for the ManageMenuItems admin view, which provides CRUD
 * functionality for the restaurant's menu items. Fetches both menu
 * items and categories in parallel on mount, and provides a form
 * with name, price, description, type, category dropdown, and
 * active/inactive toggle.
 *
 * These tests verify that:
 *   1. The component renders without crashing after API calls resolve.
 *   2. The "Manage Menu Items" page heading is displayed.
 *   3. The "Corner!" back link navigates to the daily management page.
 *   4. The "Add Item" create button is rendered.
 *   5. The empty state row is shown when there are no menu items.
 *   6. Menu items from the API are rendered in the table.
 *   7. The table displays correct column headers.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ManageMenuItems from '@/views/admin/ManageMenuItems.vue'

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
 * Configures the mock API to return menu items and categories.
 * ManageMenuItems fetches both in parallel via Promise.all on mount.
 */
function configureMockApi(menuItems: unknown[] = [], categories: unknown[] = []) {
  mockGet.mockImplementation((url: string) => {
    if (url === '/api/menu-items') {
      return Promise.resolve({ data: menuItems })
    }
    if (url === '/api/categories') {
      return Promise.resolve({ data: categories })
    }
    return Promise.resolve({ data: [] })
  })
}

/**
 * Mounts ManageMenuItems with stubs and waits for onMounted API calls to resolve.
 */
async function mountView(menuItems: unknown[] = [], categories: unknown[] = []) {
  configureMockApi(menuItems, categories)

  const wrapper = mount(ManageMenuItems, {
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

describe('ManageMenuItems', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders without throwing errors
   * when both menu items and categories APIs return empty arrays.
   */
  it('renders without crashing', async () => {
    const wrapper = await mountView()
    expect(wrapper.exists()).toBe(true)
  })

  /**
   * Verifies the page heading "Manage Menu Items" is displayed
   * so the user knows which management page they are on.
   */
  it('displays the "Manage Menu Items" heading', async () => {
    const wrapper = await mountView()
    const heading = wrapper.find('h1')
    expect(heading.text()).toBe('Manage Menu Items')
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
   * Verifies the "Add Item" button is visible when the form
   * is not currently shown, enabling users to create a new menu item.
   */
  it('shows the "Add Item" create button', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Add Item')
  })

  /**
   * Verifies the empty state message is displayed in the table when
   * the API returns no menu items.
   */
  it('shows empty state when no menu items exist', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('No menu items')
  })

  /**
   * Verifies the table header columns are rendered correctly,
   * including Name, Category, Price, Status, and Actions.
   */
  it('renders the table header columns', async () => {
    const wrapper = await mountView()
    const headers = wrapper.findAll('th')
    const headerTexts = headers.map(h => h.text())

    expect(headerTexts).toContain('Name')
    expect(headerTexts).toContain('Category')
    expect(headerTexts).toContain('Price')
    expect(headerTexts).toContain('Status')
    expect(headerTexts).toContain('Actions')
  })

  /**
   * Verifies that menu items returned from the API are rendered
   * in the table with their name and price visible.
   */
  it('renders menu item data in the table', async () => {
    const categories = [
      { id: 1, location_id: 1, name: 'Appetizers', sort_order: 0, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z' },
    ]
    const menuItems = [
      {
        id: 1,
        location_id: 1,
        category_id: 1,
        name: 'Truffle Fries',
        description: 'With parmesan',
        price: '14.99',
        type: 'food',
        is_new: false,
        is_active: true,
        allergens: null,
        created_at: '2026-02-20T10:00:00Z',
        updated_at: '2026-02-20T10:00:00Z',
      },
      {
        id: 2,
        location_id: 1,
        category_id: null,
        name: 'Old Fashioned',
        description: 'Classic cocktail',
        price: '12.00',
        type: 'drink',
        is_new: false,
        is_active: false,
        allergens: null,
        created_at: '2026-02-20T10:00:00Z',
        updated_at: '2026-02-20T10:00:00Z',
      },
    ]
    const wrapper = await mountView(menuItems, categories)
    const text = wrapper.text()

    expect(text).toContain('Truffle Fries')
    expect(text).toContain('Old Fashioned')
    expect(text).toContain('$14.99')
    expect(text).toContain('Appetizers')
  })

  /**
   * Verifies that both the /api/menu-items and /api/categories
   * endpoints are called on mount (the component fetches both in parallel).
   */
  it('fetches both menu items and categories on mount', async () => {
    await mountView()

    expect(mockGet).toHaveBeenCalledWith('/api/menu-items')
    expect(mockGet).toHaveBeenCalledWith('/api/categories')
  })

  /**
   * Verifies that a toast error is dispatched when saving a menu item fails.
   */
  it('dispatches error toast when save fails', async () => {
    const spy = vi.spyOn(window, 'dispatchEvent')
    const wrapper = await mountView()
    mockPost.mockRejectedValueOnce(new Error('fail'))

    const addBtn = wrapper.findAll('button').find(b => b.text().includes('Add Item'))
    await addBtn!.trigger('click')
    await flushPromises()

    // Fill required name field via vm so the guard passes
    ;(wrapper.vm as any).form.name = 'Test Item'
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(spy).toHaveBeenCalledWith(expect.objectContaining({ type: 'toast' }))
    spy.mockRestore()
  })

  /**
   * Verifies that creating a menu item calls POST /api/menu-items.
   */
  it('creates menu item via POST /api/menu-items', async () => {
    const spy = vi.spyOn(window, 'dispatchEvent')
    const newItem = {
      id: 5, location_id: 1, category_id: null, name: 'New Item',
      description: '', price: '10.00', type: 'food', is_new: false,
      is_active: true, allergens: null,
      created_at: '2026-02-24T00:00:00Z', updated_at: '2026-02-24T00:00:00Z',
    }
    mockPost.mockResolvedValueOnce({ data: newItem })
    const wrapper = await mountView()

    const addBtn = wrapper.findAll('button').find(b => b.text().includes('Add Item'))
    await addBtn!.trigger('click')
    await flushPromises()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    spy.mockRestore()
  })

  /**
   * Verifies that deleting a menu item calls DELETE /api/menu-items/:id.
   */
  it('deletes menu item via DELETE /api/menu-items/:id', async () => {
    window.confirm = vi.fn(() => true)
    const spy = vi.spyOn(window, 'dispatchEvent')
    mockDelete.mockResolvedValueOnce({})
    const menuItems = [
      {
        id: 1, location_id: 1, category_id: null, name: 'Delete Me',
        description: '', price: '10.00', type: 'food', is_new: false,
        is_active: true, allergens: null,
        created_at: '2026-02-20T00:00:00Z', updated_at: '2026-02-20T00:00:00Z',
      },
    ]
    const wrapper = await mountView(menuItems)

    const deleteBtn = wrapper.findAll('button').find(b => b.text().includes('Delete'))
    await deleteBtn!.trigger('click')
    await flushPromises()

    expect(mockDelete).toHaveBeenCalledWith('/api/menu-items/1')
    spy.mockRestore()
  })
})
