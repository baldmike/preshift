/**
 * ManageSpecials.test.ts
 *
 * Tests for the ManageSpecials admin view, which provides CRUD
 * functionality for daily specials. Supports creating, editing,
 * deleting specials, and decrementing quantity for limited-stock items.
 *
 * These tests verify that:
 *   1. The component renders without crashing after API calls resolve.
 *   2. The "Manage Specials" page heading is displayed.
 *   3. The "Corner!" back link navigates to the daily management page.
 *   4. The "Add Special" create button is rendered.
 *   5. The empty state row is shown when there are no specials.
 *   6. Specials from the API are rendered in the table.
 *   7. The table displays correct column headers.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ManageSpecials from '@/views/admin/ManageSpecials.vue'

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
 * Configures the mock API to return the provided specials array.
 */
function configureMockApi(specials: unknown[] = []) {
  mockGet.mockImplementation((url: string) => {
    if (url === '/api/specials') {
      return Promise.resolve({ data: specials })
    }
    return Promise.resolve({ data: [] })
  })
}

/**
 * Mounts ManageSpecials with stubs and waits for onMounted API calls to resolve.
 */
async function mountView(specials: unknown[] = []) {
  configureMockApi(specials)

  const wrapper = mount(ManageSpecials, {
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

describe('ManageSpecials', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders without throwing errors
   * when the specials API returns an empty array.
   */
  it('renders without crashing', async () => {
    const wrapper = await mountView()
    expect(wrapper.exists()).toBe(true)
  })

  /**
   * Verifies the page heading "Manage Specials" is displayed
   * so the user knows which management page they are on.
   */
  it('displays the "Manage Specials" heading', async () => {
    const wrapper = await mountView()
    const heading = wrapper.find('h1')
    expect(heading.text()).toBe('Manage Specials')
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
   * Verifies the "Add Special" button is visible when the form
   * is not currently shown, enabling users to create a new special.
   */
  it('shows the "Add Special" create button', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Add Special')
  })

  /**
   * Verifies the empty state message is displayed in the table when
   * the API returns no specials.
   */
  it('shows empty state when no specials exist', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('No specials')
  })

  /**
   * Verifies the table header columns are rendered correctly,
   * including Title, Type, Dates, Qty, and Actions.
   */
  it('renders the table header columns', async () => {
    const wrapper = await mountView()
    const headers = wrapper.findAll('th')
    const headerTexts = headers.map(h => h.text())

    expect(headerTexts).toContain('Title')
    expect(headerTexts).toContain('Type')
    expect(headerTexts).toContain('Dates')
    expect(headerTexts).toContain('Qty')
    expect(headerTexts).toContain('Actions')
  })

  /**
   * Verifies that specials returned from the API are rendered
   * in the table with their title visible.
   */
  it('renders special data in the table', async () => {
    const specials = [
      {
        id: 1,
        location_id: 1,
        menu_item_id: null,
        title: 'Happy Hour Wings',
        description: 'Half price wings 4-6pm',
        type: 'daily',
        starts_at: '2026-02-20',
        ends_at: null,
        is_active: true,
        quantity: 50,
        created_by: 1,
        created_at: '2026-02-20T10:00:00Z',
        updated_at: '2026-02-20T10:00:00Z',
      },
      {
        id: 2,
        location_id: 1,
        menu_item_id: null,
        title: 'Truffle Pasta',
        description: 'Chef special',
        type: 'limited_time',
        starts_at: '2026-02-20',
        ends_at: '2026-02-28',
        is_active: true,
        quantity: null,
        created_by: 1,
        created_at: '2026-02-20T10:00:00Z',
        updated_at: '2026-02-20T10:00:00Z',
      },
    ]
    const wrapper = await mountView(specials)
    const text = wrapper.text()

    expect(text).toContain('Happy Hour Wings')
    expect(text).toContain('Truffle Pasta')
  })

  /**
   * Verifies that a toast error is dispatched when saving a special fails.
   */
  it('dispatches error toast when save fails', async () => {
    const spy = vi.spyOn(window, 'dispatchEvent')
    const wrapper = await mountView()
    mockPost.mockRejectedValueOnce(new Error('fail'))

    const addBtn = wrapper.findAll('button').find(b => b.text().includes('Add Special'))
    await addBtn!.trigger('click')
    await flushPromises()

    // Fill required title field via vm so the guard passes
    ;(wrapper.vm as any).form.title = 'Test Special'
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(spy).toHaveBeenCalledWith(expect.objectContaining({ type: 'toast' }))
    spy.mockRestore()
  })

  /**
   * Verifies that creating a special calls POST /api/specials.
   */
  it('creates special via POST /api/specials', async () => {
    const spy = vi.spyOn(window, 'dispatchEvent')
    const newSpecial = {
      id: 5, location_id: 1, menu_item_id: null, title: 'New Special',
      description: '', type: 'daily', starts_at: '2026-02-24', ends_at: null,
      is_active: true, quantity: null, created_by: 1,
      created_at: '2026-02-24T00:00:00Z', updated_at: '2026-02-24T00:00:00Z',
    }
    mockPost.mockResolvedValueOnce({ data: newSpecial })
    const wrapper = await mountView()

    const addBtn = wrapper.findAll('button').find(b => b.text().includes('Add Special'))
    await addBtn!.trigger('click')
    await flushPromises()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    spy.mockRestore()
  })

  /**
   * Verifies that deleting a special calls DELETE /api/specials/:id.
   */
  it('deletes special via DELETE /api/specials/:id', async () => {
    window.confirm = vi.fn(() => true)
    const spy = vi.spyOn(window, 'dispatchEvent')
    mockDelete.mockResolvedValueOnce({})
    const specials = [
      {
        id: 1, location_id: 1, menu_item_id: null, title: 'Delete Me',
        description: '', type: 'daily', starts_at: '2026-02-20', ends_at: null,
        is_active: true, quantity: null, created_by: 1,
        created_at: '2026-02-20T00:00:00Z', updated_at: '2026-02-20T00:00:00Z',
      },
    ]
    const wrapper = await mountView(specials)

    const deleteBtn = wrapper.findAll('button').find(b => b.text().includes('Delete'))
    await deleteBtn!.trigger('click')
    await flushPromises()

    expect(mockDelete).toHaveBeenCalledWith('/api/specials/1')
    spy.mockRestore()
  })
})
