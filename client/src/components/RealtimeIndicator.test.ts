/**
 * RealtimeIndicator.test.ts
 *
 * Unit tests for the RealtimeIndicator.vue component.
 *
 * RealtimeIndicator is a small status dot shown in the TopBar that reflects
 * whether the browser has an active WebSocket connection to Laravel Reverb.
 * It polls the connection state every 3 seconds via window.Echo. Displays
 * a green "Live" label when connected and a gray "Offline" label when
 * disconnected.
 *
 * Tests verify:
 *   1. Displays "Offline" and gray dot when Echo is not available.
 *   2. Displays "Live" and green dot when Echo reports connected.
 *   3. Displays "Offline" when Echo reports a non-connected state.
 *   4. Updates state on polling interval.
 *   5. Title attribute reflects the connection status.
 */

import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import RealtimeIndicator from '@/components/RealtimeIndicator.vue'

describe('RealtimeIndicator.vue', () => {
  let originalEcho: any

  beforeEach(() => {
    vi.useFakeTimers()
    // Save any existing window.Echo
    originalEcho = (window as any).Echo
  })

  afterEach(() => {
    vi.useRealTimers()
    // Restore original window.Echo
    if (originalEcho !== undefined) {
      (window as any).Echo = originalEcho
    } else {
      delete (window as any).Echo
    }
  })

  /**
   * Test 1 — Shows Offline when Echo is not available
   *
   * When window.Echo is undefined (WebSocket not initialized), the
   * indicator should show "Offline" with a gray dot.
   */
  it('displays Offline with gray dot when Echo is not available', () => {
    delete (window as any).Echo

    const wrapper = mount(RealtimeIndicator)

    expect(wrapper.text()).toContain('Offline')
    expect(wrapper.find('.bg-gray-500').exists()).toBe(true)
    expect(wrapper.find('.bg-green-400').exists()).toBe(false)
  })

  /**
   * Test 2 — Shows Live when Echo is connected
   *
   * When window.Echo reports a 'connected' state, the indicator
   * should show "Live" with a green dot. We set up the Echo mock
   * before mounting, and then advance the timer to trigger the
   * initial interval check.
   */
  it('displays Live with green dot when Echo is connected', async () => {
    ;(window as any).Echo = {
      connector: {
        pusher: {
          connection: { state: 'connected' },
        },
      },
    }

    const wrapper = mount(RealtimeIndicator)

    // The onMounted checkConnection runs synchronously, but the reactive
    // update needs a tick to propagate to the DOM
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Live')
    expect(wrapper.find('.bg-green-400').exists()).toBe(true)
    expect(wrapper.find('.bg-gray-500').exists()).toBe(false)
  })

  /**
   * Test 3 — Shows Offline when Echo is not connected
   *
   * When window.Echo exists but the connection state is something other
   * than 'connected' (e.g. 'connecting', 'disconnected'), the indicator
   * should show "Offline" with a gray dot.
   */
  it('displays Offline when Echo state is not connected', () => {
    ;(window as any).Echo = {
      connector: {
        pusher: {
          connection: { state: 'disconnected' },
        },
      },
    }

    const wrapper = mount(RealtimeIndicator)

    expect(wrapper.text()).toContain('Offline')
    expect(wrapper.find('.bg-gray-500').exists()).toBe(true)
  })

  /**
   * Test 4 — Polls connection state on interval
   *
   * The component checks the connection state every 3 seconds. After
   * advancing the timer by 3 seconds with a new state, the display
   * should update accordingly.
   */
  it('updates state after polling interval', async () => {
    // Start disconnected
    ;(window as any).Echo = {
      connector: {
        pusher: {
          connection: { state: 'connecting' },
        },
      },
    }

    const wrapper = mount(RealtimeIndicator)
    expect(wrapper.text()).toContain('Offline')

    // Simulate connection completing
    ;(window as any).Echo.connector.pusher.connection.state = 'connected'

    // Advance timer by 3 seconds to trigger the polling check
    vi.advanceTimersByTime(3000)
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Live')
    expect(wrapper.find('.bg-green-400').exists()).toBe(true)
  })

  /**
   * Test 5 — Title attribute reflects connection status
   *
   * The root element should have a title attribute of "Connected" or
   * "Disconnected" for accessibility and tooltip purposes.
   */
  it('sets title attribute based on connection status', () => {
    delete (window as any).Echo

    const wrapper = mount(RealtimeIndicator)

    const root = wrapper.find('div')
    expect(root.attributes('title')).toBe('Disconnected')
  })
})
