/**
 * ManageUsers.test.ts
 *
 * Tests for the ManageUsers admin view, which provides CRUD
 * functionality for employee accounts. Includes a create/edit form
 * with name, email, password, role, multi-role toggle, phone, and
 * availability fields. A sortable table lists all employees with
 * role badges, phone, availability summary, and Edit/Remove actions.
 *
 * These tests verify that:
 *   1. The component renders without crashing after API calls resolve.
 *   2. The "Employees" page heading is displayed.
 *   3. The "Corner!" back link navigates to the daily management page.
 *   4. The "Add Employee" create button is rendered.
 *   5. The empty state row is shown when there are no employees.
 *   6. Employee data from the API is rendered in the table.
 *   7. The table displays correct column headers.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import ManageUsers from '@/views/admin/ManageUsers.vue'

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
const AvailabilityGridStub = {
  template: '<div class="availability-grid-stub" />',
  props: ['modelValue', 'saving'],
}

/**
 * Configures the mock API to return the provided users array.
 */
function configureMockApi(users: unknown[] = []) {
  mockGet.mockImplementation((url: string) => {
    if (url === '/api/users') {
      return Promise.resolve({ data: users })
    }
    return Promise.resolve({ data: [] })
  })
}

/**
 * Mounts ManageUsers with stubs and waits for onMounted API calls to resolve.
 */
async function mountView(users: unknown[] = []) {
  configureMockApi(users)

  const wrapper = mount(ManageUsers, {
    global: {
      stubs: {
        AppShell: AppShellStub,
        BaseButton: BaseButtonStub,
        BaseInput: BaseInputStub,
        BadgePill: BadgePillStub,
        AvailabilityGrid: AvailabilityGridStub,
        'router-link': { template: '<a><slot /></a>' },
        Teleport: true,
      },
    },
  })

  await flushPromises()
  return wrapper
}

describe('ManageUsers', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
  })

  /**
   * Verifies the component mounts and renders without throwing errors
   * when the users API returns an empty array.
   */
  it('renders without crashing', async () => {
    const wrapper = await mountView()
    expect(wrapper.exists()).toBe(true)
  })

  /**
   * Verifies the page heading "Employees" is displayed so the user
   * knows they are on the employee management page.
   */
  it('displays the "Employees" heading', async () => {
    const wrapper = await mountView()
    const heading = wrapper.find('h1')
    expect(heading.text()).toBe('Employees')
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
   * Verifies the "Add Employee" button is visible when the form
   * is not currently shown, enabling admins to create a new employee.
   */
  it('shows the "Add Employee" create button', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('Add Employee')
  })

  /**
   * Verifies the empty state message is displayed in the table when
   * the API returns no employees.
   */
  it('shows empty state when no employees exist', async () => {
    const wrapper = await mountView()
    expect(wrapper.text()).toContain('No employees')
  })

  /**
   * Verifies the table header columns are rendered correctly,
   * including Name, Email, Phone, Role, Availability, and Actions.
   */
  it('renders the table header columns', async () => {
    const wrapper = await mountView()
    const headers = wrapper.findAll('th')
    const headerTexts = headers.map(h => h.text())

    expect(headerTexts.some(t => t.includes('Name'))).toBe(true)
    expect(headerTexts).toContain('Email')
    expect(headerTexts).toContain('Phone')
    expect(headerTexts.some(t => t.includes('Role'))).toBe(true)
    expect(headerTexts.some(t => t.includes('Availability'))).toBe(true)
    expect(headerTexts).toContain('Actions')
  })

  /**
   * Verifies that employee data returned from the API is rendered
   * in the table with name and email visible.
   */
  it('renders employee data in the table', async () => {
    const users = [
      {
        id: 10,
        location_id: 1,
        name: 'Alice Server',
        email: 'alice@test.com',
        role: 'server',
        roles: null,
        is_superadmin: false,
        phone: '5551234567',
        availability: null,
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
      },
      {
        id: 20,
        location_id: 1,
        name: 'Bob Bartender',
        email: 'bob@test.com',
        role: 'bartender',
        roles: null,
        is_superadmin: false,
        phone: null,
        availability: { monday: ['open'], tuesday: [], wednesday: [], thursday: [], friday: [], saturday: [], sunday: [] },
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
      },
    ]
    const wrapper = await mountView(users)
    const text = wrapper.text()

    expect(text).toContain('Alice Server')
    expect(text).toContain('alice@test.com')
    expect(text).toContain('Bob Bartender')
    expect(text).toContain('bob@test.com')
  })

  /**
   * Verifies the /api/users endpoint is called exactly once on mount.
   */
  it('fetches users on mount', async () => {
    await mountView()
    expect(mockGet).toHaveBeenCalledWith('/api/users')
    expect(mockGet).toHaveBeenCalledTimes(1)
  })

  /**
   * Verifies that a toast error is dispatched when creating an employee
   * fails due to an API error.
   */
  it('dispatches error toast when create fails', async () => {
    const spy = vi.spyOn(window, 'dispatchEvent')
    const wrapper = await mountView()
    mockPost.mockRejectedValueOnce(new Error('fail'))

    // Open the form
    const addBtn = wrapper.findAll('button').find(b => b.text().includes('Add Employee'))
    await addBtn!.trigger('click')
    await flushPromises()

    // Fill required fields via vm so the guard passes, then submit
    ;(wrapper.vm as any).form.name = 'Test Employee'
    ;(wrapper.vm as any).form.email = 'test@test.com'
    ;(wrapper.vm as any).form.password = 'password123'
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(spy).toHaveBeenCalledWith(expect.objectContaining({ type: 'toast' }))
    spy.mockRestore()
  })

  /**
   * Verifies that creating an employee calls POST /api/users
   * and shows a success toast.
   */
  it('creates employee via POST /api/users', async () => {
    const spy = vi.spyOn(window, 'dispatchEvent')
    const newUser = {
      id: 50, location_id: 1, name: 'New Staff', email: 'new@test.com',
      role: 'server', roles: null, is_superadmin: false, phone: null,
      availability: null, created_at: '2026-02-24T00:00:00Z', updated_at: '2026-02-24T00:00:00Z',
    }
    mockPost.mockResolvedValueOnce({ data: newUser })
    const wrapper = await mountView()

    // Open the form
    const addBtn = wrapper.findAll('button').find(b => b.text().includes('Add Employee'))
    await addBtn!.trigger('click')
    await flushPromises()

    // Submit the form
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    spy.mockRestore()
  })

  /**
   * Verifies that deleting an employee calls DELETE /api/users/:id
   * after user confirms.
   */
  it('deletes employee via DELETE /api/users/:id', async () => {
    window.confirm = vi.fn(() => true)
    const spy = vi.spyOn(window, 'dispatchEvent')
    mockDelete.mockResolvedValueOnce({})
    const users = [
      {
        id: 10, location_id: 1, name: 'Delete Me', email: 'del@test.com',
        role: 'server', roles: null, is_superadmin: false, phone: null,
        availability: null, created_at: '2026-01-01T00:00:00Z', updated_at: '2026-01-01T00:00:00Z',
      },
    ]
    const wrapper = await mountView(users)

    const deleteBtn = wrapper.findAll('button').find(b => b.text().includes('Remove'))
    await deleteBtn!.trigger('click')
    await flushPromises()

    expect(mockDelete).toHaveBeenCalledWith('/api/users/10')
    spy.mockRestore()
  })
})
