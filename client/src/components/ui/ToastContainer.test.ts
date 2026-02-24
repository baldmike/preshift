/**
 * ToastContainer.test.ts
 *
 * Unit tests for the ToastContainer.vue component.
 *
 * ToastContainer is a global notification system rendered once inside
 * AppShell. It listens for custom 'toast' events dispatched on `window`
 * and displays auto-dismissing toast notifications. Toasts can also be
 * dismissed by clicking them.
 *
 * The component exposes an `addToast` method via defineExpose for direct
 * invocation from parent components with a template ref.
 *
 * Tests verify:
 *   1. Container renders with no toasts by default.
 *   2. Calling addToast via the exposed method adds a toast to the DOM.
 *   3. Success toasts get green background styling.
 *   4. Error toasts get red background styling.
 *   5. Info toasts get gray background styling.
 *   6. Clicking a toast removes it from the DOM.
 *   7. Window 'toast' event triggers a new toast.
 *   8. Multiple toasts can be displayed simultaneously.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ToastContainer from '@/components/ui/ToastContainer.vue'

describe('ToastContainer.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  /**
   * Test 1 — Renders empty by default
   *
   * When mounted with no toasts triggered, the container should render
   * but contain no toast message elements.
   */
  it('renders with no toasts by default', () => {
    const wrapper = mount(ToastContainer)

    const toasts = wrapper.findAll('.rounded-lg.px-4.py-3')
    expect(toasts.length).toBe(0)
  })

  /**
   * Test 2 — addToast adds a toast to the DOM
   *
   * Calling the exposed addToast method should add a visible toast
   * with the given message text.
   */
  it('displays a toast when addToast is called', async () => {
    const wrapper = mount(ToastContainer)

    wrapper.vm.addToast('Item saved!', 'success')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Item saved!')
  })

  /**
   * Test 3 — Success toast gets green background
   *
   * When a toast is added with type 'success', it should have the
   * green background class for visual distinction.
   */
  it('applies green background class for success toasts', async () => {
    const wrapper = mount(ToastContainer)

    wrapper.vm.addToast('Saved!', 'success')
    await wrapper.vm.$nextTick()

    const toast = wrapper.find('.bg-green-600')
    expect(toast.exists()).toBe(true)
    expect(toast.text()).toBe('Saved!')
  })

  /**
   * Test 4 — Error toast gets red background
   *
   * When a toast is added with type 'error', it should have the
   * red background class to indicate a failure.
   */
  it('applies red background class for error toasts', async () => {
    const wrapper = mount(ToastContainer)

    wrapper.vm.addToast('Something went wrong', 'error')
    await wrapper.vm.$nextTick()

    const toast = wrapper.find('.bg-red-600')
    expect(toast.exists()).toBe(true)
    expect(toast.text()).toBe('Something went wrong')
  })

  /**
   * Test 5 — Info toast gets gray background
   *
   * When a toast is added with type 'info', it should have the
   * gray background class for neutral notifications.
   */
  it('applies gray background class for info toasts', async () => {
    const wrapper = mount(ToastContainer)

    wrapper.vm.addToast('New update available', 'info')
    await wrapper.vm.$nextTick()

    const toast = wrapper.find('.bg-gray-800')
    expect(toast.exists()).toBe(true)
    expect(toast.text()).toBe('New update available')
  })

  /**
   * Test 6 — Clicking a toast removes it
   *
   * Each toast has a click handler that removes it from the list,
   * allowing users to dismiss notifications early.
   */
  it('removes a toast when it is clicked', async () => {
    const wrapper = mount(ToastContainer)

    wrapper.vm.addToast('Click to dismiss', 'info')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Click to dismiss')

    const toast = wrapper.find('.bg-gray-800')
    await toast.trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).not.toContain('Click to dismiss')
  })

  /**
   * Test 7 — Window 'toast' event triggers a toast
   *
   * The component listens for custom 'toast' events on window.
   * Dispatching such an event should add a toast to the display.
   */
  it('adds a toast in response to a window toast event', async () => {
    const wrapper = mount(ToastContainer)

    window.dispatchEvent(new CustomEvent('toast', {
      detail: { message: 'Event toast!', type: 'success' },
    }))
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Event toast!')
  })

  /**
   * Test 8 — Multiple toasts can be displayed at once
   *
   * Adding several toasts in quick succession should display all of
   * them simultaneously in the container.
   */
  it('displays multiple toasts simultaneously', async () => {
    const wrapper = mount(ToastContainer)

    wrapper.vm.addToast('First toast', 'info')
    wrapper.vm.addToast('Second toast', 'success')
    wrapper.vm.addToast('Third toast', 'error')
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('First toast')
    expect(wrapper.text()).toContain('Second toast')
    expect(wrapper.text()).toContain('Third toast')
  })
})
