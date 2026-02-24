/**
 * SetupView.test.ts
 *
 * Tests for the SetupView component, the initial setup flow for new admin
 * users who need to create their first establishment.
 *
 * Verifies:
 *   1. The form renders inputs for name, city, and state plus a submit button.
 *   2. Input fields bind correctly via v-model.
 *   3. Successful form submission posts to /api/setup, updates the auth store,
 *      and redirects to /manage/daily.
 *   4. Failed submission displays the error banner with the server message.
 *   5. The submit button is disabled and shows a spinner while loading.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import SetupView from '@/views/auth/SetupView.vue'

// ── Hoisted mock references (available inside vi.mock factories) ───────────

const { pushMock, postMock } = vi.hoisted(() => ({
  pushMock: vi.fn(),
  postMock: vi.fn(),
}))

// ── Track router push calls ────────────────────────────────────────────────

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: vi.fn() }),
}))

// ── Mock useApi ────────────────────────────────────────────────────────────

vi.mock('@/composables/useApi', () => ({
  default: {
    get: vi.fn(),
    post: postMock,
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

// ── Mock the auth store ────────────────────────────────────────────────────

let mockUser: any = null
let mockLocations: any[] = []

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    get user() { return mockUser },
    set user(v: any) { mockUser = v },
    get locations() { return mockLocations },
    set locations(v: any[]) { mockLocations = v },
  }),
}))

// ── Tests ──────────────────────────────────────────────────────────────────

describe('SetupView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    pushMock.mockClear()
    postMock.mockClear()
    mockUser = { id: 1, name: 'Admin', role: 'admin', location_id: null }
    mockLocations = []
  })

  /**
   * The setup form must render three text inputs (establishment name, city,
   * state) and a Create Establishment submit button.
   */
  it('renders name, city, state inputs and a submit button', () => {
    const wrapper = mount(SetupView)

    const inputs = wrapper.findAll('input')
    expect(inputs.length).toBe(3)
    expect(inputs[0].attributes('placeholder')).toContain('Downtown Taproom')
    expect(inputs[1].attributes('placeholder')).toContain('Chicago')
    expect(inputs[2].attributes('placeholder')).toContain('IL')

    const button = wrapper.find('button[type="submit"]')
    expect(button.exists()).toBe(true)
    expect(button.text()).toContain('Create Establishment')
  })

  /**
   * Typing into the name, city, and state fields should update the reactive
   * v-model bindings so handleSetup sends the correct data.
   */
  it('binds name, city, and state inputs via v-model', async () => {
    const wrapper = mount(SetupView)

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Test Bar')
    await inputs[1].setValue('Austin')
    await inputs[2].setValue('TX')

    expect((inputs[0].element as HTMLInputElement).value).toBe('Test Bar')
    expect((inputs[1].element as HTMLInputElement).value).toBe('Austin')
    expect((inputs[2].element as HTMLInputElement).value).toBe('TX')
  })

  /**
   * On successful form submission, the component should POST to /api/setup
   * with the form data, update authStore.user and authStore.locations from
   * the response, and redirect to /manage/daily.
   */
  it('posts to /api/setup and redirects to /manage/daily on success', async () => {
    const responseUser = { id: 1, name: 'Admin', role: 'admin', location_id: 10 }
    const responseLocations = [{ id: 10, name: 'Test Bar', role: 'admin' }]

    postMock.mockResolvedValue({
      data: { user: responseUser, locations: responseLocations },
    })

    const wrapper = mount(SetupView)

    const inputs = wrapper.findAll('input')
    await inputs[0].setValue('Test Bar')
    await inputs[1].setValue('Austin')
    await inputs[2].setValue('TX')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(postMock).toHaveBeenCalledWith('/api/setup', {
      name: 'Test Bar',
      city: 'Austin',
      state: 'TX',
    })
    expect(mockUser).toEqual(responseUser)
    expect(mockLocations).toEqual(responseLocations)
    expect(pushMock).toHaveBeenCalledWith('/manage/daily')
  })

  /**
   * When the setup API call fails, the error banner should appear with the
   * server-provided message (or a fallback if none is returned).
   */
  it('displays error banner when setup fails', async () => {
    postMock.mockRejectedValue({
      response: { data: { message: 'Establishment name is required.' } },
    })

    const wrapper = mount(SetupView)

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Establishment name is required.')
    expect(pushMock).not.toHaveBeenCalled()
  })

  /**
   * Displays the default fallback error message when the server response
   * does not include a specific message.
   */
  it('displays default error message when server provides no message', async () => {
    postMock.mockRejectedValue({ response: {} })

    const wrapper = mount(SetupView)

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Failed to create establishment.')
  })

  /**
   * While the setup request is in flight, the submit button should be
   * disabled and show the "Creating..." loading text.
   */
  it('disables submit button and shows spinner while loading', async () => {
    let resolvePost: (v: any) => void
    postMock.mockImplementation(() => new Promise((resolve) => {
      resolvePost = resolve
    }))

    const wrapper = mount(SetupView)

    await wrapper.find('form').trigger('submit')
    await wrapper.vm.$nextTick()

    const button = wrapper.find('button[type="submit"]')
    expect(button.attributes('disabled')).toBeDefined()
    expect(wrapper.text()).toContain('Creating...')

    resolvePost!({ data: { user: mockUser, locations: [] } })
    await flushPromises()

    expect(button.attributes('disabled')).toBeUndefined()
  })
})
