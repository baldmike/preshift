/**
 * AcknowledgeButton.test.ts
 *
 * Unit tests for the AcknowledgeButton.vue component.
 *
 * AcknowledgeButton lets staff acknowledge (mark as seen) a pre-shift item.
 * It shows:
 *   - A "HEARD" button in its unacknowledged state
 *   - A green checkmark (SVG) once the item has been acknowledged
 *   - A loading spinner while the API call is in progress
 *
 * Props:
 *   - type: string           -- acknowledgment category (e.g. 'eighty_sixed')
 *   - id: number             -- ID of the item being acknowledged
 *   - acknowledged: boolean  -- whether the item is already acknowledged
 *   - size?: 'sm' | 'md'     -- controls button dimensions and label visibility
 *
 * Tests verify:
 *   1. The "HEARD" button is shown when not acknowledged.
 *   2. A checkmark is shown when acknowledged.
 *   3. Clicking the button calls the acknowledge function.
 *   4. A loading spinner is shown during the API call.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import AcknowledgeButton from '@/components/AcknowledgeButton.vue'

// ── Shared mock references ──────────────────────────────────────────────────
const acknowledgeMock = vi.fn()

vi.mock('@/composables/useAcknowledgments', () => ({
  useAcknowledgments: () => ({
    acknowledge: acknowledgeMock,
    isAcknowledged: vi.fn().mockReturnValue(false),
  }),
}))

describe('AcknowledgeButton.vue', () => {
  beforeEach(() => {
    acknowledgeMock.mockReset()
    acknowledgeMock.mockResolvedValue(undefined)
  })

  /**
   * Test 1 — Shows "HEARD" button when not acknowledged
   *
   * When the acknowledged prop is false, the component should render a
   * <button> element with the text "HEARD".
   */
  it('shows HEARD button when not acknowledged', () => {
    const wrapper = mount(AcknowledgeButton, {
      props: { type: 'eighty_sixed', id: 1, acknowledged: false },
    })

    const button = wrapper.find('button')
    expect(button.exists()).toBe(true)
    expect(button.text()).toContain('HEARD')
  })

  /**
   * Test 2 — Shows checkmark when acknowledged
   *
   * When the acknowledged prop is true, the component should render an
   * SVG checkmark icon instead of the button.
   */
  it('shows checkmark when acknowledged', () => {
    const wrapper = mount(AcknowledgeButton, {
      props: { type: 'eighty_sixed', id: 1, acknowledged: true },
    })

    // The button should NOT be rendered
    const button = wrapper.find('button')
    expect(button.exists()).toBe(false)

    // An SVG checkmark should be rendered
    const svg = wrapper.find('svg')
    expect(svg.exists()).toBe(true)
  })

  /**
   * Test 3 — Calls acknowledge on click
   *
   * When the user clicks the "HEARD" button, the component should call
   * the acknowledge function from the composable with the correct type
   * and id arguments.
   */
  it('calls acknowledge on click', async () => {
    const wrapper = mount(AcknowledgeButton, {
      props: { type: 'special', id: 42, acknowledged: false },
    })

    const button = wrapper.find('button')
    await button.trigger('click')
    await flushPromises()

    expect(acknowledgeMock).toHaveBeenCalledTimes(1)
    expect(acknowledgeMock).toHaveBeenCalledWith('special', 42)
  })

  /**
   * Test 4 — Shows loading spinner during API call
   *
   * When the acknowledge function is in progress (the promise has not yet
   * resolved), the component should render a spinner SVG with the
   * "animate-spin" CSS class while the button remains disabled.
   */
  it('shows loading spinner during API call', async () => {
    // Create a promise that we control so we can inspect the loading state
    let resolveAcknowledge!: () => void
    acknowledgeMock.mockReturnValue(
      new Promise<void>((resolve) => {
        resolveAcknowledge = resolve
      }),
    )

    const wrapper = mount(AcknowledgeButton, {
      props: { type: 'announcement', id: 5, acknowledged: false },
    })

    const button = wrapper.find('button')
    await button.trigger('click')

    // While the promise is pending, the spinner should be visible
    await wrapper.vm.$nextTick()
    const spinner = wrapper.find('.animate-spin')
    expect(spinner.exists()).toBe(true)

    // The button should be disabled during loading
    expect(button.attributes('disabled')).toBeDefined()

    // Resolve the promise and verify spinner disappears
    resolveAcknowledge()
    await flushPromises()

    const spinnerAfter = wrapper.find('.animate-spin')
    expect(spinnerAfter.exists()).toBe(false)
  })
})
